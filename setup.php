<?php
/**
 * SIMAS-GPS — Setup Script (HANYA UNTUK INSTALASI AWAL)
 * Jalankan sekali via browser untuk membuat semua tabel di Railway.
 * HAPUS file ini setelah setup selesai!
 *
 * Akses: https://your-railway-url/setup.php?key=SIMASGPS2026
 */

// Kunci keamanan — hanya yang tahu kunci ini bisa menjalankan setup
define('SETUP_KEY', 'SIMASGPS2026');

if (($_GET['key'] ?? '') !== SETUP_KEY) {
    http_response_code(403);
    die('<h2 style="font-family:sans-serif;color:red">403 Forbidden — Setup key salah.</h2>');
}

require_once __DIR__ . '/config/database.php';

$pdo = getDB();
$results = [];
$errors  = [];

// ─────────────────────────────────────────────────────────
//  SQL SCHEMA — semua tabel SIMAS-GPS
// ─────────────────────────────────────────────────────────
$statements = [

    // 1. Tabel users
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `nama` varchar(150) NOT NULL,
        `username` varchar(60) NOT NULL,
        `password` varchar(255) NOT NULL COMMENT 'bcrypt hash',
        `role` enum('admin','guru','siswa') NOT NULL DEFAULT 'siswa',
        `email` varchar(150) DEFAULT NULL,
        `no_hp` varchar(20) DEFAULT NULL,
        `foto` varchar(255) DEFAULT NULL,
        `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_username` (`username`),
        KEY `idx_role` (`role`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 2. Tabel kelas
    "CREATE TABLE IF NOT EXISTS `kelas` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `nama_kelas` varchar(50) NOT NULL,
        `wali_kelas_id` int(10) UNSIGNED DEFAULT NULL,
        `mode_absen` enum('individu','device') NOT NULL DEFAULT 'individu',
        `tahun_ajaran` varchar(10) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_tahun_ajaran` (`tahun_ajaran`),
        KEY `fk_kelas_wali` (`wali_kelas_id`),
        CONSTRAINT `fk_kelas_wali` FOREIGN KEY (`wali_kelas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 3. Tabel siswa
    "CREATE TABLE IF NOT EXISTS `siswa` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(10) UNSIGNED NOT NULL,
        `nis` varchar(20) NOT NULL,
        `kelas_id` int(10) UNSIGNED DEFAULT NULL,
        `jenis_kelamin` enum('L','P') NOT NULL DEFAULT 'L',
        `alamat` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_nis` (`nis`),
        UNIQUE KEY `uq_user_id` (`user_id`),
        KEY `idx_kelas_id` (`kelas_id`),
        CONSTRAINT `fk_siswa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 4. Tabel absensi
    "CREATE TABLE IF NOT EXISTS `absensi` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `siswa_id` int(10) UNSIGNED NOT NULL,
        `tanggal` date NOT NULL,
        `jam_masuk` time DEFAULT NULL,
        `jam_pulang` time DEFAULT NULL,
        `lat_masuk` decimal(10,8) DEFAULT NULL,
        `long_masuk` decimal(11,8) DEFAULT NULL,
        `jarak_masuk_meter` decimal(8,2) DEFAULT NULL,
        `lat_pulang` decimal(10,8) DEFAULT NULL,
        `long_pulang` decimal(11,8) DEFAULT NULL,
        `jarak_pulang_meter` decimal(8,2) DEFAULT NULL,
        `status` enum('hadir','terlambat','izin','sakit','alpa') NOT NULL DEFAULT 'alpa',
        `keterangan` text DEFAULT NULL,
        `input_manual_oleh` int(10) UNSIGNED DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_siswa_tanggal` (`siswa_id`,`tanggal`),
        KEY `idx_tanggal` (`tanggal`),
        KEY `idx_status` (`status`),
        KEY `fk_absensi_manual_oleh` (`input_manual_oleh`),
        CONSTRAINT `fk_absensi_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_absensi_manual_oleh` FOREIGN KEY (`input_manual_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 5. Tabel pengajuan_izin
    "CREATE TABLE IF NOT EXISTS `pengajuan_izin` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `siswa_id` int(10) UNSIGNED NOT NULL,
        `tanggal` date NOT NULL,
        `jenis` enum('izin','sakit') NOT NULL,
        `alasan` text NOT NULL,
        `file_bukti` varchar(255) DEFAULT NULL,
        `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `diproses_oleh` int(10) UNSIGNED DEFAULT NULL,
        `tanggal_proses` datetime DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_siswa_id` (`siswa_id`),
        KEY `idx_status` (`status`),
        KEY `idx_tanggal` (`tanggal`),
        KEY `fk_izin_diproses_oleh` (`diproses_oleh`),
        CONSTRAINT `fk_izin_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_izin_diproses_oleh` FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 6. Tabel pengaturan_sekolah
    "CREATE TABLE IF NOT EXISTS `pengaturan_sekolah` (
        `id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
        `latitude` decimal(10,8) NOT NULL DEFAULT 0.00000000,
        `longitude` decimal(11,8) NOT NULL DEFAULT 0.00000000,
        `radius_meter` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
        `jam_masuk_mulai` time NOT NULL DEFAULT '06:30:00',
        `jam_masuk_selesai` time NOT NULL DEFAULT '07:30:00',
        `jam_pulang_mulai` time NOT NULL DEFAULT '14:00:00',
        `jam_pulang_selesai` time NOT NULL DEFAULT '15:00:00',
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 7. Tabel device_tokens (fitur device binding)
    "CREATE TABLE IF NOT EXISTS `device_tokens` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(10) UNSIGNED NOT NULL,
        `token` varchar(128) NOT NULL,
        `fingerprint` varchar(128) DEFAULT NULL,
        `device_info` varchar(500) DEFAULT NULL,
        `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `last_seen_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_user_device` (`user_id`),
        UNIQUE KEY `uq_token` (`token`),
        CONSTRAINT `fk_device_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 8. Insert pengaturan sekolah default
    "INSERT IGNORE INTO `pengaturan_sekolah` (`id`) VALUES (1)",

    // 9. Insert user admin default (password: admin123)
    "INSERT IGNORE INTO `users` (`id`, `nama`, `username`, `password`, `role`, `status`) VALUES
        (1, 'Administrator', 'admin', '\$2y\$10\$jsMSKTTtoYgU25mYc9dkEeUoaLZJchrop0/vejBId6q3zNrGzDdEm', 'admin', 'aktif')",

    // 10. Insert guru-guru default (password: guru123)
    "INSERT IGNORE INTO `users` (`id`, `nama`, `username`, `password`, `role`, `status`) VALUES
        (21, 'Budi Santoso, S.Pd', 'budiguru', '\$2y\$10\$kwsQ4826kLSxOyims8GXpOYKa4FaKSVMLGUUzs9V7WRrGBc33wNM6', 'guru', 'aktif'),
        (22, 'Siti Aminah, M.Pd', 'sitiguru', '\$2y\$10\$kwsQ4826kLSxOyims8GXpOYKa4FaKSVMLGUUzs9V7WRrGBc33wNM6', 'guru', 'aktif'),
        (23, 'Ahmad Hidayat, S.Kom', 'ahmadguru', '\$2y\$10\$kwsQ4826kLSxOyims8GXpOYKa4FaKSVMLGUUzs9V7WRrGBc33wNM6', 'guru', 'aktif')",

    // 11. Insert kelas default
    "INSERT IGNORE INTO `kelas` (`id`, `nama_kelas`, `wali_kelas_id`, `mode_absen`, `tahun_ajaran`) VALUES
        (1, 'VII A', 21, 'individu', '2025/2026'),
        (2, 'VII B', 22, 'device', '2025/2026'),
        (3, 'VIII A', 23, 'individu', '2025/2026')",

    // 12. Insert siswa default (password: siswa123)
    "INSERT IGNORE INTO `users` (`id`, `nama`, `username`, `password`, `role`, `status`) VALUES
        (24,'Andi Saputra','andi','\$2y\$10\$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW','siswa','aktif'),
        (25,'Bunga Lestari','bunga','\$2y\$10\$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW','siswa','aktif'),
        (26,'Citra Kirana','citra','\$2y\$10\$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW','siswa','aktif'),
        (27,'Deni Setiawan','deni','\$2y\$10\$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW','siswa','aktif'),
        (28,'Eka Putri','eka','\$2y\$10\$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW','siswa','aktif')",

    "INSERT IGNORE INTO `siswa` (`id`, `user_id`, `nis`, `kelas_id`, `jenis_kelamin`, `alamat`) VALUES
        (1,24,'1001',1,'L','Padang Pariaman'),
        (2,25,'1002',1,'P','Padang Pariaman'),
        (3,26,'1003',1,'P','Padang Pariaman'),
        (4,27,'1004',1,'L','Padang Pariaman'),
        (5,28,'1005',1,'P','Padang Pariaman')",
];

// ─────────────────────────────────────────────────────────
//  Jalankan semua statement
// ─────────────────────────────────────────────────────────
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
foreach ($statements as $i => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = "✅ Statement " . ($i + 1) . " — OK";
    } catch (PDOException $e) {
        $errors[] = "❌ Statement " . ($i + 1) . " — " . $e->getMessage();
    }
}
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

$all_ok = empty($errors);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Setup SIMAS-GPS</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; max-width: 700px; margin: 40px auto; padding: 20px; }
h1 { color: #818cf8; }
.ok { color: #34d399; }
.err { color: #f87171; }
.box { background: #1e293b; border-radius: 12px; padding: 20px; margin: 16px 0; }
.warn { background: #7c3aed22; border: 1px solid #7c3aed; border-radius: 8px; padding: 12px; color: #c4b5fd; margin-top: 20px; }
</style>
</head>
<body>
<h1>🛠️ SIMAS-GPS Setup</h1>
<div class="box">
    <h3><?= $all_ok ? '✅ Setup Berhasil!' : '⚠️ Ada Error' ?></h3>
    <?php foreach ($results as $r): ?>
        <p class="ok"><?= $r ?></p>
    <?php endforeach; ?>
    <?php foreach ($errors as $e): ?>
        <p class="err"><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
</div>
<?php if ($all_ok): ?>
<div class="warn">
    <strong>⚠️ PENTING:</strong> Hapus file <code>setup.php</code> ini setelah setup selesai!<br>
    Atau akses tidak akan bekerja tanpa kunci yang benar.<br><br>
    <strong>Login Admin:</strong> username <code>admin</code> / password <code>admin123</code>
</div>
<p>✅ Database siap! <a href="/" style="color:#818cf8">Klik di sini untuk ke halaman utama →</a></p>
<?php endif; ?>
</body>
</html>
