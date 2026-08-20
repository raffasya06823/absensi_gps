-- ============================================================
-- SIMAS-GPS — Migration: Tabel Device Tokens
-- Jalankan script ini di phpMyAdmin atau MySQL CLI
-- untuk menambahkan fitur Device Binding
-- ============================================================

USE `simas_gps`;

-- Buat tabel device_tokens
CREATE TABLE IF NOT EXISTS `device_tokens` (
  `id`            int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       int(10) UNSIGNED NOT NULL COMMENT 'FK ke users (role=siswa)',
  `token`         varchar(128) NOT NULL COMMENT 'Token acak unik yang disimpan di localStorage browser HP siswa',
  `fingerprint`   varchar(128) DEFAULT NULL COMMENT 'Hash fingerprint browser sebagai lapisan verifikasi kedua',
  `device_info`   varchar(500) DEFAULT NULL COMMENT 'User-Agent HP saat registrasi (untuk info admin)',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Kapan HP ini pertama kali terdaftar',
  `last_seen_at`  timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Terakhir digunakan login',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_device`  (`user_id`) COMMENT 'Satu user_id hanya boleh punya SATU device terdaftar',
  UNIQUE KEY `uq_token`        (`token`),
  CONSTRAINT `fk_device_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Menyimpan device token HP siswa untuk pembatasan satu akun = satu HP';
