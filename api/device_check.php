<?php
/**
 * SIMAS-GPS — API: Device Check (Opsional/Debug)
 * Endpoint AJAX untuk mengecek status device token tanpa melakukan login.
 * Berguna untuk testing dan debugging saja.
 * 
 * POST params: device_token, user_id
 * Response: JSON {status: 'ok'|'new'|'blocked'|'error', message: '...'}
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Hanya izinkan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$token   = trim($_POST['device_token']   ?? '');
$fp      = trim($_POST['device_fingerprint'] ?? '');
$user_id = (int)($_POST['user_id']       ?? 0);

if ($user_id <= 0 || $token === '') {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
    exit;
}

$result = verify_device_token($user_id, $token, $fp);

$messages = [
    'ok'      => 'Perangkat dikenali. Login diizinkan.',
    'new'     => 'Perangkat baru. Akan didaftarkan otomatis saat login.',
    'blocked' => 'Akun ini sudah terdaftar di perangkat lain. Hubungi admin untuk reset.',
];

echo json_encode([
    'status'  => $result,
    'message' => $messages[$result] ?? 'Status tidak diketahui.',
]);
