<?php
// SIMAS-GPS — Guru Rekap Kehadiran
$page_title  = 'Rekap Kehadiran';
$active_menu = 'rekap_kehadiran';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('guru');

$pdo = getDB();
$guru_id = $_SESSION['user_id'] ?? null;

// Jika tidak ada user_id, redirect ke login
if (!$guru_id) {
    header('Location: ' . base_url() . '/login.php');
    exit;
}

// Get kelas yang diampu guru ini sebagai wali kelas
$kelas_guru = $pdo->prepare("SELECT k.id, k.nama_kelas, k.tahun_ajaran 
                             FROM kelas k 
                             WHERE k.wali_kelas_id = ?
                             ORDER BY k.tahun_ajaran DESC, k.nama_kelas ASC");
$kelas_guru->execute([$guru_id]);
$kelas_list = $kelas_guru->fetchAll();

// Jika guru tidak memiliki kelas, set array kosong
if ($kelas_list === false) {
    $kelas_list = [];
}

// Filter
$filter_kelas = $_GET['kelas'] ?? (!empty($kelas_list) ? $kelas_list[0]['id'] : '');
$search = trim($_GET['q'] ?? '');

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
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
} else {
    // Jika tidak ada filter, tampilkan siswa dari kelas yang diampu guru ini
    if (!empty($kelas_list)) {
        $kelas_ids = array_column($kelas_list, 'id');
        $placeholders = implode(',', array_fill(0, count($kelas_ids), '?'));
        $where .= " AND s.kelas_id IN ($placeholders)";
        $params = array_merge($params, $kelas_ids);
    } else {
        $where .= " AND 1=0"; // Tidak ada kelas
    }
}

// Get total rows
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM siswa s 
                            JOIN users u ON s.user_id = u.id 
                            LEFT JOIN kelas k ON s.kelas_id = k.id 
                            WHERE $where");
$stmtTotal->execute($params);
$totalRows = $stmtTotal->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Get siswa with attendance statistics
$stmt = $pdo->prepare("
    SELECT 
        s.id, s.user_id, s.nis, s.kelas_id, u.nama, u.username, 
        k.nama_kelas, k.tahun_ajaran,
        COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as total_hadir,
        COUNT(CASE WHEN a.status = 'terlambat' THEN 1 END) as total_terlambat,
        COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as total_izin,
        COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as total_sakit,
        COUNT(CASE WHEN a.status = 'alpa' THEN 1 END) as total_alpa,
        COUNT(a.id) as total_rekaman
    FROM siswa s 
    JOIN users u ON s.user_id = u.id 
    LEFT JOIN kelas k ON s.kelas_id = k.id 
    LEFT JOIN absensi a ON s.id = a.siswa_id
    WHERE $where
    GROUP BY s.id
    ORDER BY k.nama_kelas ASC, u.nama ASC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$siswa_list = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-6">
    <?php if (empty($kelas_list)): ?>
        <!-- Pesan jika guru tidak memiliki kelas -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 text-center">
            <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3 text-yellow-600"></i>
            <h3 class="text-lg font-bold text-yellow-800 mb-2">Belum Ada Kelas yang Diampu</h3>
            <p class="text-sm text-yellow-700">Anda belum ditugaskan sebagai wali kelas. Silakan hubungi administrator untuk penugasan kelas.</p>
        </div>
    <?php else: ?>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Rekap Kehadiran Siswa</h2>
            <p class="text-sm text-slate-500 mt-1">Statistik akumulasi kehadiran siswa kelas Anda</p>
        </div>
        <a href="cetak_rekap_kehadiran.php?kelas=<?= urlencode($filter_kelas) ?>&q=<?= urlencode($search) ?>" 
           target="_blank" 
           class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2 justify-center">
            <i data-lucide="printer" class="w-4 h-4"></i> Cetak Laporan
        </a>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <form method="GET" id="filterForm" class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <input type="text" name="q" id="searchInput" value="<?= e($search) ?>" placeholder="Cari nama/NIS..." 
                       class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500"
                       autocomplete="off">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
            </div>
            <select name="kelas" id="kelasSelect" class="border border-slate-300 rounded-lg text-sm px-4 py-2 focus:ring-indigo-500">
                <option value="">Semua Kelas Anda</option>
                <?php foreach($kelas_list as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>>
                        <?= e($k['nama_kelas']) ?> (<?= e($k['tahun_ajaran']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <script>
    // Live search implementation
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');
    const kelasSelect = document.getElementById('kelasSelect');
    const filterForm = document.getElementById('filterForm');

    // Auto submit on search input (debounced)
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterForm.submit();
        }, 500); // Wait 500ms after user stops typing
    });

    // Auto submit on select change
    kelasSelect.addEventListener('change', () => filterForm.submit());
    </script>

    <!-- Statistics Cards -->
    <?php
    $total_stats = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
    foreach($siswa_list as $s) {
        $total_stats['hadir'] += $s['total_hadir'];
        $total_stats['terlambat'] += $s['total_terlambat'];
        $total_stats['izin'] += $s['total_izin'];
        $total_stats['sakit'] += $s['total_sakit'];
        $total_stats['alpa'] += $s['total_alpa'];
    }
    ?>
    
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <p class="text-xs font-medium opacity-90">Hadir</p>
            </div>
            <p class="text-3xl font-bold"><?= number_format($total_stats['hadir']) ?></p>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="clock" class="w-5 h-5"></i>
                <p class="text-xs font-medium opacity-90">Terlambat</p>
            </div>
            <p class="text-3xl font-bold"><?= number_format($total_stats['terlambat']) ?></p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="file-text" class="w-5 h-5"></i>
                <p class="text-xs font-medium opacity-90">Izin</p>
            </div>
            <p class="text-3xl font-bold"><?= number_format($total_stats['izin']) ?></p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="heart-pulse" class="w-5 h-5"></i>
                <p class="text-xs font-medium opacity-90">Sakit</p>
            </div>
            <p class="text-3xl font-bold"><?= number_format($total_stats['sakit']) ?></p>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="x-circle" class="w-5 h-5"></i>
                <p class="text-xs font-medium opacity-90">Alpha</p>
            </div>
            <p class="text-3xl font-bold"><?= number_format($total_stats['alpa']) ?></p>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <th class="px-4 py-3 font-semibold">No</th>
                        <th class="px-4 py-3 font-semibold">Siswa / NIS</th>
                        <th class="px-4 py-3 font-semibold">Kelas</th>
                        <th class="px-4 py-3 font-semibold text-center">Hadir</th>
                        <th class="px-4 py-3 font-semibold text-center">Terlambat</th>
                        <th class="px-4 py-3 font-semibold text-center">Izin</th>
                        <th class="px-4 py-3 font-semibold text-center">Sakit</th>
                        <th class="px-4 py-3 font-semibold text-center">Alpha</th>
                        <th class="px-4 py-3 font-semibold text-center">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($siswa_list)): ?>
                        <tr><td colspan="9" class="px-6 py-8 text-center text-slate-400">Data tidak ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($siswa_list as $idx => $s): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-600"><?= $offset + $idx + 1 ?></td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-800"><?= e($s['nama']) ?></p>
                                    <p class="text-xs text-slate-500">NIS: <?= e($s['nis']) ?></p>
                                </td>
                                <td class="px-4 py-3 text-slate-700"><?= e($s['nama_kelas'] ?? '-') ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <?= $s['total_hadir'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <?= $s['total_terlambat'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                        <?= $s['total_izin'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                        <?= $s['total_sakit'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <?= $s['total_alpa'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-800">
                                        <?= $s['total_rekaman'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-slate-200 flex justify-between items-center">
            <span class="text-sm text-slate-500">Halaman <?= $page ?> dari <?= $totalPages ?> (Total: <?= $totalRows ?> siswa)</span>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&kelas=<?= urlencode($filter_kelas) ?>" 
                       class="px-3 py-1 border rounded text-sm hover:bg-slate-50">Sebelumnya</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&kelas=<?= urlencode($filter_kelas) ?>" 
                       class="px-3 py-1 border rounded text-sm hover:bg-slate-50">Selanjutnya</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; // End if empty kelas_list ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
