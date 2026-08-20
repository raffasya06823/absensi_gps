<?php
/**
 * SIMAS-GPS — Logout
 * Hapus session dan cookie, redirect ke login dengan flash message.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Simpan flash message sebelum destroy
$_SESSION['flash']['success'] = 'Anda berhasil keluar dari sistem.';

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Mulai session baru hanya untuk flash message
session_start();
$_SESSION['flash']['success'] = 'Anda berhasil keluar dari sistem.';

header('Location: ' . base_url() . '/login.php');
exit;
