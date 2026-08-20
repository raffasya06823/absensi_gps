<?php
/**
 * SIMAS-GPS — API Proses Absen
 * Menerima request AJAX dari geolocation.js
 */

// Set timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/haversine.php';

// Pastikan user adalah siswa (atau sedang dalam mode device)
if (empty($_SESSION['siswa_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Harap login sebagai siswa.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

// Ambil payload JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['lat']) || !isset($data['lng']) || !isset($data['jenis'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data koordinat tidak lengkap.']);
    exit;
}

$siswa_id   = (int)$_SESSION['siswa_id'];
$lat_siswa  = (float)$data['lat'];
$lng_siswa  = (float)$data['lng'];
$jenis      = $data['jenis']; // 'masuk' atau 'pulang'
$csrf_token = $data['csrf_token'] ?? '';

// Verifikasi CSRF (opsional via ajax, namun baik untuk keamanan)
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Sesi tidak valid (CSRF). Silakan muat ulang halaman.']);
    exit;
}

try {
    $pdo = getDB();

    // 1. Ambil pengaturan sekolah
    $stmt = $pdo->query("SELECT * FROM pengaturan_sekolah WHERE id=1");
    $cfg = $stmt->fetch();
    
    if (!$cfg) {
        echo json_encode(['status' => 'error', 'message' => 'Pengaturan sekolah belum diset admin.']);
        exit;
    }

    $lat_sekolah = (float)$cfg['latitude'];
    $lng_sekolah = (float)$cfg['longitude'];
    $radius_max  = (float)$cfg['radius_meter'];

    // 2. Hitung jarak
    $jarak = hitungJarak($lat_siswa, $lng_siswa, $lat_sekolah, $lng_sekolah);

    if ($jarak > $radius_max) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Anda berada di luar area sekolah.',
            'jarak' => round($jarak)
        ]);
        exit;
    }

    // 3. Tentukan status berdasarkan jam
    $sekarang = date('H:i:s');
    $tanggal  = date('Y-m-d');
    $status_kehadiran = 'hadir';

    if ($jenis === 'masuk') {
        // Cek apakah terlambat saat absen masuk
        // Format H:i:s bisa dibandingkan langsung sebagai string
        if ($sekarang > $cfg['jam_masuk_selesai']) {
            $status_kehadiran = 'terlambat';
        }
    } else if ($jenis === 'pulang') {
        // Validasi jam pulang - harus setelah jam_pulang_mulai
        if ($sekarang < $cfg['jam_pulang_mulai']) {
            echo json_encode(['status' => 'error', 'message' => 'Belum waktunya jam pulang. Mulai dari ' . substr($cfg['jam_pulang_mulai'], 0, 5)]);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Jenis absen tidak dikenal.']);
        exit;
    }

    // 4. Simpan ke database
    // Cek apakah sudah absen hari ini
    $stmtCek = $pdo->prepare("SELECT * FROM absensi WHERE siswa_id = ? AND tanggal = ?");
    $stmtCek->execute([$siswa_id, $tanggal]);
    $absen_hari_ini = $stmtCek->fetch();

    if ($jenis === 'masuk') {
        if ($absen_hari_ini && $absen_hari_ini['jam_masuk'] !== null) {
            echo json_encode(['status' => 'error', 'message' => 'Anda sudah melakukan absen masuk hari ini.']);
            exit;
        }

        if ($absen_hari_ini) {
            // Update record yang sudah ada (misal sebelumnya di-set alpa/izin oleh sistem/guru namun masih bisa di-override? Biasanya tidak boleh, asumsikan update jika jam_masuk null)
            $stmtUpd = $pdo->prepare("UPDATE absensi SET jam_masuk=?, lat_masuk=?, long_masuk=?, jarak_masuk_meter=?, status=? WHERE id=?");
            $stmtUpd->execute([$sekarang, $lat_siswa, $lng_siswa, $jarak, $status_kehadiran, $absen_hari_ini['id']]);
        } else {
            // Insert baru
            $stmtIns = $pdo->prepare("INSERT INTO absensi (siswa_id, tanggal, jam_masuk, lat_masuk, long_masuk, jarak_masuk_meter, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$siswa_id, $tanggal, $sekarang, $lat_siswa, $lng_siswa, $jarak, $status_kehadiran]);
        }
        
    } else {
        // Absen Pulang
        if (!$absen_hari_ini) {
            echo json_encode(['status' => 'error', 'message' => 'Anda belum melakukan absen masuk hari ini.']);
            exit;
        }
        if ($absen_hari_ini['jam_pulang'] !== null) {
            echo json_encode(['status' => 'error', 'message' => 'Anda sudah melakukan absen pulang hari ini.']);
            exit;
        }

        // Update jam pulang, JANGAN ubah status karena status sudah ditentukan saat absen masuk
        // Status tetap 'hadir' atau 'terlambat' sesuai saat absen masuk
        $stmtUpd = $pdo->prepare("UPDATE absensi SET jam_pulang=?, lat_pulang=?, long_pulang=?, jarak_pulang_meter=? WHERE id=?");
        $stmtUpd->execute([$sekarang, $lat_siswa, $lng_siswa, $jarak, $absen_hari_ini['id']]);
    }

    // Jika mode device, set flag agar bisa diclear nanti atau di redirect kembali
    $is_device = is_device_mode();
    if ($is_device) {
        clear_device_student_session(); // Logout siswa agar bisa gantian
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Absen ' . ucfirst($jenis) . ' berhasil!',
        'jarak' => round($jarak),
        'is_device' => $is_device
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Kesalahan database.']);
}
