<?php
// Pastikan variabel $laporan, $filter_tgl tersedia dari file yang memanggil
if (!isset($laporan)) exit('Direct access not permitted');

$judul_laporan = $is_admin ? "LAPORAN KEHADIRAN KESELURUHAN" : "REKAPITULASI KEHADIRAN KELAS " . strtoupper($nama_kelas ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan - SIMAS-GPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { size: A4 portrait; margin: 1.5cm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        body { font-family: 'Times New Roman', Times, serif; color: #000; background: #fff; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; text-align: center; }
        .kop-surat h1 { font-size: 20px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .kop-surat h2 { font-size: 24px; font-weight: bold; margin: 5px 0; text-transform: uppercase; }
        .kop-surat p { font-size: 14px; margin: 0; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
        table.data-table th, table.data-table td { border: 1px solid #000; padding: 6px 8px; text-align: center; }
        table.data-table th { background-color: #f3f4f6; font-weight: bold; }
        table.data-table td.text-left { text-align: left; }
        
        .ttd-area { margin-top: 50px; width: 100%; display: flex; justify-content: flex-end; }
        .ttd-box { width: 250px; text-align: center; font-size: 14px; }
        .ttd-box .nama { margin-top: 70px; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body class="p-8 max-w-4xl mx-auto" onload="window.print()">

    <!-- Tombol Kembali (Tidak ikut tercetak) -->
    <div class="no-print mb-8 flex gap-2">
        <button onclick="window.close()" class="bg-slate-800 text-white px-4 py-2 rounded shadow text-sm font-sans">Tutup &amp; Kembali</button>
        <button onclick="window.print()" class="bg-indigo-600 text-white px-4 py-2 rounded shadow text-sm font-sans ml-2">Cetak / Simpan PDF</button>
    </div>

    <!-- KOP Surat -->
    <div class="kop-surat flex items-center justify-between">
        <div class="w-20">
            <!-- Placeholder Logo Tut Wuri Handayani / Pemda -->
            <div class="w-16 h-16 border-2 border-black rounded-full flex items-center justify-center text-[10px] font-bold">LOGO</div>
        </div>
        <div class="flex-1 text-center px-4">
            
            <h2>SMP NEGERI 1 VII KOTO SUNGAI SARIAK</h2>
            <p>Jln. Raya Sungai Sariak, Kec. VII Koto Sungai Sariak, Kab. Padang Pariaman, Sumatera Barat</p>
        </div>
        <div class="w-20"></div>
    </div>

    <div class="text-center mb-6">
        <h3 class="font-bold text-lg underline"><?= $judul_laporan ?></h3>
        <p class="text-sm mt-1">Tanggal Absensi: <?= date('d F Y', strtotime($filter_tgl)) ?></p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">NIS</th>
                <th style="width: 30%; text-align: left;">Nama Siswa</th>
                <?php if ($is_admin): ?>
                <th style="width: 10%;">Kelas</th>
                <?php endif; ?>
                <th style="width: 5%;">L/P</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 10%;">Masuk</th>
                <th style="width: 10%;">Pulang</th>
                <th style="width: 15%;">Ket</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporan)): ?>
                <tr><td colspan="<?= $is_admin ? '9' : '8' ?>">Nihil (Tidak ada data siswa)</td></tr>
            <?php else: ?>
                <?php 
                $no = 1;
                foreach ($laporan as $row): 
                    $status = ucfirst($row['status'] ?: 'Alpa');
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['nama']) ?></td>
                        <?php if ($is_admin): ?>
                        <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                        <td><strong><?= $status ?></strong></td>
                        <td><?= $row['jam_masuk'] ? substr($row['jam_masuk'],0,5) : '-' ?></td>
                        <td><?= $row['jam_pulang'] ? substr($row['jam_pulang'],0,5) : '-' ?></td>
                        <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd-area">
        <div class="ttd-box">
            <p>Sungai Sariak, <?= date('d F Y') ?></p>
            <p><?= $is_admin ? 'Kepala Sekolah / Admin' : 'Wali Kelas ' . htmlspecialchars($nama_kelas ?? '') ?></p>
            <div class="nama"><?= htmlspecialchars($_SESSION['nama']) ?></div>
            <?php if (!$is_admin): ?>
            <p>NIP. .........................</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
