<?php
/**
 * SIMAS-GPS — Halaman Login
 * Mendukung: login normal (admin/guru/siswa) & mode device kelas
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Sudah login? Redirect ke dashboard
if (!empty($_SESSION['user_id'])) {
    switch ($_SESSION['role'] ?? '') {
        case 'admin': redirect(base_url() . '/admin/dashboard.php'); break;
        case 'guru':  redirect(base_url() . '/guru/dashboard.php');  break;
        case 'siswa': redirect(base_url() . '/siswa/dashboard.php'); break;
    }
}
// Device mode aktif tapi belum pilih siswa?
if (!empty($_SESSION['device_mode'])) {
    redirect(base_url() . '/siswa/pilih_siswa.php');
}

$error   = '';
$success = '';
$tab     = 'normal'; // tab aktif default

// Flash message dari logout
$flash_success = get_flash('success');
$flash_error   = get_flash('error');

// ─────────────────────────────────────────────────────────
//  PROSES POST
// ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $mode = $_POST['mode'] ?? 'normal';

    // ── LOGIN NORMAL ─────────────────────────────────────
    if ($mode === 'normal') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Ambil device token & fingerprint yang dikirim oleh device.js
        $device_token       = trim($_POST['device_token']       ?? '');
        $device_fingerprint = trim($_POST['device_fingerprint'] ?? '');
        $device_info        = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            $pdo  = getDB();
            $stmt = $pdo->prepare(
                "SELECT u.*, s.id AS siswa_id
                 FROM users u
                 LEFT JOIN siswa s ON s.user_id = u.id
                 WHERE u.username = :uname AND u.status = 'aktif'
                 LIMIT 1"
            );
            $stmt->execute([':uname' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {

                // ── CEK DEVICE BINDING (hanya untuk role siswa) ──────
                if ($user['role'] === 'siswa') {
                    $user_id_int = (int)$user['id'];

                    // Jika siswa login tanpa device.js mengirim token
                    // (misalnya dari PC/browser yang belum generate token)
                    // maka token akan kosong → tangani sebagai perangkat baru
                    // tapi HANYA jika belum ada device terdaftar.
                    $device_status = verify_device_token($user_id_int, $device_token, $device_fingerprint);

                    if ($device_status === 'new') {
                        // HP pertama kali → daftarkan otomatis
                        if ($device_token !== '') {
                            register_device_token($user_id_int, $device_token, $device_fingerprint, $device_info);
                        }
                        // Jika token kosong (JS tidak jalan), tetap izinkan
                        // tapi tidak daftarkan (device tidak teridentifikasi)
                    } elseif ($device_status === 'blocked') {
                        // HP berbeda → TOLAK LOGIN
                        password_verify('dummy', '$2y$10$dummy.hash.to.prevent.timing.attack.padding');
                        $error = 'Akun ini sudah terdaftar di perangkat lain. Hubungi admin untuk reset perangkat Anda.';
                        $tab = 'normal';
                        goto end_login;
                    } else {
                        // 'ok' → HP cocok, update waktu terakhir aktif
                        update_device_last_seen($user_id_int);
                    }
                }
                // ─────────────────────────────────────────────────────

                set_user_session($user, $user['siswa_id'] ? (int)$user['siswa_id'] : null);

                // Redirect sesuai role
                switch ($user['role']) {
                    case 'admin': redirect(base_url() . '/admin/dashboard.php'); break;
                    case 'guru':  redirect(base_url() . '/guru/dashboard.php');  break;
                    case 'siswa': redirect(base_url() . '/siswa/dashboard.php'); break;
                    default:      redirect(base_url() . '/login.php');
                }
            } else {
                // Hindari timing attack
                password_verify('dummy', '$2y$10$dummy.hash.to.prevent.timing.attack.padding');
                $error = 'Username atau password salah. Periksa kembali.';
            }
        }
        end_login:
        $tab = 'normal';

    // ── DEVICE MODE ──────────────────────────────────────
    } elseif ($mode === 'device') {
        $kelas_id = (int)($_POST['kelas_id'] ?? 0);
        $username = trim($_POST['dev_username'] ?? '');
        $password = $_POST['dev_password'] ?? '';

        if (!$kelas_id || $username === '' || $password === '') {
            $error = 'Semua field mode device wajib diisi.';
        } else {
            $pdo  = getDB();

            // Validasi kredensial guru/admin
            $stmt = $pdo->prepare(
                "SELECT * FROM users
                 WHERE username = :uname AND status = 'aktif'
                       AND role IN ('admin','guru')
                 LIMIT 1"
            );
            $stmt->execute([':uname' => $username]);
            $auth_user = $stmt->fetch();

            if ($auth_user && password_verify($password, $auth_user['password'])) {
                // Validasi kelas ada & mode device
                $stmtK = $pdo->prepare(
                    "SELECT * FROM kelas WHERE id = ? AND mode_absen = 'device' LIMIT 1"
                );
                $stmtK->execute([$kelas_id]);
                $kelas = $stmtK->fetch();

                if ($kelas) {
                    set_device_session($kelas, (int)$auth_user['id']);
                    redirect(base_url() . '/siswa/pilih_siswa.php');
                } else {
                    $error = 'Kelas tidak ditemukan atau belum diatur ke mode Device.';
                }
            } else {
                password_verify('dummy', '$2y$10$dummy.hash.to.prevent.timing.attack.padding');
                $error = 'Username atau password salah.';
            }
        }
        $tab = 'device';
    }
}

// ─────────────────────────────────────────────────────────
//  AMBIL DAFTAR KELAS DEVICE MODE (untuk dropdown)
// ─────────────────────────────────────────────────────────
$device_kelas = [];
try {
    $pdo = getDB();
    $stmt = $pdo->query(
        "SELECT id, nama_kelas, tahun_ajaran FROM kelas
         WHERE mode_absen = 'device' ORDER BY nama_kelas ASC"
    );
    $device_kelas = $stmt->fetchAll();
} catch (Exception $e) {
    // DB belum ada kelas — abaikan
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="description" content="Login SIMAS-GPS — Sistem Informasi Monitoring Absensi Siswa Berbasis GPS SMPN 1 VII Koto Sungai Sarik">
    <meta name="theme-color" content="#1e1b4b">
    <title>Login — SIMAS-GPS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 900: '#1e1b4b', 800: '#312e81', 700: '#3730a3', 600: '#4338ca', 500: '#4f46e5', 400: '#818cf8', 100: '#e0e7ff' }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    animation: {
                        'fade-in': 'fadeIn .4s ease both',
                        'slide-up': 'slideUp .4s ease both',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn:  { from: { opacity: '0' }, to: { opacity: '1' } },
                        slideUp: { from: { opacity: '0', transform: 'translateY(20px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                        float:   { '0%,100%': { transform: 'translateY(0px)' }, '50%': { transform: 'translateY(-12px)' } },
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Inter', system-ui, sans-serif; }

        .bg-mesh {
            background-color: #1e1b4b;
            background-image:
                radial-gradient(at 20% 50%, rgba(99,102,241,.35) 0, transparent 50%),
                radial-gradient(at 80% 10%, rgba(124,58,237,.3) 0, transparent 40%),
                radial-gradient(at 60% 80%, rgba(79,70,229,.25) 0, transparent 50%);
        }
        .glass {
            background: rgba(255,255,255,.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.12);
        }
        .input-field {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            color: white;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .input-field::placeholder { color: rgba(255,255,255,.4); }
        .input-field:focus {
            outline: none;
            border-color: #818cf8;
            background: rgba(255,255,255,.12);
            box-shadow: 0 0 0 3px rgba(129,140,248,.2);
        }
        .input-field option { background: #1e1b4b; color: white; }

        /* Tab indicator */
        .tab-indicator {
            transition: transform .3s cubic-bezier(.4,0,.2,1), width .3s;
        }

        /* Orbit decorations */
        .orbit { animation: spin 20s linear infinite; transform-origin: center; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Button loading state */
        .btn-loading { position: relative; pointer-events: none; }
        .btn-loading::after {
            content: '';
            position: absolute; inset: 0; margin: auto;
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: white;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        /* Particles */
        .particle {
            position: absolute; border-radius: 50%;
            background: rgba(129,140,248,.15);
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-mesh min-h-screen flex items-center justify-center p-4 overflow-hidden">

<!-- Decorative background blobs -->
<div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
    <div class="particle w-64 h-64 -top-20 -left-20" style="animation-delay:0s"></div>
    <div class="particle w-48 h-48 top-1/3 -right-16" style="animation-delay:2s"></div>
    <div class="particle w-32 h-32 bottom-20 left-1/4" style="animation-delay:4s"></div>
    <!-- Orbit ring -->
    <svg class="absolute w-[600px] h-[600px] top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-5 orbit" viewBox="0 0 600 600" fill="none">
        <circle cx="300" cy="300" r="280" stroke="white" stroke-width="1" stroke-dasharray="10 20"/>
        <circle cx="300" cy="300" r="200" stroke="white" stroke-width="1" stroke-dasharray="5 15"/>
    </svg>
</div>

<!-- ================================================================
     MAIN CARD
     ================================================================ -->
<div class="w-full max-w-md relative z-10 animate-slide-up">

    <!-- Logo & Branding -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-slate-800 shadow-2xl mb-4 animate-float text-white">
            <i data-lucide="map-pin" class="w-8 h-8"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">SIMAS-GPS</h1>
        <p class="text-indigo-300 text-sm mt-1 font-medium">SMPN 1 VII Koto Sungai Sarik</p>
        <p class="text-indigo-400 text-xs mt-0.5">Sistem Monitoring Absensi Siswa</p>
    </div>

    <!-- Glass Card -->
    <div class="glass rounded-2xl p-6 shadow-2xl">

        <!-- Alert: Error -->
        <?php if ($error): ?>
        <div class="flex items-start gap-3 bg-red-500/20 border border-red-400/30 rounded-2xl px-4 py-3 mb-5 animate-fade-in" role="alert">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-red-400"></i>
            <p class="text-red-200 text-sm"><?= e($error) ?></p>
        </div>
        <?php endif; ?>

        <!-- Alert: Success (logout dll) -->
        <?php if ($flash_success): ?>
        <div class="flex items-start gap-3 bg-emerald-500/20 border border-emerald-400/30 rounded-2xl px-4 py-3 mb-5 animate-fade-in" role="alert">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 text-emerald-400"></i>
            <p class="text-emerald-200 text-sm"><?= e($flash_success) ?></p>
        </div>
        <?php endif; ?>

        <!-- ── TAB SWITCHER ── -->
        <div class="relative flex bg-white/5 rounded-2xl p-1 mb-6" id="tab-switcher" role="tablist">
            <!-- Indicator -->
            <div id="tab-indicator"
                 class="tab-indicator absolute top-1 bottom-1 w-1/2 bg-gradient-to-r from-slate-600 to-indigo-700 rounded-xl shadow-lg"
                 style="left:4px; <?= $tab === 'device' ? 'transform:translateX(calc(100% - 8px))' : '' ?>"></div>
            <button type="button" role="tab" id="tab-btn-normal"
                    onclick="switchTab('normal')"
                    aria-selected="<?= $tab === 'normal' ? 'true' : 'false' ?>"
                    class="relative z-10 flex-1 py-2.5 flex justify-center items-center gap-2 text-sm font-semibold rounded-xl transition-colors duration-200
                           <?= $tab === 'normal' ? 'text-white' : 'text-indigo-300 hover:text-white' ?>">
                <i data-lucide="key" class="w-4 h-4"></i> Login Akun
            </button>
            <button type="button" role="tab" id="tab-btn-device"
                    onclick="switchTab('device')"
                    aria-selected="<?= $tab === 'device' ? 'true' : 'false' ?>"
                    class="relative z-10 flex-1 py-2.5 flex justify-center items-center gap-2 text-sm font-semibold rounded-xl transition-colors duration-200
                           <?= $tab === 'device' ? 'text-white' : 'text-indigo-300 hover:text-white' ?>">
                <i data-lucide="smartphone" class="w-4 h-4"></i> Mode Device
            </button>
        </div>

        <!-- ════════════════════════════════════════════
             TAB 1: LOGIN NORMAL
             ════════════════════════════════════════════ -->
        <div id="panel-normal" role="tabpanel" <?= $tab === 'device' ? 'class="hidden"' : '' ?>>
            <form method="POST" action="" id="form-login-normal">
                <input type="hidden" name="mode" value="normal">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                <!-- Username -->
                <div class="mb-4">
                    <label for="username" class="block text-indigo-200 text-xs font-semibold uppercase tracking-wider mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-indigo-400">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </span>
                        <input type="text" id="username" name="username" autocomplete="username"
                               value="<?= e($_POST['username'] ?? '') ?>"
                               class="input-field w-full pl-11 pr-4 py-3 rounded-xl text-sm"
                               placeholder="Masukkan username" required autofocus>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-indigo-200 text-xs font-semibold uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-indigo-400">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </span>
                        <input type="password" id="password" name="password" autocomplete="current-password"
                               class="input-field w-full pl-11 pr-12 py-3 rounded-xl text-sm"
                               placeholder="Masukkan password" required>
                        <button type="button" onclick="togglePass('password')"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-indigo-400 hover:text-white transition-colors"
                                aria-label="Tampilkan password">
                            <i data-lucide="eye" id="eye-password" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" id="btn-normal"
                        class="w-full bg-gradient-to-r from-slate-700 to-indigo-800 hover:from-slate-600 hover:to-indigo-700
                               text-white font-bold py-3.5 rounded-xl transition-all duration-200 shadow-lg
                               hover:-translate-y-0.5 active:translate-y-0 text-sm flex justify-center items-center gap-2">
                    Masuk ke Sistem <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <!-- Hint kredensial default (development only) -->
            <div class="mt-5 p-3 bg-amber-500/10 border border-amber-400/20 rounded-xl">
                <p class="text-amber-300 text-xs text-center font-medium flex items-center justify-center gap-1.5">
                    <i data-lucide="info" class="w-4 h-4"></i> Default Admin: <code class="bg-black/20 px-1.5 py-0.5 rounded">admin</code> /
                    <code class="bg-black/20 px-1.5 py-0.5 rounded">admin123</code>
                </p>
                <p class="text-amber-400/70 text-[10px] text-center mt-1">Hapus hint ini sebelum deploy ke production</p>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             TAB 2: DEVICE MODE
             ════════════════════════════════════════════ -->
        <div id="panel-device" role="tabpanel" <?= $tab === 'normal' ? 'class="hidden"' : '' ?>>

            <?php if (empty($device_kelas)): ?>
            <!-- Tidak ada kelas device mode -->
            <div class="text-center py-8">
                <div class="text-indigo-400 mb-3 flex justify-center"><i data-lucide="monitor-x" class="w-12 h-12"></i></div>
                <p class="text-indigo-200 font-semibold text-sm">Belum Ada Kelas Device Mode</p>
                <p class="text-indigo-400 text-xs mt-2 leading-relaxed">
                    Admin perlu mengatur kelas ke mode "Device" di<br>menu Kelola Kelas terlebih dahulu.
                </p>
            </div>
            <?php else: ?>
            <form method="POST" action="" id="form-device" onsubmit="onSubmitForm(this)">
                <input type="hidden" name="mode" value="device">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                <!-- Info -->
                <div class="bg-indigo-500/15 border border-indigo-400/25 rounded-xl px-4 py-3 mb-5">
                    <p class="text-indigo-200 text-xs leading-relaxed flex gap-2">
                        <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                        <span><strong>Mode Device Kelas:</strong> Siswa tidak perlu login sendiri. Guru/admin aktifkan perangkat untuk kelas, lalu siswa pilih nama mereka.</span>
                    </p>
                </div>

                <!-- Pilih Kelas -->
                <div class="mb-4">
                    <label for="kelas_id" class="block text-indigo-200 text-xs font-semibold uppercase tracking-wider mb-2">Pilih Kelas</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-indigo-400 pointer-events-none">
                            <i data-lucide="monitor" class="w-5 h-5"></i>
                        </span>
                        <select id="kelas_id" name="kelas_id" required
                                class="input-field w-full pl-11 pr-4 py-3 rounded-xl text-sm appearance-none cursor-pointer">
                            <option value="">— Pilih kelas —</option>
                            <?php foreach ($device_kelas as $k): ?>
                            <option value="<?= (int)$k['id'] ?>"
                                <?= (isset($_POST['kelas_id']) && (int)$_POST['kelas_id'] === (int)$k['id']) ? 'selected' : '' ?>>
                                <?= e($k['nama_kelas']) ?> (<?= e($k['tahun_ajaran']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-indigo-400 pointer-events-none">
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </span>
                    </div>
                </div>

                <!-- Username Guru/Admin -->
                <div class="mb-4">
                    <label for="dev_username" class="block text-indigo-200 text-xs font-semibold uppercase tracking-wider mb-2">Username Guru / Admin</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-indigo-400">
                            <i data-lucide="user-check" class="w-5 h-5"></i>
                        </span>
                        <input type="text" id="dev_username" name="dev_username" autocomplete="username"
                               value="<?= e($_POST['dev_username'] ?? '') ?>"
                               class="input-field w-full pl-11 pr-4 py-3 rounded-xl text-sm"
                               placeholder="Username guru / admin">
                    </div>
                </div>

                <!-- Password Guru/Admin -->
                <div class="mb-6">
                    <label for="dev_password" class="block text-indigo-200 text-xs font-semibold uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-indigo-400">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </span>
                        <input type="password" id="dev_password" name="dev_password"
                               class="input-field w-full pl-11 pr-12 py-3 rounded-xl text-sm"
                               placeholder="Password guru / admin">
                        <button type="button" onclick="togglePass('dev_password')"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-indigo-400 hover:text-white transition-colors"
                                aria-label="Tampilkan password">
                            <i data-lucide="eye" id="eye-dev_password" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" id="btn-device"
                        class="w-full bg-gradient-to-r from-slate-700 to-indigo-800 hover:from-slate-600 hover:to-indigo-700
                               text-white font-bold py-3.5 rounded-xl transition-all duration-200 shadow-lg
                               hover:-translate-y-0.5 active:translate-y-0 text-sm flex justify-center items-center gap-2">
                    Aktifkan Device <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>

    </div><!-- /glass card -->

    <!-- Footer -->
    <p class="text-center text-indigo-500 text-xs mt-6">
        &copy; <?= date('Y') ?> SIMAS-GPS &bull; Dikembangkan untuk SMPN 1 VII Koto Sungai Sarik
    </p>

</div><!-- /main card -->

<script>
// Initialize Lucide Icons
lucide.createIcons();

// ─────────────────────────────────────────────────────────
//  Tab Switcher
// ─────────────────────────────────────────────────────────
function switchTab(tab) {
    const indicator = document.getElementById('tab-indicator');
    const panelNormal = document.getElementById('panel-normal');
    const panelDevice = document.getElementById('panel-device');
    const btnNormal   = document.getElementById('tab-btn-normal');
    const btnDevice   = document.getElementById('tab-btn-device');

    if (tab === 'normal') {
        indicator.style.transform = 'translateX(0)';
        panelNormal.classList.remove('hidden');
        panelDevice.classList.add('hidden');
        btnNormal.classList.replace('text-indigo-300', 'text-white');
        btnDevice.classList.replace('text-white', 'text-indigo-300');
        btnNormal.setAttribute('aria-selected', 'true');
        btnDevice.setAttribute('aria-selected', 'false');
    } else {
        indicator.style.transform = 'translateX(calc(100% - 8px))';
        panelDevice.classList.remove('hidden');
        panelNormal.classList.add('hidden');
        btnDevice.classList.replace('text-indigo-300', 'text-white');
        btnNormal.classList.replace('text-white', 'text-indigo-300');
        btnDevice.setAttribute('aria-selected', 'true');
        btnNormal.setAttribute('aria-selected', 'false');
    }
}

// ─────────────────────────────────────────────────────────
//  Toggle password visibility
// ─────────────────────────────────────────────────────────
function togglePass(fieldId) {
    const input = document.getElementById(fieldId);
    // Lucide replaces <i id="eye-..."> with an <svg> tag that doesn't have the original ID but keeps classes.
    // However, the cleanest way is to re-render the icon by replacing the parent's innerHTML or finding the svg.
    const btn = input.nextElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = `<i data-lucide="eye-off" id="eye-${fieldId}" class="w-5 h-5"></i>`;
    } else {
        input.type = 'password';
        btn.innerHTML = `<i data-lucide="eye" id="eye-${fieldId}" class="w-5 h-5"></i>`;
    }
    lucide.createIcons();
}

// ─────────────────────────────────────────────────────────
//  Loading state saat submit
// ─────────────────────────────────────────────────────────
function onSubmitForm(form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.textContent = '';
    btn.classList.add('btn-loading');
    btn.disabled = true;
}
</script>

<!-- Device Binding JS: generate token & fingerprint, inject ke form login -->
<script src="<?= base_url() ?>/assets/js/device.js"></script>

<!-- Override submit button untuk form-login-normal (loading state sudah dihandle device.js) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const formNormal = document.getElementById('form-login-normal');
    if (formNormal) {
        formNormal.addEventListener('submit', () => {
            const btn = formNormal.querySelector('button[type="submit"]');
            if (btn) {
                btn.textContent = '';
                btn.classList.add('btn-loading');
                btn.disabled = true;
            }
        });
    }
    // Form device mode tetap pakai onSubmitForm biasa
    const formDevice = document.getElementById('form-device');
    if (formDevice) {
        formDevice.addEventListener('submit', () => onSubmitForm(formDevice));
    }
});
</script>

</body>
</html>
