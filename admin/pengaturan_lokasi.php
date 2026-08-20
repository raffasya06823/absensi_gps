<?php
// SIMAS-GPS — Admin Pengaturan Lokasi Sekolah
$page_title  = 'Pengaturan Lokasi & Waktu';
$active_menu = 'pengaturan_lokasi';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
cek_role('admin');

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $lat     = (float)$_POST['latitude'];
    $lon     = (float)$_POST['longitude'];
    $radius  = (int)$_POST['radius_meter'];
    $j_masuk_m = $_POST['jam_masuk_mulai'];
    $j_masuk_s = $_POST['jam_masuk_selesai'];
    $j_pulang_m = $_POST['jam_pulang_mulai'];
    $j_pulang_s = $_POST['jam_pulang_selesai'];

    try {
        $stmt = $pdo->prepare("UPDATE pengaturan_sekolah SET latitude=?, longitude=?, radius_meter=?, jam_masuk_mulai=?, jam_masuk_selesai=?, jam_pulang_mulai=?, jam_pulang_selesai=? WHERE id=1");
        $stmt->execute([$lat, $lon, $radius, $j_masuk_m, $j_masuk_s, $j_pulang_m, $j_pulang_s]);
        set_flash('success', 'Pengaturan lokasi & waktu berhasil disimpan.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menyimpan pengaturan.');
    }
    redirect(base_url() . '/admin/pengaturan_lokasi.php');
}

$stmt = $pdo->query("SELECT * FROM pengaturan_sekolah WHERE id=1");
$cfg = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Pengaturan Lokasi & Waktu Absen</h2>
        <p class="text-sm text-slate-500 mt-1">Atur titik koordinat sekolah dan batas jam absensi.</p>
    </div>

    <?php if ($flash_success): ?>
        <div class="bg-emerald-50 text-emerald-600 px-4 py-3 rounded-xl border border-emerald-200 text-sm flex gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="bg-red-50 text-red-600 px-4 py-3 rounded-xl border border-red-200 text-sm flex gap-2"><i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5"></i> <?= e($flash_error) ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        
        <div class="p-6 space-y-8">
            <!-- Lokasi GPS -->
            <div>
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2 mb-4 flex items-center gap-2"><i data-lucide="map-pin" class="w-5 h-5 text-indigo-500"></i> Koordinat Sekolah & Radius</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Latitude</label>
                        <input type="text" name="latitude" id="lat" value="<?= e($cfg['latitude']) ?>" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Longitude</label>
                        <input type="text" name="longitude" id="lon" value="<?= e($cfg['longitude']) ?>" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                    </div>
                    <div class="md:col-span-2 flex flex-col sm:flex-row gap-4 items-end">
                        <div class="w-full sm:w-1/2">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Radius Toleransi (Meter)</label>
                            <input type="number" name="radius_meter" value="<?= e($cfg['radius_meter']) ?>" required min="10" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                            <p class="text-xs text-slate-500 mt-1">Batas maksimal jarak siswa dari koordinat sekolah.</p>
                        </div>
                        <div class="w-full sm:w-1/2">
                            <button type="button" onclick="getLocation()" id="btnGetLoc" class="w-full bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg text-sm font-semibold shadow-sm transition-colors flex justify-center items-center gap-2">
                                <i data-lucide="crosshair" class="w-4 h-4"></i> Ambil Lokasi Saat Ini
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jam Absen -->
            <div>
                <h3 class="text-lg font-bold text-slate-800 border-b pb-2 mb-4 flex items-center gap-2"><i data-lucide="clock" class="w-5 h-5 text-indigo-500"></i> Jadwal Absensi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Jam Masuk -->
                    <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                        <p class="font-semibold text-emerald-800 mb-3 text-sm flex items-center gap-2"><i data-lucide="log-in" class="w-4 h-4"></i> Absen Masuk</p>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Mulai Jam</label>
                                <input type="time" name="jam_masuk_mulai" value="<?= substr($cfg['jam_masuk_mulai'],0,5) ?>" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Toleransi / Selesai Jam</label>
                                <input type="time" name="jam_masuk_selesai" value="<?= substr($cfg['jam_masuk_selesai'],0,5) ?>" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                                <p class="text-[10px] text-slate-500 mt-1">Lewat dari jam ini, status siswa akan tercatat Terlambat.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Jam Pulang -->
                    <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                        <p class="font-semibold text-blue-800 mb-3 text-sm flex items-center gap-2"><i data-lucide="log-out" class="w-4 h-4"></i> Absen Pulang</p>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Mulai Jam</label>
                                <input type="time" name="jam_pulang_mulai" value="<?= substr($cfg['jam_pulang_mulai'],0,5) ?>" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Selesai Jam</label>
                                <input type="time" name="jam_pulang_selesai" value="<?= substr($cfg['jam_pulang_selesai'],0,5) ?>" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-colors">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<script>
function getLocation() {
    const btn = document.getElementById('btnGetLoc');
    
    if (!navigator.geolocation) {
        alert("Browser Anda tidak mendukung Geolocation.");
        return;
    }

    btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Membaca GPS...';
    lucide.createIcons();
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('lat').value = position.coords.latitude.toFixed(8);
            document.getElementById('lon').value = position.coords.longitude.toFixed(8);
            btn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i> Berhasil Diambil';
            lucide.createIcons();
            setTimeout(() => {
                btn.innerHTML = '<i data-lucide="crosshair" class="w-4 h-4"></i> Ambil Lokasi Saat Ini';
                lucide.createIcons();
                btn.disabled = false;
            }, 2000);
        },
        function(error) {
            let msg = '';
            switch(error.code) {
                case error.PERMISSION_DENIED: msg = "Izin lokasi ditolak oleh pengguna."; break;
                case error.POSITION_UNAVAILABLE: msg = "Informasi lokasi tidak tersedia (Edge tidak dapat membaca Wi-Fi/IP Anda)."; break;
                case error.TIMEOUT: msg = "Waktu permintaan lokasi habis (Timeout)."; break;
                default: msg = "Terjadi kesalahan tidak diketahui."; break;
            }
            alert("Gagal: " + msg + "\nDetail Teknis: " + error.message);
            btn.innerHTML = '<i data-lucide="crosshair" class="w-4 h-4"></i> Ambil Lokasi Saat Ini';
            lucide.createIcons();
            btn.disabled = false;
        },
        { enableHighAccuracy: false, timeout: 15000, maximumAge: 0 }
    );
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
