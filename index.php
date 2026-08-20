<?php
/**
 * SIMAS-GPS — Halaman Utama / Redirect
 * Arahkan user ke halaman yang sesuai berdasarkan status login
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    switch ($_SESSION['role'] ?? '') {
        case 'admin': header('Location: ' . base_url() . '/admin/dashboard.php'); break;
        case 'guru':  header('Location: ' . base_url() . '/guru/dashboard.php');  break;
        case 'siswa': header('Location: ' . base_url() . '/siswa/dashboard.php'); break;
        default:      header('Location: ' . base_url() . '/login.php'); break;
    }
} else {
    header('Location: ' . base_url() . '/login.php');
}
exit;
