<?php
// SIMAS-GPS — Guru: Approve Izin Siswa
$page_title  = 'Approve Izin Siswa';
$active_menu = 'approve_izin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('guru');

$pdo = getDB();
$guru_id = (int)$_SESSION['user_id'];

// Proses Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $action = $_POST['action'];
    $pengajuan_id = (int)$_POST['pengajuan_id'];

    if ($action === 'approve' || $action === 'reject') {
        $status_baru = $action === 'approve' ? 'approved' : 'rejected';
        
        try {
            $pdo->beginTransaction();

            // Cek data pengajuan dan pastikan miliki kelas yang diajar oleh guru ini
            $stmtCek = $pdo->prepare("
                SELECT p.*, s.kelas_id 
                FROM pengajuan_izin p 
                JOIN siswa s ON p.siswa_id = s.id 
                JOIN kelas k ON s.kelas_id = k.id 
                WHERE p.id = ? AND k.wali_kelas_id = ?
            ");
            $stmtCek->execute([$pengajuan_id, $guru_id]);
            $pengajuan = $stmtCek->fetch();

            if ($pengajuan) {
                // Update status pengajuan
                $now = date('Y-m-d H:i:s');
                $stmtUpd = $pdo->prepare("UPDATE pengajuan_izin SET status = ?, diproses_oleh = ?, tanggal_proses = ? WHERE id = ?");
                $stmtUpd->execute([$status_baru, $guru_id, $now, $pengajuan_id]);

                // Jika approve, otomatis update tabel absensi
                if ($action === 'approve') {
                    $tanggal = $pengajuan['tanggal'];
                    $siswa_id = $pengajuan['siswa_id'];
                    $jenis_absen = $pengajuan['jenis']; // 'izin' atau 'sakit'

                    $stmtAbs = $pdo->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ?");
                    $stmtAbs->execute([$siswa_id, $tanggal]);
                    $absen_ada = $stmtAbs->fetch();

                    if ($absen_ada) {
                        $stmtU = $pdo->prepare("UPDATE absensi SET status = ?, keterangan = ?, input_manual_oleh = ? WHERE id = ?");
                        $stmtU->execute([$jenis_absen, $pengajuan['alasan'], $guru_id, $absen_ada['id']]);
                    } else {
                        $stmtI = $pdo->prepare("INSERT INTO absensi (siswa_id, tanggal, status, keterangan, input_manual_oleh) VALUES (?, ?, ?, ?, ?)");
                        $stmtI->execute([$siswa_id, $tanggal, $jenis_absen, $pengajuan['alasan'], $guru_id]);
                    }
                }

                set_flash('success', "Pengajuan berhasil di-" . ($action === 'approve' ? 'setujui' : 'tolak') . ".");
            } else {
                set_flash('error', 'Pengajuan tidak valid atau bukan dari siswa wali Anda.');
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
    redirect(base_url() . '/guru/approve_izin.php');
}

// Ambil daftar pengajuan (hanya siswa dari kelas di mana guru ini adalah walinya)
// Default tampilkan yang 'pending' di atas, baru yang sudah diproses
$stmt = $pdo->prepare("
    SELECT p.*, u.nama, s.nis, k.nama_kelas 
    FROM pengajuan_izin p 
    JOIN siswa s ON p.siswa_id = s.id 
    JOIN users u ON s.user_id = u.id 
    JOIN kelas k ON s.kelas_id = k.id 
    WHERE k.wali_kelas_id = ? 
    ORDER BY CASE WHEN p.status = 'pending' THEN 0 ELSE 1 END, p.tanggal DESC, p.created_at DESC
");
$stmt->execute([$guru_id]);
$pengajuan_list = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>

<div class="max-w-5xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Verifikasi Izin & Sakit</h2>
        <p class="text-sm text-slate-500 mt-1">Daftar pengajuan izin/sakit siswa di kelas perwalian Anda.</p>
    </div>

    <?php if ($flash_success): ?>
        <div class="bg-emerald-50 text-emerald-600 px-4 py-3 rounded-xl border border-emerald-200 text-sm flex gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="bg-red-50 text-red-600 px-4 py-3 rounded-xl border border-red-200 text-sm flex gap-2"><i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_error) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <th class="px-6 py-4 font-semibold w-24 text-center">Bukti</th>
                        <th class="px-6 py-4 font-semibold">Data Siswa</th>
                        <th class="px-6 py-4 font-semibold">Detail Pengajuan</th>
                        <th class="px-6 py-4 font-semibold text-center w-32">Status</th>
                        <th class="px-6 py-4 font-semibold text-center w-40">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($pengajuan_list)): ?>
                        <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400">Belum ada data pengajuan izin/sakit.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pengajuan_list as $p): 
                            $isPdf = str_ends_with(strtolower($p['file_bukti'] ?? ''), 'pdf');
                        ?>
                            <tr class="hover:bg-slate-50 <?= $p['status'] === 'pending' ? 'bg-amber-50/20' : '' ?>">
                                <!-- Bukti -->
                                <td class="px-6 py-4 text-center align-top">
                                    <?php if ($p['file_bukti']): ?>
                                        <a href="<?= base_url() . '/' . $p['file_bukti'] ?>" target="_blank" class="block w-16 h-16 rounded-xl border border-slate-200 bg-white mx-auto overflow-hidden hover:shadow-md transition-shadow flex items-center justify-center">
                                            <?php if ($isPdf): ?>
                                                <div class="w-full h-full flex flex-col items-center justify-center text-red-500 bg-slate-50">
                                                    <i data-lucide="file-text" class="w-6 h-6"></i><span class="text-[8px] font-bold mt-1">PDF</span>
                                                </div>
                                            <?php else: ?>
                                                <img src="<?= base_url() . '/' . $p['file_bukti'] ?>" class="w-full h-full object-cover" alt="Bukti">
                                            <?php endif; ?>
                                        </a>
                                        <p class="text-[9px] text-slate-400 mt-1">Klik untuk lihat</p>
                                    <?php else: ?>
                                        <i data-lucide="ban" class="w-6 h-6 mx-auto text-slate-300 mt-4"></i>
                                    <?php endif; ?>
                                </td>

                                <!-- Siswa -->
                                <td class="px-6 py-4 align-top">
                                    <p class="font-bold text-slate-800"><?= e($p['nama']) ?></p>
                                    <p class="text-xs text-slate-500 mt-1">NIS: <?= e($p['nis']) ?></p>
                                    <span class="inline-block mt-1.5 text-[10px] bg-slate-100 px-2 py-0.5 rounded border border-slate-200 font-semibold"><?= e($p['nama_kelas']) ?></span>
                                </td>

                                <!-- Detail -->
                                <td class="px-6 py-4 align-top">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-slate-700 capitalize flex items-center gap-1">
                                            <?= $p['jenis'] === 'sakit' ? '<i data-lucide="thermometer" class="w-4 h-4 text-purple-500"></i> Sakit' : '<i data-lucide="file-text" class="w-4 h-4 text-sky-500"></i> Izin' ?>
                                        </span>
                                        <span class="text-xs text-slate-400 bg-white border px-2 py-0.5 rounded-full">Tgl: <?= date('d M Y', strtotime($p['tanggal'])) ?></span>
                                    </div>
                                    <p class="text-sm text-slate-600 line-clamp-3">"<?= e($p['alasan']) ?>"</p>
                                    <p class="text-[10px] text-slate-400 mt-2">Diajukan: <?= date('d M Y H:i', strtotime($p['created_at'])) ?></p>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 text-center align-middle">
                                    <?php if ($p['status'] === 'pending'): ?>
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">Menunggu</span>
                                    <?php elseif ($p['status'] === 'approved'): ?>
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">Disetujui</span>
                                    <?php else: ?>
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">Ditolak</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 text-center align-middle">
                                    <?php if ($p['status'] === 'pending'): ?>
                                        <div class="flex flex-col gap-2">
                                            <form method="POST" onsubmit="return confirm('Setujui pengajuan ini? (Akan otomatis mencatat absen <?= $p['jenis'] ?>)')">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="pengajuan_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold py-1.5 px-3 rounded shadow-sm transition-colors flex items-center justify-center gap-1.5"><i data-lucide="check" class="w-3.5 h-3.5"></i> Setujui</button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('Tolak pengajuan ini?')">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="pengajuan_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-1.5 px-3 rounded shadow-sm transition-colors flex items-center justify-center gap-1.5"><i data-lucide="x" class="w-3.5 h-3.5"></i> Tolak</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Sudah Diproses<br><?= date('d/m/Y H:i', strtotime($p['tanggal_proses'])) ?></span>
                                    <?php endif; ?>
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
