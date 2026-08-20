<?php
// SIMAS-GPS — Guru: Laporan Rekap Kelas
$page_title  = 'Rekap Kelas';
$active_menu = 'rekap_kelas';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('guru');

$pdo = getDB();
$guru_id = (int)$_SESSION['user_id'];

// Filter
$filter_tgl   = $_GET['tanggal'] ?? date('Y-m-d');
$filter_kelas = $_GET['kelas_id'] ?? '';
$is_export    = isset($_GET['export']) && $_GET['export'] === 'csv';

// Daftar kelas perwalian guru ini
$kelas_list = $pdo->prepare("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = ? ORDER BY tahun_ajaran DESC, nama_kelas ASC");
$kelas_list->execute([$guru_id]);
$kelas_list = $kelas_list->fetchAll();

// Jika belum diset filter kelas, otomatis pilih kelas pertama yang diampu
if ($filter_kelas === '' && !empty($kelas_list)) {
    $filter_kelas = $kelas_list[0]['id'];
}

$laporan = [];
if ($filter_kelas !== '') {
    $sql = "
        SELECT 
            s.id AS siswa_id, s.nis, s.jenis_kelamin, u.nama,
            k.nama_kelas,
            a.jam_masuk, a.jam_pulang, a.status, a.keterangan
        FROM siswa s
        JOIN users u ON s.user_id = u.id
        JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = ?
        WHERE k.id = ? AND k.wali_kelas_id = ? AND u.status = 'aktif'
        ORDER BY u.nama ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$filter_tgl, $filter_kelas, $guru_id]);
    $laporan = $stmt->fetchAll();
}

// PROSES EXPORT CSV
if ($is_export && !empty($laporan)) {
    $nama_kls = $laporan[0]['nama_kelas'] ?? 'Kelas';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Rekap_' . preg_replace('/[^A-Za-z0-9]/', '_', $nama_kls) . '_' . $filter_tgl . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'NIS', 'Nama Siswa', 'L/P', 'Status', 'Jam Masuk', 'Jam Pulang', 'Keterangan']);
    
    $no = 1;
    foreach ($laporan as $row) {
        $status = ucfirst($row['status'] ?: 'Alpa');
        fputcsv($output, [
            $no++,
            $row['nis'],
            $row['nama'],
            $row['jenis_kelamin'],
            $status,
            $row['jam_masuk'] ?: '-',
            $row['jam_pulang'] ?: '-',
            $row['keterangan'] ?: '-'
        ]);
    }
    fclose($output);
    exit;
}

// PROSES CETAK PDF KOP SURAT
if (isset($_GET['action']) && $_GET['action'] === 'cetak') {
    $is_admin = false;
    $nama_kelas = $laporan[0]['nama_kelas'] ?? '';
    require_once __DIR__ . '/../includes/cetak_template.php';
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Rekapitulasi Kelas</h2>
            <p class="text-sm text-slate-500 mt-1">Pantau kehadiran siswa khusus kelas perwalian Anda.</p>
        </div>
        
        <?php if (!empty($kelas_list)): ?>
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <input type="date" name="tanggal" value="<?= e($filter_tgl) ?>" required class="border border-slate-300 rounded-xl px-4 py-2 text-sm focus:ring-indigo-500 bg-white">
            
            <select name="kelas_id" required class="border border-slate-300 rounded-xl px-4 py-2 text-sm focus:ring-indigo-500 bg-white min-w-[150px]">
                <?php foreach($kelas_list as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm">
                Lihat
            </button>
            <button type="submit" name="export" value="csv" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm flex items-center justify-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> CSV
            </button>
            <button type="submit" name="action" value="cetak" formtarget="_blank" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm flex items-center justify-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($kelas_list)): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-2xl p-6 text-center shadow-sm">
            <i data-lucide="alert-triangle" class="w-8 h-8 mx-auto mb-2 text-amber-500"></i>
            <p class="font-bold text-lg">Anda belum menjadi Wali Kelas</p>
            <p class="text-sm mt-1">Hubungi Administrator untuk menetapkan Anda sebagai wali pada kelas tertentu.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-700 text-sm">Absensi Tgl: <?= date('d F Y', strtotime($filter_tgl)) ?></h3>
                <span class="text-xs text-slate-500 font-semibold bg-white border px-3 py-1 rounded-lg shadow-sm">Siswa: <?= count($laporan) ?></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                            <th class="px-5 py-3 font-semibold text-center w-12">No</th>
                            <th class="px-5 py-3 font-semibold">Nama Siswa</th>
                            <th class="px-5 py-3 font-semibold text-center">NIS</th>
                            <th class="px-5 py-3 font-semibold text-center w-28">Status</th>
                            <th class="px-5 py-3 font-semibold text-center">Masuk</th>
                            <th class="px-5 py-3 font-semibold text-center">Pulang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($laporan)): ?>
                            <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">Tidak ada data.</td></tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($laporan as $row): 
                                $status = $row['status'] ?: 'alpa';
                                $bg = 'bg-slate-100 text-slate-600';
                                if ($status === 'hadir') $bg = 'bg-emerald-100 text-emerald-700';
                                if ($status === 'terlambat') $bg = 'bg-amber-100 text-amber-700';
                                if ($status === 'izin') $bg = 'bg-sky-100 text-sky-700';
                                if ($status === 'sakit') $bg = 'bg-purple-100 text-purple-700';
                                if ($status === 'alpa') $bg = 'bg-red-100 text-red-700';
                            ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3 text-center text-slate-400"><?= $no++ ?></td>
                                    <td class="px-5 py-3 font-bold text-slate-800"><?= e($row['nama']) ?></td>
                                    <td class="px-5 py-3 text-center text-slate-500 text-xs font-medium"><?= e($row['nis']) ?></td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $bg ?>">
                                            <?= $status ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center text-slate-700 font-semibold"><?= $row['jam_masuk'] ? substr($row['jam_masuk'],0,5) : '-' ?></td>
                                    <td class="px-5 py-3 text-center text-slate-700 font-semibold"><?= $row['jam_pulang'] ? substr($row['jam_pulang'],0,5) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
