<?php
/**
 * SIMAS-GPS — Pilih Siswa (Mode Device Kelas)
 * Setelah guru/admin mengaktifkan device mode untuk suatu kelas,
 * halaman ini menampilkan daftar nama siswa. Siswa cukup tap namanya
 * untuk diarahkan ke halaman absen — tanpa perlu login dengan password.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Hanya bisa diakses dari device mode yang sudah diautentikasi
cek_device_mode();

$kelas_id   = (int)$_SESSION['device_kelas_id'];
$kelas_nama = e($_SESSION['device_kelas_nama']);
$pdo        = getDB();
$error      = '';

// ─────────────────────────────────────────────────────────
//  HANDLE: Siswa dipilih → set session → redirect ke absen
// ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['siswa_id'])) {
    verify_csrf();

    $siswa_id_post = (int)$_POST['siswa_id'];

    // Validasi: siswa harus benar-benar ada di kelas ini
    $stmt = $pdo->prepare(
        "SELECT s.id AS siswa_id, u.id AS user_id, u.nama, u.foto
         FROM siswa s
         JOIN users u ON s.user_id = u.id
         WHERE s.id = :sid AND s.kelas_id = :kid AND u.status = 'aktif'
         LIMIT 1"
    );
    $stmt->execute([':sid' => $siswa_id_post, ':kid' => $kelas_id]);
    $siswa = $stmt->fetch();

    if ($siswa) {
        set_device_student_session($siswa);
        redirect(base_url() . '/siswa/absen.php');
    } else {
        $error = 'Siswa tidak valid. Silakan coba lagi.';
    }
}

// ─────────────────────────────────────────────────────────
//  AMBIL DAFTAR SISWA DI KELAS INI
// ─────────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT s.id AS siswa_id, u.nama, s.nis, s.jenis_kelamin, u.foto
     FROM siswa s
     JOIN users u ON s.user_id = u.id
     WHERE s.kelas_id = :kid AND u.status = 'aktif'
     ORDER BY u.nama ASC"
);
$stmt->execute([':kid' => $kelas_id]);
$siswa_list = $stmt->fetchAll();

// Cek apakah absen hari ini sudah dilakukan (untuk badge status)
$today = date('Y-m-d');
$stmt2 = $pdo->prepare(
    "SELECT a.siswa_id, a.status, a.jam_masuk, a.jam_pulang
     FROM absensi a
     JOIN siswa s ON a.siswa_id = s.id
     WHERE s.kelas_id = :kid AND a.tanggal = :tgl"
);
$stmt2->execute([':kid' => $kelas_id, ':tgl' => $today]);
$absensi_hari_ini = [];
foreach ($stmt2->fetchAll() as $row) {
    $absensi_hari_ini[$row['siswa_id']] = $row;
}

$csrf = csrf_token();

// Badge warna per status
$status_badge = [
    'hadir'     => ['bg' => 'bg-emerald-500/20 border-emerald-400/40', 'text' => 'text-emerald-300', 'label' => 'Hadir'],
    'terlambat' => ['bg' => 'bg-amber-500/20 border-amber-400/40',    'text' => 'text-amber-300',   'label' => 'Terlambat'],
    'izin'      => ['bg' => 'bg-sky-500/20 border-sky-400/40',        'text' => 'text-sky-300',     'label' => 'Izin'],
    'sakit'     => ['bg' => 'bg-purple-500/20 border-purple-400/40',  'text' => 'text-purple-300',  'label' => 'Sakit'],
    'alpa'      => ['bg' => 'bg-red-500/20 border-red-400/40',        'text' => 'text-red-300',     'label' => 'Alpa'],
];
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#1e1b4b">
    <title>Pilih Siswa — <?= $kelas_nama ?> — SIMAS-GPS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    animation: { 'slide-up': 'slideUp .4s ease both' },
                    keyframes:  { slideUp: { from: { opacity:'0', transform:'translateY(16px)' }, to: { opacity:'1', transform:'translateY(0)' } } }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Inter', system-ui, sans-serif; }
        .bg-mesh {
            background-color: #1e1b4b;
            background-image:
                radial-gradient(at 15% 40%, rgba(99,102,241,.3) 0, transparent 50%),
                radial-gradient(at 85% 20%, rgba(124,58,237,.25) 0, transparent 45%);
        }
        .student-card {
            transition: transform .15s ease, box-shadow .15s ease, background .15s;
        }
        .student-card:active { transform: scale(.96); }
        .student-card:hover  { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(79,70,229,.3); }

        /* Realtime clock */
        #bigclock { font-variant-numeric: tabular-nums; }

        /* Search highlight */
        .hidden-card { display: none; }
    </style>
</head>
<body class="bg-mesh min-h-screen text-white">

<!-- ═══════════════════════════════════════════════════════
     HEADER
     ═══════════════════════════════════════════════════════ -->
<header class="sticky top-0 z-40 bg-[#1e1b4b]/80 backdrop-blur-lg border-b border-white/10 px-4 py-3">
    <div class="max-w-2xl mx-auto flex items-center justify-between gap-3">
        <!-- Branding -->
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-400 to-violet-600 flex items-center justify-center shadow-lg flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
                <p class="font-bold text-sm text-white leading-tight">Mode Device Kelas</p>
                <p class="text-indigo-300 text-xs truncate max-w-[160px]"><?= $kelas_nama ?></p>
            </div>
        </div>

        <!-- Clock & Exit -->
        <div class="flex items-center gap-3">
            <div id="bigclock" class="text-right hidden sm:block">
                <div class="text-white font-bold text-sm" id="clock-time">--:--:--</div>
                <div class="text-indigo-400 text-[10px]" id="clock-date">--</div>
            </div>
            <a href="<?= base_url() ?>/logout.php"
               onclick="return confirm('Keluar dari mode device?')"
               class="flex items-center gap-1.5 bg-red-500/20 hover:bg-red-500/40 border border-red-400/30 text-red-300 hover:text-red-100
                      px-3 py-1.5 rounded-xl text-xs font-semibold transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> <span class="hidden xs:inline">Keluar</span>
            </a>
        </div>
    </div>
</header>

<!-- ═══════════════════════════════════════════════════════
     MAIN
     ═══════════════════════════════════════════════════════ -->
<main class="max-w-2xl mx-auto px-4 py-6">

    <!-- Alert Error -->
    <?php if ($error): ?>
    <div class="flex items-center gap-3 bg-red-500/20 border border-red-400/30 rounded-2xl px-4 py-3 mb-6" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><p class="text-red-200 text-sm"><?= e($error) ?></p>
    </div>
    <?php endif; ?>

    <!-- Instruksi -->
    <div class="text-center mb-6">
        <h2 class="text-xl font-bold text-white">Pilih Nama Kamu</h2>
        <p class="text-indigo-300 text-sm mt-1">Tap nama untuk melakukan absen sekarang</p>

        <!-- Summary hari ini -->
        <div class="flex justify-center gap-4 mt-4 flex-wrap">
            <?php
            $hadir = count(array_filter($absensi_hari_ini, fn($a) => in_array($a['status'], ['hadir','terlambat'])));
            $belum = count($siswa_list) - count($absensi_hari_ini);
            ?>
            <span class="bg-emerald-500/15 border border-emerald-400/30 text-emerald-300 text-xs px-3 py-1.5 rounded-full font-semibold flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Sudah Absen: <?= $hadir ?>
            </span>
            <span class="bg-slate-500/15 border border-slate-400/30 text-slate-300 text-xs px-3 py-1.5 rounded-full font-semibold flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Belum Absen: <?= $belum ?>
            </span>
            <span class="bg-indigo-500/15 border border-indigo-400/30 text-indigo-300 text-xs px-3 py-1.5 rounded-full font-semibold flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Total: <?= count($siswa_list) ?>
            </span>
        </div>
    </div>

    <!-- Search box -->
    <div class="relative mb-5">
        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-indigo-400 w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="search-siswa"
               placeholder="Cari nama siswa..."
               oninput="filterSiswa(this.value)"
               class="w-full pl-10 pr-4 py-3 rounded-2xl text-sm font-medium
                      bg-white/8 border border-white/15 text-white placeholder-indigo-400
                      focus:outline-none focus:border-indigo-400 focus:bg-white/12
                      transition-all">
    </div>

    <?php if (empty($siswa_list)): ?>
    <!-- Kosong -->
    <div class="text-center py-16">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto mb-4 text-indigo-400 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <p class="text-indigo-200 font-semibold">Belum Ada Siswa di Kelas Ini</p>
        <p class="text-indigo-400 text-sm mt-2">Hubungi admin untuk menambahkan data siswa.</p>
    </div>
    <?php else: ?>

    <!-- ─────────────────────────────────────────────────────
         GRID SISWA
         ───────────────────────────────────────────────────── -->
    <div id="siswa-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <?php foreach ($siswa_list as $idx => $s):
            $sid      = (int)$s['siswa_id'];
            $absensi  = $absensi_hari_ini[$sid] ?? null;
            $sudah    = ($absensi && $absensi['jam_masuk']) ? true : false;
            $sudah_pulang = ($absensi && $absensi['jam_pulang']) ? true : false;
            $inisial  = strtoupper(substr($s['nama'], 0, 1));
            $badge    = $absensi ? ($status_badge[$absensi['status']] ?? null) : null;

            // Warna avatar berdasarkan indeks
            $avatar_colors = [
                'from-indigo-400 to-violet-600',
                'from-emerald-400 to-teal-600',
                'from-amber-400 to-orange-600',
                'from-pink-400 to-rose-600',
                'from-sky-400 to-cyan-600',
                'from-purple-400 to-fuchsia-600',
            ];
            $av_color = $avatar_colors[$idx % count($avatar_colors)];
        ?>
        <form method="POST" action="" class="student-form" data-nama="<?= strtolower($s['nama']) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="siswa_id" value="<?= $sid ?>">
            <button type="submit"
                    class="student-card w-full bg-white/6 hover:bg-indigo-500/20 border border-white/10
                           rounded-2xl p-4 text-center cursor-pointer animate-slide-up"
                    style="animation-delay: <?= ($idx * 30) ?>ms"
                    <?= $sudah_pulang ? 'title="Sudah absen pulang hari ini"' : '' ?>>

                <!-- Avatar -->
                <div class="relative inline-block mb-3">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br <?= $av_color ?> flex items-center justify-center
                                text-white font-bold text-xl shadow-lg mx-auto">
                        <?= e($inisial) ?>
                    </div>
                    <!-- Status dot -->
                    <?php if ($sudah): ?>
                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full <?= $sudah_pulang ? 'bg-emerald-400' : 'bg-amber-400' ?>
                                 border-2 border-[#1e1b4b] flex-shrink-0"
                          title="<?= $sudah_pulang ? 'Lengkap' : 'Masuk saja' ?>"></span>
                    <?php endif; ?>
                </div>

                <!-- Nama -->
                <p class="text-white font-semibold text-xs leading-tight truncate"><?= e($s['nama']) ?></p>
                <p class="text-indigo-400 text-[10px] mt-0.5"><?= e($s['nis']) ?></p>

                <!-- Status badge -->
                <?php if ($badge): ?>
                <span class="inline-block mt-2 px-2 py-0.5 text-[10px] font-semibold rounded-full border
                             <?= $badge['bg'] ?> <?= $badge['text'] ?>">
                    <?= $badge['label'] ?>
                </span>
                <?php elseif ($sudah): ?>
                <span class="inline-block mt-2 px-2 py-0.5 text-[10px] font-semibold rounded-full border
                             bg-amber-500/20 border-amber-400/40 text-amber-300">
                    Belum Pulang
                </span>
                <?php else: ?>
                <span class="inline-block mt-2 px-2 py-0.5 text-[10px] font-semibold rounded-full border
                             bg-slate-500/20 border-slate-400/30 text-slate-400">
                    Belum Absen
                </span>
                <?php endif; ?>

            </button>
        </form>
        <?php endforeach; ?>
    </div>

    <!-- No result search -->
    <div id="no-result" class="hidden text-center py-10">
        <p class="text-indigo-400 text-sm">Tidak ada siswa dengan nama tersebut.</p>
    </div>

    <?php endif; ?>

</main>

<script>
// ─────────────────────────────────────────────────────────
//  Realtime clock
// ─────────────────────────────────────────────────────────
(function tick() {
    const now    = new Date();
    const days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    const ct = document.getElementById('clock-time');
    const cd = document.getElementById('clock-date');
    if (ct) ct.textContent = String(now.getHours()).padStart(2,'0') + ':' +
                              String(now.getMinutes()).padStart(2,'0') + ':' +
                              String(now.getSeconds()).padStart(2,'0');
    if (cd) cd.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
    setTimeout(tick, 1000);
})();

// ─────────────────────────────────────────────────────────
//  Filter pencarian siswa
// ─────────────────────────────────────────────────────────
function filterSiswa(q) {
    q = q.trim().toLowerCase();
    const forms  = document.querySelectorAll('.student-form');
    const noRes  = document.getElementById('no-result');
    let visible  = 0;

    forms.forEach(f => {
        const nama = f.dataset.nama || '';
        if (!q || nama.includes(q)) {
            f.style.display = '';
            visible++;
        } else {
            f.style.display = 'none';
        }
    });

    if (noRes) noRes.classList.toggle('hidden', visible > 0 || !q);
}

// ─────────────────────────────────────────────────────────
//  Loading state saat pilih siswa
// ─────────────────────────────────────────────────────────
document.querySelectorAll('.student-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.style.opacity = '0.6';
        btn.style.pointerEvents = 'none';
        btn.innerHTML = '<div class="flex items-center justify-center gap-2 py-2"><svg class="animate-spin w-5 h-5 text-indigo-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg><span class="text-indigo-200 text-xs">Memuat...</span></div>';
    });
});
</script>

</body>
</html>
