<?php
// SIMAS-GPS — Admin Dashboard
$page_title  = 'Dashboard Admin';
$active_menu = 'dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('admin');

$pdo = getDB();
$hari_ini = date('Y-m-d');

// 1. Ringkasan Total
$total_siswa = $pdo->query("SELECT COUNT(*) FROM siswa s JOIN users u ON s.user_id = u.id WHERE u.status='aktif'")->fetchColumn();
$total_guru  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='guru' AND status='aktif'")->fetchColumn();
$total_kelas = $pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();

// 2. Statistik Kehadiran Hari Ini
$stmtAbsen = $pdo->prepare("
    SELECT a.status, COUNT(*) as jumlah 
    FROM absensi a
    JOIN siswa s ON a.siswa_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE a.tanggal = ? AND u.status = 'aktif'
    GROUP BY a.status
");
$stmtAbsen->execute([$hari_ini]);
$absen_raw = $stmtAbsen->fetchAll();

$stats = [
    'hadir'     => 0,
    'terlambat' => 0,
    'izin'      => 0,
    'sakit'     => 0,
    'alpa'      => 0
];
$total_absen_tercatat = 0;

foreach ($absen_raw as $row) {
    $st = $row['status'];
    if (isset($stats[$st])) {
        $stats[$st] = (int)$row['jumlah'];
        $total_absen_tercatat += $stats[$st];
    }
}
// Siswa yang belum absen otomatis dianggap alpa (atau belum diabsen) di ringkasan hari ini
$belum_absen = max(0, $total_siswa - $total_absen_tercatat);
$stats['alpa'] += $belum_absen;

// 3. Data Grafik 7 Hari Terakhir
$dates = [];
$grafik_hadir = [];
$grafik_terlambat = [];
$grafik_izin_sakit = [];

for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('d M', strtotime($tgl));
    
    // Hadir
    $stmtH = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE tanggal = ? AND status = 'hadir'");
    $stmtH->execute([$tgl]);
    $grafik_hadir[] = $stmtH->fetchColumn();
    
    // Terlambat
    $stmtT = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE tanggal = ? AND status = 'terlambat'");
    $stmtT->execute([$tgl]);
    $grafik_terlambat[] = $stmtT->fetchColumn();
    
    // Izin & Sakit
    $stmtI = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE tanggal = ? AND status IN ('izin', 'sakit')");
    $stmtI->execute([$tgl]);
    $grafik_izin_sakit[] = $stmtI->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-6xl mx-auto space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-slate-700 to-indigo-900 rounded-2xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden">
        <div class="absolute right-0 bottom-0 opacity-10 pointer-events-none -mb-10 -mr-10">
            <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
        </div>
        <div class="relative z-10">
            <h2 class="text-2xl md:text-3xl font-bold mb-2 flex items-center gap-2">
                Selamat Datang, <?= e($user['nama']) ?>!
            </h2>
            <p class="text-indigo-200 text-sm md:text-base max-w-xl">Ringkasan kehadiran siswa SMPN 1 VII Koto Sungai Sarik hari ini. Pastikan untuk selalu memantau aktivitas sistem.</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Card: Siswa -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4 hover:-translate-y-1 transition-transform">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Siswa</p>
                <p class="text-2xl font-bold text-slate-800"><?= $total_siswa ?></p>
            </div>
        </div>
        <!-- Card: Guru -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4 hover:-translate-y-1 transition-transform">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="graduation-cap" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Guru</p>
                <p class="text-2xl font-bold text-slate-800"><?= $total_guru ?></p>
            </div>
        </div>
        <!-- Card: Kelas -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4 hover:-translate-y-1 transition-transform">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="school" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Kelas</p>
                <p class="text-2xl font-bold text-slate-800"><?= $total_kelas ?></p>
            </div>
        </div>
        <!-- Card: Setup Lokasi -->
        <a href="<?= base_url() ?>/admin/pengaturan_lokasi.php" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4 hover:-translate-y-1 hover:border-indigo-300 transition-all cursor-pointer">
            <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="map-pin" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Koordinat</p>
                <p class="text-sm font-bold text-indigo-600 flex items-center gap-1">Lihat/Atur <i data-lucide="arrow-right" class="w-3 h-3"></i></p>
            </div>
        </a>
    </div>

    <!-- Kehadiran Hari Ini & Grafik -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Kehadiran Hari Ini -->
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col">
            <h3 class="font-bold text-slate-800 mb-6 flex items-center gap-2"><i data-lucide="activity" class="w-5 h-5 text-indigo-500"></i> Kehadiran Hari Ini</h3>
            
            <div class="space-y-5 flex-1">
                <!-- Hadir -->
                <div>
                    <div class="flex justify-between text-sm mb-1 font-semibold">
                        <span class="text-emerald-700 flex items-center gap-1.5"><i data-lucide="check-circle" class="w-4 h-4"></i> Hadir</span>
                        <span class="text-slate-800"><?= $stats['hadir'] ?></span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: <?= $total_siswa ? ($stats['hadir']/$total_siswa*100) : 0 ?>%"></div>
                    </div>
                </div>

                <!-- Terlambat -->
                <div>
                    <div class="flex justify-between text-sm mb-1 font-semibold">
                        <span class="text-amber-600 flex items-center gap-1.5"><i data-lucide="clock" class="w-4 h-4"></i> Terlambat</span>
                        <span class="text-slate-800"><?= $stats['terlambat'] ?></span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-amber-400 h-2 rounded-full" style="width: <?= $total_siswa ? ($stats['terlambat']/$total_siswa*100) : 0 ?>%"></div>
                    </div>
                </div>

                <!-- Izin -->
                <div>
                    <div class="flex justify-between text-sm mb-1 font-semibold">
                        <span class="text-sky-600 flex items-center gap-1.5"><i data-lucide="file-text" class="w-4 h-4"></i> Izin</span>
                        <span class="text-slate-800"><?= $stats['izin'] ?></span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-sky-400 h-2 rounded-full" style="width: <?= $total_siswa ? ($stats['izin']/$total_siswa*100) : 0 ?>%"></div>
                    </div>
                </div>

                <!-- Sakit -->
                <div>
                    <div class="flex justify-between text-sm mb-1 font-semibold">
                        <span class="text-purple-600 flex items-center gap-1.5"><i data-lucide="thermometer" class="w-4 h-4"></i> Sakit</span>
                        <span class="text-slate-800"><?= $stats['sakit'] ?></span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-purple-400 h-2 rounded-full" style="width: <?= $total_siswa ? ($stats['sakit']/$total_siswa*100) : 0 ?>%"></div>
                    </div>
                </div>

                <!-- Alpa/Belum Absen -->
                <div>
                    <div class="flex justify-between text-sm mb-1 font-semibold">
                        <span class="text-red-600 flex items-center gap-1.5"><i data-lucide="x-circle" class="w-4 h-4"></i> Alpa / Belum Info</span>
                        <span class="text-slate-800"><?= $stats['alpa'] ?></span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: <?= $total_siswa ? ($stats['alpa']/$total_siswa*100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
            
            <a href="<?= base_url() ?>/admin/laporan.php" class="mt-6 block text-center bg-slate-50 hover:bg-slate-100 text-indigo-600 text-sm font-semibold py-2.5 rounded-xl transition-colors">
                Lihat Laporan Lengkap
            </a>
        </div>

        <!-- Grafik 7 Hari -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-6">Tren Kehadiran (7 Hari Terakhir)</h3>
            <div class="relative h-64 w-full">
                <canvas id="kehadiranChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('kehadiranChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [
            {
                label: 'Hadir Tepat Waktu',
                data: <?= json_encode($grafik_hadir) ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.8)', // emerald
                borderRadius: 4
            },
            {
                label: 'Terlambat',
                data: <?= json_encode($grafik_terlambat) ?>,
                backgroundColor: 'rgba(251, 191, 36, 0.8)', // amber
                borderRadius: 4
            },
            {
                label: 'Izin / Sakit',
                data: <?= json_encode($grafik_izin_sakit) ?>,
                backgroundColor: 'rgba(56, 189, 248, 0.8)', // sky
                borderRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } }
        },
        scales: {
            x: { stacked: true, grid: { display: false } },
            y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
