<?php
/**
 * SIMAS-GPS — Auth Helper
 * Fungsi cek login, proteksi role, session management, dan CSRF
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true jika pakai HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Session Timeout (120 menit = 7200 detik)
$timeout_duration = 7200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['flash']['error'] = 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.';
    // Gunakan fungsi base_url() tapi karena mungkin belum dideklarasikan di baris ini, pakai konstanta APP_BASE_PATH
    $redirect_path = defined('APP_BASE_PATH') ? APP_BASE_PATH : '/absensi_gps';
    header('Location: ' . $redirect_path . '/login.php');
    exit;
}
$_SESSION['last_activity'] = time(); // update last activity time stamp

// ─────────────────────────────────────────────────────────
//  BASE URL
// ─────────────────────────────────────────────────────────

function base_url(): string {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $is_https ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // APP_BASE_PATH didefinisikan di config/database.php, baca dari ENV
    // Railway: '' (root domain) | XAMPP lokal: '/absensi_gps'
    $base_path = defined('APP_BASE_PATH') ? APP_BASE_PATH : '/absensi_gps';
    return $protocol . '://' . $host . $base_path;
}

// ─────────────────────────────────────────────────────────
//  REDIRECT AMAN
// ─────────────────────────────────────────────────────────

function redirect(string $url): void {
    header('Location: ' . filter_var($url, FILTER_SANITIZE_URL));
    exit;
}

// ─────────────────────────────────────────────────────────
//  CEK LOGIN (normal atau device mode dengan siswa dipilih)
// ─────────────────────────────────────────────────────────

/**
 * Pastikan user sudah login.
 * Jika tidak, redirect ke halaman login.
 */
function cek_login(): void {
    if (empty($_SESSION['user_id'])) {
        // Izinkan jika device_mode aktif dan menunggu pemilihan siswa
        // (akan ditangani oleh cek_device_mode di pilih_siswa.php)
        redirect(base_url() . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}

// ─────────────────────────────────────────────────────────
//  CEK ROLE
// ─────────────────────────────────────────────────────────

/**
 * Cek apakah user punya role yang diizinkan.
 * @param string|array $role_diizinkan
 */
function cek_role($role_diizinkan): void {
    cek_login();

    $role_user = $_SESSION['role'] ?? '';

    if (is_string($role_diizinkan)) {
        $role_diizinkan = [$role_diizinkan];
    }

    if (!in_array($role_user, $role_diizinkan, true)) {
        $base = base_url();
        switch ($role_user) {
            case 'admin': redirect("$base/admin/dashboard.php"); break;
            case 'guru':  redirect("$base/guru/dashboard.php");  break;
            case 'siswa': redirect("$base/siswa/dashboard.php"); break;
            default:      redirect("$base/login.php");           break;
        }
    }
}

// ─────────────────────────────────────────────────────────
//  CEK DEVICE MODE
// ─────────────────────────────────────────────────────────

/**
 * Pastikan request datang dari sesi device kelas yang sudah diautentikasi.
 * Digunakan di siswa/pilih_siswa.php
 */
function cek_device_mode(): void {
    if (empty($_SESSION['device_mode']) || empty($_SESSION['device_kelas_id'])) {
        redirect(base_url() . '/login.php');
    }
}

/**
 * Cek apakah sesi saat ini adalah device mode (kelas shared).
 */
function is_device_mode(): bool {
    return !empty($_SESSION['device_mode']);
}

// ─────────────────────────────────────────────────────────
//  DATA USER YANG LOGIN
// ─────────────────────────────────────────────────────────

/**
 * Ambil array data user yang sedang aktif dari session.
 * @return array|null
 */
function user_login(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'nama'      => $_SESSION['nama']      ?? '',
        'role'      => $_SESSION['role']      ?? '',
        'foto'      => $_SESSION['foto']      ?? null,
        'siswa_id'  => $_SESSION['siswa_id']  ?? null,
    ];
}

/**
 * Ambil siswa_id dari session (untuk query absensi).
 * Mendukung login normal (siswa.id dari tabel siswa) maupun device mode.
 */
function get_siswa_id(): ?int {
    return isset($_SESSION['siswa_id']) ? (int)$_SESSION['siswa_id'] : null;
}

// ─────────────────────────────────────────────────────────
//  SET SESSION SETELAH LOGIN BERHASIL
// ─────────────────────────────────────────────────────────

/**
 * Set session untuk login normal (admin / guru / siswa).
 * Tambahkan siswa_id jika role = siswa.
 * @param array $user  Row dari tabel users
 * @param int|null $siswa_id  Row id dari tabel siswa (hanya untuk role siswa)
 */
function set_user_session(array $user, ?int $siswa_id = null): void {
    session_regenerate_id(true);
    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['nama']     = $user['nama'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['foto']     = $user['foto'] ?? null;
    if ($siswa_id !== null) {
        $_SESSION['siswa_id'] = $siswa_id;
    }
    // Hapus sisa-sisa device session kalau ada
    unset($_SESSION['device_mode'], $_SESSION['device_kelas_id'],
          $_SESSION['device_kelas_nama'], $_SESSION['device_auth_user_id']);
}

/**
 * Set session untuk device mode kelas.
 * @param array $kelas  Row dari tabel kelas
 * @param int   $auth_user_id  ID guru/admin yang mengautentikasi device
 */
function set_device_session(array $kelas, int $auth_user_id): void {
    session_regenerate_id(true);
    $_SESSION['device_mode']         = true;
    $_SESSION['device_kelas_id']     = (int)$kelas['id'];
    $_SESSION['device_kelas_nama']   = $kelas['nama_kelas'];
    $_SESSION['device_auth_user_id'] = $auth_user_id;
    // Belum ada siswa yang dipilih
    unset($_SESSION['user_id'], $_SESSION['nama'], $_SESSION['role'],
          $_SESSION['foto'],    $_SESSION['siswa_id']);
}

/**
 * Set session siswa sementara di atas device session (setelah siswa dipilih).
 * @param array $siswa  Row join dari siswa + users
 */
function set_device_student_session(array $siswa): void {
    $_SESSION['user_id']  = (int)$siswa['user_id'];
    $_SESSION['siswa_id'] = (int)$siswa['siswa_id'];
    $_SESSION['nama']     = $siswa['nama'];
    $_SESSION['role']     = 'siswa';
    $_SESSION['foto']     = $siswa['foto'] ?? null;
}

/**
 * Hapus data siswa sementara dari device session (setelah absen selesai).
 * Device kelas tetap aktif.
 */
function clear_device_student_session(): void {
    unset($_SESSION['user_id'], $_SESSION['siswa_id'],
          $_SESSION['nama'],    $_SESSION['role'], $_SESSION['foto']);
}

// ─────────────────────────────────────────────────────────
//  CSRF TOKEN
// ─────────────────────────────────────────────────────────

/**
 * Generate atau ambil CSRF token dari session.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token dari request POST.
 * @throws RuntimeException jika token tidak valid
 */
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Permintaan tidak valid (CSRF check gagal). Silakan refresh halaman dan coba lagi.');
    }
}

/**
 * Helper: cetak hidden input CSRF token di dalam form.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

// ─────────────────────────────────────────────────────────
//  HELPER UMUM
// ─────────────────────────────────────────────────────────

/**
 * Escape output HTML untuk mencegah XSS.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Flash message: set pesan satu-kali yang hilang setelah dibaca.
 */
function set_flash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): string {
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ─────────────────────────────────────────────────────────
//  DEVICE TOKEN — Satu Akun Siswa = Satu HP
// ─────────────────────────────────────────────────────────

/**
 * Verifikasi apakah device token yang dikirim dari HP cocok dengan
 * yang tersimpan di database untuk user_id tersebut.
 *
 * @param int    $user_id     ID dari tabel users
 * @param string $token       Token dari localStorage browser HP siswa
 * @param string $fingerprint Hash fingerprint browser (lapisan ke-2)
 * @return string  'ok'      → Token cocok, izinkan login
 *                 'new'     → Belum ada device terdaftar, daftarkan otomatis
 *                 'blocked' → Ada device lain yang terdaftar, tolak login
 */
function verify_device_token(int $user_id, string $token, string $fingerprint = ''): string {
    if ($user_id <= 0 || $token === '') return 'blocked';

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT token, fingerprint FROM device_tokens WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $record = $stmt->fetch();

    // Belum ada device terdaftar → HP pertama, daftarkan
    if (!$record) {
        return 'new';
    }

    // Cek apakah token cocok
    if (hash_equals($record['token'], $token)) {
        return 'ok';
    }

    // Token beda → cek fingerprint sebagai fallback
    // (misal: siswa hapus localStorage tapi pakai HP yang sama)
    if ($fingerprint !== '' && $record['fingerprint'] !== null &&
        hash_equals($record['fingerprint'], $fingerprint)) {
        return 'ok';
    }

    // Token dan fingerprint tidak cocok → HP berbeda
    return 'blocked';
}

/**
 * Daftarkan device token baru untuk siswa.
 * Dipanggil hanya saat verify_device_token() mengembalikan 'new'.
 *
 * @param int    $user_id     ID dari tabel users
 * @param string $token       Token dari localStorage
 * @param string $fingerprint Hash fingerprint browser
 * @param string $device_info User-Agent string HP (untuk info admin)
 */
function register_device_token(int $user_id, string $token, string $fingerprint = '', string $device_info = ''): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "INSERT INTO device_tokens (user_id, token, fingerprint, device_info)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             token       = VALUES(token),
             fingerprint = VALUES(fingerprint),
             device_info = VALUES(device_info)"
    );
    $stmt->execute([$user_id, $token, $fingerprint ?: null, $device_info ?: null]);
}

/**
 * Update last_seen_at saat siswa login dengan token yang valid.
 *
 * @param int $user_id  ID dari tabel users
 */
function update_device_last_seen(int $user_id): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("UPDATE device_tokens SET last_seen_at = NOW() WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

/**
 * Reset (hapus) device token siswa — dipanggil oleh admin
 * saat siswa ganti HP dan perlu daftarkan HP baru.
 *
 * @param int $user_id  ID dari tabel users (siswa)
 * @return bool  true jika berhasil dihapus
 */
function reset_device_token(int $user_id): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare("DELETE FROM device_tokens WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->rowCount() > 0;
}
