<?php
// SIMAS-GPS — Siswa: Riwayat Absen
$page_title  = 'Riwayat Kehadiran';
$active_menu = 'riwayat';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('siswa');

$pdo = getDB();
$siswa_id = (int)$_SESSION['siswa_id'];

// Default filter (Bulan Ini)
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

$sql = "
    SELECT tanggal, jam_masuk, jam_pulang, jarak_masuk_meter, jarak_pulang_meter, status, keterangan 
    FROM absensi 
    WHERE siswa_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?
    ORDER BY tanggal DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$siswa_id, $filter_bulan]);
$riwayat = $stmt->fetchAll();

// Menghitung statistik bulan ini
$stats = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
foreach ($riwayat as $r) {
    if (isset($stats[$r['status']])) {
        $stats[$r['status']]++;
    }
}

// Generate dropdown bulan (6 bulan terakhir)
$bulan_list = [];
for ($i = 0; $i < 6; $i++) {
    $tgl = date('Y-m', strtotime("-$i months"));
    $nama_bulan = date('F Y', strtotime($tgl . '-01'));
    // Terjemahan bulan sederhana
    $nama_bulan = str_replace(
        ['January','February','March','April','May','June','July','August','September','October','November','December'],
        ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
        $nama_bulan
    );
    $bulan_list[$tgl] = $nama_bulan;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6 pb-10">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Riwayat Kehadiran</h2>
            <p class="text-sm text-slate-500 mt-1">Pantau catatan absensi Anda secara detail.</p>
        </div>
        
        <form method="GET" class="flex items-center gap-2">
            <select name="bulan" class="border border-slate-300 rounded-xl px-4 py-2 text-sm focus:ring-indigo-500 bg-white" onchange="this.form.submit()">
                <?php foreach ($bulan_list as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $filter_bulan === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Statistik Singkat -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-emerald-600 mb-1">Hadir</p>
                <p class="text-2xl font-black text-emerald-700"><?= $stats['hadir'] ?></p>
            </div>
            <i data-lucide="check-circle" class="w-7 h-7 text-emerald-400"></i>
        </div>
        <div class="bg-amber-50 rounded-2xl p-4 border border-amber-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-amber-600 mb-1">Terlambat</p>
                <p class="text-2xl font-black text-amber-700"><?= $stats['terlambat'] ?></p>
            </div>
            <i data-lucide="clock" class="w-7 h-7 text-amber-400"></i>
        </div>
        <div class="bg-sky-50 rounded-2xl p-4 border border-sky-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-sky-600 mb-1">Izin/Sakit</p>
                <p class="text-2xl font-black text-sky-700"><?= $stats['izin'] + $stats['sakit'] ?></p>
            </div>
            <i data-lucide="file-text" class="w-7 h-7 text-sky-400"></i>
        </div>
        <div class="bg-red-50 rounded-2xl p-4 border border-red-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-red-600 mb-1">Alpa</p>
                <p class="text-2xl font-black text-red-700"><?= $stats['alpa'] ?></p>
            </div>
            <i data-lucide="x-circle" class="w-7 h-7 text-red-400"></i>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <th class="px-5 py-4 font-semibold">Tanggal</th>
                        <th class="px-5 py-4 font-semibold text-center w-28">Status</th>
                        <th class="px-5 py-4 font-semibold text-center">Masuk</th>
                        <th class="px-5 py-4 font-semibold text-center">Pulang</th>
                        <th class="px-5 py-4 font-semibold">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($riwayat)): ?>
                        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">Tidak ada riwayat kehadiran di bulan ini.</td></tr>
                    <?php else: ?>
                        <?php 
                        foreach ($riwayat as $row): 
                            $status = $row['status'];
                            $bg = 'bg-slate-100 text-slate-600';
                            if ($status === 'hadir') $bg = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                            if ($status === 'terlambat') $bg = 'bg-amber-100 text-amber-700 border-amber-200';
                            if ($status === 'izin') $bg = 'bg-sky-100 text-sky-700 border-sky-200';
                            if ($status === 'sakit') $bg = 'bg-purple-100 text-purple-700 border-purple-200';
                            if ($status === 'alpa') $bg = 'bg-red-100 text-red-700 border-red-200';
                        ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-800"><?= date('d M Y', strtotime($row['tanggal'])) ?></p>
                                    <p class="text-[10px] text-slate-400"><?= date('l', strtotime($row['tanggal'])) ?></p>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $bg ?> border">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <?php if ($row['jam_masuk']): ?>
                                        <p class="font-semibold text-slate-700"><?= substr($row['jam_masuk'],0,5) ?></p>
                                        <?php if ($row['jarak_masuk_meter']): ?>
                                            <p class="text-[9px] text-slate-400">Jarak: <?= (float)$row['jarak_masuk_meter'] ?>m</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-300">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <?php if ($row['jam_pulang']): ?>
                                        <p class="font-semibold text-slate-700"><?= substr($row['jam_pulang'],0,5) ?></p>
                                        <?php if ($row['jarak_pulang_meter']): ?>
                                            <p class="text-[9px] text-slate-400">Jarak: <?= (float)$row['jarak_pulang_meter'] ?>m</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-300">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-600 max-w-[200px] truncate" title="<?= e($row['keterangan']) ?>">
                                    <?= e($row['keterangan'] ?: '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
