<?php
/**
 * SIMAS-GPS — Header Global
 * Tailwind CSS via CDN, mobile-first, dark sidebar ready
 *
 * @param string $page_title  Judul halaman (ditampilkan di <title> dan breadcrumb)
 * @param string $active_menu Nama menu aktif untuk highlight sidebar
 */

// Auth sudah di-require di setiap halaman (auth.php menangani session_start)
// Pastikan auth.php sudah di-include sebelum header.php
$user = [
    'id'   => $_SESSION['user_id'] ?? null,
    'nama' => $_SESSION['nama']    ?? 'Guest',
    'role' => $_SESSION['role']    ?? '',
    'foto' => $_SESSION['foto']    ?? null,
];

// Base URL helper (fallback jika auth.php belum di-include)
if (!function_exists('base_url')) {
    function base_url(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . (defined('APP_BASE_PATH') ? APP_BASE_PATH : '/absensi_gps');
    }
}

$base        = base_url();
$page_title  = $page_title  ?? 'SIMAS-GPS';
$active_menu = $active_menu ?? '';

// Definisi menu berdasarkan role
$menus = [];
if ($user['role'] === 'admin') {
    $menus = [
        ['icon' => 'layout-dashboard',  'label' => 'Dashboard',        'href' => "$base/admin/dashboard.php",          'key' => 'dashboard'],
        ['icon' => 'users',             'label' => 'Kelola Siswa',     'href' => "$base/admin/kelola_siswa.php",        'key' => 'kelola_siswa'],
        ['icon' => 'graduation-cap',    'label' => 'Kelola Guru',      'href' => "$base/admin/kelola_guru.php",         'key' => 'kelola_guru'],
        ['icon' => 'school',            'label' => 'Kelola Kelas',     'href' => "$base/admin/kelola_kelas.php",        'key' => 'kelola_kelas'],
        ['icon' => 'map-pin',           'label' => 'Pengaturan Lokasi','href' => "$base/admin/pengaturan_lokasi.php",   'key' => 'pengaturan_lokasi'],
        ['icon' => 'bar-chart-3',       'label' => 'Rekap Kehadiran',  'href' => "$base/admin/rekap_kehadiran.php",     'key' => 'rekap_kehadiran'],
        ['icon' => 'clipboard-list',    'label' => 'Laporan',          'href' => "$base/admin/laporan.php",             'key' => 'laporan'],
    ];
} elseif ($user['role'] === 'guru') {
    $menus = [
        ['icon' => 'layout-dashboard',  'label' => 'Dashboard Guru',   'href' => "$base/guru/dashboard.php",            'key' => 'dashboard'],
        ['icon' => 'clipboard-list',    'label' => 'Rekap Kelas',      'href' => "$base/guru/rekap_kelas.php",          'key' => 'rekap_kelas'],
        ['icon' => 'bar-chart-3',       'label' => 'Rekap Siswa',      'href' => "$base/guru/rekap_kehadiran.php",      'key' => 'rekap_kehadiran'],
        ['icon' => 'check-circle',      'label' => 'Approve Izin',     'href' => "$base/guru/approve_izin.php",         'key' => 'approve_izin'],
        ['icon' => 'pen-tool',          'label' => 'Absen Manual',     'href' => "$base/guru/absen_manual.php",         'key' => 'absen_manual'],
    ];
} elseif ($user['role'] === 'siswa') {
    $menus = [
        ['icon' => 'home',              'label' => 'Dashboard Siswa',  'href' => "$base/siswa/dashboard.php",           'key' => 'dashboard'],
        ['icon' => 'map-pin',           'label' => 'Absen Sekarang',   'href' => "$base/siswa/absen.php",               'key' => 'absen'],
        ['icon' => 'calendar-days',     'label' => 'Riwayat Absen',    'href' => "$base/siswa/riwayat.php",             'key' => 'riwayat'],
        ['icon' => 'file-text',         'label' => 'Ajukan Izin',      'href' => "$base/siswa/ajukan_izin.php",         'key' => 'ajukan_izin'],
    ];
}

$role_label = ['admin' => 'Administrator', 'guru' => 'Guru', 'siswa' => 'Siswa'];
$avatar_bg  = ['admin' => 'from-slate-700 to-slate-900', 'guru' => 'from-blue-600 to-indigo-800', 'siswa' => 'from-teal-600 to-emerald-800'];
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="SIMAS-GPS — Sistem Informasi Monitoring Absensi Siswa Berbasis GPS SMPN 1 VII Koto Sungai Sarik">
    <meta name="theme-color" content="#1e1b4b">
    <title><?= htmlspecialchars($page_title) ?> — SIMAS-GPS</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50:  '#f0f0ff',
                            100: '#e4e4ff',
                            500: '#4f46e5',
                            600: '#4338ca',
                            700: '#3730a3',
                            800: '#312e81',
                            900: '#1e1b4b',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Custom minimal CSS -->
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Inter', system-ui, sans-serif; }

        /* Sidebar transition */
        #sidebar { transition: transform 0.3s ease; }
        #sidebar-overlay { transition: opacity 0.3s ease; }

        /* Scrollbar halus */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 4px; }

        /* Active menu item */
        .menu-active {
            background: rgba(255, 255, 255, 0.15);
            border-left: 3px solid #cbd5e1;
        }
        .menu-active span, .menu-active i { color: white !important; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800">

<?php if ($user['id']): ?>
<!-- ========== OVERLAY MOBILE ========== -->
<div id="sidebar-overlay"
     class="fixed inset-0 bg-black/50 z-40 hidden opacity-0 lg:hidden"
     onclick="toggleSidebar()"></div>

<!-- ========== SIDEBAR ========== -->
<aside id="sidebar"
       class="fixed top-0 left-0 h-full w-64 bg-primary-900 z-50 flex flex-col
              -translate-x-full lg:translate-x-0">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white font-bold text-sm shadow-lg">
            GPS
        </div>
        <div>
            <p class="text-white font-bold text-sm leading-tight">SIMAS-GPS</p>
            <p class="text-indigo-300 text-xs">SMPN 1 VII Koto</p>
        </div>
        <!-- Tombol tutup sidebar (mobile) -->
        <button onclick="toggleSidebar()"
                class="ml-auto text-white/60 hover:text-white lg:hidden text-xl leading-none">
            &times;
        </button>
    </div>

    <!-- User Info -->
    <div class="px-4 py-4 border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $avatar_bg[$user['role']] ?? 'from-slate-500 to-slate-600' ?> flex items-center justify-center text-white font-semibold text-sm flex-shrink-0 shadow-inner">
                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="text-white text-sm font-medium truncate"><?= htmlspecialchars($user['nama']) ?></p>
                <span class="text-xs px-2 py-0.5 rounded-full bg-white/10 text-slate-200 font-medium">
                    <?= $role_label[$user['role']] ?? $user['role'] ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <?php foreach ($menus as $menu): ?>
            <?php $isActive = ($active_menu === $menu['key']); ?>
            <a href="<?= $menu['href'] ?>"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200
                      <?= $isActive ? 'menu-active' : 'text-indigo-200 hover:bg-white/10 hover:text-white' ?>">
                <i data-lucide="<?= $menu['icon'] ?>" class="w-5 h-5"></i>
                <span class="text-sm font-medium"><?= $menu['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Logout -->
    <div class="px-3 py-4 border-t border-white/10">
        <a href="<?= $base ?>/logout.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-red-300 hover:bg-red-500/20 hover:text-red-200 transition-all duration-200">
            <i data-lucide="log-out" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Keluar</span>
        </a>
    </div>
</aside>

<!-- ========== MAIN WRAPPER ========== -->
<div class="lg:ml-64 min-h-screen flex flex-col">

    <!-- Topbar -->
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200 px-4 py-3 flex items-center gap-3">
        <!-- Hamburger (mobile) -->
        <button onclick="toggleSidebar()"
                class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg hover:bg-slate-100 transition-colors"
                aria-label="Buka menu">
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Breadcrumb / Title -->
        <div class="flex-1 min-w-0">
            <h1 class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($page_title) ?></h1>
            <p class="text-xs text-slate-400" id="clock"></p>
        </div>

        <!-- Notif placeholder -->
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br <?= $avatar_bg[$user['role']] ?? 'from-slate-500 to-slate-600' ?> flex items-center justify-center text-white font-semibold text-xs">
                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
            </div>
        </div>
    </header>

    <!-- Page Content wrapper — footer.php menutup div ini -->
    <main class="flex-1 p-4 md:p-6">

<?php else: ?>
<!-- Jika belum login, hanya tampilkan konten tanpa layout sidebar -->
<main class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-900 via-primary-800 to-indigo-900 p-4">
<?php endif; ?>

<script>
// Jam realtime di topbar
(function updateClock() {
    const el = document.getElementById('clock');
    if (!el) return;
    const now  = new Date();
    const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    el.textContent = days[now.getDay()] + ', ' +
        now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear() +
        ' — ' + String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
    setTimeout(updateClock, 1000);
})();

// Toggle sidebar mobile
function toggleSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const isHidden = sidebar.classList.contains('-translate-x-full');

    if (isHidden) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.remove('opacity-0'), 10);
    } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    }
}
</script>
