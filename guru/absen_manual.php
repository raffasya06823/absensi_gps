<?php
// SIMAS-GPS — Guru: Absen Manual
$page_title  = 'Absen Manual';
$active_menu = 'absen_manual';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('guru');

$pdo = getDB();
$guru_id = (int)$_SESSION['user_id'];

// Proses jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $siswa_id   = (int)$_POST['siswa_id'];
    $tanggal    = $_POST['tanggal'] ?? date('Y-m-d');
    $status     = $_POST['status'] ?? 'hadir';
    $jam        = $_POST['jam'] ?? date('H:i:s');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Validasi input
    if (empty($siswa_id) || empty($tanggal) || empty($status) || empty($keterangan)) {
        set_flash('error', 'Semua field wajib diisi, termasuk alasan manual.');
    } else {
        // Cek apakah siswa ini benar-benar ada di kelas yang diampu guru ini
        $cek = $pdo->prepare("
            SELECT s.id FROM siswa s
            JOIN kelas k ON s.kelas_id = k.id
            WHERE s.id = ? AND k.wali_kelas_id = ?
        ");
        $cek->execute([$siswa_id, $guru_id]);
        
        if ($cek->fetch()) {
            // Cek apakah sudah ada absen di tanggal tsb
            $stmtCek = $pdo->prepare("SELECT id, jam_masuk, jam_pulang FROM absensi WHERE siswa_id = ? AND tanggal = ?");
            $stmtCek->execute([$siswa_id, $tanggal]);
            $existing = $stmtCek->fetch();

            try {
                if ($existing) {
                    // Update absen yang ada (misal jam_masuk kosong atau mau ditimpa manual)
                    $stmtUpd = $pdo->prepare("
                        UPDATE absensi 
                        SET status = ?, jam_masuk = IFNULL(jam_masuk, ?), keterangan = ?, input_manual_oleh = ?
                        WHERE id = ?
                    ");
                    $stmtUpd->execute([$status, $jam, "Manual: " . $keterangan, $guru_id, $existing['id']]);
                } else {
                    // Insert absen baru
                    $stmtIns = $pdo->prepare("
                        INSERT INTO absensi (siswa_id, tanggal, jam_masuk, status, keterangan, input_manual_oleh)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmtIns->execute([$siswa_id, $tanggal, $jam, $status, "Manual: " . $keterangan, $guru_id]);
                }
                set_flash('success', 'Absen manual berhasil disimpan.');
            } catch (Exception $e) {
                set_flash('error', 'Gagal menyimpan data: ' . $e->getMessage());
            }
        } else {
            set_flash('error', 'Siswa tidak ditemukan atau bukan dari kelas Anda.');
        }
    }
    // Redirect agar tidak re-submit form jika direfresh
    redirect(base_url() . '/guru/absen_manual.php');
}

// Ambil daftar siswa untuk dropdown
// Hanya tampilkan siswa dari kelas yang diampu oleh guru ini
$stmtSiswa = $pdo->prepare("
    SELECT s.id, u.nama, s.nis, k.nama_kelas 
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE k.wali_kelas_id = ? AND u.status = 'aktif'
    ORDER BY k.nama_kelas ASC, u.nama ASC
");
$stmtSiswa->execute([$guru_id]);
$siswa_list = $stmtSiswa->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Absen Manual</h2>
        <p class="text-sm text-slate-500 mt-1">Gunakan fitur ini hanya dalam keadaan darurat (contoh: HP siswa rusak, GPS bermasalah).</p>
    </div>

    <?php if ($msg = get_flash('success')): ?>
        <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 p-4 rounded-xl text-sm font-medium flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i> <?= e($msg) ?>
        </div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
        <div class="bg-red-50 text-red-700 border border-red-200 p-4 rounded-xl text-sm font-medium flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-4 h-4"></i> <?= e($msg) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($siswa_list)): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-700 p-6 rounded-2xl text-center shadow-sm">
            <i data-lucide="alert-triangle" class="w-8 h-8 mx-auto mb-2 text-amber-500"></i>
            <p class="font-bold">Tidak ada data siswa.</p>
            <p class="text-sm mt-1">Anda mungkin belum ditugaskan sebagai Wali Kelas pada kelas mana pun.</p>
        </div>
    <?php else: ?>
        <form method="POST" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-5">
            <?= csrf_field() ?>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Pilih Siswa <span class="text-red-500">*</span></label>
                <select name="siswa_id" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">-- Pilih Siswa --</option>
                    <?php 
                    $current_kelas = '';
                    foreach ($siswa_list as $s) {
                        if ($current_kelas !== $s['nama_kelas']) {
                            if ($current_kelas !== '') echo "</optgroup>";
                            echo "<optgroup label='Kelas " . e($s['nama_kelas']) . "'>";
                            $current_kelas = $s['nama_kelas'];
                        }
                        echo "<option value='{$s['id']}'>" . e($s['nama']) . " (" . e($s['nis']) . ")</option>";
                    }
                    echo "</optgroup>";
                    ?>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal Absen <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Jam Absen <span class="text-red-500">*</span></label>
                    <input type="time" name="jam" value="<?= date('H:i') ?>" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Status Kehadiran <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3 mt-2">
                    <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                        <input type="radio" name="status" value="hadir" checked class="w-4 h-4 text-indigo-600">
                        <span class="text-sm font-medium text-slate-700">Hadir Tepat Waktu</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                        <input type="radio" name="status" value="terlambat" class="w-4 h-4 text-amber-600">
                        <span class="text-sm font-medium text-slate-700">Terlambat</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Alasan Manual <span class="text-red-500">*</span></label>
                <textarea name="keterangan" rows="3" required placeholder="Contoh: HP siswa rusak, GPS bermasalah, cuaca buruk..." class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500 text-sm resize-none"></textarea>
                <p class="text-[11px] text-slate-500 mt-1">Alasan ini akan tercatat di sistem sebagai bukti pertanggungjawaban absen manual.</p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-colors flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan Absen
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
