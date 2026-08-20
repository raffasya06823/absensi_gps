<?php
// SIMAS-GPS — Siswa Absen Sekarang
$page_title  = 'Absen Sekarang';
$active_menu = 'absen';

// Set timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Bisa diakses oleh siswa mandiri ATAU siswa via device kelas
if (empty($_SESSION['siswa_id'])) {
    redirect(base_url() . '/login.php');
}

$pdo = getDB();
$siswa_id = (int)$_SESSION['siswa_id'];
$tanggal  = date('Y-m-d');
$sekarang = date('H:i:s');

// Ambil info siswa
$stmtS = $pdo->prepare("SELECT s.nis, u.nama, k.nama_kelas FROM siswa s JOIN users u ON s.user_id = u.id LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.id = ?");
$stmtS->execute([$siswa_id]);
$siswa = $stmtS->fetch();

// Cek status absen hari ini
$stmtA = $pdo->prepare("SELECT * FROM absensi WHERE siswa_id = ? AND tanggal = ?");
$stmtA->execute([$siswa_id, $tanggal]);
$absen = $stmtA->fetch();

$sudah_masuk  = ($absen && $absen['jam_masuk'] !== null);
$sudah_pulang = ($absen && $absen['jam_pulang'] !== null);

// Ambil cfg jam
$cfg = $pdo->query("SELECT jam_masuk_mulai, jam_masuk_selesai, jam_pulang_mulai, jam_pulang_selesai, radius_meter FROM pengaturan_sekolah WHERE id=1")->fetch();

// Perbandingan waktu - gunakan string comparison karena format H:i:s sudah comparable
// Format H:i:s (00:00:00 - 23:59:59) bisa dibandingkan langsung sebagai string
$boleh_pulang = ($sekarang >= $cfg['jam_pulang_mulai']);

require_once __DIR__ . '/../includes/header.php';
$csrf = csrf_token();
?>

<div class="max-w-md mx-auto space-y-6 animate-fade-in pb-10">
    <!-- Profil Singkat -->
    <div class="bg-indigo-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
            <i data-lucide="map-pin" class="w-24 h-24"></i>
        </div>
        <div class="relative z-10 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold border border-white/30 shadow-inner">
                <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
            </div>
            <div>
                <h2 class="text-xl font-bold leading-tight"><?= e($siswa['nama']) ?></h2>
                <p class="text-indigo-200 text-sm mt-1">NIS: <?= e($siswa['nis']) ?> &bull; <?= e($siswa['nama_kelas'] ?? '-') ?></p>
            </div>
        </div>
    </div>

    <!-- Jam Realtime -->
    <div class="bg-white rounded-2xl p-6 text-center shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm font-medium mb-1"><?= date('l, d F Y') ?></p>
        <div id="waktu-lokal" class="text-5xl font-black text-slate-800 tracking-tight" style="font-variant-numeric: tabular-nums;">
            <?= date('H:i:s') ?>
        </div>
        <p class="text-xs text-slate-400 mt-2">Pastikan izin lokasi GPS di browser Anda aktif.</p>
    </div>

    <!-- Alert Container -->
    <div id="result-box" class="hidden rounded-xl p-4 border shadow-sm transition-all"></div>

    <!-- Aksi Absen -->
    <div class="space-y-4">
        <!-- Panel Absen Masuk -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border <?= $sudah_masuk ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200' ?>">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="font-bold text-slate-800">Masuk</h3>
                    <p class="text-xs text-slate-500 mt-0.5"><?= substr($cfg['jam_masuk_mulai'],0,5) ?> - <?= substr($cfg['jam_masuk_selesai'],0,5) ?></p>
                </div>
                <?php if ($sudah_masuk): ?>
                    <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full border border-emerald-200">Selesai (<?= substr($absen['jam_masuk'],0,5) ?>)</span>
                <?php endif; ?>
            </div>
            
            <?php if (!$sudah_masuk): ?>
                <button id="btn-absen-masuk" onclick="prosesAbsen('masuk', '<?= $csrf ?>')" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl transition-all shadow-sm flex justify-center items-center gap-2">
                    <i data-lucide="log-in" class="w-5 h-5"></i> Rekam Absen Masuk
                </button>
            <?php else: ?>
                <button disabled class="w-full bg-slate-100 text-slate-400 font-bold py-3.5 rounded-xl cursor-not-allowed">
                    Sudah Absen Masuk
                </button>
            <?php endif; ?>
        </div>

        <!-- Panel Absen Pulang -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border <?= $sudah_pulang ? 'border-blue-200 bg-blue-50/30' : 'border-slate-200' ?>">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="font-bold text-slate-800">Pulang</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Mulai <?= substr($cfg['jam_pulang_mulai'],0,5) ?></p>
                </div>
                <?php if ($sudah_pulang): ?>
                    <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full border border-blue-200">Selesai (<?= substr($absen['jam_pulang'],0,5) ?>)</span>
                <?php endif; ?>
            </div>

            <?php if (!$sudah_masuk): ?>
                <button disabled class="w-full bg-slate-100 text-slate-400 font-bold py-3.5 rounded-xl cursor-not-allowed text-sm">
                    Harus absen masuk terlebih dahulu
                </button>
            <?php elseif ($sudah_pulang): ?>
                <button disabled class="w-full bg-slate-100 text-slate-400 font-bold py-3.5 rounded-xl cursor-not-allowed">
                    Sudah Absen Pulang
                </button>
            <?php else: ?>
                <!-- Tombol absen pulang aktif setelah jam_pulang_mulai -->
                <?php if (!$boleh_pulang): ?>
                    <button disabled class="w-full bg-slate-100 text-slate-400 font-bold py-3.5 rounded-xl cursor-not-allowed text-sm">
                        Belum Waktu Pulang (Mulai <?= substr($cfg['jam_pulang_mulai'],0,5) ?>)
                    </button>
                <?php else: ?>
                    <button id="btn-absen-pulang" onclick="prosesAbsen('pulang', '<?= $csrf ?>')" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold py-3.5 rounded-xl transition-all shadow-sm flex justify-center items-center gap-2">
                        <i data-lucide="log-out" class="w-5 h-5"></i> Rekam Absen Pulang
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Indikator Radius -->
    <div class="text-center mt-6">
        <p class="text-xs text-slate-400">Toleransi radius lokasi sekolah: <span class="font-bold text-slate-600"><?= $cfg['radius_meter'] ?> Meter</span></p>
    </div>
</div>

<script src="<?= base_url() ?>/assets/js/geolocation.js"></script>
<script>
// Jam Realtime
(function tick() {
    const el = document.getElementById('waktu-lokal');
    if (!el) return;
    const now = new Date();
    el.textContent = String(now.getHours()).padStart(2,'0') + ':' + 
                     String(now.getMinutes()).padStart(2,'0') + ':' + 
                     String(now.getSeconds()).padStart(2,'0');
    setTimeout(tick, 1000);
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
