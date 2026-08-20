<?php
// SIMAS-GPS — Admin Kelola Siswa
$page_title  = 'Kelola Siswa';
$active_menu = 'kelola_siswa';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('admin');

$pdo = getDB();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $action = $_POST['action'];

    if ($action === 'save') {
        $id            = $_POST['id'] ?? '';
        $user_id       = $_POST['user_id'] ?? '';
        $nama          = trim($_POST['nama'] ?? '');
        $username      = trim($_POST['username'] ?? '');
        $nis           = trim($_POST['nis'] ?? '');
        $kelas_id      = !empty($_POST['kelas_id']) ? $_POST['kelas_id'] : null;
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'L';
        $alamat        = trim($_POST['alamat'] ?? '');
        $status        = $_POST['status'] ?? 'aktif';
        $password      = $_POST['password'] ?? '';

        if (empty($nama) || empty($username) || empty($nis)) {
            set_flash('error', 'Nama, Username, dan NIS wajib diisi.');
        } else {
            try {
                $pdo->beginTransaction();
                
                if (empty($id)) {
                    // Create User
                    if (empty($password)) $password = 'siswa123'; // Default password
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmtU = $pdo->prepare("INSERT INTO users (nama, username, password, role, status) VALUES (?, ?, ?, 'siswa', ?)");
                    $stmtU->execute([$nama, $username, $hash, $status]);
                    $new_user_id = $pdo->lastInsertId();

                    // Create Siswa
                    $stmtS = $pdo->prepare("INSERT INTO siswa (user_id, nis, kelas_id, jenis_kelamin, alamat) VALUES (?, ?, ?, ?, ?)");
                    $stmtS->execute([$new_user_id, $nis, $kelas_id, $jenis_kelamin, $alamat]);
                    
                    set_flash('success', 'Data siswa berhasil ditambahkan.');
                } else {
                    // Update User
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmtU = $pdo->prepare("UPDATE users SET nama = ?, username = ?, password = ?, status = ? WHERE id = ?");
                        $stmtU->execute([$nama, $username, $hash, $status, $user_id]);
                    } else {
                        $stmtU = $pdo->prepare("UPDATE users SET nama = ?, username = ?, status = ? WHERE id = ?");
                        $stmtU->execute([$nama, $username, $status, $user_id]);
                    }

                    // Update Siswa
                    $stmtS = $pdo->prepare("UPDATE siswa SET nis = ?, kelas_id = ?, jenis_kelamin = ?, alamat = ? WHERE id = ?");
                    $stmtS->execute([$nis, $kelas_id, $jenis_kelamin, $alamat, $id]);

                    set_flash('success', 'Data siswa berhasil diperbarui.');
                }
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($e->getCode() == 23000) {
                    set_flash('error', 'Username atau NIS sudah digunakan.');
                } else {
                    set_flash('error', 'Terjadi kesalahan sistem.');
                }
            }
        }
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? '';
        try {
            // Karena foreign key ON DELETE CASCADE, menghapus user akan menghapus data siswa dan absensi
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'siswa'");
            $stmt->execute([$user_id]);
            set_flash('success', 'Data siswa berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('error', 'Gagal menghapus siswa.');
        }
    } elseif ($action === 'reset_device') {
        // ── RESET DEVICE TOKEN siswa (saat siswa ganti HP) ──
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id > 0) {
            $deleted = reset_device_token($user_id);
            if ($deleted) {
                set_flash('success', 'Perangkat siswa berhasil direset. Siswa dapat login dari HP baru.');
            } else {
                set_flash('error', 'Siswa ini belum memiliki perangkat terdaftar.');
            }
        }
    }
    redirect(base_url() . '/admin/kelola_siswa.php');
}

// Search and Pagination
$search = trim($_GET['q'] ?? '');
$filter_kelas = $_GET['kelas'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where = "u.role = 'siswa'";
$params = [];
if ($search !== '') {
    $where .= " AND (u.nama LIKE ? OR u.username LIKE ? OR s.nis LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filter_kelas !== '') {
    $where .= " AND s.kelas_id = ?";
    $params[] = $filter_kelas;
}

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM siswa s JOIN users u ON s.user_id = u.id WHERE $where");
$stmtTotal->execute($params);
$totalRows = $stmtTotal->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("SELECT s.id, s.user_id, s.nis, s.kelas_id, s.jenis_kelamin, s.alamat, u.nama, u.username, u.status, k.nama_kelas, 
                              uw.nama AS wali_kelas_nama,
                              dt.id AS device_id, dt.device_info, dt.registered_at AS device_registered_at, dt.last_seen_at
                       FROM siswa s 
                       JOIN users u ON s.user_id = u.id 
                       LEFT JOIN kelas k ON s.kelas_id = k.id 
                       LEFT JOIN users uw ON k.wali_kelas_id = uw.id
                       LEFT JOIN device_tokens dt ON dt.user_id = u.id
                       WHERE $where ORDER BY k.nama_kelas ASC, u.nama ASC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$siswa = $stmt->fetchAll();

$kelas_list = $pdo->query("SELECT k.id, k.nama_kelas, k.tahun_ajaran, u.nama AS wali_kelas_nama 
                          FROM kelas k 
                          LEFT JOIN users u ON k.wali_kelas_id = u.id 
                          ORDER BY k.tahun_ajaran DESC, k.nama_kelas ASC")->fetchAll();

// Ambil info kelas yang sedang dipilih untuk ditampilkan
$selected_kelas_info = null;
if ($filter_kelas !== '') {
    foreach($kelas_list as $k) {
        if ($k['id'] == $filter_kelas) {
            $selected_kelas_info = $k;
            break;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Kelola Siswa</h2>
            <p class="text-sm text-slate-500 mt-1">Manajemen data siswa.</p>
        </div>
        <button onclick="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah Siswa
        </button>
    </div>

    <?php if ($flash_success): ?>
        <div class="bg-emerald-50 text-emerald-600 px-4 py-3 rounded-xl border border-emerald-200 text-sm flex gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="bg-red-50 text-red-600 px-4 py-3 rounded-xl border border-red-200 text-sm flex gap-2"><i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_error) ?></div>
    <?php endif; ?>

    <?php if ($selected_kelas_info): ?>
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 flex items-center gap-3">
            <i data-lucide="users" class="w-5 h-5 text-indigo-600"></i>
            <div class="flex-1">
                <p class="text-sm font-semibold text-indigo-900"> Kelas: <?= e($selected_kelas_info['nama_kelas']) ?></p>
                <p class="text-xs text-indigo-700 mt-0.5">
                    Wali Kelas: <?= $selected_kelas_info['wali_kelas_nama'] ? e($selected_kelas_info['wali_kelas_nama']) : '<span class="text-indigo-400">Belum Ditentukan</span>' ?>
                </p>
            </div>
            <a href="?q=<?= urlencode($search) ?>" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Hapus Filter</a>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row gap-4">
            <form method="GET" class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto flex-1">
                <div class="relative w-full sm:w-64">
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama/NIS..." class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
                </div>
                <select name="kelas" onchange="this.form.submit()" class="border border-slate-300 rounded-lg text-sm px-4 py-2 focus:ring-indigo-500">
                    <option value="">Semua Kelas</option>
                    <?php foreach($kelas_list as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <th class="px-6 py-3 font-semibold">Siswa / NIS</th>
                        <th class="px-6 py-3 font-semibold">Kelas</th>
                        <th class="px-6 py-3 font-semibold">L/P</th>
                        <th class="px-6 py-3 font-semibold text-center">Status</th>
                        <th class="px-6 py-3 font-semibold text-center">Device HP</th>
                        <th class="px-6 py-3 font-semibold text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($siswa)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">Data tidak ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($siswa as $s): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-800"><?= e($s['nama']) ?></p>
                                    <p class="text-xs text-slate-500">NIS: <?= e($s['nis']) ?> | @<?= e($s['username']) ?></p>
                                </td>
                                <td class="px-6 py-4 text-slate-600"><?= e($s['nama_kelas'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-slate-600"><?= $s['jenis_kelamin'] ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($s['status'] === 'aktif'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">Aktif</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Kolom status device HP -->
                                <td class="px-6 py-4 text-center">
                                    <?php if (!empty($s['device_id'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-700"
                                              title="Terdaftar: <?= e(date('d/m/Y H:i', strtotime($s['device_registered_at']))) ?> | Terakhir aktif: <?= e(date('d/m/Y H:i', strtotime($s['last_seen_at']))) ?>">
                                            <i data-lucide="smartphone" class="w-3 h-3"></i> Terdaftar
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500">
                                            <i data-lucide="smartphone-nfc" class="w-3 h-3"></i> Belum
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="editSiswa(<?= htmlspecialchars(json_encode($s)) ?>)" class="text-indigo-600 hover:text-indigo-900 mr-1 inline-flex items-center" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                    <?php if (!empty($s['device_id'])): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Reset perangkat <?= e(addslashes($s['nama'])) ?>?\nSiswa ini akan bisa login dari HP baru.')">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="reset_device">
                                        <input type="hidden" name="user_id" value="<?= $s['user_id'] ?>">
                                        <button type="submit" class="text-amber-500 hover:text-amber-700 inline-flex items-center mr-1" title="Reset Device HP">
                                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Yakin menghapus siswa ini? Semua data absennya juga akan terhapus.')">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $s['user_id'] ?>">
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
                <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&kelas=<?= urlencode($filter_kelas) ?>" class="px-3 py-1 border rounded text-sm">Sebelumnnya</a><?php endif; ?>
                <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&kelas=<?= urlencode($filter_kelas) ?>" class="px-3 py-1 border rounded text-sm">Selanjutnya</a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Form -->
<div id="modalForm" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 opacity-0 transition-opacity p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-auto transform scale-95 transition-transform" id="modalCard">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl">
            <h3 class="font-bold text-lg text-slate-800" id="modalTitle">Tambah Siswa</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="inputId">
            <input type="hidden" name="user_id" id="inputUserId">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" id="inputNama" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">NIS</label>
                    <input type="text" name="nis" id="inputNis" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Username</label>
                    <input type="text" name="username" id="inputUsername" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Kelas</label>
                    <select name="kelas_id" id="inputKelas" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach($kelas_list as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= e($k['nama_kelas']) ?> (<?= e($k['tahun_ajaran']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="inputJK" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Status</label>
                    <select name="status" id="inputStatus" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" id="inputPassword" placeholder="Kosongkan jika tidak diubah" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                    <p class="text-[10px] text-slate-500 mt-1" id="pwHint">Default: siswa123</p>
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
    document.getElementById('modalTitle').textContent = 'Tambah Siswa';
    document.getElementById('inputId').value = '';
    document.getElementById('inputUserId').value = '';
    document.getElementById('inputNama').value = '';
    document.getElementById('inputUsername').value = '';
    document.getElementById('inputNis').value = '';
    document.getElementById('inputKelas').value = '';
    document.getElementById('inputJK').value = 'L';
    document.getElementById('inputStatus').value = 'aktif';
    document.getElementById('inputPassword').value = '';
    document.getElementById('pwHint').textContent = 'Default: siswa123';
    toggleModal(true);
}

function editSiswa(data) {
    document.getElementById('modalTitle').textContent = 'Edit Siswa';
    document.getElementById('inputId').value = data.id;
    document.getElementById('inputUserId').value = data.user_id;
    document.getElementById('inputNama').value = data.nama;
    document.getElementById('inputUsername').value = data.username;
    document.getElementById('inputNis').value = data.nis;
    document.getElementById('inputKelas').value = data.kelas_id || '';
    document.getElementById('inputJK').value = data.jenis_kelamin;
    document.getElementById('inputStatus').value = data.status;
    document.getElementById('inputPassword').value = '';
    document.getElementById('pwHint').textContent = 'Isi jika ingin ganti password';
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
