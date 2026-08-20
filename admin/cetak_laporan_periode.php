<?php
// SIMAS-GPS — Cetak Laporan Periode
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('admin');

$pdo = getDB();

// Filter
$filter_dari   = $_GET['dari'] ?? date('Y-m-d');
$filter_sampai = $_GET['sampai'] ?? date('Y-m-d');
$filter_kelas  = $_GET['kelas_id'] ?? '';
$search        = trim($_GET['q'] ?? '');

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

// Ambil list siswa beserta statistik absensi
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
array_unshift($params, $filter_dari, $filter_sampai);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// Hitung total hari
$date1 = new DateTime($filter_dari);
$date2 = new DateTime($filter_sampai);
$interval = $date1->diff($date2);
$total_hari = $interval->days + 1;

// Hitung grand total
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

// Get filter info
$filter_info = null;
if ($filter_kelas) {
    $kelas_info = $pdo->prepare("SELECT k.nama_kelas, k.tahun_ajaran, u.nama as wali_kelas 
                                 FROM kelas k 
                                 LEFT JOIN users u ON k.wali_kelas_id = u.id 
                                 WHERE k.id = ?");
    $kelas_info->execute([$filter_kelas]);
    $filter_info = $kelas_info->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi Periode</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            padding: 20mm;
            font-size: 11pt;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }
        .header h1 { 
            font-size: 20pt; 
            margin-bottom: 5px;
            color: #1e40af;
        }
        .header h2 { 
            font-size: 16pt; 
            font-weight: normal;
            color: #475569;
            margin-bottom: 10px;
        }
        .info-section {
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .info-section table {
            width: 100%;
        }
        .info-section td {
            padding: 3px 0;
        }
        .info-section td:first-child {
            width: 150px;
            font-weight: 600;
            color: #334155;
        }
        table.data { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
        }
        table.data th { 
            background: linear-gradient(to bottom, #3b82f6, #2563eb);
            color: white; 
            padding: 10px 6px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #1e40af;
            font-size: 9pt;
        }
        table.data td { 
            padding: 8px 6px;
            border: 1px solid #cbd5e1;
            text-align: center;
            font-size: 9pt;
        }
        table.data tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table.data td:nth-child(2) {
            text-align: left;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 8pt;
        }
        .badge-hadir { background: #d1fae5; color: #065f46; }
        .badge-terlambat { background: #fef3c7; color: #92400e; }
        .badge-izin { background: #dbeafe; color: #1e40af; }
        .badge-sakit { background: #e9d5ff; color: #6b21a8; }
        .badge-alpa { background: #fee2e2; color: #991b1b; }
        .badge-total { background: #e2e8f0; color: #1e293b; }
        .summary {
            margin-top: 20px;
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            background: white;
            border: 2px solid;
        }
        .summary-item.hadir { border-color: #10b981; }
        .summary-item.terlambat { border-color: #f59e0b; }
        .summary-item.izin { border-color: #3b82f6; }
        .summary-item.sakit { border-color: #a855f7; }
        .summary-item.alpa { border-color: #ef4444; }
        .summary-item.total { border-color: #64748b; }
        .summary-item label {
            display: block;
            font-size: 9pt;
            color: #64748b;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .summary-item .value {
            font-size: 18pt;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
        }
        .signature {
            margin-top: 60px;
            text-align: center;
        }
        @media print {
            body { padding: 0; }
            @page { margin: 15mm; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIMAS-GPS</h1>
        <h2>LAPORAN ABSENSI PERIODE</h2>
        <p style="color: #64748b; font-size: 10pt;">
            Dicetak pada: <?= date('d F Y, H:i') ?> WIB
        </p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td>Periode</td>
                <td>: <?= date('d F Y', strtotime($filter_dari)) ?> - <?= date('d F Y', strtotime($filter_sampai)) ?> (<?= $total_hari ?> hari)</td>
            </tr>
            <?php if ($filter_info): ?>
                <tr>
                    <td>Kelas</td>
                    <td>: <?= e($filter_info['nama_kelas']) ?></td>
                </tr>
                <tr>
                    <td>Wali Kelas</td>
                    <td>: <?= e($filter_info['wali_kelas'] ?? '-') ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td>Kelas</td>
                    <td>: Semua Kelas</td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>Jumlah Siswa</td>
                <td>: <?= count($laporan) ?> siswa</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 150px;">Nama Siswa</th>
                <th style="width: 60px;">NIS</th>
                <th style="width: 60px;">Kelas</th>
                <th style="width: 50px;">Hadir</th>
                <th style="width: 50px;">Terlambat</th>
                <th style="width: 50px;">Izin</th>
                <th style="width: 50px;">Sakit</th>
                <th style="width: 50px;">Alpha</th>
                <th style="width: 50px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporan)): ?>
                <tr><td colspan="10" style="padding: 30px;">Data tidak ditemukan</td></tr>
            <?php else: ?>
                <?php foreach ($laporan as $idx => $s): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td style="text-align: left; font-weight: 600;"><?= e($s['nama']) ?></td>
                        <td><?= e($s['nis']) ?></td>
                        <td><?= e($s['nama_kelas'] ?? '-') ?></td>
                        <td><span class="badge badge-hadir"><?= $s['total_hadir'] ?></span></td>
                        <td><span class="badge badge-terlambat"><?= $s['total_terlambat'] ?></span></td>
                        <td><span class="badge badge-izin"><?= $s['total_izin'] ?></span></td>
                        <td><span class="badge badge-sakit"><?= $s['total_sakit'] ?></span></td>
                        <td><span class="badge badge-alpa"><?= $s['total_alpa'] ?></span></td>
                        <td><span class="badge badge-total"><?= $s['total_rekaman'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary">
        <h3 style="margin-bottom: 10px; color: #1e293b;">Ringkasan Total Kehadiran</h3>
        <div class="summary-grid">
            <div class="summary-item hadir">
                <label>HADIR</label>
                <div class="value" style="color: #10b981;"><?= number_format($grand_total['hadir']) ?></div>
            </div>
            <div class="summary-item terlambat">
                <label>TERLAMBAT</label>
                <div class="value" style="color: #f59e0b;"><?= number_format($grand_total['terlambat']) ?></div>
            </div>
            <div class="summary-item izin">
                <label>IZIN</label>
                <div class="value" style="color: #3b82f6;"><?= number_format($grand_total['izin']) ?></div>
            </div>
            <div class="summary-item sakit">
                <label>SAKIT</label>
                <div class="value" style="color: #a855f7;"><?= number_format($grand_total['sakit']) ?></div>
            </div>
            <div class="summary-item alpa">
                <label>ALPHA</label>
                <div class="value" style="color: #ef4444;"><?= number_format($grand_total['alpa']) ?></div>
            </div>
            <div class="summary-item total">
                <label>TOTAL REKAMAN</label>
                <div class="value" style="color: #64748b;"><?= number_format($grand_total['rekaman']) ?></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p style="margin-bottom: 40px; font-weight: 600;">Mengetahui,</p>
        <div class="signature" style="display: flex; justify-content: space-around; align-items: flex-start; margin-top: 80px;">
            <div style="text-align: center; width: 40%;">
                <p style="margin-bottom: 60px; color: #94a3b8; font-size: 9pt;">(Tanda Tangan)</p>
                <p style="font-weight: 600; border-top: 2px solid #000; padding-top: 5px;">
                    Kepala Sekolah
                </p>
            </div>
            <div style="text-align: center; width: 40%;">
                <p style="margin-bottom: 60px; color: #94a3b8; font-size: 9pt;">(Tanda Tangan)</p>
                <p style="font-weight: 600; border-top: 2px solid #000; padding-top: 5px;">
                    Wali Kelas
                </p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
