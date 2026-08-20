<?php
// SIMAS-GPS — Guru Dashboard
$page_title  = 'Dashboard Guru';
$active_menu = 'dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('guru');

$pdo = getDB();
$guru_id = (int)$_SESSION['user_id'];

// Hitung total kelas yg diampu
$total_kelas = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE wali_kelas_id = ?");
$total_kelas->execute([$guru_id]);
$total_kelas = $total_kelas->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-gradient-to-r from-slate-700 to-indigo-800 rounded-2xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10">
            <h2 class="text-2xl md:text-3xl font-bold mb-2 flex items-center gap-2">Halo, Bpk/Ibu <?= e($user['nama']) ?>!</h2>
            <p class="text-indigo-200 text-sm max-w-xl">Selamat datang di Panel Guru. Anda saat ini menjadi wali dari <?= $total_kelas ?> kelas.</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="<?= base_url() ?>/guru/rekap_kelas.php" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col items-center text-center group">
            <div class="mb-4 text-indigo-500 group-hover:scale-110 transition-transform">
                <i data-lucide="clipboard-list" class="w-12 h-12"></i>
            </div>
            <h3 class="font-bold text-slate-800">Rekap Kelas</h3>
            <p class="text-xs text-slate-500 mt-2">Lihat dan cetak absensi siswa kelas Anda.</p>
        </a>

        <a href="<?= base_url() ?>/guru/approve_izin.php" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col items-center text-center group">
            <div class="mb-4 text-emerald-500 group-hover:scale-110 transition-transform">
                <i data-lucide="check-circle" class="w-12 h-12"></i>
            </div>
            <h3 class="font-bold text-slate-800">Approve Izin</h3>
            <p class="text-xs text-slate-500 mt-2">Setujui atau tolak izin/sakit siswa.</p>
        </a>

        <a href="<?= base_url() ?>/guru/absen_manual.php" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col items-center text-center group">
            <div class="mb-4 text-amber-500 group-hover:scale-110 transition-transform">
                <i data-lucide="pen-tool" class="w-12 h-12"></i>
            </div>
            <h3 class="font-bold text-slate-800">Absen Manual</h3>
            <p class="text-xs text-slate-500 mt-2">Input absen jika perangkat siswa bermasalah.</p>
        </a>

        <a href="<?= base_url() ?>/guru/rekap_kehadiran.php" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col items-center text-center group">
            <div class="mb-4 text-sky-500 group-hover:scale-110 transition-transform">
                <i data-lucide="bar-chart-3" class="w-12 h-12"></i>
            </div>
            <h3 class="font-bold text-slate-800">Rekap Siswa</h3>
            <p class="text-xs text-slate-500 mt-2">Lihat statistik kehadiran kumulatif per siswa.</p>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
