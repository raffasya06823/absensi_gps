<?php
/**
 * SIMAS-GPS — Konfigurasi Database PDO
 * Mendukung environment variables (Railway) dan fallback ke nilai default (lokal XAMPP).
 * 
 * Di Railway: set variabel via Railway Dashboard → Variables.
 * Di lokal  : buat file .env (tidak perlu, langsung ubah nilai default di sini jika mau).
 */

// ── Load .env file jika ada (untuk development lokal) ──
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// ── Set timezone ──
date_default_timezone_set('Asia/Jakarta');

// ── Baca konfigurasi dari ENV (Railway otomatis set ini via Dashboard) ──
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'simas_gps');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ── BASE_PATH untuk URL helper ──
// Railway: kosong string '' karena deploy di root domain
// XAMPP lokal: '/absensi_gps'
define('APP_BASE_PATH', getenv('BASE_PATH') ?: '/absensi_gps');
define('APP_ENV',       getenv('APP_ENV')   ?: 'development');

// ── Koneksi PDO ──
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("[SIMAS-GPS] DB Error: " . $e->getMessage());
            $isProd = (APP_ENV === 'production');
            die(json_encode([
                'status'  => 'error',
                'message' => $isProd
                    ? 'Koneksi database gagal. Hubungi administrator.'
                    : 'DB Error: ' . $e->getMessage(),
            ]));
        }
    }

    return $pdo;
}

// Buat instance awal agar error koneksi terdeteksi lebih awal
try {
    $pdo = getDB();
} catch (Exception $e) {
    error_log("[SIMAS-GPS] Inisialisasi DB gagal: " . $e->getMessage());
}
