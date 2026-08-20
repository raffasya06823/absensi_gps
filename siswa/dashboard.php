<?php
// SIMAS-GPS — Siswa Dashboard
$page_title  = 'Dashboard Siswa';
$active_menu = 'dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('siswa');

$pdo = getDB();
$siswa_id = (int)$_SESSION['siswa_id'];
$tanggal  = date('Y-m-d');

// Cek absen hari ini
$stmtA = $pdo->prepare("SELECT * FROM absensi WHERE siswa_id = ? AND tanggal = ?");
$stmtA->execute([$siswa_id, $tanggal]);
$absen = $stmtA->fetch();

$st_masuk  = $absen['jam_masuk'] ?? null;
$st_pulang = $absen['jam_pulang'] ?? null;
$st_status = $absen['status'] ?? null;

// Get statistik kehadiran siswa
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'hadir' THEN 1 END) as total_hadir,
        COUNT(CASE WHEN status = 'terlambat' THEN 1 END) as total_terlambat,
        COUNT(CASE WHEN status = 'izin' THEN 1 END) as total_izin,
        COUNT(CASE WHEN status = 'sakit' THEN 1 END) as total_sakit,
        COUNT(CASE WHEN status = 'alpa' THEN 1 END) as total_alpa,
        COUNT(id) as total_rekaman
    FROM absensi 
    WHERE siswa_id = ?
");
$stmtStats->execute([$siswa_id]);
$stats = $stmtStats->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto space-y-6">
    <div class="bg-gradient-to-r from-slate-700 to-emerald-800 rounded-2xl p-6 text-white shadow-lg text-center relative overflow-hidden">
        <h2 class="text-xl font-bold mb-1">Halo, <?= e($user['nama']) ?>!</h2>
        <p class="text-emerald-100 text-sm">Semoga harimu menyenangkan.</p>
    </div>

    <!-- Status Hari Ini -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
        <h3 class="font-bold text-slate-800 mb-4 border-b pb-2 flex items-center gap-2">
            <i data-lucide="activity" class="w-4 h-4 text-emerald-500"></i> Status Kehadiran
        </h3>
        
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-600 flex items-center gap-1.5"><i data-lucide="log-in" class="w-4 h-4 text-slate-400"></i> Masuk</span>
                <?php if ($st_masuk): ?>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200"><?= substr($st_masuk,0,5) ?></span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-slate-100 text-slate-500 text-xs font-bold rounded-full">Belum</span>
                <?php endif; ?>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-600 flex items-center gap-1.5"><i data-lucide="log-out" class="w-4 h-4 text-slate-400"></i> Pulang</span>
                <?php if ($st_pulang): ?>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200"><?= substr($st_pulang,0,5) ?></span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-slate-100 text-slate-500 text-xs font-bold rounded-full">Belum</span>
                <?php endif; ?>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <span class="text-sm font-semibold text-slate-600">Status Akhir</span>
                <?php if ($st_status): 
                    $bg = 'bg-slate-100 text-slate-700';
                    if($st_status==='hadir') $bg = 'bg-emerald-500 text-white';
                    if($st_status==='terlambat') $bg = 'bg-amber-500 text-white';
                    if(in_array($st_status, ['izin','sakit'])) $bg = 'bg-sky-500 text-white';
                ?>
                    <span class="px-3 py-1 <?= $bg ?> text-xs font-bold rounded-full uppercase"><?= $st_status ?></span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-red-100 text-red-700 border border-red-200 text-xs font-bold rounded-full uppercase flex items-center gap-1"><i data-lucide="x-circle" class="w-3 h-3"></i> Alpa</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Action Menu -->
    <div class="grid grid-cols-2 gap-4">
        <a href="<?= base_url() ?>/siswa/absen.php" class="bg-gradient-to-br from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white p-4 rounded-2xl shadow-md text-center transition-all hover:-translate-y-1">
            <i data-lucide="map-pin" class="w-8 h-8 mx-auto mb-2 opacity-90"></i>
            <span class="text-sm font-bold">Absen GPS</span>
        </a>
        <a href="<?= base_url() ?>/siswa/ajukan_izin.php" class="bg-white border border-slate-200 hover:bg-slate-50 hover:border-indigo-300 text-slate-700 p-4 rounded-2xl shadow-sm text-center transition-all hover:-translate-y-1">
            <i data-lucide="file-text" class="w-8 h-8 mx-auto mb-2 text-indigo-500"></i>
            <span class="text-sm font-bold">Ajukan Izin</span>
        </a>
    </div>

    <!-- Statistik Kehadiran -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
        <h3 class="font-bold text-slate-800 mb-4 border-b pb-2 flex items-center gap-2">
            <i data-lucide="bar-chart-3" class="w-4 h-4 text-indigo-500"></i> Rekap Kehadiran Saya
        </h3>
        
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 p-4 rounded-xl border border-emerald-200">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                    <p class="text-xs font-semibold text-emerald-700">Hadir</p>
                </div>
                <p class="text-2xl font-bold text-emerald-700"><?= $stats['total_hadir'] ?></p>
            </div>
            
            <div class="bg-gradient-to-br from-amber-50 to-amber-100 p-4 rounded-xl border border-amber-200">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                    <p class="text-xs font-semibold text-amber-700">Terlambat</p>
                </div>
                <p class="text-2xl font-bold text-amber-700"><?= $stats['total_terlambat'] ?></p>
            </div>
            
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-xl border border-blue-200">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="file-text" class="w-4 h-4 text-blue-600"></i>
                    <p class="text-xs font-semibold text-blue-700">Izin</p>
                </div>
                <p class="text-2xl font-bold text-blue-700"><?= $stats['total_izin'] ?></p>
            </div>
            
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-xl border border-purple-200">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="heart-pulse" class="w-4 h-4 text-purple-600"></i>
                    <p class="text-xs font-semibold text-purple-700">Sakit</p>
                </div>
                <p class="text-2xl font-bold text-purple-700"><?= $stats['total_sakit'] ?></p>
            </div>
            
            <div class="bg-gradient-to-br from-red-50 to-red-100 p-4 rounded-xl border border-red-200">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="x-circle" class="w-4 h-4 text-red-600"></i>
                    <p class="text-xs font-semibold text-red-700">Alpha</p>
                </div>
                <p class="text-2xl font-bold text-red-700"><?= $stats['total_alpa'] ?></p>
            </div>
            
            <div class="bg-gradient-to-br from-slate-50 to-slate-100 p-4 rounded-xl border border-slate-200">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="database" class="w-4 h-4 text-slate-600"></i>
                    <p class="text-xs font-semibold text-slate-700">Total</p>
                </div>
                <p class="text-2xl font-bold text-slate-700"><?= $stats['total_rekaman'] ?></p>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-200">
            <a href="<?= base_url() ?>/siswa/riwayat.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold flex items-center justify-center gap-1">
                Lihat Riwayat Lengkap <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
