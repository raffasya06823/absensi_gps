-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 13 Jul 2026 pada 07.29
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simas_gps`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(10) UNSIGNED NOT NULL,
  `siswa_id` int(10) UNSIGNED NOT NULL COMMENT 'FK ke siswa',
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
  `input_manual_oleh` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK ke users (guru/admin), NULL jika absen mandiri',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `absensi`
--

INSERT INTO `absensi` (`id`, `siswa_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `lat_masuk`, `long_masuk`, `jarak_masuk_meter`, `lat_pulang`, `long_pulang`, `jarak_pulang_meter`, `status`, `keterangan`, `input_manual_oleh`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-06-15', '07:16:00', '13:44:00', -0.58593514, 100.21896147, 25.00, -0.58593514, 100.21896147, 25.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(2, 3, '2026-06-15', '07:12:00', '13:41:00', -0.58595014, 100.21897147, 35.00, -0.58595014, 100.21897147, 35.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(3, 4, '2026-06-15', '07:17:00', '13:44:00', -0.58593414, 100.21895747, 34.00, -0.58593414, 100.21895747, 34.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(4, 5, '2026-06-15', '07:19:00', '13:41:00', -0.58593714, 100.21897247, 15.00, -0.58593714, 100.21897247, 15.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(5, 7, '2026-06-15', '07:17:00', '13:43:00', -0.58593514, 100.21895647, 18.00, -0.58593514, 100.21895647, 18.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(6, 8, '2026-06-15', '07:43:00', '13:44:00', -0.58593514, 100.21895647, 30.00, -0.58593514, 100.21895647, 30.00, 'terlambat', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(7, 10, '2026-06-15', '07:18:00', '13:44:00', -0.58593414, 100.21896747, 42.00, -0.58593414, 100.21896747, 42.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(8, 11, '2026-06-15', '07:12:00', '13:45:00', -0.58594214, 100.21897047, 28.00, -0.58594214, 100.21897047, 28.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(9, 12, '2026-06-15', '07:11:00', '13:42:00', -0.58594814, 100.21895447, 44.00, -0.58594814, 100.21895447, 44.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(10, 13, '2026-06-15', '07:15:00', '13:43:00', -0.58594214, 100.21897347, 41.00, -0.58594214, 100.21897347, 41.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(11, 14, '2026-06-15', '07:19:00', '13:45:00', -0.58595214, 100.21896247, 14.00, -0.58595214, 100.21896247, 14.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(12, 15, '2026-06-15', '07:18:00', '13:45:00', -0.58593814, 100.21895747, 23.00, -0.58593814, 100.21895747, 23.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(13, 1, '2026-06-16', '07:17:00', '13:40:00', -0.58593314, 100.21897147, 20.00, -0.58593314, 100.21897147, 20.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(14, 2, '2026-06-16', '07:12:00', '13:43:00', -0.58593614, 100.21895447, 7.00, -0.58593614, 100.21895447, 7.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(15, 3, '2026-06-16', '07:15:00', '13:41:00', -0.58593414, 100.21896647, 35.00, -0.58593414, 100.21896647, 35.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(16, 4, '2026-06-16', '07:14:00', '13:41:00', -0.58594914, 100.21896847, 44.00, -0.58594914, 100.21896847, 44.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(17, 5, '2026-06-16', '07:11:00', '13:40:00', -0.58593214, 100.21895747, 10.00, -0.58593214, 100.21895747, 10.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(18, 6, '2026-06-16', '07:14:00', '13:41:00', -0.58593714, 100.21896347, 5.00, -0.58593714, 100.21896347, 5.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(19, 8, '2026-06-16', '07:12:00', '13:41:00', -0.58594114, 100.21895547, 43.00, -0.58594114, 100.21895547, 43.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(20, 9, '2026-06-16', '07:15:00', '13:42:00', -0.58595114, 100.21896247, 37.00, -0.58595114, 100.21896247, 37.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(21, 10, '2026-06-16', '07:15:00', '13:40:00', -0.58594314, 100.21897047, 36.00, -0.58594314, 100.21897047, 36.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(22, 11, '2026-06-16', '07:18:00', '13:40:00', -0.58593214, 100.21896847, 20.00, -0.58593214, 100.21896847, 20.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(23, 12, '2026-06-16', '07:12:00', '13:45:00', -0.58594714, 100.21895647, 20.00, -0.58594714, 100.21895647, 20.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(24, 13, '2026-06-16', '07:16:00', '13:40:00', -0.58594814, 100.21897247, 7.00, -0.58594814, 100.21897247, 7.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(25, 14, '2026-06-16', '07:16:00', '13:41:00', -0.58594914, 100.21896147, 6.00, -0.58594914, 100.21896147, 6.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(26, 15, '2026-06-16', '07:18:00', '13:40:00', -0.58594214, 100.21897047, 6.00, -0.58594214, 100.21897047, 6.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(27, 2, '2026-06-17', '07:18:00', '13:44:00', -0.58593214, 100.21896347, 24.00, -0.58593214, 100.21896347, 24.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(28, 3, '2026-06-17', '07:12:00', '13:40:00', -0.58593914, 100.21895447, 25.00, -0.58593914, 100.21895447, 25.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(29, 4, '2026-06-17', '07:45:00', '13:41:00', -0.58593314, 100.21896847, 18.00, -0.58593314, 100.21896847, 18.00, 'terlambat', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(30, 5, '2026-06-17', '07:16:00', '13:42:00', -0.58594714, 100.21895547, 21.00, -0.58594714, 100.21895547, 21.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(31, 6, '2026-06-17', '07:11:00', '13:42:00', -0.58593314, 100.21895647, 8.00, -0.58593314, 100.21895647, 8.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(32, 7, '2026-06-17', '07:43:00', '13:45:00', -0.58594214, 100.21896147, 43.00, -0.58594214, 100.21896147, 43.00, 'terlambat', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(33, 8, '2026-06-17', '07:13:00', '13:44:00', -0.58594014, 100.21895647, 16.00, -0.58594014, 100.21895647, 16.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(34, 9, '2026-06-17', '07:18:00', '13:44:00', -0.58595114, 100.21896047, 37.00, -0.58595114, 100.21896047, 37.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(35, 10, '2026-06-17', '07:16:00', '13:40:00', -0.58594714, 100.21895747, 25.00, -0.58594714, 100.21895747, 25.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(36, 11, '2026-06-17', '07:10:00', '13:44:00', -0.58594914, 100.21896647, 5.00, -0.58594914, 100.21896647, 5.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(37, 12, '2026-06-17', '07:10:00', '13:41:00', -0.58595114, 100.21896947, 11.00, -0.58595114, 100.21896947, 11.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(38, 13, '2026-06-17', '07:10:00', '13:42:00', -0.58593214, 100.21895947, 10.00, -0.58593214, 100.21895947, 10.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(39, 14, '2026-06-17', '07:19:00', '13:44:00', -0.58593214, 100.21896747, 6.00, -0.58593214, 100.21896747, 6.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(40, 15, '2026-06-17', '07:14:00', '13:40:00', -0.58595014, 100.21896047, 39.00, -0.58595014, 100.21896047, 39.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(41, 2, '2026-06-18', '07:17:00', '13:40:00', -0.58594914, 100.21896647, 30.00, -0.58594914, 100.21896647, 30.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(42, 3, '2026-06-18', '07:14:00', '13:41:00', -0.58594114, 100.21896947, 31.00, -0.58594114, 100.21896947, 31.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(43, 4, '2026-06-18', '07:45:00', '13:45:00', -0.58594614, 100.21896647, 10.00, -0.58594614, 100.21896647, 10.00, 'terlambat', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(44, 5, '2026-06-18', '07:14:00', '13:44:00', -0.58593714, 100.21897147, 5.00, -0.58593714, 100.21897147, 5.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(45, 6, '2026-06-18', '07:19:00', '13:40:00', -0.58593514, 100.21897447, 13.00, -0.58593514, 100.21897447, 13.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(46, 7, '2026-06-18', '07:15:00', '13:43:00', -0.58593714, 100.21897147, 11.00, -0.58593714, 100.21897147, 11.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(47, 8, '2026-06-18', '07:17:00', '13:43:00', -0.58594314, 100.21895647, 12.00, -0.58594314, 100.21895647, 12.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(48, 9, '2026-06-18', '07:11:00', '13:42:00', -0.58594514, 100.21895447, 7.00, -0.58594514, 100.21895447, 7.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(49, 10, '2026-06-18', '07:10:00', '13:42:00', -0.58594114, 100.21895447, 26.00, -0.58594114, 100.21895447, 26.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(50, 13, '2026-06-18', '07:14:00', '13:44:00', -0.58593814, 100.21896447, 36.00, -0.58593814, 100.21896447, 36.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(51, 14, '2026-06-18', '07:17:00', '13:41:00', -0.58595014, 100.21895547, 11.00, -0.58595014, 100.21895547, 11.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(52, 1, '2026-06-19', NULL, NULL, -0.58593514, 100.21896247, 36.00, -0.58593514, 100.21896247, 36.00, 'sakit', 'Demam tinggi', NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(53, 2, '2026-06-19', '07:14:00', '13:42:00', -0.58594414, 100.21896947, 9.00, -0.58594414, 100.21896947, 9.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(54, 3, '2026-06-19', '07:19:00', '13:41:00', -0.58595114, 100.21896947, 43.00, -0.58595114, 100.21896947, 43.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(55, 4, '2026-06-19', '07:15:00', '13:40:00', -0.58593914, 100.21896847, 32.00, -0.58593914, 100.21896847, 32.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(56, 5, '2026-06-19', '07:41:00', '13:45:00', -0.58593914, 100.21895647, 36.00, -0.58593914, 100.21895647, 36.00, 'terlambat', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(57, 6, '2026-06-19', '07:11:00', '13:44:00', -0.58594614, 100.21897447, 12.00, -0.58594614, 100.21897447, 12.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(58, 7, '2026-06-19', '07:41:00', '13:41:00', -0.58594414, 100.21895947, 22.00, -0.58594414, 100.21895947, 22.00, 'terlambat', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(59, 8, '2026-06-19', '07:15:00', '13:45:00', -0.58593514, 100.21897147, 35.00, -0.58593514, 100.21897147, 35.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(60, 9, '2026-06-19', '07:43:00', '13:41:00', -0.58593914, 100.21896947, 14.00, -0.58593914, 100.21896947, 14.00, 'terlambat', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(61, 10, '2026-06-19', '07:15:00', '13:40:00', -0.58593414, 100.21896447, 23.00, -0.58593414, 100.21896447, 23.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(62, 11, '2026-06-19', '07:11:00', '13:41:00', -0.58594514, 100.21896647, 30.00, -0.58594514, 100.21896647, 30.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(63, 12, '2026-06-19', '07:44:00', '13:40:00', -0.58594614, 100.21895847, 21.00, -0.58594614, 100.21895847, 21.00, 'terlambat', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(64, 13, '2026-06-19', '07:13:00', '13:45:00', -0.58594214, 100.21896947, 8.00, -0.58594214, 100.21896947, 8.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(65, 14, '2026-06-19', '07:19:00', '13:44:00', -0.58593514, 100.21896547, 39.00, -0.58593514, 100.21896547, 39.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(66, 15, '2026-06-19', '07:13:00', '13:43:00', -0.58595014, 100.21895747, 29.00, -0.58595014, 100.21895747, 29.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(67, 2, '2026-06-20', '07:18:00', '13:43:00', -0.58593914, 100.21897247, 15.00, -0.58593914, 100.21897247, 15.00, 'izin', 'Acara keluarga', 21, '2026-06-20 16:47:50', '2026-06-21 00:01:21'),
(68, 3, '2026-06-20', '07:17:00', '13:44:00', -0.58594314, 100.21896747, 30.00, -0.58594314, 100.21896747, 30.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(69, 4, '2026-06-20', '07:12:00', '13:43:00', -0.58594014, 100.21896347, 18.00, -0.58594014, 100.21896347, 18.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(70, 5, '2026-06-20', '07:18:00', '13:44:00', -0.58594314, 100.21895947, 43.00, -0.58594314, 100.21895947, 43.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(71, 6, '2026-06-20', '07:13:00', '13:45:00', -0.58593914, 100.21895647, 8.00, -0.58593914, 100.21895647, 8.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(72, 7, '2026-06-20', '07:18:00', '13:43:00', -0.58593714, 100.21896447, 34.00, -0.58593714, 100.21896447, 34.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(73, 8, '2026-06-20', '07:41:00', '13:42:00', -0.58593414, 100.21895547, 9.00, -0.58593414, 100.21895547, 9.00, 'terlambat', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(74, 9, '2026-06-20', '07:14:00', '13:43:00', -0.58594714, 100.21896247, 36.00, -0.58594714, 100.21896247, 36.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(75, 10, '2026-06-20', '07:14:00', '13:43:00', -0.58594314, 100.21895647, 18.00, -0.58594314, 100.21895647, 18.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(76, 12, '2026-06-20', '07:17:00', '13:40:00', -0.58594314, 100.21896047, 40.00, -0.58594314, 100.21896047, 40.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(77, 13, '2026-06-20', '07:11:00', '13:43:00', -0.58594114, 100.21897347, 17.00, -0.58594114, 100.21897347, 17.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(78, 14, '2026-06-20', '07:15:00', '13:45:00', -0.58594214, 100.21895647, 10.00, -0.58594214, 100.21895647, 10.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(79, 15, '2026-06-20', '07:11:00', '13:43:00', -0.58593614, 100.21895947, 24.00, -0.58593614, 100.21895947, 24.00, 'hadir', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(80, 1, '2026-06-20', '19:19:53', '19:20:58', -0.58618753, 100.21892689, 15.64, -0.58618753, 100.21892689, 15.64, 'terlambat', NULL, NULL, '2026-06-20 17:19:53', '2026-06-20 17:20:58'),
(81, 5, '2026-06-21', '01:53:47', NULL, -0.58618753, 100.21892689, 15.64, NULL, NULL, NULL, 'hadir', NULL, NULL, '2026-06-20 23:53:47', '2026-06-20 23:53:47'),
(82, 3, '2026-07-13', '06:51:47', NULL, -0.94552050, 100.38145936, 0.65, NULL, NULL, NULL, 'hadir', NULL, NULL, '2026-07-13 04:51:47', '2026-07-13 04:51:47'),
(83, 4, '2026-07-13', '07:02:35', '12:14:45', -0.94550924, 100.38146258, 1.30, -0.94551753, 100.38145430, 0.00, 'hadir', NULL, NULL, '2026-07-13 05:02:35', '2026-07-13 05:14:45'),
(84, 5, '2026-07-13', '07:10:16', '12:13:57', -0.94551220, 100.38145976, 0.85, -0.94551617, 100.38147093, 1.86, 'hadir', NULL, NULL, '2026-07-13 05:10:16', '2026-07-13 05:13:57'),
(85, 6, '2026-07-13', '12:16:36', '12:17:12', -0.94552170, 100.38144460, 1.17, -0.94551753, 100.38145430, 0.00, 'terlambat', NULL, NULL, '2026-07-13 05:16:36', '2026-07-13 05:17:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_kelas` varchar(50) NOT NULL COMMENT 'Contoh: VII A, VIII B',
  `wali_kelas_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK ke users (role=guru)',
  `mode_absen` enum('individu','device') NOT NULL DEFAULT 'individu',
  `tahun_ajaran` varchar(10) NOT NULL COMMENT 'Contoh: 2025/2026',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `wali_kelas_id`, `mode_absen`, `tahun_ajaran`, `created_at`) VALUES
(1, 'VII A', 21, 'individu', '2025/2026', '2026-06-20 16:47:50'),
(2, 'VII B', 22, 'device', '2025/2026', '2026-06-20 16:47:50'),
(3, 'VIII A', 23, 'individu', '2025/2026', '2026-06-20 16:47:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan_izin`
--

CREATE TABLE `pengajuan_izin` (
  `id` int(10) UNSIGNED NOT NULL,
  `siswa_id` int(10) UNSIGNED NOT NULL COMMENT 'FK ke siswa',
  `tanggal` date NOT NULL COMMENT 'Tanggal izin/sakit',
  `jenis` enum('izin','sakit') NOT NULL,
  `alasan` text NOT NULL,
  `file_bukti` varchar(255) DEFAULT NULL COMMENT 'Path relatif file upload',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `diproses_oleh` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK ke users (guru/admin)',
  `tanggal_proses` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pengajuan_izin`
--

INSERT INTO `pengajuan_izin` (`id`, `siswa_id`, `tanggal`, `jenis`, `alasan`, `file_bukti`, `status`, `diproses_oleh`, `tanggal_proses`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-06-19', 'sakit', 'Demam tinggi', NULL, 'approved', NULL, NULL, '2026-06-20 16:47:50', '2026-06-20 16:47:50'),
(2, 2, '2026-06-20', 'izin', 'Acara keluarga', NULL, 'approved', 21, '2026-06-21 02:01:21', '2026-06-20 16:47:50', '2026-06-21 00:01:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan_sekolah`
--

CREATE TABLE `pengaturan_sekolah` (
  `id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `latitude` decimal(10,8) NOT NULL DEFAULT 0.00000000,
  `longitude` decimal(11,8) NOT NULL DEFAULT 0.00000000,
  `radius_meter` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `jam_masuk_mulai` time NOT NULL DEFAULT '06:30:00',
  `jam_masuk_selesai` time NOT NULL DEFAULT '07:30:00',
  `jam_pulang_mulai` time NOT NULL DEFAULT '14:00:00',
  `jam_pulang_selesai` time NOT NULL DEFAULT '15:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pengaturan_sekolah`
--

INSERT INTO `pengaturan_sekolah` (`id`, `latitude`, `longitude`, `radius_meter`, `jam_masuk_mulai`, `jam_masuk_selesai`, `jam_pulang_mulai`, `jam_pulang_selesai`, `updated_at`) VALUES
(1, -0.94551753, 100.38145430, 100, '06:00:00', '07:30:00', '11:59:00', '16:00:00', '2026-07-13 04:58:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'FK ke users',
  `nis` varchar(20) NOT NULL COMMENT 'Nomor Induk Siswa',
  `kelas_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK ke kelas',
  `jenis_kelamin` enum('L','P') NOT NULL DEFAULT 'L',
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id`, `user_id`, `nis`, `kelas_id`, `jenis_kelamin`, `alamat`, `created_at`) VALUES
(1, 24, '1001', 1, 'L', 'Padang Pariaman', '2026-06-20 16:47:50'),
(2, 25, '1002', 1, 'P', 'Padang Pariaman', '2026-06-20 16:47:50'),
(3, 26, '1003', 1, 'P', 'Padang Pariaman', '2026-06-20 16:47:50'),
(4, 27, '1004', 1, 'L', 'Padang Pariaman', '2026-06-20 16:47:50'),
(5, 28, '1005', 1, 'P', 'Padang Pariaman', '2026-06-20 16:47:50'),
(6, 29, '2001', 2, 'L', 'Padang Pariaman', '2026-06-20 16:47:50'),
(7, 30, '2002', 2, 'P', 'Padang Pariaman', '2026-06-20 16:47:50'),
(8, 31, '2003', 2, 'L', 'Padang Pariaman', '2026-06-20 16:47:50'),
(9, 32, '2004', 2, 'P', 'Padang Pariaman', '2026-06-20 16:47:50'),
(10, 33, '2005', 2, 'L', 'Padang Pariaman', '2026-06-20 16:47:50'),
(11, 34, '3001', 3, 'L', 'Padang Pariaman', '2026-06-20 16:47:50'),
(12, 35, '3002', 3, 'P', 'Padang Pariaman', '2026-06-20 16:47:50'),
(13, 36, '3003', 3, 'L', 'Padang Pariaman', '2026-06-20 16:47:50'),
(14, 37, '3004', 3, 'P', 'Padang Pariaman', '2026-06-20 16:47:50'),
(15, 38, '3005', 3, 'L', 'Padang Pariaman', '2026-06-20 16:47:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(150) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'bcrypt hash',
  `role` enum('admin','guru','siswa') NOT NULL DEFAULT 'siswa',
  `email` varchar(150) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL COMMENT 'path relatif dari root project',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `email`, `no_hp`, `foto`, `status`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$jsMSKTTtoYgU25mYc9dkEeUoaLZJchrop0/vejBId6q3zNrGzDdEm', 'admin', 'admin@simasgps.sch.id', NULL, NULL, 'aktif', '2026-06-20 16:15:19'),
(21, 'Budi Santoso, S.Pd', 'budiguru', '$2y$10$kwsQ4826kLSxOyims8GXpOYKa4FaKSVMLGUUzs9V7WRrGBc33wNM6', 'guru', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(22, 'Siti Aminah, M.Pd', 'sitiguru', '$2y$10$kwsQ4826kLSxOyims8GXpOYKa4FaKSVMLGUUzs9V7WRrGBc33wNM6', 'guru', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(23, 'Ahmad Hidayat, S.Kom', 'ahmadguru', '$2y$10$kwsQ4826kLSxOyims8GXpOYKa4FaKSVMLGUUzs9V7WRrGBc33wNM6', 'guru', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(24, 'Andi Saputra', 'andi', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(25, 'Bunga Lestari', 'bunga', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(26, 'Citra Kirana', 'citra', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(27, 'Deni Setiawan', 'deni', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(28, 'Eka Putri', 'eka', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(29, 'Fajar Maulana', 'fajar', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(30, 'Gita Savitri', 'gita', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(31, 'Hendra Wijaya', 'hendra', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(32, 'Indah Permata', 'indah', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(33, 'Joko Susilo', 'joko', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(34, 'Kevin Sanjaya', 'kevin', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(35, 'Lina Marlina', 'lina', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(36, 'Muhammad Rizky', 'rizky', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(37, 'Nia Ramadhani', 'nia', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50'),
(38, 'Oscar Lawalata', 'oscar', '$2y$10$sKSavv6wmsWNO2zOqULy..iHa1EP3H.JUJFj4ac0r0AidBIqHGOrW', 'siswa', NULL, NULL, NULL, 'aktif', '2026-06-20 16:47:50');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_siswa_tanggal` (`siswa_id`,`tanggal`) COMMENT 'Cegah duplikasi absen per siswa per hari',
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_absensi_manual_oleh` (`input_manual_oleh`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tahun_ajaran` (`tahun_ajaran`),
  ADD KEY `fk_kelas_wali` (`wali_kelas_id`);

--
-- Indeks untuk tabel `pengajuan_izin`
--
ALTER TABLE `pengajuan_izin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_siswa_id` (`siswa_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `fk_izin_diproses_oleh` (`diproses_oleh`);

--
-- Indeks untuk tabel `pengaturan_sekolah`
--
ALTER TABLE `pengaturan_sekolah`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nis` (`nis`),
  ADD UNIQUE KEY `uq_user_id` (`user_id`),
  ADD KEY `idx_kelas_id` (`kelas_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pengajuan_izin`
--
ALTER TABLE `pengajuan_izin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `fk_absensi_manual_oleh` FOREIGN KEY (`input_manual_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_absensi_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_wali` FOREIGN KEY (`wali_kelas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengajuan_izin`
--
ALTER TABLE `pengajuan_izin`
  ADD CONSTRAINT `fk_izin_diproses_oleh` FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_izin_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_siswa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
