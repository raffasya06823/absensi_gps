<?php
// SIMAS-GPS — Siswa: Ajukan Izin / Sakit
$page_title  = 'Ajukan Izin / Sakit';
$active_menu = 'ajukan_izin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('siswa');

$pdo = getDB();
$siswa_id = (int)$_SESSION['siswa_id'];

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $tanggal = $_POST['tanggal'] ?? '';
    $jenis   = $_POST['jenis'] ?? '';
    $alasan  = trim($_POST['alasan'] ?? '');
    
    if (empty($tanggal) || empty($jenis) || empty($alasan)) {
        set_flash('error', 'Semua kolom form wajib diisi.');
        redirect(base_url() . '/siswa/ajukan_izin.php');
    }

    // Cek apakah sudah ada pengajuan di tanggal yang sama
    $stmtCek = $pdo->prepare("SELECT id FROM pengajuan_izin WHERE siswa_id = ? AND tanggal = ?");
    $stmtCek->execute([$siswa_id, $tanggal]);
    if ($stmtCek->fetch()) {
        set_flash('error', 'Anda sudah mengajukan izin/sakit pada tanggal tersebut.');
        redirect(base_url() . '/siswa/ajukan_izin.php');
    }

    // Proses Upload Bukti (opsional atau wajib?) Kita asumsikan opsional (tergantung kebijakan, tapi di UI wajib upload bukti surat)
    $file_path = null;
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['bukti']['tmp_name'];
        $name     = $_FILES['bukti']['name'];
        $size     = $_FILES['bukti']['size'];
        $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        // Validasi ekstensi
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowed_ext)) {
            set_flash('error', 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.');
            redirect(base_url() . '/siswa/ajukan_izin.php');
        }

        // Validasi Mime Type Murni dengan finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($mime, $allowed_mimes)) {
            set_flash('error', 'Isi file tidak sesuai dengan ekstensinya (Terdeteksi keamanan).');
            redirect(base_url() . '/siswa/ajukan_izin.php');
        }

        // Validasi ukuran (Max 2MB)
        if ($size > 2 * 1024 * 1024) {
            set_flash('error', 'Ukuran file terlalu besar. Maksimal 2MB.');
            redirect(base_url() . '/siswa/ajukan_izin.php');
        }

        // Simpan file
        $upload_dir = __DIR__ . '/../uploads/bukti_izin/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
        
        $new_filename = uniqid('bukti_') . '_' . time() . '.' . $ext;
        if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
            $file_path = 'uploads/bukti_izin/' . $new_filename;
        } else {
            set_flash('error', 'Gagal mengunggah file bukti.');
            redirect(base_url() . '/siswa/ajukan_izin.php');
        }
    } else {
        set_flash('error', 'File bukti surat wajib diunggah.');
        redirect(base_url() . '/siswa/ajukan_izin.php');
    }

    try {
        $stmtIns = $pdo->prepare("INSERT INTO pengajuan_izin (siswa_id, tanggal, jenis, alasan, file_bukti, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmtIns->execute([$siswa_id, $tanggal, $jenis, $alasan, $file_path]);
        set_flash('success', 'Pengajuan berhasil dikirim dan menunggu persetujuan wali kelas.');
    } catch (PDOException $e) {
        set_flash('error', 'Terjadi kesalahan saat menyimpan pengajuan.');
    }
    
    redirect(base_url() . '/siswa/ajukan_izin.php');
}

// Ambil riwayat pengajuan siswa
$stmt = $pdo->prepare("SELECT * FROM pengajuan_izin WHERE siswa_id = ? ORDER BY tanggal DESC, created_at DESC");
$stmt->execute([$siswa_id]);
$riwayat = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>

<div class="max-w-4xl mx-auto space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Ajukan Izin / Sakit</h2>
            <p class="text-sm text-slate-500 mt-1">Sertakan surat bukti yang valid untuk diverifikasi guru.</p>
        </div>
    </div>

    <?php if ($flash_success): ?>
        <div class="bg-emerald-50 text-emerald-600 px-4 py-3 rounded-xl border border-emerald-200 text-sm flex gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="bg-red-50 text-red-600 px-4 py-3 rounded-xl border border-red-200 text-sm flex gap-2"><i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_error) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Form Pengajuan -->
        <div class="lg:col-span-1">
            <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4 sticky top-24">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Absen</label>
                    <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Keterangan</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="jenis" value="sakit" class="peer sr-only" required>
                            <div class="text-center py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:text-purple-700 transition-all">Sakit</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="jenis" value="izin" class="peer sr-only">
                            <div class="text-center py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 peer-checked:bg-sky-50 peer-checked:border-sky-500 peer-checked:text-sky-700 transition-all">Izin</div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Detail</label>
                    <textarea name="alasan" required rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500" placeholder="Tulis alasan dengan jelas..."></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Upload Bukti (Surat Dokter/Ortu)</label>
                    <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-[10px] text-slate-400 mt-1">Maks. 2MB. Format: JPG, PNG, PDF.</p>
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl transition-colors shadow-sm mt-2 text-sm">
                    Kirim Pengajuan
                </button>
            </form>
        </div>

        <!-- Riwayat Pengajuan -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-800">Riwayat Pengajuan Anda</h3>
                </div>
                
                <div class="divide-y divide-slate-100">
                    <?php if (empty($riwayat)): ?>
                    <div class="p-8 text-center text-slate-400">
                        <i data-lucide="file-text" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
                        <p class="mt-3 text-sm">Belum ada riwayat pengajuan izin/sakit.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach($riwayat as $r): 
                            // Tentukan warna badge
                            $bgStatus = 'bg-amber-100 text-amber-700 border-amber-200';
                            $lblStatus = 'Menunggu';
                            if ($r['status'] === 'approved') {
                                $bgStatus = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                                $lblStatus = 'Disetujui';
                            } elseif ($r['status'] === 'rejected') {
                                $bgStatus = 'bg-red-100 text-red-700 border-red-200';
                                $lblStatus = 'Ditolak';
                            }
                            
                            $isPdf = str_ends_with(strtolower($r['file_bukti']), 'pdf');
                        ?>
                        <div class="p-5 hover:bg-slate-50 transition-colors flex flex-col sm:flex-row gap-4">
                            <!-- Thumbnail Bukti -->
                            <div class="w-20 h-20 flex-shrink-0 rounded-xl border border-slate-200 bg-slate-100 overflow-hidden flex items-center justify-center">
                                <?php if ($r['file_bukti']): ?>
                                    <?php if ($isPdf): ?>
                                        <a href="<?= base_url() . '/' . $r['file_bukti'] ?>" target="_blank" class="w-full h-full flex flex-col items-center justify-center text-red-500 hover:bg-slate-200 transition-colors">
                                            <i data-lucide="file-text" class="w-7 h-7"></i><span class="text-[10px] font-bold mt-1">PDF</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= base_url() . '/' . $r['file_bukti'] ?>" target="_blank">
                                            <img src="<?= base_url() . '/' . $r['file_bukti'] ?>" class="w-full h-full object-cover object-center" alt="Bukti">
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <i data-lucide="ban" class="w-7 h-7 text-slate-300"></i>
                                <?php endif; ?>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-1">
                                    <p class="font-bold text-slate-800 flex items-center gap-2">
                                        <span class="capitalize text-sm"><?= $r['jenis'] ?></span>
                                        <span class="text-xs font-semibold px-2 py-0.5 border rounded-full <?= $bgStatus ?>"><?= $lblStatus ?></span>
                                    </p>
                                    <p class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded-md border border-slate-200 flex items-center gap-1">
                                        <i data-lucide="calendar" class="w-3 h-3"></i> <?= date('d M Y', strtotime($r['tanggal'])) ?>
                                    </p>
                                </div>
                                <p class="text-sm text-slate-600 mt-1.5 line-clamp-2" title="<?= e($r['alasan']) ?>">"<?= e($r['alasan']) ?>"</p>
                                <p class="text-[10px] text-slate-400 mt-2">Diajukan: <?= date('d M Y, H:i', strtotime($r['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
