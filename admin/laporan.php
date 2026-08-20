<?php
// SIMAS-GPS — Admin Laporan (Semua Kelas)
$page_title  = 'Laporan Keseluruhan';
$active_menu = 'laporan';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('admin');

$pdo = getDB();

// Filter
$filter_dari   = $_GET['dari'] ?? date('Y-m-d');
$filter_sampai = $_GET['sampai'] ?? date('Y-m-d');
$filter_kelas  = $_GET['kelas_id'] ?? '';
$search        = trim($_GET['q'] ?? '');
$is_export     = isset($_GET['export']) && $_GET['export'] === 'csv';

// Handle quick date filters
if (isset($_GET['quick'])) {
    switch ($_GET['quick']) {
        case 'today':
            $filter_dari = $filter_sampai = date('Y-m-d');
            break;
        case '7days':
            $filter_dari = date('Y-m-d', strtotime('-6 days'));
            $filter_sampai = date('Y-m-d');
            break;
        case '30days':
            $filter_dari = date('Y-m-d', strtotime('-29 days'));
            $filter_sampai = date('Y-m-d');
            break;
        case 'thismonth':
            $filter_dari = date('Y-m-01');
            $filter_sampai = date('Y-m-t');
            break;
    }
}

// Query dasar siswa
$where = "u.role = 'siswa' AND u.status = 'aktif'";
$params = [];

if ($search !== '') {
    $where .= " AND (u.nama LIKE ? OR s.nis LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_kelas !== '') {
    $where .= " AND s.kelas_id = ?";
    $params[] = $filter_kelas;
}

// Ambil list siswa beserta statistik absensi mereka pada periode terpilih
$sql = "
    SELECT 
        s.id AS siswa_id, s.nis, s.jenis_kelamin, u.nama,
        k.nama_kelas,
        COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as total_hadir,
        COUNT(CASE WHEN a.status = 'terlambat' THEN 1 END) as total_terlambat,
        COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as total_izin,
        COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as total_sakit,
        COUNT(CASE WHEN a.status = 'alpa' THEN 1 END) as total_alpa,
        COUNT(a.id) as total_rekaman
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal BETWEEN ? AND ?
    WHERE $where
    GROUP BY s.id
    ORDER BY k.nama_kelas ASC, u.nama ASC
";
array_unshift($params, $filter_dari, $filter_sampai); // masukkan tanggal di awal params

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// Hitung total hari dalam periode
$date1 = new DateTime($filter_dari);
$date2 = new DateTime($filter_sampai);
$interval = $date1->diff($date2);
$total_hari = $interval->days + 1;

// Hitung grand total untuk summary
$grand_total = [
    'hadir' => 0,
    'terlambat' => 0,
    'izin' => 0,
    'sakit' => 0,
    'alpa' => 0,
    'rekaman' => 0
];
foreach ($laporan as $row) {
    $grand_total['hadir'] += $row['total_hadir'];
    $grand_total['terlambat'] += $row['total_terlambat'];
    $grand_total['izin'] += $row['total_izin'];
    $grand_total['sakit'] += $row['total_sakit'];
    $grand_total['alpa'] += $row['total_alpa'];
    $grand_total['rekaman'] += $row['total_rekaman'];
}

// PROSES EXPORT CSV
if ($is_export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Absensi_' . $filter_dari . '_sampai_' . $filter_sampai . '.csv');
    
    $output = fopen('php://output', 'w');
    // Header CSV
    fputcsv($output, ['No', 'NIS', 'Nama Siswa', 'Kelas', 'L/P', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha', 'Total Rekaman']);
    
    $no = 1;
    foreach ($laporan as $row) {
        fputcsv($output, [
            $no++,
            $row['nis'],
            $row['nama'],
            $row['nama_kelas'],
            $row['jenis_kelamin'],
            $row['total_hadir'],
            $row['total_terlambat'],
            $row['total_izin'],
            $row['total_sakit'],
            $row['total_alpa'],
            $row['total_rekaman']
        ]);
    }
    fclose($output);
    exit;
}

// PROSES CETAK PDF KOP SURAT
if (isset($_GET['action']) && $_GET['action'] === 'cetak') {
    $is_admin = true;
    require_once __DIR__ . '/../includes/cetak_template.php';
    exit;
}

// Daftar kelas untuk dropdown filter
$kelas_list = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY tahun_ajaran DESC, nama_kelas ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Laporan Absensi</h2>
                <p class="text-sm text-slate-500 mt-1">Pantau & export kehadiran periode siswa</p>
            </div>
            
            <div class="flex gap-2">
                <a href="?export=csv&dari=<?= urlencode($filter_dari) ?>&sampai=<?= urlencode($filter_sampai) ?>&kelas_id=<?= urlencode($filter_kelas) ?>&q=<?= urlencode($search) ?>" 
                   class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm flex items-center justify-center gap-2">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Export CSV
                </a>
                <a href="cetak_laporan_periode.php?dari=<?= urlencode($filter_dari) ?>&sampai=<?= urlencode($filter_sampai) ?>&kelas_id=<?= urlencode($filter_kelas) ?>&q=<?= urlencode($search) ?>" 
                   target="_blank"
                   class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm flex items-center justify-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4"></i> Cetak PDF
                </a>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 space-y-4">
            <form method="GET" id="filterForm" class="space-y-4">
                <!-- Quick Date Buttons -->
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="setQuickDate('today')" class="quick-btn px-3 py-1.5 text-xs font-semibold rounded-lg border-2 transition-all hover:bg-indigo-50 hover:border-indigo-300">
                        Hari Ini
                    </button>
                    <button type="button" onclick="setQuickDate('7days')" class="quick-btn px-3 py-1.5 text-xs font-semibold rounded-lg border-2 transition-all hover:bg-indigo-50 hover:border-indigo-300">
                        7 Hari Terakhir
                    </button>
                    <button type="button" onclick="setQuickDate('30days')" class="quick-btn px-3 py-1.5 text-xs font-semibold rounded-lg border-2 transition-all hover:bg-indigo-50 hover:border-indigo-300">
                        30 Hari Terakhir
                    </button>
                    <button type="button" onclick="setQuickDate('thismonth')" class="quick-btn px-3 py-1.5 text-xs font-semibold rounded-lg border-2 transition-all hover:bg-indigo-50 hover:border-indigo-300">
                        Bulan Ini
                    </button>
                </div>

                <!-- Date Range & Filters -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex items-center gap-2 flex-1">
                        <label class="text-sm font-semibold text-slate-600 whitespace-nowrap">Dari:</label>
                        <input type="date" name="dari" id="dariInput" value="<?= e($filter_dari) ?>" 
                               class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 bg-white">
                    </div>
                    <div class="flex items-center gap-2 flex-1">
                        <label class="text-sm font-semibold text-slate-600 whitespace-nowrap">Sampai:</label>
                        <input type="date" name="sampai" id="sampaiInput" value="<?= e($filter_sampai) ?>" 
                               class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 bg-white">
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <input type="text" name="q" id="searchInput" value="<?= e($search) ?>" placeholder="Cari nama/NIS..." 
                               class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500"
                               autocomplete="off">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
                    </div>
                    
                    <select name="kelas_id" id="kelasSelect" class="border border-slate-300 rounded-lg px-4 py-2 text-sm focus:ring-indigo-500 bg-white min-w-[200px]">
                        <option value="">Semua Kelas</option>
                        <?php foreach($kelas_list as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <p class="text-xs font-medium opacity-90">Hadir</p>
                </div>
                <p class="text-2xl font-bold"><?= number_format($grand_total['hadir']) ?></p>
            </div>
            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    <p class="text-xs font-medium opacity-90">Terlambat</p>
                </div>
                <p class="text-2xl font-bold"><?= number_format($grand_total['terlambat']) ?></p>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <p class="text-xs font-medium opacity-90">Izin</p>
                </div>
                <p class="text-2xl font-bold"><?= number_format($grand_total['izin']) ?></p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="heart-pulse" class="w-4 h-4"></i>
                    <p class="text-xs font-medium opacity-90">Sakit</p>
                </div>
                <p class="text-2xl font-bold"><?= number_format($grand_total['sakit']) ?></p>
            </div>
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                    <p class="text-xs font-medium opacity-90">Alpha</p>
                </div>
                <p class="text-2xl font-bold"><?= number_format($grand_total['alpa']) ?></p>
            </div>
            <div class="bg-gradient-to-br from-slate-600 to-slate-700 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="database" class="w-4 h-4"></i>
                    <p class="text-xs font-medium opacity-90">Total</p>
                </div>
                <p class="text-2xl font-bold"><?= number_format($grand_total['rekaman']) ?></p>
            </div>
        </div>
    </div>

    <!-- Table Result -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <h3 class="font-bold text-slate-700 text-sm">
                Periode: <?= date('d M Y', strtotime($filter_dari)) ?> - <?= date('d M Y', strtotime($filter_sampai)) ?> 
                <span class="text-slate-500 font-normal">(<?= $total_hari ?> hari)</span>
            </h3>
            <span class="text-xs text-slate-500 font-semibold bg-white border px-3 py-1 rounded-lg shadow-sm">
                <?= count($laporan) ?> Siswa
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <th class="px-4 py-3 font-semibold text-center w-12">No</th>
                        <th class="px-4 py-3 font-semibold">Nama Siswa / NIS</th>
                        <th class="px-4 py-3 font-semibold text-center">Kelas</th>
                        <th class="px-4 py-3 font-semibold text-center">Hadir</th>
                        <th class="px-4 py-3 font-semibold text-center">Terlambat</th>
                        <th class="px-4 py-3 font-semibold text-center">Izin</th>
                        <th class="px-4 py-3 font-semibold text-center">Sakit</th>
                        <th class="px-4 py-3 font-semibold text-center">Alpha</th>
                        <th class="px-4 py-3 font-semibold text-center">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($laporan)): ?>
                        <tr><td colspan="9" class="px-6 py-10 text-center text-slate-400">Data tidak ditemukan untuk filter ini.</td></tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach ($laporan as $row): 
                        ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-center text-slate-400"><?= $no++ ?></td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-800"><?= e($row['nama']) ?></p>
                                    <p class="text-xs text-slate-500">NIS: <?= e($row['nis']) ?></p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs font-semibold text-slate-700"><?= e($row['nama_kelas'] ?? '-') ?></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <?= $row['total_hadir'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <?= $row['total_terlambat'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                        <?= $row['total_izin'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                        <?= $row['total_sakit'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <?= $row['total_alpa'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-800">
                                        <?= $row['total_rekaman'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Quick date buttons
function setQuickDate(type) {
    const params = new URLSearchParams(window.location.search);
    params.set('quick', type);
    params.delete('dari');
    params.delete('sampai');
    window.location.search = params.toString();
}

// Live search & auto submit
let searchTimeout;
const searchInput = document.getElementById('searchInput');
const kelasSelect = document.getElementById('kelasSelect');
const dariInput = document.getElementById('dariInput');
const sampaiInput = document.getElementById('sampaiInput');
const filterForm = document.getElementById('filterForm');

// Auto submit on search input (debounced)
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        filterForm.submit();
    }, 500);
});

// Auto submit on date/select change
kelasSelect.addEventListener('change', () => filterForm.submit());
dariInput.addEventListener('change', () => filterForm.submit());
sampaiInput.addEventListener('change', () => filterForm.submit());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
