<?php
// SIMAS-GPS — Admin Kelola Kelas
$page_title  = 'Kelola Kelas';
$active_menu = 'kelola_kelas';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('admin');

$pdo = getDB();

// Handle Form Submission (Create / Update / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $action = $_POST['action'];

    if ($action === 'save') {
        $id            = $_POST['id'] ?? '';
        $nama_kelas    = trim($_POST['nama_kelas'] ?? '');
        $wali_kelas_id = !empty($_POST['wali_kelas_id']) ? $_POST['wali_kelas_id'] : null;
        $mode_absen    = $_POST['mode_absen'] ?? 'individu';
        $tahun_ajaran  = trim($_POST['tahun_ajaran'] ?? '');

        if (empty($nama_kelas) || empty($tahun_ajaran)) {
            set_flash('error', 'Nama Kelas dan Tahun Ajaran wajib diisi.');
        } else {
            try {
                if (empty($id)) {
                    $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, wali_kelas_id, mode_absen, tahun_ajaran) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nama_kelas, $wali_kelas_id, $mode_absen, $tahun_ajaran]);
                    set_flash('success', 'Data kelas berhasil ditambahkan.');
                } else {
                    $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, wali_kelas_id = ?, mode_absen = ?, tahun_ajaran = ? WHERE id = ?");
                    $stmt->execute([$nama_kelas, $wali_kelas_id, $mode_absen, $tahun_ajaran, $id]);
                    set_flash('success', 'Data kelas berhasil diperbarui.');
                }
            } catch (PDOException $e) {
                set_flash('error', 'Terjadi kesalahan saat menyimpan kelas.');
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('success', 'Data kelas berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('error', 'Gagal menghapus kelas (mungkin masih ada siswa terkait).');
        }
    }
    redirect(base_url() . '/admin/kelola_kelas.php');
}

// Search and Pagination
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (k.nama_kelas LIKE ? OR k.tahun_ajaran LIKE ? OR u.nama LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM kelas k LEFT JOIN users u ON k.wali_kelas_id = u.id WHERE $where");
$stmtTotal->execute($params);
$totalRows = $stmtTotal->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("SELECT k.*, u.nama as wali_nama 
                       FROM kelas k 
                       LEFT JOIN users u ON k.wali_kelas_id = u.id 
                       WHERE $where ORDER BY k.tahun_ajaran DESC, k.nama_kelas ASC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$kelas = $stmt->fetchAll();

// Get list of gurus for dropdown
$gurus = $pdo->query("SELECT id, nama FROM users WHERE role = 'guru' AND status = 'aktif' ORDER BY nama ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Kelola Kelas</h2>
            <p class="text-sm text-slate-500 mt-1">Manajemen data kelas dan mode absensi.</p>
        </div>
        <button onclick="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah Kelas
        </button>
    </div>

    <?php if ($flash_success): ?>
        <div class="bg-emerald-50 text-emerald-600 px-4 py-3 rounded-xl border border-emerald-200 text-sm flex gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="bg-red-50 text-red-600 px-4 py-3 rounded-xl border border-red-200 text-sm flex gap-2"><i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_error) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50">
            <form method="GET" class="relative w-full sm:w-72">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama kelas/wali..." class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <th class="px-6 py-3 font-semibold w-12 text-center">No</th>
                        <th class="px-6 py-3 font-semibold">Nama Kelas</th>
                        <th class="px-6 py-3 font-semibold">Tahun Ajaran</th>
                        <th class="px-6 py-3 font-semibold">Wali Kelas</th>
                        <th class="px-6 py-3 font-semibold text-center">Mode Absen</th>
                        <th class="px-6 py-3 font-semibold text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($kelas)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400">Data tidak ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($kelas as $index => $k): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-center text-slate-500"><?= $offset + $index + 1 ?></td>
                                <td class="px-6 py-4 font-semibold text-slate-800"><?= e($k['nama_kelas']) ?></td>
                                <td class="px-6 py-4 text-slate-600"><?= e($k['tahun_ajaran']) ?></td>
                                <td class="px-6 py-4 text-slate-600"><?= e($k['wali_nama'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($k['mode_absen'] === 'device'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-violet-100 text-violet-700 inline-flex items-center gap-1"><i data-lucide="tablet" class="w-3 h-3"></i> Device Kelas</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 inline-flex items-center gap-1"><i data-lucide="user" class="w-3 h-3"></i> Individu</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="editKelas(<?= htmlspecialchars(json_encode($k)) ?>)" class="text-indigo-600 hover:text-indigo-900 mr-2 inline-flex items-center" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Yakin menghapus kelas ini?')">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 inline-flex items-center" title="Hapus"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-slate-200 flex justify-between items-center">
            <span class="text-sm text-slate-500">Halaman <?= $page ?> dari <?= $totalPages ?></span>
            <div class="flex gap-2">
                <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>" class="px-3 py-1 border rounded text-sm">Sebelumnnya</a><?php endif; ?>
                <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>" class="px-3 py-1 border rounded text-sm">Selanjutnya</a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Form -->
<div id="modalForm" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 opacity-0 transition-opacity">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 transform scale-95 transition-transform overflow-hidden" id="modalCard">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800" id="modalTitle">Tambah Kelas</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="inputId">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Kelas</label>
                    <input type="text" name="nama_kelas" id="inputNamaKelas" required placeholder="Contoh: VII A" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tahun Ajaran</label>
                    <input type="text" name="tahun_ajaran" id="inputTahun" required placeholder="2025/2026" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Wali Kelas</label>
                <select name="wali_kelas_id" id="inputWali" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                    <option value="">-- Tanpa Wali Kelas --</option>
                    <?php foreach($gurus as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= e($g['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Mode Absen</label>
                <select name="mode_absen" id="inputMode" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                    <option value="individu">Individu (Siswa login masing-masing)</option>
                    <option value="device">Device (Guru buka perangkat untuk kelas)</option>
                </select>
                <p class="text-xs text-slate-500 mt-1">Jika Device, siswa tidak butuh password untuk absen di kelas.</p>
            </div>

            <div class="pt-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Kelas';
    document.getElementById('inputId').value = '';
    document.getElementById('inputNamaKelas').value = '';
    document.getElementById('inputTahun').value = '<?= date('Y').'/'.(date('Y')+1) ?>';
    document.getElementById('inputWali').value = '';
    document.getElementById('inputMode').value = 'individu';
    toggleModal(true);
}

function editKelas(data) {
    document.getElementById('modalTitle').textContent = 'Edit Kelas';
    document.getElementById('inputId').value = data.id;
    document.getElementById('inputNamaKelas').value = data.nama_kelas;
    document.getElementById('inputTahun').value = data.tahun_ajaran;
    document.getElementById('inputWali').value = data.wali_kelas_id || '';
    document.getElementById('inputMode').value = data.mode_absen;
    toggleModal(true);
}

function closeModal() { toggleModal(false); }

function toggleModal(show) {
    const modal = document.getElementById('modalForm');
    const card = document.getElementById('modalCard');
    if(show) {
        modal.classList.remove('hidden'); modal.classList.add('flex');
        setTimeout(() => { modal.classList.remove('opacity-0'); card.classList.remove('scale-95'); }, 10);
    } else {
        modal.classList.add('opacity-0'); card.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 300);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
