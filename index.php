<?php
/**
 * SIMAS-GPS — Halaman Utama / Redirect
 * Arahkan user ke halaman yang sesuai berdasarkan status login
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    switch ($_SESSION['role'] ?? '') {
        case 'admin': header('Location: /absensi_gps/admin/dashboard.php'); break;
        case 'guru':  header('Location: /absensi_gps/guru/dashboard.php');  break;
        case 'siswa': header('Location: /absensi_gps/siswa/dashboard.php'); break;
        default:      header('Location: /absensi_gps/login.php'); break;
    }
} else {
    header('Location: /absensi_gps/login.php');
}
exit;
