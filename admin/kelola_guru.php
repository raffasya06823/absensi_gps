<?php
// SIMAS-GPS — Admin Kelola Guru
$page_title  = 'Kelola Guru';
$active_menu = 'kelola_guru';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('admin');

$pdo = getDB();

// Handle Form Submission (Create / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $action = $_POST['action'];

    if ($action === 'save') {
        $id       = $_POST['id'] ?? '';
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $no_hp    = trim($_POST['no_hp'] ?? '');
        $status   = $_POST['status'] ?? 'aktif';
        $password = $_POST['password'] ?? '';

        if (empty($nama) || empty($username)) {
            set_flash('error', 'Nama dan Username wajib diisi.');
        } else {
            try {
                if (empty($id)) {
                    // Create
                    if (empty($password)) $password = 'guru123'; // Default password
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role, email, no_hp, status) VALUES (?, ?, ?, 'guru', ?, ?, ?)");
                    $stmt->execute([$nama, $username, $hash, $email, $no_hp, $status]);
                    set_flash('success', 'Data guru berhasil ditambahkan.');
                } else {
                    // Update
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("UPDATE users SET nama = ?, username = ?, password = ?, email = ?, no_hp = ?, status = ? WHERE id = ? AND role = 'guru'");
                        $stmt->execute([$nama, $username, $hash, $email, $no_hp, $status, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET nama = ?, username = ?, email = ?, no_hp = ?, status = ? WHERE id = ? AND role = 'guru'");
                        $stmt->execute([$nama, $username, $email, $no_hp, $status, $id]);
                    }
                    set_flash('success', 'Data guru berhasil diperbarui.');
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    set_flash('error', 'Username sudah digunakan.');
                } else {
                    set_flash('error', 'Terjadi kesalahan sistem.');
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'guru'");
            $stmt->execute([$id]);
            set_flash('success', 'Data guru berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('error', 'Gagal menghapus data guru.');
        }
    }
    redirect(base_url() . '/admin/kelola_guru.php');
}

// Search and Pagination
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where = "role = 'guru'";
$params = [];
if ($search !== '') {
    $where .= " AND (nama LIKE ? OR username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
$stmtTotal->execute($params);
$totalRows = $stmtTotal->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("SELECT id, nama, username, email, no_hp, status FROM users WHERE $where ORDER BY nama ASC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$gurus = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>

<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Kelola Guru</h2>
            <p class="text-sm text-slate-500 mt-1">Manajemen data guru dan wali kelas.</p>
        </div>
        <button onclick="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah Guru
        </button>
    </div>

    <?php if ($flash_success): ?>
        <div class="bg-emerald-50 text-emerald-600 px-4 py-3 rounded-xl border border-emerald-200 text-sm flex gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="bg-red-50 text-red-600 px-4 py-3 rounded-xl border border-red-200 text-sm flex gap-2"><i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_error) ?></div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between gap-4">
            <form method="GET" class="relative w-full sm:w-72">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama/username..." class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <th class="px-6 py-3 font-semibold w-12 text-center">No</th>
                        <th class="px-6 py-3 font-semibold">Nama / Username</th>
                        <th class="px-6 py-3 font-semibold">Kontak</th>
                        <th class="px-6 py-3 font-semibold text-center">Status</th>
                        <th class="px-6 py-3 font-semibold text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($gurus)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">Data tidak ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($gurus as $index => $g): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-center text-slate-500"><?= $offset + $index + 1 ?></td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-800"><?= e($g['nama']) ?></p>
                                    <p class="text-xs text-slate-500">@<?= e($g['username']) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-slate-600"><?= e($g['email'] ?: '-') ?></p>
                                    <p class="text-xs text-slate-500"><?= e($g['no_hp'] ?: '-') ?></p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($g['status'] === 'aktif'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Aktif</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="editGuru(<?= htmlspecialchars(json_encode($g)) ?>)" class="text-indigo-600 hover:text-indigo-900 mr-2 inline-flex items-center" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus guru ini?')">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
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
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>" class="px-3 py-1 border rounded text-sm hover:bg-slate-50">Sebelumnnya</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>" class="px-3 py-1 border rounded text-sm hover:bg-slate-50">Selanjutnya</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Form -->
<div id="modalForm" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 opacity-0 transition-opacity">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 transform scale-95 transition-transform overflow-hidden" id="modalCard">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800" id="modalTitle">Tambah Guru</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="inputId">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama" id="inputNama" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Username</label>
                <input type="text" name="username" id="inputUsername" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" id="inputEmail" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">No HP</label>
                    <input type="text" name="no_hp" id="inputNoHp" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Status</label>
                    <select name="status" id="inputStatus" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" id="inputPassword" placeholder="Kosongkan jika tidak diubah" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-[10px] text-slate-500 mt-1" id="pwHint">Default: guru123 (jika baru)</p>
                </div>
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
    document.getElementById('modalTitle').textContent = 'Tambah Guru';
    document.getElementById('inputId').value = '';
    document.getElementById('inputNama').value = '';
    document.getElementById('inputUsername').value = '';
    document.getElementById('inputEmail').value = '';
    document.getElementById('inputNoHp').value = '';
    document.getElementById('inputStatus').value = 'aktif';
    document.getElementById('inputPassword').value = '';
    document.getElementById('pwHint').textContent = 'Default: guru123';
    
    const modal = document.getElementById('modalForm');
    const card = document.getElementById('modalCard');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95');
    }, 10);
}

function editGuru(data) {
    document.getElementById('modalTitle').textContent = 'Edit Guru';
    document.getElementById('inputId').value = data.id;
    document.getElementById('inputNama').value = data.nama;
    document.getElementById('inputUsername').value = data.username;
    document.getElementById('inputEmail').value = data.email || '';
    document.getElementById('inputNoHp').value = data.no_hp || '';
    document.getElementById('inputStatus').value = data.status;
    document.getElementById('inputPassword').value = '';
    document.getElementById('pwHint').textContent = 'Isi jika ingin ganti password';
    
    const modal = document.getElementById('modalForm');
    const card = document.getElementById('modalCard');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95');
    }, 10);
}

function closeModal() {
    const modal = document.getElementById('modalForm');
    const card = document.getElementById('modalCard');
    modal.classList.add('opacity-0');
    card.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 300);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
