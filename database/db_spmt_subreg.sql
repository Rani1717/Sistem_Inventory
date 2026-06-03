-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 03, 2026 at 06:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_spmt_app_backend`
--
DROP DATABASE IF EXISTS `db_spmt_app_backend`;
CREATE DATABASE IF NOT EXISTS `db_spmt_app_backend` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_spmt_app_backend`;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_cctv`
--

DROP TABLE IF EXISTS `dashboard_cctv`;
CREATE TABLE IF NOT EXISTS `dashboard_cctv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lokasi` varchar(150) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 0,
  `color` char(7) NOT NULL DEFAULT '#5B8DEF',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `dashboard_cctv`
--

TRUNCATE TABLE `dashboard_cctv`;
--
-- Dumping data for table `dashboard_cctv`
--

INSERT INTO `dashboard_cctv` (`id`, `lokasi`, `jumlah`, `color`, `created_at`, `updated_at`) VALUES
(1, 'SAMUDERA', 7, '#6fcf97', '2026-04-30 08:14:45', '2026-04-30 08:31:36'),
(3, 'PELDAM', 9, '#f2a541', '2026-04-30 08:14:45', '2026-04-30 08:33:45'),
(4, 'NUSANTARA', 8, '#34b3d8', '2026-04-30 08:14:45', '2026-04-30 08:33:16'),
(5, 'JT RORO', 7, '#f58b82', '2026-04-30 08:14:45', '2026-04-30 08:35:43'),
(6, 'TERMINAL PENUMPANG', 16, '#bf1d1d', '2026-04-30 08:14:45', '2026-04-30 08:36:54'),
(7, 'POS GATE 1', 1, '#3aa0ff', '2026-04-30 08:14:45', '2026-04-30 08:34:47'),
(8, 'POS GATE IV TRAFFIC MONITORING', 1, '#a86200', '2026-04-30 08:14:45', '2026-04-30 08:47:16'),
(9, 'C.CAIR DELI', 3, '#5b8def', '2026-04-30 08:32:10', '2026-04-30 08:32:10'),
(10, 'BEST C.CAIR', 1, '#d44949', '2026-04-30 08:32:36', '2026-04-30 08:32:57'),
(11, 'NUSANTARA GATE', 5, '#9630c5', '2026-04-30 08:33:31', '2026-04-30 08:46:30'),
(12, 'CY DELI', 1, '#edef61', '2026-04-30 08:34:03', '2026-04-30 08:34:03'),
(13, 'TRUNSTILE TKBM', 1, '#5b8def', '2026-04-30 08:34:34', '2026-04-30 08:34:34'),
(14, 'PKL', 6, '#46b458', '2026-04-30 08:35:59', '2026-04-30 08:36:18');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_update_log`
--

DROP TABLE IF EXISTS `inventory_update_log`;
CREATE TABLE IF NOT EXISTS `inventory_update_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `division_code` varchar(100) NOT NULL,
  `page_key` varchar(255) NOT NULL,
  `inventory_db_name` varchar(255) NOT NULL,
  `action_type` varchar(30) NOT NULL,
  `item_scope` varchar(30) NOT NULL,
  `item_key` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inventory_update_lookup` (`division_code`,`page_key`,`updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Truncate table before insert `inventory_update_log`
--

TRUNCATE TABLE `inventory_update_log`;
--
-- Dumping data for table `inventory_update_log`
--

INSERT INTO `inventory_update_log` (`id`, `division_code`, `page_key`, `inventory_db_name`, `action_type`, `item_scope`, `item_key`, `updated_at`) VALUES
(1, 'SPMT_SPJM', 'user:putri rossa', 'db_spmt_spjm', 'create', 'pc', 'INV-KMU01-0577-RJWA-2024', '2026-05-01 11:31:51'),
(2, 'SPMT_PENDUKUNG_OPERASI', 'user:putri rossa', 'db_spmt_divisi_pendukung_operasi', 'update', 'pc', 'SMG/A01/2017/00014', '2026-05-01 15:05:02'),
(3, 'SPMT_PENDUKUNG_OPERASI', 'computer:03sum-0060pc', 'db_spmt_divisi_pendukung_operasi', 'update', 'pc', 'SMG/A01/2017/00014', '2026-05-01 15:16:12'),
(4, 'SPMT_TEKNIK_IT', 'user:dheny', 'db_spmt_teknik_dan_it', 'update', 'pc', 'INV-KMU01-0653-RJWA-2024', '2026-05-01 15:17:04'),
(5, 'SPMT_TEKNIK_IT', 'user:ana', 'db_spmt_teknik_dan_it', 'update', 'pc', 'INV-KMU01-0653-RJWA-2024', '2026-05-01 15:17:21'),
(6, 'SPMT_TEKNIK_IT', 'user:winar dheny', 'db_spmt_teknik_dan_it', 'update', 'pc', 'INV-KMU01-0653-RJWA-2024', '2026-05-01 15:17:40'),
(7, 'SPMT_TEKNIK_IT', 'user:putri ananta', 'db_spmt_teknik_dan_it', 'create', 'pc', 'INV KWU RJWA', '2026-05-01 15:19:25'),
(8, 'SPMT_TEKNIK_IT', 'user:putri ananta', 'db_spmt_teknik_dan_it', 'delete', 'bundle', 'user:putri ananta', '2026-05-01 15:30:05'),
(9, 'SPMT_TEKNIK_IT', 'user:putri rossa', 'db_spmt_teknik_dan_it', 'create', 'pc', 'INV-KWU-0374-RJWA-2024', '2026-05-01 15:38:21'),
(10, 'SPMT_TEKNIK_IT', 'user:putri rossa', 'db_spmt_teknik_dan_it', 'update', 'pc', 'INV-KWU-0374-RJWA-2024', '2026-05-01 15:42:06'),
(11, 'SPMT_SPJM', 'user:putri rossa', 'db_spmt_spjm', 'create', 'perangkat_lain', 'INV-KWU-RJWA', '2026-05-03 20:24:48'),
(12, 'SPMT_SPJM', 'user:putri rossa', 'db_spmt_spjm', 'update', 'pc', 'INV-KMU01-0577-RJWA-2024', '2026-05-03 20:25:28'),
(13, 'SPMT_SPJM', 'user:putri ananta', 'db_spmt_spjm', 'create', 'pc', 'INV-KMU01-0653-RJWA-2024', '2026-05-03 20:28:31'),
(14, 'SPMT_SPJM', 'user:putri ananta', 'db_spmt_spjm', 'create', 'perangkat_lain', 'INV-KMU01-0578-RJWA-2024', '2026-05-03 20:41:25');

-- --------------------------------------------------------

--
-- Table structure for table `it_support_request`
--

DROP TABLE IF EXISTS `it_support_request`;
CREATE TABLE IF NOT EXISTS `it_support_request` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(30) NOT NULL,
  `reporter_user_id` bigint(20) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL,
  `email_pelapor` varchar(255) NOT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `nama_pelapor` varchar(255) NOT NULL,
  `divisi` varchar(255) NOT NULL,
  `aset_yang_perlu_diperbaiki` varchar(255) NOT NULL,
  `lokasi_perbaikan` varchar(255) NOT NULL,
  `deskripsi_kerusakan` text NOT NULL,
  `dokumentasi_kerusakan` varchar(255) DEFAULT NULL,
  `status` enum('NOT YET','ON PROGRESS','DONE') NOT NULL DEFAULT 'NOT YET',
  `handled_by_user_id` bigint(20) DEFAULT NULL,
  `handling_email_sent_at` datetime DEFAULT NULL,
  `handling_email_status` varchar(30) DEFAULT NULL,
  `handling_email_message` varchar(255) DEFAULT NULL,
  `catatan_penanganan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notification_read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_it_support_ticket_no` (`ticket_no`),
  KEY `idx_it_support_status` (`status`),
  KEY `idx_it_support_notification_read` (`notification_read_at`),
  KEY `idx_it_support_tanggal` (`tanggal`),
  KEY `idx_it_support_reporter_user` (`reporter_user_id`),
  KEY `idx_it_support_handled_by` (`handled_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `it_support_request`
--

TRUNCATE TABLE `it_support_request`;
--
-- Dumping data for table `it_support_request`
--

INSERT INTO `it_support_request` (`id`, `ticket_no`, `reporter_user_id`, `tanggal`, `jam`, `email_pelapor`, `email_verified`, `nama_pelapor`, `divisi`, `aset_yang_perlu_diperbaiki`, `lokasi_perbaikan`, `deskripsi_kerusakan`, `dokumentasi_kerusakan`, `status`, `handled_by_user_id`, `handling_email_sent_at`, `handling_email_status`, `handling_email_message`, `catatan_penanganan`, `created_at`, `updated_at`, `notification_read_at`) VALUES
(1, 'TSR-20260312-0001', NULL, '2026-03-12', '13:04:54', 'xyz@gmail.com', 0, 'JOKO', 'RENDAL', 'PC', 'RENDAL', 'Lemot dan susah dipakai', 'public/assets/images/complaint-1.png', 'DONE', 101, NULL, NULL, NULL, 'Performa perangkat sudah dinormalisasi.', '2026-04-30 08:14:45', '2026-04-30 08:14:45', NULL),
(2, 'TSR-20260221-0002', NULL, '2026-02-21', '14:04:34', 'xyz@gmail.com', 0, 'ANA S', 'TEKNIK', 'PRINTER', 'TEKNIK', 'Tidak bisa print', 'public/assets/images/complaint-2.png', 'NOT YET', NULL, NULL, NULL, NULL, NULL, '2026-04-30 08:14:45', '2026-04-30 08:30:59', '2026-04-30 08:30:59'),
(3, 'TSR-20260110-0003', NULL, '2026-01-10', '13:04:54', 'xyz@gmail.com', 0, 'SITA W', 'IT', 'CCTV', 'TIANG TRIANGLE', 'Error connection', 'public/assets/images/complaint-3.png', 'DONE', 101, NULL, NULL, NULL, 'Koneksi jaringan dan power adaptor sudah diperbaiki.', '2026-04-30 08:14:45', '2026-04-30 08:14:45', NULL),
(4, 'TSR-20251227-0004', NULL, '2025-12-27', '10:43:24', 'xyz@gmail.com', 0, 'PUTRI R', 'PELDAM', 'GATE IN RORO', 'RORO', 'Pas dipencet tombol gate tidak menyala', 'public/assets/images/complaint-4.png', 'ON PROGRESS', 101, NULL, NULL, NULL, 'Tombol gate sudah diganti.', '2026-04-30 08:14:45', '2026-04-30 08:16:05', NULL),
(5, 'TSR-20251104-0005', NULL, '2025-11-04', '15:10:43', 'xyz@gmail.com', 0, 'BAYU S', 'RENDAL', 'PC', 'RENDAL', 'Microsoft word tidak bisa dipakai', 'public/assets/images/complaint-5.png', 'DONE', 101, NULL, NULL, NULL, 'Office berhasil diaktivasi ulang.', '2026-04-30 08:14:45', '2026-04-30 08:14:45', NULL),
(6, 'TSR-20251012-0006', NULL, '2025-10-12', '09:38:52', 'xyz@gmail.com', 0, 'CINDY E', 'IT', 'MONITOR', 'IT', 'Layar monitor tbtb mati', 'public/assets/images/complaint-6.png', 'NOT YET', NULL, NULL, NULL, NULL, NULL, '2026-04-30 08:14:45', '2026-04-30 08:42:43', '2026-04-30 08:42:43'),
(7, 'TSR-20250916-0007', NULL, '2025-09-16', '14:32:54', 'xyz@gmail.com', 0, 'CYNTIA K', 'TEKNIK', 'CCTV', 'NUSANTARA', 'CCTV terkena badai', 'public/assets/images/complaint-7.png', 'NOT YET', NULL, NULL, NULL, NULL, NULL, '2026-04-30 08:14:45', '2026-04-30 08:42:51', '2026-04-30 08:42:51'),
(8, 'TSR-20260430-0001', NULL, '2026-04-30', '08:53:29', 'putri@gmail.com', 1, 'Putri', 'OPERASIONAL', 'printer', 'gate nusantara', 'gate palang tidak dapat berfungsi', 'public/uploads/it-support/it_support_20260430_085330_c65d3f80.png', 'DONE', 1, '2026-05-03 19:44:14', 'QUEUED_LOG', 'Fungsi mail server belum aktif; email dicatat di log.', 'sudah di cek', '2026-04-30 08:53:30', '2026-05-03 19:44:14', '2026-04-30 08:53:39');

-- --------------------------------------------------------

--
-- Table structure for table `it_support_request_history`
--

DROP TABLE IF EXISTS `it_support_request_history`;
CREATE TABLE IF NOT EXISTS `it_support_request_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) NOT NULL,
  `ticket_no` varchar(30) NOT NULL,
  `old_status` enum('NOT YET','ON PROGRESS','DONE') NOT NULL,
  `new_status` enum('NOT YET','ON PROGRESS','DONE') NOT NULL,
  `old_catatan_penanganan` text DEFAULT NULL,
  `new_catatan_penanganan` text DEFAULT NULL,
  `changed_by_user_id` bigint(20) DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_it_support_history_request` (`request_id`),
  KEY `idx_it_support_history_ticket` (`ticket_no`),
  KEY `idx_it_support_history_changed_by` (`changed_by_user_id`),
  KEY `idx_it_support_history_changed_at` (`changed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `it_support_request_history`
--

TRUNCATE TABLE `it_support_request_history`;
--
-- Dumping data for table `it_support_request_history`
--

INSERT INTO `it_support_request_history` (`id`, `request_id`, `ticket_no`, `old_status`, `new_status`, `old_catatan_penanganan`, `new_catatan_penanganan`, `changed_by_user_id`, `changed_at`) VALUES
(1, 1, 'TSR-20260312-0001', 'NOT YET', 'ON PROGRESS', NULL, 'Performa perangkat sedang dicek.', 101, '2026-03-12 13:20:00'),
(2, 1, 'TSR-20260312-0001', 'ON PROGRESS', 'DONE', 'Performa perangkat sedang dicek.', 'Performa perangkat sudah dinormalisasi.', 101, '2026-03-12 15:05:00'),
(3, 3, 'TSR-20260110-0003', 'NOT YET', 'DONE', NULL, 'Koneksi jaringan dan power adaptor sudah diperbaiki.', 101, '2026-01-10 15:22:00'),
(4, 4, 'TSR-20251227-0004', 'DONE', 'ON PROGRESS', 'Tombol gate sudah diganti.', 'Tombol gate sudah diganti.', 101, '2026-04-30 08:16:06'),
(5, 8, 'TSR-20260430-0001', 'NOT YET', 'ON PROGRESS', NULL, NULL, 1, '2026-04-30 08:54:03'),
(6, 8, 'TSR-20260430-0001', 'ON PROGRESS', 'DONE', NULL, 'sudah di cek', 1, '2026-05-03 19:44:12');

-- --------------------------------------------------------

--
-- Table structure for table `log_barang`
--

DROP TABLE IF EXISTS `log_barang`;
CREATE TABLE IF NOT EXISTS `log_barang` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `log_no` varchar(30) NOT NULL,
  `tanggal` date NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `status` enum('MASUK','KELUAR') NOT NULL,
  `divisi` varchar(255) DEFAULT NULL,
  `inventory_database` varchar(255) DEFAULT NULL,
  `sumber_tabel` enum('pc','perangkat_lain') DEFAULT NULL,
  `id_inventaris` varchar(255) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `no_po` varchar(50) DEFAULT NULL,
  `surat_pemesanan_pdf` varchar(255) DEFAULT NULL,
  `dibuat_oleh_user_id` bigint(20) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_log_barang_log_no` (`log_no`),
  KEY `idx_log_barang_tanggal` (`tanggal`),
  KEY `idx_log_barang_status` (`status`),
  KEY `idx_log_barang_dibuat_oleh` (`dibuat_oleh_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `log_barang`
--

TRUNCATE TABLE `log_barang`;
--
-- Dumping data for table `log_barang`
--

INSERT INTO `log_barang` (`id`, `log_no`, `tanggal`, `nama_barang`, `status`, `divisi`, `inventory_database`, `sumber_tabel`, `id_inventaris`, `qty`, `no_po`, `surat_pemesanan_pdf`, `dibuat_oleh_user_id`, `keterangan`, `created_at`) VALUES
(1, 'LOG-20260322-0001', '2026-03-22', 'PC DELL OPTIPLEX 3070', 'MASUK', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'pc', NULL, 1, '6430000717', NULL, 1, NULL, '2026-04-30 08:14:45'),
(2, 'LOG-20260220-0002', '2026-02-20', 'MOUSE LOGITECH', 'MASUK', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'perangkat_lain', NULL, 1, '6430000718', NULL, 1, NULL, '2026-04-30 08:14:45'),
(3, 'LOG-20260217-0003', '2026-02-17', 'PC HP OMNI 220', 'KELUAR', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'pc', NULL, 1, '6430000719', NULL, 1, NULL, '2026-04-30 08:14:45'),
(4, 'LOG-20260205-0004', '2026-02-05', 'KEYBOARD DELL', 'MASUK', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'perangkat_lain', NULL, 1, '-', NULL, 1, NULL, '2026-04-30 08:14:45'),
(5, 'LOG-20260129-0005', '2026-01-29', 'MOUSE LOGITECH', 'MASUK', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'perangkat_lain', NULL, 1, '-', NULL, 1, NULL, '2026-04-30 08:14:45'),
(6, 'LOG-20260114-0006', '2026-01-14', 'MOUSE ASUS', 'MASUK', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'perangkat_lain', NULL, 1, '-', NULL, 1, NULL, '2026-04-30 08:14:45'),
(7, 'LOG-20260108-0007', '2026-01-08', 'KEYBOARD LOGITECH', 'KELUAR', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'perangkat_lain', NULL, 1, '-', NULL, 1, NULL, '2026-04-30 08:14:45'),
(8, 'LOG-20251225-0008', '2025-12-25', 'PC LENOVO IDEACENTRE', 'MASUK', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'pc', NULL, 1, NULL, NULL, 1, 'Seed awal untuk menyesuaikan tampilan log barang pada website MVC.', '2026-04-30 08:14:45'),
(9, 'LOG-20251213-0009', '2025-12-13', 'MOUSE LOGITECH', 'MASUK', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'perangkat_lain', NULL, 1, NULL, NULL, 1, 'Seed awal untuk menyesuaikan tampilan log barang pada website MVC.', '2026-04-30 08:14:45'),
(10, 'LOG-20251204-0010', '2025-12-04', 'KEYBOARD HP', 'MASUK', 'TEKNOLOGI INFORMASI', 'db_spmt_teknik_dan_it', 'perangkat_lain', NULL, 1, NULL, NULL, 1, 'Seed awal untuk menyesuaikan tampilan log barang pada website MVC.', '2026-04-30 08:14:45'),
(11, 'LOG-20260430-0001', '2026-04-30', 'PC DELL OPTIPLEX 3070', 'MASUK', NULL, NULL, NULL, NULL, 1, '10234T5F', 'uploads/log-barang/po_20260430_031719_fd5bd329.pdf', 1, NULL, '2026-04-30 08:17:19');

-- --------------------------------------------------------

--
-- Table structure for table `master_divisi`
--

DROP TABLE IF EXISTS `master_divisi`;
CREATE TABLE IF NOT EXISTS `master_divisi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `division_code` varchar(100) NOT NULL,
  `sheet_sumber` varchar(20) NOT NULL,
  `division_group_name` varchar(255) NOT NULL,
  `division_label` varchar(255) NOT NULL,
  `inventory_db_name` varchar(255) NOT NULL,
  `sql_file_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_master_divisi_code` (`division_code`),
  UNIQUE KEY `uq_master_divisi_db` (`inventory_db_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `master_divisi`
--

TRUNCATE TABLE `master_divisi`;
--
-- Dumping data for table `master_divisi`
--

INSERT INTO `master_divisi` (`id`, `division_code`, `sheet_sumber`, `division_group_name`, `division_label`, `inventory_db_name`, `sql_file_name`, `is_active`, `created_at`) VALUES
(1, 'SPMT_PENDUKUNG_OPERASI', 'SPMT', 'DIVISI PENDUKUNG OPERASI_SPMT', 'DIVISI PENDUKUNG OPERASI', 'db_spmt_divisi_pendukung_operasi', 'spmt__divisi_pendukung_operasi.sql', 1, '2026-04-30 08:14:45'),
(2, 'SPMT_TEKNIK_IT', 'SPMT', 'TEKNIK & IT_SPMT', 'TEKNIK & IT', 'db_spmt_teknik_dan_it', 'spmt__teknik_dan_it.sql', 1, '2026-04-30 08:14:45'),
(3, 'SPMT_RENDAL_OPS', 'SPMT', 'RENDAL OPS_SPMT', 'RENDAL OPS', 'db_spmt_rendal_ops', 'spmt__rendal_ops.sql', 1, '2026-04-30 08:14:45'),
(4, 'SPMT_INTEGRATED_PNC', 'SPMT', 'integrated PNC_SPMT', 'INTEGRATED PNC', 'db_spmt_integrated_pnc', 'spmt__integrated_pnc.sql', 1, '2026-04-30 08:14:45'),
(5, 'SPMT_OPERASIONAL', 'SPMT', 'OPERASIONAL_SPMT', 'OPERASIONAL', 'db_spmt_operasional', 'spmt__operasional.sql', 1, '2026-04-30 08:14:45'),
(6, 'SPMT_RUANG_RAPAT_BRANCH_MANAGER', 'SPMT', 'RUANG RAPAT & BRANCH MANAGER_SPMT', 'RUANG RAPAT & BRANCH MANAGER', 'db_spmt_ruang_rapat_dan_branch_manager', 'spmt__ruang_rapat_dan_branch_manager.sql', 1, '2026-04-30 08:14:45'),
(7, 'SUBREG_KEUANGAN_FINANCE', 'SUBREG', 'Divisi Keuangan (Finance) SUBREG', 'DIVISI KEUANGAN (FINANCE)', 'db_subreg_divisi_keuangan_finance', 'subreg__divisi_keuangan_finance.sql', 1, '2026-04-30 08:14:45'),
(8, 'SUBREG_TEKNIK', 'SUBREG', 'TEKNIK SUBREG', 'TEKNIK', 'db_subreg_teknik', 'subreg__teknik.sql', 1, '2026-04-30 08:14:45'),
(9, 'SUBREG_PROPERTI_SDM_UMUM', 'SUBREG', 'PROPERTI - SDM UMUM SUBREG', 'PROPERTI - SDM UMUM', 'db_subreg_properti_sdm_umum', 'subreg__properti_sdm_umum.sql', 1, '2026-04-30 08:14:45'),
(10, 'SUBREG_INTEGRATED_PNC', 'SUBREG', 'integrated PNC SUBREG', 'INTEGRATED PNC', 'db_subreg_integrated_pnc', 'subreg__integrated_pnc.sql', 1, '2026-04-30 08:14:45'),
(11, 'SPMT_SPJM', 'SPMT', 'SPJM_SPMT', 'SPJM', 'db_spmt_spjm', 'spmt__spjm.sql', 1, '2026-05-01 11:30:21');

-- --------------------------------------------------------

--
-- Table structure for table `routine_monitoring`
--

DROP TABLE IF EXISTS `routine_monitoring`;
CREATE TABLE IF NOT EXISTS `routine_monitoring` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_type` enum('daily','weekly') NOT NULL DEFAULT 'daily',
  `period_key` varchar(20) NOT NULL,
  `monitor_date` date NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `condition_status` varchar(20) NOT NULL DEFAULT 'BAIK',
  `keterangan` text DEFAULT NULL,
  `checked_by_user_id` int(11) DEFAULT NULL,
  `checked_by_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_routine_period_item` (`period_type`,`period_key`,`item_name`),
  UNIQUE KEY `uniq_routine_period_item_id` (`period_type`,`period_key`,`item_id`),
  KEY `idx_routine_period` (`period_type`,`period_key`),
  KEY `idx_routine_date` (`monitor_date`),
  KEY `idx_routine_item_id` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `routine_monitoring`
--

TRUNCATE TABLE `routine_monitoring`;
--
-- Dumping data for table `routine_monitoring`
--

INSERT INTO `routine_monitoring` (`id`, `period_type`, `period_key`, `monitor_date`, `item_id`, `item_name`, `condition_status`, `keterangan`, `checked_by_user_id`, `checked_by_name`, `created_at`, `updated_at`) VALUES
(10, 'weekly', '2026-04-27', '2026-04-27', 1, 'GATE 1', 'BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:12:23', '2026-05-01 05:12:23'),
(11, 'weekly', '2026-04-27', '2026-04-27', 2, 'GATE 2', 'BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:12:23', '2026-05-01 05:12:23'),
(12, 'weekly', '2026-04-27', '2026-04-27', 3, 'CCTV GATE', 'BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:12:23', '2026-05-01 05:12:23'),
(13, 'weekly', '2026-04-27', '2026-04-27', 4, 'CCTV LOBBY', 'BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:12:23', '2026-05-01 05:12:23'),
(14, 'weekly', '2026-04-27', '2026-04-27', 5, 'SERVER UTAMA', 'BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:12:23', '2026-05-01 05:12:23'),
(15, 'weekly', '2026-04-27', '2026-04-27', 6, 'SERVER BACKUP', 'BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:12:23', '2026-05-01 05:12:23'),
(16, 'daily', '2026-05-01', '2026-05-01', 3, 'CCTV GATE', 'BAIK', NULL, 102, 'Putri', '2026-05-01 05:36:34', '2026-05-01 06:46:05'),
(17, 'daily', '2026-05-01', '2026-05-01', 4, 'CCTV LOBBY', 'BAIK', NULL, 102, 'Putri', '2026-05-01 05:36:34', '2026-05-01 06:46:05'),
(18, 'daily', '2026-05-01', '2026-05-01', 1, 'GATE 1', 'KURANG BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:36:34', '2026-05-01 05:50:45'),
(19, 'daily', '2026-05-01', '2026-05-01', 2, 'GATE 2', 'KURANG BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:36:34', '2026-05-01 05:50:45'),
(20, 'daily', '2026-05-01', '2026-05-01', 6, 'SERVER BACKUP', 'BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:36:34', '2026-05-01 06:20:10'),
(21, 'daily', '2026-05-01', '2026-05-01', 5, 'SERVER UTAMA', 'BAIK', NULL, 1, 'ADMIN SPMT', '2026-05-01 05:36:34', '2026-05-01 06:20:10');

-- --------------------------------------------------------

--
-- Table structure for table `routine_monitoring_categories`
--

DROP TABLE IF EXISTS `routine_monitoring_categories`;
CREATE TABLE IF NOT EXISTS `routine_monitoring_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_routine_category_name` (`category_name`),
  KEY `idx_routine_category_active` (`is_active`,`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `routine_monitoring_categories`
--

TRUNCATE TABLE `routine_monitoring_categories`;
--
-- Dumping data for table `routine_monitoring_categories`
--

INSERT INTO `routine_monitoring_categories` (`id`, `category_name`, `icon_class`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'GATE', 'fa-solid fa-door-open', 1, '2026-05-01 05:48:55', '2026-05-01 05:48:55'),
(2, 'CCTV', 'fa-solid fa-video', 1, '2026-05-01 05:48:55', '2026-05-01 05:48:55'),
(3, 'SERVER', 'fa-solid fa-server', 1, '2026-05-01 05:48:55', '2026-05-01 05:48:55'),
(4, 'PC', 'fa-solid fa-list-check', 0, '2026-05-01 06:20:30', '2026-05-01 06:58:17');

-- --------------------------------------------------------

--
-- Table structure for table `routine_monitoring_items`
--

DROP TABLE IF EXISTS `routine_monitoring_items`;
CREATE TABLE IF NOT EXISTS `routine_monitoring_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_group` varchar(50) NOT NULL DEFAULT 'UMUM',
  `category_field` varchar(50) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_routine_item_name` (`item_name`),
  KEY `idx_routine_item_group` (`item_group`),
  KEY `idx_routine_item_active` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `routine_monitoring_items`
--

TRUNCATE TABLE `routine_monitoring_items`;
--
-- Dumping data for table `routine_monitoring_items`
--

INSERT INTO `routine_monitoring_items` (`id`, `item_group`, `category_field`, `item_name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'GATE', 'GATE', 'GATE 1', 10, 1, '2026-05-01 05:02:03', '2026-05-01 06:03:17'),
(2, 'GATE', 'GATE', 'GATE 2', 20, 1, '2026-05-01 05:02:03', '2026-05-01 06:03:17'),
(3, 'CCTV', 'CCTV', 'CCTV GATE', 30, 1, '2026-05-01 05:02:03', '2026-05-01 06:03:17'),
(4, 'CCTV', 'CCTV', 'CCTV LOBBY', 40, 1, '2026-05-01 05:02:03', '2026-05-01 06:03:17'),
(5, 'SERVER', 'SERVER', 'SERVER UTAMA', 50, 1, '2026-05-01 05:02:03', '2026-05-01 06:03:17'),
(6, 'SERVER', 'SERVER', 'SERVER BACKUP', 60, 1, '2026-05-01 05:02:03', '2026-05-01 06:03:17'),
(7, 'CCTV', 'CCTV', 'CCTV_TP', 70, 1, '2026-05-01 06:58:55', '2026-05-01 06:58:55'),
(8, 'CCTV', 'CCTV', 'CCTV_PKL', 80, 1, '2026-05-01 06:59:19', '2026-05-01 06:59:19'),
(9, 'CCTV', 'CCTV', 'CCTV_SAMUDERA', 90, 1, '2026-05-01 07:00:22', '2026-05-01 07:00:22'),
(10, 'CCTV', 'CCTV', 'CCTV_NUSANTARA', 100, 1, '2026-05-01 07:14:24', '2026-05-01 07:14:24'),
(11, 'CCTV', 'CCTV', 'CCTV_TP_DOMESTIK', 110, 0, '2026-05-01 07:14:41', '2026-05-03 12:46:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` char(64) NOT NULL,
  `role` enum('admin','operator','user') NOT NULL DEFAULT 'user',
  `default_divisi_id` int(11) DEFAULT NULL,
  `unit_kerja_default` varchar(255) DEFAULT NULL,
  `sheet_sumber` varchar(20) DEFAULT NULL,
  `source_row_excel` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_default_divisi` (`default_divisi_id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `users`
--

TRUNCATE TABLE `users`;
--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `nama_lengkap`, `email`, `password_hash`, `role`, `default_divisi_id`, `unit_kerja_default`, `sheet_sumber`, `source_row_excel`, `is_active`, `must_change_password`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'admin.spmt', 'ADMIN SPMT', 'admin.spmt@pelindo.local', '6f2cb9dd8f4b65e24e1c3f3fa5bc57982349237f11abceacd45bbcb74d621c25', 'admin', 2, 'IT', NULL, NULL, 1, 0, '2026-05-03 19:42:13', '2026-04-30 08:14:45', '2026-05-03 19:42:13'),
(2, 'linda.rahayu', 'LINDA RAHAYU', 'linda.rahayu@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 5, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(3, 'diah.ayu', 'DIAH AYU', 'diah.ayu@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 6, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(4, 'ani.setyarini', 'ANI SETYARINI', 'ani.setyarini@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 7, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(5, 'elisabeth', 'ELISABETH', 'elisabeth@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 8, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(6, 'muchlisin', 'MUCHLISIN', 'muchlisin@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 9, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(7, 'rio', 'RIO', 'rio@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 10, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(8, 'siti.fatimah', 'SITI FATIMAH', 'siti.fatimah@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 11, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(9, 'desiana', 'DESIANA', 'desiana@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 12, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(10, 'guntur.prima', 'GUNTUR PRIMA', 'guntur.prima@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 13, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(11, 'asep.sarju', 'ASEP SARJU', 'asep.sarju@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 14, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(12, 'adhi.prasetyo', 'ADHI PRASETYO', 'adhi.prasetyo@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 1, 'PENDUKUNG OPERASI', 'SPMT', 15, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(13, 'emma', 'EMMA', 'emma@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'TEKNIK', 'SPMT', 21, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(14, 'agus.tri', 'AGUS TRI', 'agus.tri@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'TEKNIK', 'SPMT', 22, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(15, 'winar.dheny', 'WINAR DHENY', 'winar.dheny@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'TEKNIK', 'SPMT', 23, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(16, 'tisda.fanerfa', 'TISDA FANERFA', 'tisda.fanerfa@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'TEKNIK', 'SPMT', 24, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(17, 'tri.wahyudi', 'TRI WAHYUDI', 'tri.wahyudi@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'TEKNIK', 'SPMT', 25, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(18, 'feni.rinasari', 'FENI RINASARI', 'feni.rinasari@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'IT', 'SPMT', 26, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(19, 'aditya.ari.s', 'ADITYA ARI S', 'aditya.ari.s@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'IT', 'SPMT', 27, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(20, 'sutryono', 'SUTRYONO', 'sutryono@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'IT', 'SPMT', 28, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(21, 'bayu.agus', 'BAYU AGUS', 'bayu.agus@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'IT', 'SPMT', 29, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(22, 'user', '-', 'user@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'IT', 'SPMT', 30, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(23, 'monitor.cctv', 'MONITOR CCTV', 'monitor.cctv@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 2, 'IT', 'SPMT', 31, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(24, 'andrid', 'ANDRID', 'andrid@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 3, 'RENDAL OPS', 'SPMT', 35, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(25, 'rifa.nur.a', 'RIFA NUR A', 'rifa.nur.a@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 3, 'RENDAL OPS', 'SPMT', 36, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(26, 'anis', 'ANIS', 'anis@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 3, 'RENDAL-OPS', 'SPMT', 37, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(27, 'ninda', 'NINDA', 'ninda@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 3, 'RENDAL-OPS', 'SPMT', 38, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(28, 'richard.ferdinan', 'RICHARD FERDINAN', 'richard.ferdinan@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 3, 'RENDAL-OPS', 'SPMT', 39, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(29, 'ida', 'IDA', 'ida@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 3, 'RENDAL-OPS', 'SPMT', 40, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(30, 'ragil', 'RAGIL', 'ragil@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 3, 'RENDAL-OPS', 'SPMT', 41, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(31, 'wahyu.hendra', 'WAHYU HENDRA', 'wahyu.hendra@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 3, 'RENDAL-OPS', 'SPMT', 42, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(32, 'ferdi', 'FERDI', 'ferdi@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 3, 'RENDAL-OPS', 'SPMT', 43, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(33, 'dispatch.nusantara', 'DISPATCH NUSANTARA', 'dispatch.nusantara@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 4, 'PNC SPMT', 'SPMT', 47, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(34, 'dispatch.deli', 'DISPATCH DELI', 'dispatch.deli@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 4, 'PNC SPMT', 'SPMT', 48, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(35, 'dispatcher.peldam', 'DISPATCHER PELDAM', 'dispatcher.peldam@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 4, 'PNC SPMT', 'SPMT', 49, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(36, 'dispatch.samudra', 'DISPATCH SAMUDRA', 'dispatch.samudra@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 4, 'PNC SPMT', 'SPMT', 50, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(37, 'yos.indra', 'YOS INDRA', 'yos.indra@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 4, 'PNC SPMT', 'SPMT', 51, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(38, 'inke.w', 'INKE W', 'inke.w@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 4, 'PNC SPMT', 'SPMT', 52, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(39, 'rifky', 'RIFKY', 'rifky@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 4, 'PNC SPMT', 'SPMT', 54, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(40, 'security', 'SECURITY', 'security@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 4, 'PNC SPMT', 'SPMT', 55, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(41, 'staf.tp.4', 'STAF TP 4', 'staf.tp.4@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'TERMINAL PENUMPANG', 'SPMT', 60, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(42, 'staff.tp.2.jadwal.kapal', 'STAFF TP 2 (Jadwal Kapal)', 'staff.tp.2.jadwal.kapal@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'TERMINAL PENUMPANG', 'SPMT', 61, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(43, 'staff.tp.3', 'STAFF TP 3', 'staff.tp.3@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'TERMINAL PENUMPANG', 'SPMT', 62, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(44, 'staff.tp.1', 'STAFF TP 1', 'staff.tp.1@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'TERMINAL PENUMPANG', 'SPMT', 63, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(45, 'joko.sasmito', 'JOKO SASMITO', 'joko.sasmito@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'TERMINAL PENUMPANG', 'SPMT', 64, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(46, 'gate.boarding', 'GATE BOARDING', 'gate.boarding@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'TERMINAL PENUMPANG', 'SPMT', 65, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(47, 'seapass.boarding', 'SEAPASS BOARDING', 'seapass.boarding@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'TERMINAL PENUMPANG', 'SPMT', 66, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(48, 'k3.first.aid', 'K3 FIRST AID', 'k3.first.aid@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'TERMINAL PENUMPANG', 'SPMT', 67, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(49, 'port.security.terminal.penumpang', 'PORT SECURITY TERMINAL PENUMPANG', 'port.security.terminal.penumpang@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'TERMINAL PENUMPANG', 'SPMT', 68, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(50, 'gate.timbangan.2', 'GATE Timbangan 2', 'gate.timbangan.2@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'JT SAMUDRA', 'SPMT', 69, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(51, 'gate.timbangan.1', 'GATE Timbangan 1', 'gate.timbangan.1@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'JT NUSANTARA', 'SPMT', 70, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(52, 'co.nusantara', 'CO NUSANTARA', 'co.nusantara@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'GATE NUSANTARA', 'SPMT', 71, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(53, 'kiosk.gate.out.nusantara', 'KIOSK GATE OUT Nusantara', 'kiosk.gate.out.nusantara@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'GATE NUSANTARA', 'SPMT', 73, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(54, 'kiosk.gate.in.nusantara', 'KIOSK GATE IN Nusantara', 'kiosk.gate.in.nusantara@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'GATE NUSANTARA', 'SPMT', 74, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(55, 'co.deli', 'CO DELI', 'co.deli@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'CO CC DELI', 'SPMT', 75, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(56, 'co.samudra.01', 'CO SAMUDRA 01', 'co.samudra.01@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'CO SAMUDERA 01', 'SPMT', 76, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(57, 'staff.peldam', 'STAFF PELDAM', 'staff.peldam@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'CO PELDAM', 'SPMT', 77, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(58, 'pos.office.gate.peldam', 'POS OFFICE GATE PELDAM', 'pos.office.gate.peldam@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'CO PELDAM', 'SPMT', 78, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(59, 'kiosk.gate.in.peldam', 'KIOSK GATE IN Peldam', 'kiosk.gate.in.peldam@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'GATE IN PELDAM', 'SPMT', 79, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(60, 'kiosk.gate.out.peldam', 'KIOSK GATE OUT Peldam', 'kiosk.gate.out.peldam@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'GATE OUT PELDAM', 'SPMT', 80, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(61, 'co.samudera.02', 'CO SAMUDERA 02', 'co.samudera.02@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'GATE SAMUDERA 02', 'SPMT', 82, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(62, '03kom.032pc', '03KOM-032PC', '03kom.032pc@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'RORO Pos Office', 'SPMT', 83, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(63, 'kiosk.gate.roro', 'KIOSK GATE RORO', 'kiosk.gate.roro@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 5, 'RORO Gate In Mobil', 'SPMT', 84, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(64, 'kusumawardani', 'KUSUMAWARDANI', 'kusumawardani@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 6, 'RECEPTIONIST', 'SPMT', 91, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(65, 'rapat', 'RAPAT', 'rapat@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 6, 'RAPAT TANJUNG TEMBAGA', 'SPMT', 92, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(66, 'rapat.opsroom', 'RAPAT OPSROOM', 'rapat.opsroom@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 6, 'OPSROOM TENAU KUPANG', 'SPMT', 93, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(67, 'rapat.gm', 'RAPAT GM', 'rapat.gm@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 6, 'RAPAT GM', 'SPMT', 94, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(68, 'gm', 'GM', 'gm@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 6, 'GM', 'SPMT', 95, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(69, 'monitor.cctv.gm', 'MONITOR CCTV GM', 'monitor.cctv.gm@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 6, 'GM', 'SPMT', 96, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(70, 'sekretaris.gm', 'SEKRETARIS GM', 'sekretaris.gm@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 6, 'SEKRETARIS', 'SPMT', 97, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(71, 'tri.sunarni', 'TRI SUNARNI', 'tri.sunarni@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 7, 'KEUANGAN', 'SUBREG', 5, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(72, 'fitri.rachmiati', 'FITRI RACHMIATI', 'fitri.rachmiati@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 7, 'KEUANGAN', 'SUBREG', 6, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(73, 'dina.paramita', 'DINA PARAMITA', 'dina.paramita@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 7, 'KEUANGAN', 'SUBREG', 7, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(74, 'ika.bagus.l', 'IKA BAGUS L', 'ika.bagus.l@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 7, 'KEUANGAN', 'SUBREG', 8, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(75, 'pemagang', 'PEMAGANG', 'pemagang@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 7, 'KEUANGAN', 'SUBREG', 9, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(76, 'reza.arfany', 'REZA ARFANY', 'reza.arfany@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 8, 'TEKNIK', 'SUBREG', 17, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(77, 'hizkia.pandega', 'HIZKIA PANDEGA', 'hizkia.pandega@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 8, 'TEKNIK', 'SUBREG', 18, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(78, 'artsswinda.bunga', 'ARTSSWINDA BUNGA', 'artsswinda.bunga@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 8, 'TEKNIK', 'SUBREG', 19, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(79, 'bibit.k', 'BIBIT K', 'bibit.k@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 8, 'TEKNIK', 'SUBREG', 20, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(80, 'susiana.kety.mait', 'SUSIANA KETY MAIT', 'susiana.kety.mait@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 24, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(81, 'sri.wahyuningsih', 'SRI WAHYUNINGSIH', 'sri.wahyuningsih@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 25, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(82, 'esty.aryani', 'ESTY ARYANI', 'esty.aryani@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 26, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(83, 'wiyosi', 'WIYOSI', 'wiyosi@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 27, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(84, 'indira.h', 'INDIRA H', 'indira.h@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 28, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(85, 'cahaya', 'CAHAYA', 'cahaya@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 29, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(86, 'hery.susanto', 'HERY SUSANTO', 'hery.susanto@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 30, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(87, 'pc.admin.rtk', 'PC ADMIN RTK', 'pc.admin.rtk@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 32, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(88, 'anastasia', 'ANASTASIA', 'anastasia@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 33, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(89, 'endry.wahyu', 'ENDRY WAHYU', 'endry.wahyu@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 34, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(90, 'sri.rahayu.yulianti', 'SRI RAHAYU YULIANTI', 'sri.rahayu.yulianti@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 35, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(91, 'sri.rahayu', 'SRI RAHAYU', 'sri.rahayu@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 36, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(92, 'nur.laily', 'NUR LAILY', 'nur.laily@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 37, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(93, 'mira.eka.putri', 'MIRA EKA PUTRI', 'mira.eka.putri@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 38, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(94, 'herdyan.purwandito', 'HERDYAN PURWANDITO', 'herdyan.purwandito@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 9, 'SDM UMUM PROPERTI', 'SUBREG', 39, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(95, 'ko.pocc.subreg', 'KO POCC -SUBREG', 'ko.pocc.subreg@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 10, 'PNC Subreg', 'SUBREG', 43, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(96, 'm.fuadi.jupri.subreg', 'M. FUADI JUPRI - SUBREG', 'm.fuadi.jupri.subreg@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 10, 'PNC Subreg', 'SUBREG', 44, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(97, 'ingrid.adhi.subreg', 'INGRID ADHI -SUBREG', 'ingrid.adhi.subreg@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 10, 'PNC Subreg', 'SUBREG', 45, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(98, 'ayu.wury.subreg', 'AYU WURY - SUBREG', 'ayu.wury.subreg@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 10, 'PNC Subreg', 'SUBREG', 46, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(99, 'riski.puji.subreg', 'RISKI PUJI  - SUBREG', 'riski.puji.subreg@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 10, 'PNC Subreg', 'SUBREG', 47, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(100, 'julius.anwar.subreg', 'JULIUS ANWAR - SUBREG', 'julius.anwar.subreg@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'user', 10, 'PNC Subreg', 'SUBREG', 48, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(101, 'it.support.operator', 'IT SUPPORT OPERATOR', 'it.support.operator@spmt.local', '5f0b787ba6fcb06e52e3a8d9564908eab8ce7e0ee782f4b31112a93aadc583d3', 'operator', 2, 'IT', NULL, NULL, 1, 1, NULL, '2026-04-30 08:14:45', '2026-04-30 08:14:45'),
(102, 'putri', 'Putri', 'putrirossaananta@gmail.com', 'e98ad157372606220a0aab2d0a4af25dae5897e7d97fa097bb589b39c197cf65', 'user', 2, 'TEKNIK & IT', 'SPMT', NULL, 1, 0, '2026-05-03 21:19:28', '2026-05-01 10:35:43', '2026-05-03 21:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `user_divisi_akses`
--

DROP TABLE IF EXISTS `user_divisi_akses`;
CREATE TABLE IF NOT EXISTS `user_divisi_akses` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `divisi_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_divisi_akses` (`user_id`,`divisi_id`),
  KEY `idx_user_divisi_divisi` (`divisi_id`)
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `user_divisi_akses`
--

TRUNCATE TABLE `user_divisi_akses`;
--
-- Dumping data for table `user_divisi_akses`
--

INSERT INTO `user_divisi_akses` (`id`, `user_id`, `divisi_id`, `created_at`) VALUES
(1, 1, 1, '2026-04-30 08:14:45'),
(2, 1, 2, '2026-04-30 08:14:45'),
(3, 1, 3, '2026-04-30 08:14:45'),
(4, 1, 4, '2026-04-30 08:14:45'),
(5, 1, 5, '2026-04-30 08:14:45'),
(6, 1, 6, '2026-04-30 08:14:45'),
(7, 1, 7, '2026-04-30 08:14:45'),
(8, 1, 8, '2026-04-30 08:14:45'),
(9, 1, 9, '2026-04-30 08:14:45'),
(10, 1, 10, '2026-04-30 08:14:45'),
(11, 2, 1, '2026-04-30 08:14:45'),
(12, 3, 1, '2026-04-30 08:14:45'),
(13, 4, 1, '2026-04-30 08:14:45'),
(14, 5, 1, '2026-04-30 08:14:45'),
(15, 6, 1, '2026-04-30 08:14:45'),
(16, 7, 1, '2026-04-30 08:14:45'),
(17, 8, 1, '2026-04-30 08:14:45'),
(18, 9, 1, '2026-04-30 08:14:45'),
(19, 10, 1, '2026-04-30 08:14:45'),
(20, 11, 1, '2026-04-30 08:14:45'),
(21, 12, 1, '2026-04-30 08:14:45'),
(22, 13, 2, '2026-04-30 08:14:45'),
(23, 14, 2, '2026-04-30 08:14:45'),
(24, 15, 2, '2026-04-30 08:14:45'),
(25, 16, 2, '2026-04-30 08:14:45'),
(26, 17, 2, '2026-04-30 08:14:45'),
(27, 18, 2, '2026-04-30 08:14:45'),
(28, 19, 2, '2026-04-30 08:14:45'),
(29, 20, 2, '2026-04-30 08:14:45'),
(30, 21, 2, '2026-04-30 08:14:45'),
(31, 22, 2, '2026-04-30 08:14:45'),
(32, 23, 2, '2026-04-30 08:14:45'),
(33, 24, 3, '2026-04-30 08:14:45'),
(34, 25, 3, '2026-04-30 08:14:45'),
(35, 26, 3, '2026-04-30 08:14:45'),
(36, 27, 3, '2026-04-30 08:14:45'),
(37, 28, 3, '2026-04-30 08:14:45'),
(38, 29, 3, '2026-04-30 08:14:45'),
(39, 30, 3, '2026-04-30 08:14:45'),
(40, 31, 3, '2026-04-30 08:14:45'),
(41, 32, 3, '2026-04-30 08:14:45'),
(42, 33, 4, '2026-04-30 08:14:45'),
(43, 34, 4, '2026-04-30 08:14:45'),
(44, 35, 4, '2026-04-30 08:14:45'),
(45, 36, 4, '2026-04-30 08:14:45'),
(46, 37, 4, '2026-04-30 08:14:45'),
(47, 38, 4, '2026-04-30 08:14:45'),
(48, 39, 4, '2026-04-30 08:14:45'),
(49, 40, 4, '2026-04-30 08:14:45'),
(50, 41, 5, '2026-04-30 08:14:45'),
(51, 42, 5, '2026-04-30 08:14:45'),
(52, 43, 5, '2026-04-30 08:14:45'),
(53, 44, 5, '2026-04-30 08:14:45'),
(54, 45, 5, '2026-04-30 08:14:45'),
(55, 46, 5, '2026-04-30 08:14:45'),
(56, 47, 5, '2026-04-30 08:14:45'),
(57, 48, 5, '2026-04-30 08:14:45'),
(58, 49, 5, '2026-04-30 08:14:45'),
(59, 50, 5, '2026-04-30 08:14:45'),
(60, 51, 5, '2026-04-30 08:14:45'),
(61, 52, 5, '2026-04-30 08:14:45'),
(62, 53, 5, '2026-04-30 08:14:45'),
(63, 54, 5, '2026-04-30 08:14:45'),
(64, 55, 5, '2026-04-30 08:14:45'),
(65, 56, 5, '2026-04-30 08:14:45'),
(66, 57, 5, '2026-04-30 08:14:45'),
(67, 58, 5, '2026-04-30 08:14:45'),
(68, 59, 5, '2026-04-30 08:14:45'),
(69, 60, 5, '2026-04-30 08:14:45'),
(70, 61, 5, '2026-04-30 08:14:45'),
(71, 62, 5, '2026-04-30 08:14:45'),
(72, 63, 5, '2026-04-30 08:14:45'),
(73, 64, 6, '2026-04-30 08:14:45'),
(74, 65, 6, '2026-04-30 08:14:45'),
(75, 66, 6, '2026-04-30 08:14:45'),
(76, 67, 6, '2026-04-30 08:14:45'),
(77, 68, 6, '2026-04-30 08:14:45'),
(78, 69, 6, '2026-04-30 08:14:45'),
(79, 70, 6, '2026-04-30 08:14:45'),
(80, 71, 7, '2026-04-30 08:14:45'),
(81, 72, 7, '2026-04-30 08:14:45'),
(82, 73, 7, '2026-04-30 08:14:45'),
(83, 74, 7, '2026-04-30 08:14:45'),
(84, 75, 7, '2026-04-30 08:14:45'),
(85, 75, 8, '2026-04-30 08:14:45'),
(86, 75, 9, '2026-04-30 08:14:45'),
(87, 76, 8, '2026-04-30 08:14:45'),
(88, 77, 8, '2026-04-30 08:14:45'),
(89, 78, 8, '2026-04-30 08:14:45'),
(90, 79, 8, '2026-04-30 08:14:45'),
(91, 80, 9, '2026-04-30 08:14:45'),
(92, 81, 9, '2026-04-30 08:14:45'),
(93, 82, 9, '2026-04-30 08:14:45'),
(94, 83, 9, '2026-04-30 08:14:45'),
(95, 84, 9, '2026-04-30 08:14:45'),
(96, 85, 9, '2026-04-30 08:14:45'),
(97, 86, 9, '2026-04-30 08:14:45'),
(98, 87, 9, '2026-04-30 08:14:45'),
(99, 88, 9, '2026-04-30 08:14:45'),
(100, 89, 9, '2026-04-30 08:14:45'),
(101, 90, 9, '2026-04-30 08:14:45'),
(102, 91, 9, '2026-04-30 08:14:45'),
(103, 92, 9, '2026-04-30 08:14:45'),
(104, 93, 9, '2026-04-30 08:14:45'),
(105, 94, 9, '2026-04-30 08:14:45'),
(106, 95, 10, '2026-04-30 08:14:45'),
(107, 96, 10, '2026-04-30 08:14:45'),
(108, 97, 10, '2026-04-30 08:14:45'),
(109, 98, 10, '2026-04-30 08:14:45'),
(110, 99, 10, '2026-04-30 08:14:45'),
(111, 100, 10, '2026-04-30 08:14:45'),
(112, 101, 2, '2026-04-30 08:14:45'),
(113, 101, 3, '2026-04-30 08:14:45'),
(114, 101, 5, '2026-04-30 08:14:45'),
(115, 101, 8, '2026-04-30 08:14:45'),
(116, 102, 2, '2026-05-01 10:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `v_it_support_request_ui`
--

DROP TABLE IF EXISTS `v_it_support_request_ui`;
CREATE TABLE IF NOT EXISTS `v_it_support_request_ui` (
  `id` bigint(20) NOT NULL DEFAULT 0,
  `ticket_no` varchar(30) NOT NULL,
  `tanggal_dan_jam` varchar(86) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `divisi` varchar(255) NOT NULL,
  `barang` varchar(255) NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `dokumentasi` varchar(255) DEFAULT NULL,
  `status` enum('NOT YET','ON PROGRESS','DONE') NOT NULL DEFAULT 'NOT YET'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `v_it_support_request_ui`
--

TRUNCATE TABLE `v_it_support_request_ui`;
-- --------------------------------------------------------

--
-- Table structure for table `v_log_barang_ui`
--

DROP TABLE IF EXISTS `v_log_barang_ui`;
CREATE TABLE IF NOT EXISTS `v_log_barang_ui` (
  `id` bigint(20) NOT NULL DEFAULT 0,
  `log_no` varchar(30) NOT NULL,
  `tanggal` date NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `status` enum('MASUK','KELUAR') NOT NULL,
  `divisi` varchar(255) DEFAULT NULL,
  `inventory_database` varchar(255) DEFAULT NULL,
  `sumber_tabel` enum('pc','perangkat_lain') DEFAULT NULL,
  `id_inventaris` varchar(255) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `no_po` varchar(50) DEFAULT NULL,
  `surat_pemesanan_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `v_log_barang_ui`
--

TRUNCATE TABLE `v_log_barang_ui`;
-- --------------------------------------------------------

--
-- Table structure for table `v_users_login`
--

DROP TABLE IF EXISTS `v_users_login`;
CREATE TABLE IF NOT EXISTS `v_users_login` (
  `id` bigint(20) NOT NULL DEFAULT 0,
  `username` varchar(100) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` char(64) NOT NULL,
  `role` enum('admin','operator','user') NOT NULL DEFAULT 'user',
  `unit_kerja_default` varchar(255) DEFAULT NULL,
  `division_label` varchar(255) DEFAULT NULL,
  `inventory_db_name` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `v_users_login`
--

TRUNCATE TABLE `v_users_login`;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `it_support_request`
--
ALTER TABLE `it_support_request`
  ADD CONSTRAINT `fk_it_support_handled_by_user` FOREIGN KEY (`handled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_it_support_reporter_user` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `it_support_request_history`
--
ALTER TABLE `it_support_request_history`
  ADD CONSTRAINT `fk_it_support_history_changed_by` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_it_support_history_request` FOREIGN KEY (`request_id`) REFERENCES `it_support_request` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `log_barang`
--
ALTER TABLE `log_barang`
  ADD CONSTRAINT `fk_log_barang_dibuat_oleh` FOREIGN KEY (`dibuat_oleh_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_default_divisi` FOREIGN KEY (`default_divisi_id`) REFERENCES `master_divisi` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_divisi_akses`
--
ALTER TABLE `user_divisi_akses`
  ADD CONSTRAINT `fk_user_divisi_divisi` FOREIGN KEY (`divisi_id`) REFERENCES `master_divisi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_divisi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Database: `db_spmt_divisi_pendukung_operasi`
--
DROP DATABASE IF EXISTS `db_spmt_divisi_pendukung_operasi`;
CREATE DATABASE IF NOT EXISTS `db_spmt_divisi_pendukung_operasi` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_spmt_divisi_pendukung_operasi`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `inventory_order` bigint(20) DEFAULT NULL,
  `inventory_created_at` datetime DEFAULT current_timestamp(),
  `inventory_updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`, `inventory_order`, `inventory_created_at`, `inventory_updated_at`) VALUES
(NULL, 'PENDUKUNG OPERASI', 'PC', 'DELL OPTIPLEX 3070', '03TEM2007021PSM', 'LINDA RAHAYU', 'Core i5', '8 GB', '500 GB', NULL, 'WINDOWS 10 PRO', 'LICENSED', 'MS Office 2013', NULL, NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
('INV-KMU01-0768-RJWA-2024', 'PENDUKUNG OPERASI', 'PC', 'LENOVO', '03SUM-015PC', 'DIAH AYU', 'Core i3 - 4170', '8 GB', '1 TB', '10.3.10.185', 'Windows 10 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'LICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
('INV-KMU01-0741-RJWA-2024', 'PENDUKUNG OPERASI', 'PC', 'HP 280 G4 MT', '03POCC-002PC', 'ANI SETYARINI', 'Core i5 - 9400', '8 GB', '1,5 GB', '10.3.10.212', 'Windows 10 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
(NULL, 'PENDUKUNG OPERASI', 'PC', 'DELL OPTIPLEX 3070', '03OPERASI-041PC', 'ELISABETH', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.10.80', 'Windows 11 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
('INV-KMU01-0770-RJWA-2024', 'PENDUKUNG OPERASI', 'PC', 'HP 280 G4 MT', '03POCC-004PC', 'MUCHLISIN', 'Core i7 - 8700', '8 GB', '1,5 TB', '10.3.10.56', 'Windows 10 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
(NULL, 'PENDUKUNG OPERASI', 'PC', 'DELL OPTIPLEX 3070', '03TEM2007023PSM', 'RIO', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.10.192', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'LICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
('INV-KMU01-0577-RJWA-2024', 'PENDUKUNG OPERASI', 'PC', 'DELL OPTIPLEX 3070', '03TEM2007029PSM', 'SITI FATIMAH', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.10.99', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'LICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
('INV-KMU01-0581-RJWA-2024', 'PENDUKUNG OPERASI', 'PC', 'LENOVO ideacentre 300S-08IHH', '03KOM-055PC', 'DESIANA', 'Core i5 - 9400', '8 GB', '2 TB', '10.3.10.222', 'Windows 10 Pro', 'LICENSED', 'MS Office Home And Business 2019', 'LICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
('INV-KMU01-0585-RJWA-2024', 'PENDUKUNG OPERASI', 'PC', 'DELL OPTIPLEX 3070', '03TEM2007028PSM', 'GUNTUR PRIMA', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.10.179', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
('INV-KMU01-0767-RJWA-2024', 'PENDUKUNG OPERASI', 'PC', 'HP Pavilion 500 PC', '03SUM-014PC', 'ASEP SARJU', 'Core i3 - 4170', '8 GB', '500 GB', '10.3.10.47', 'Windows 8.1 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
('INV-KMU01-0764-RJWA-2024', 'PENDUKUNG OPERASI', 'PC', 'DELL OPTIPLEX 3070', '03TEM2007008PSM', 'ADHI PRASETYO', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.10.170', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
('SMG/A01/2017/00014', 'PENDUKUNG OPERASI', 'PC', 'LENOVO', '03SUM-0060PC', NULL, 'Core i3 - 4170', '16 GB', '500 GB', '10.183.27.58', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', 'images/inv-pc.png', 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18'),
(NULL, 'PENDUKUNG OPERASI', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912025PSM', NULL, 'Core i5', '8 GB', '1 TB', NULL, 'WINDOWS 10 PRO', 'LICENSED', 'MS Office 2013', NULL, NULL, 'AKTIF', NULL, '2026-05-01 15:30:18', '2026-05-01 15:30:18');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;
--
-- Dumping data for table `perangkat_lain`
--

INSERT INTO `perangkat_lain` (`id_inventaris`, `jenis_perangkat`, `merk_perangkat`, `unit_kerja`, `user`, `status`, `gambar`, `created_at`, `updated_at`, `last_edited_at`, `sync_at`, `edit_source`) VALUES
(NULL, 'MONITOR', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-FTG01-0013-RJWA-2024', 'WEBCAM', 'LOGI', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0748-RJWA-2024', 'MONITOR', 'HP (Dual Monitor)', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0731-RJWA-2024', 'KEYBOARD', 'HP', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0757-RJWA-2024', 'MOUSE', 'HP', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('Speaker 06 - 2022', 'SPEAKER', 'LOGI', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0741-RJWA-2024', 'MONITOR', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0728-RJWA-2024', 'KEYBOARD', 'HP', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'MOUSE', 'ASUS', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'WEBCAM', 'JETE', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'MONITOR', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0358-RJWA-2024', 'KEYBOARD', 'HP', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'MOUSE', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0776-2024', 'WEBCAM', 'LOGI', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0747-RJWA-2024', 'MONITOR', 'HP (Dual Monitor)', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0734-RJWA-2024', 'KEYBOARD', 'HP', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0760-RJWA-2024', 'MOUSE', 'LOGITECH', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'SPEAKER', 'LOGI', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0750-RJWA-2024', 'MONITOR', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0737-RJWA-2024', 'KEYBOARD', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0763-RJWA-2024', 'MOUSE', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'PRINTER', 'EPSON L3110', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0579-RJWA-2024', 'MONITOR', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0578-RJWA-2024', 'KEYBOARD', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0580-RJWA-2024', 'MOUSE', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0583-RJWA-2024', 'MONITOR', 'LENOVO', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0582-RJWA-2024', 'KEYBOARD', 'LOGITECH', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0584-RJWA-2024', 'MOUSE', 'LENOVO', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0587-RJWA-2024', 'MONITOR', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0586-RJWA-2024', 'KEYBOARD', 'LOGITECH', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0588-RJWA-2024', 'MOUSE', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0395-RJWA-2024', 'WEBCAM', 'LOGITECH', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0743-RJWA-2024', 'MONITOR', 'HP', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0730-RJWA-2024', 'KEYBOARD', 'HP', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0756-RJWA-2024', 'MOUSE', 'LOGITECH', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0748-RJWA-2024', 'MONITOR', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'KEYBOARD', 'HP', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0754-RJWA-2024', 'MOUSE', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'WEBCAM', 'JETE', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'MONITOR', 'LENOVO', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
(NULL, 'KEYBOARD', 'HP', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-KMU01-0415-RJWA-2024', 'MOUSE', 'DELL', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync'),
('INV-PRT01-0034-RJWA-2024', 'SPEAKER', 'LOGI', 'PENDUKUNG OPERASI', '', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-05-01 15:16:12', NULL, '2026-05-01 15:16:12', 'pc_user_sync');
--
-- Database: `db_spmt_integrated_pnc`
--
DROP DATABASE IF EXISTS `db_spmt_integrated_pnc`;
CREATE DATABASE IF NOT EXISTS `db_spmt_integrated_pnc` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_spmt_integrated_pnc`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`) VALUES
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', '03PNC-009PC', 'DISPATCH NUSANTARA', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', '10.183.26.53', 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', '03PNC-010PC', 'DISPATCH DELI', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', '10.183.26.54', 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', '03PNC-011PC', 'DISPATCHER PELDAM', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', '10.183.26.56', 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', '03PNC-012PC', 'DISPATCH SAMUDRA', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', '10.183.26.55', 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', '03PNC-013PC', 'YOS INDRA', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', '10.183.26.61', 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', '03PNC-014PC', 'INKE W', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', '10.183.26.62', 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', '03PNC-015PC', NULL, 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', '10.183.26.63', 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', '03PNC-016PC', 'RIFKY', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', '10.183.26.64', 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', 'DESKTOP-QJJDDIM', 'SECURITY', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', '10.183.26.67', 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'PNC SPMT', 'PC', 'HP Pro SFF 400 G9', 'DESKTOP-OUCCCC9', NULL, 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro', 'LICENSED', 'MS Office 2019', 'LICENSED', NULL, 'AKTIF');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;
--
-- Dumping data for table `perangkat_lain`
--

INSERT INTO `perangkat_lain` (`id_inventaris`, `jenis_perangkat`, `merk_perangkat`, `unit_kerja`, `user`, `status`, `gambar`, `created_at`, `updated_at`, `last_edited_at`, `sync_at`, `edit_source`) VALUES
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', 'DISPATCH NUSANTARA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'PNC SPMT', 'DISPATCH NUSANTARA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0378-RJWA-2024', 'MOUSE', 'DELL', 'PNC SPMT', 'DISPATCH NUSANTARA', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', 'DISPATCH DELI', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'PNC SPMT', 'DISPATCH DELI', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'PNC SPMT', 'DISPATCH DELI', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', 'DISPATCHER PELDAM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'PNC SPMT', 'DISPATCHER PELDAM', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LOGITCEH', 'PNC SPMT', 'DISPATCHER PELDAM', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', 'DISPATCH SAMUDRA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'PNC SPMT', 'DISPATCH SAMUDRA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'PNC SPMT', 'DISPATCH SAMUDRA', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', 'YOS INDRA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'PNC SPMT', 'YOS INDRA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'PNC SPMT', 'YOS INDRA', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', 'INKE W', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'PNC SPMT', 'INKE W', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'PNC SPMT', 'INKE W', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', NULL, 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'PNC SPMT', NULL, 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'PNC SPMT', NULL, 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', 'RIFKY', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'PNC SPMT', 'RIFKY', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'PNC SPMT', 'RIFKY', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', 'SECURITY', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'LOGITECH', 'PNC SPMT', 'SECURITY', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LOGIGITECH', 'PNC SPMT', 'SECURITY', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG ODDYSSEY G9', 'PNC SPMT', NULL, 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual');
--
-- Database: `db_spmt_operasional`
--
DROP DATABASE IF EXISTS `db_spmt_operasional`;
CREATE DATABASE IF NOT EXISTS `db_spmt_operasional` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_spmt_operasional`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`) VALUES
(NULL, 'TERMINAL PENUMPANG', 'PC', 'LENOVO ideacentre 300S-08IHH', '03OPERASI-025PC', 'STAF TP 4', 'Core i3 - 4170', '8 GB', '1 TB', '10.3.19.153', 'Windows 11 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'TERMINAL PENUMPANG', 'PC', 'LENOVO', '03OPERASI-029PC', 'STAFF TP 2 (Jadwal Kapal)', '-', '4 GB', '500 GB', '10.3.19.55', 'Windows 10 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
('INV-KMU01-0342-RJWA-2024', 'TERMINAL PENUMPANG', 'PC', 'DELL OPTIPLEX 3070', 'DESKTOP-GD11N53', 'STAFF TP 3', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.19.160', 'Windows 11 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'TERMINAL PENUMPANG', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912035PSM', 'STAFF TP 1', 'Core i5 - 9500', '12 GB', '1 TB', '10.3.19.110', 'Windows 11 Pro', 'LICENSED', 'MS Office', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'TERMINAL PENUMPANG', 'PC', 'DELL OPTIPLEX 3070', '03TEM2007007PSM', 'JOKO SASMITO', 'Core i5 - 9500', '8 GB', '1 TB', '10.3.19.51', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'TERMINAL PENUMPANG', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912043PSM', 'GATE BOARDING', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.19.210', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'TERMINAL PENUMPANG', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912037PSM', 'SEAPASS BOARDING', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.19.202', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'TERMINAL PENUMPANG', 'PC', 'DELL OPTIPLEX 3070', '01KTP2007015PSM', 'K3 FIRST AID', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.19.100', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
('SMG/A1/2027/0000', 'TERMINAL PENUMPANG', 'PC', 'HP PAVILION 500 PC Series', '03CCTV-009PC', 'PORT SECURITY TERMINAL PENUMPANG', 'Core i5 - 3570', '8 GB', '500 GB', '10.3.19.107', 'Windows 11 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'JT SAMUDRA', 'PC', 'TOUCHINDO IPC6U6 Series', '03KOM-034PC', 'GATE Timbangan 2', 'Core i5 - 4200U', '8 GB', '256 GB', '10.3.18.50', 'Windows 10 Pro', 'LICENSED', 'MS Office', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'JT NUSANTARA', 'PC', 'ADVANTECH ARK-6322', '03OPERASI-050PC', 'GATE Timbangan 1', 'Intel Celeron', '8 GB', '256 GB', '10.183.24.71', 'Windows 10 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'GATE NUSANTARA', 'PC', 'IDEACENTRE', 'NUSANTARA-01-PC', 'CO NUSANTARA', 'Core i3 - 6100', '8 GB', '256 GB', '10.183.24.72', 'Windows 10 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'GATE NUSANTARA', 'PC', 'ADVANTECH ARK-6322', '03OPERASI-044PC', 'CO NUSANTARA', 'Core i5 - 9500', '8 GB', '500 GB', '10.183.24.73', 'Windows 10 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'GATE NUSANTARA', 'PC', 'ADVANTECH ARK-6322', 'GET-OUT-NUSANTARA', 'KIOSK GATE OUT Nusantara', 'Core i5 - 10500E', '8 GB', '500 GB', '10.183.24.15', 'Windows 11 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'GATE NUSANTARA', 'PC', 'ADVANTECH ARK-6322', 'GET-IN-NUSANTARA', 'KIOSK GATE IN Nusantara', 'Core i5 - 10500E', '8 GB', '500 GB', '10.183.24.11', 'Windows 11 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'CO CC DELI', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912044PSM', 'CO DELI', 'Core i5', '8 GB', '500 GB', NULL, 'WIN 10 Pro', 'LICENSED', 'MS Office 2013', NULL, NULL, 'AKTIF'),
(NULL, 'CO SAMUDERA 01', 'PC', 'HP Omni 200 PC', '03OPS-046PC', 'CO SAMUDRA 01', 'Dual Core', '4 GB', '500 GB', NULL, 'WIN 10 Pro', 'LICENSED', 'MS Office 2013', NULL, NULL, 'AKTIF'),
('INV-KMU01-0771-RJWA-2024', 'CO PELDAM', 'PC', 'DELL OPTIPLEX 3070', '03TEM2007016PSM', 'STAFF PELDAM', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.16.54', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'CO PELDAM', 'PC', 'DELL OPTIPLEX 3070', '03TPS1912012PSM', 'POS OFFICE GATE PELDAM', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.16.51', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'GATE IN PELDAM', 'PC', 'ADVANTECH ARK-6322', '03OPS-057PC', 'KIOSK GATE IN Peldam', 'Core i7 - 4600U', '12 GB', '256 GB', '10.3.16.57', 'Windows 11 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'GATE OUT PELDAM', 'PC', 'ADVANTECH ARK-6322', '03OPS-058PC', 'KIOSK GATE OUT Peldam', 'Intel Celeron', '8 GB', '128 GB', '10.3.16.58', 'Windows 11 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'GATE SAMUDERA 01', 'PC', 'ADVANTECH ARK-6322', NULL, NULL, 'Intel Celeron', '8GB', '128 GB', NULL, 'WIN 10 Pro', 'LICENSED', NULL, NULL, NULL, 'AKTIF'),
('PC GATE S02', 'GATE SAMUDERA 02', 'PC', 'ADVANTECH UNO-2484G', 'DESKTOP-C7026DV', 'CO SAMUDERA 02', 'Core i3 - 6100U', '8 GB', '128 GB', '10.3.5.2', 'Windows 10 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'RORO Pos Office', 'PC', 'ADVANTECH ARK-6322', 'Pos Office', '03KOM-032PC', 'Intel Celeron', '8 GB', '128 GB', '10.183.24.135', 'Windows 10 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'RORO Gate In Mobil', 'PC', 'ADVANTECH', 'gate-in-mobil', 'KIOSK GATE RORO', 'Core i3-6100', '8 GB', '128 GB', '10.183.24.131', 'Windows 10 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'RORO Gate Out Motor', 'PC', 'ADVANTECH', 'gate-out-motor', 'KIOSK GATE RORO', 'Core i3-6100', '8 GB', '128 GB', '10.183.24.132', 'Windows 10 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'RORO Gate Out Mobil', 'PC', 'ADVANTECH', 'gate-out-mobil', 'KIOSK GATE RORO', 'Core i3-6100', '8 GB', '128 GB', '10.183.24.133', 'Windows 10 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF'),
(NULL, 'RORO Gate In Motor', 'PC', 'ADVANTECH', 'gate-in-motor', 'KIOSK GATE RORO', 'Core i3-6100', '8 GB', '128 GB', '10.183.24.134', 'Windows 10 Pro', 'LICENSED', '-', '-', NULL, 'AKTIF');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;
--
-- Dumping data for table `perangkat_lain`
--

INSERT INTO `perangkat_lain` (`id_inventaris`, `jenis_perangkat`, `merk_perangkat`, `unit_kerja`, `user`, `status`, `gambar`, `created_at`, `updated_at`, `last_edited_at`, `sync_at`, `edit_source`) VALUES
(NULL, 'MONITOR', 'LENOVO', 'TERMINAL PENUMPANG', 'STAF TP 4', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'LENOVO', 'TERMINAL PENUMPANG', 'STAF TP 4', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'DELL', 'TERMINAL PENUMPANG', 'STAF TP 4', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SPEAKER', 'GMC', 'TERMINAL PENUMPANG', 'STAF TP 4', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER STRUK', 'EPSON', 'TERMINAL PENUMPANG', 'STAF TP 4', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'LENOVO', 'TERMINAL PENUMPANG', 'STAFF TP 2 (Jadwal Kapal)', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'TERMINAL PENUMPANG', 'STAFF TP 2 (Jadwal Kapal)', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'DELL', 'TERMINAL PENUMPANG', 'STAFF TP 2 (Jadwal Kapal)', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER & SCANNER', 'EPSON', 'TERMINAL PENUMPANG', 'STAFF TP 2 (Jadwal Kapal)', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'DELL', 'TERMINAL PENUMPANG', 'STAFF TP 3', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'TERMINAL PENUMPANG', 'STAFF TP 3', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LOGITECH B-100', 'TERMINAL PENUMPANG', 'STAFF TP 3', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER & SCANNER', 'EPSON', 'TERMINAL PENUMPANG', 'STAFF TP 3', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'DELL', 'TERMINAL PENUMPANG', 'STAFF TP 1', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'LOGITECH', 'TERMINAL PENUMPANG', 'STAFF TP 1', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LOGITECH', 'TERMINAL PENUMPANG', 'STAFF TP 1', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'DELL', 'TERMINAL PENUMPANG', 'JOKO SASMITO', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'DELL', 'TERMINAL PENUMPANG', 'JOKO SASMITO', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'DELL', 'TERMINAL PENUMPANG', 'JOKO SASMITO', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SPEAKER (no brand)', NULL, 'TERMINAL PENUMPANG', 'JOKO SASMITO', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'DELL', 'TERMINAL PENUMPANG', 'GATE BOARDING', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0356-RJWA-2024', 'KEYBOARD', 'DELL', 'TERMINAL PENUMPANG', 'GATE BOARDING', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'TERMINAL PENUMPANG', 'GATE BOARDING', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'DELL', 'TERMINAL PENUMPANG', 'SEAPASS BOARDING', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'DELL', 'TERMINAL PENUMPANG', 'SEAPASS BOARDING', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'DELL', 'TERMINAL PENUMPANG', 'SEAPASS BOARDING', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SCANNER', 'SCANLOGIC', 'TERMINAL PENUMPANG', 'SEAPASS BOARDING', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'DELL', 'TERMINAL PENUMPANG', 'K3 FIRST AID', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'TERMINAL PENUMPANG', 'K3 FIRST AID', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'TERMINAL PENUMPANG', 'K3 FIRST AID', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SPEAKER', 'ADVANCE', 'TERMINAL PENUMPANG', 'K3 FIRST AID', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER', 'EPSON', 'TERMINAL PENUMPANG', 'K3 FIRST AID', 'RUSAK', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SCANNER', 'EPSON', 'TERMINAL PENUMPANG', 'K3 FIRST AID', 'BERFUNGSI', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'ROOTER', 'TP-Link 150 Mbps', 'TERMINAL PENUMPANG', 'K3 FIRST AID', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'LG (2)', 'TERMINAL PENUMPANG', 'PORT SECURITY TERMINAL PENUMPANG', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'TERMINAL PENUMPANG', 'PORT SECURITY TERMINAL PENUMPANG', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'TERMINAL PENUMPANG', 'PORT SECURITY TERMINAL PENUMPANG', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'HP & DELL (Dual Monitor)', 'JT SAMUDRA', 'GATE Timbangan 2', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'LOGITECH', 'JT SAMUDRA', 'GATE Timbangan 2', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LOGITECH', 'JT SAMUDRA', 'GATE Timbangan 2', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SCANNER BARCODE', 'SCANLOGIC', 'JT SAMUDRA', 'GATE Timbangan 2', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER NOTA', 'EPSON', 'JT SAMUDRA', 'GATE Timbangan 2', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'TIMBANGAN', 'SARTORIUS XS System Controller', 'JT SAMUDRA', 'GATE Timbangan 2', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG', 'JT NUSANTARA', 'GATE Timbangan 1', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'LOGITECH', 'JT NUSANTARA', 'GATE Timbangan 1', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'DELL', 'JT NUSANTARA', 'GATE Timbangan 1', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'TIMBANGAN', 'Maxxis 5', 'JT NUSANTARA', 'GATE Timbangan 1', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER NOTA', 'EPSON', 'JT NUSANTARA', 'GATE Timbangan 1', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'LENOVO', 'GATE NUSANTARA', 'CO NUSANTARA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'GATE NUSANTARA', 'CO NUSANTARA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LOGITECH', 'GATE NUSANTARA', 'CO NUSANTARA', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER NOTA', 'EPSON', 'GATE NUSANTARA', 'CO NUSANTARA', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'HP', 'GATE NUSANTARA', 'CO NUSANTARA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'LOGITECH', 'GATE NUSANTARA', 'CO NUSANTARA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0608-RJWA-2024', 'MOUSE', 'DELL', 'GATE NUSANTARA', 'CO NUSANTARA', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'DELL', 'CO PELDAM', 'STAFF PELDAM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0735-RJWA-2024', 'KEYBOARD', 'LENOVO', 'CO PELDAM', 'STAFF PELDAM', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0759-RJWA-2024', 'MOUSE', 'LOGITECH', 'CO PELDAM', 'STAFF PELDAM', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER & SCANNER', 'EPSON', 'CO PELDAM', 'STAFF PELDAM', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'LG & DELL (Dual Monitor)', 'CO PELDAM', 'POS OFFICE GATE PELDAM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0389-RJWA-2024', 'KEYBOARD', 'LOGITECH', 'CO PELDAM', 'POS OFFICE GATE PELDAM', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LOGITECH', 'CO PELDAM', 'POS OFFICE GATE PELDAM', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'HP, DELL & LENOVO (Triple Monitor)', 'GATE SAMUDERA 02', 'CO SAMUDERA 02', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0376-RJWA-2024', 'KEYBOARD', 'DELL', 'GATE SAMUDERA 02', 'CO SAMUDERA 02', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'GATE SAMUDERA 02', 'CO SAMUDERA 02', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SCANNER BARCODE', 'BLUEPRINT', 'GATE SAMUDERA 02', 'CO SAMUDERA 02', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER NOTA', 'EPSON', 'GATE SAMUDERA 02', 'CO SAMUDERA 02', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'LENOVO', 'RORO Pos Office', '03KOM-032PC', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'RORO Pos Office', '03KOM-032PC', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0416-RJWA-2024', 'MOUSE', 'HP', 'RORO Pos Office', '03KOM-032PC', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER NOTA', 'EPSON', 'RORO Pos Office', '03KOM-032PC', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual');
--
-- Database: `db_spmt_rendal_ops`
--
DROP DATABASE IF EXISTS `db_spmt_rendal_ops`;
CREATE DATABASE IF NOT EXISTS `db_spmt_rendal_ops` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_spmt_rendal_ops`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`) VALUES
(NULL, 'RENDAL OPS', 'PC', 'VENOM RX RAS-7D46', '03HSSE-001PC', 'ANDRID', 'Core i5 - 12400F', '16 GB', '500 GB', '10.3.7.180', 'Windows 10 Pro', 'LICENSED', 'MS Office Professional Plus 2019', 'LICENSED', NULL, 'AKTIF'),
('INV-KMU01-0772-RJWA-2024', 'RENDAL OPS', 'PC', 'DELL OPTIPLEX 3070', '03TEM2007018PSM', 'RIFA NUR A', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.7.199', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'RENDAL-OPS', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912025PSM', 'ANIS', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.7.117', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
('INV-KMU01-0569-RJWA-2024', 'RENDAL-OPS', 'PC', 'LENOVO IDEACENTRE 3', '03OPS-026PC', 'NINDA', 'Core i5 - 12400', '8 GB', '1,5 TB', '10.3.7.49', 'Windows 10 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'RENDAL-OPS', 'PC', 'HP 500-332X', '03SMI-006PC', 'RICHARD FERDINAN', 'Core i3 - 4150', '8 GB', '500 GB', '10.3.7.153', 'Windows 10 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'RENDAL-OPS', 'PC', 'HP ELITEDESK 800 G1 SFF', '03OPS-050PC', 'IDA', 'Core i7 - 6700', '16 GB', '1 TB', '10.3.7.190', 'Windows 10 Pro', 'LICENSED', 'MS Office LSTC Professional Plus 2021', 'LICENSED', NULL, 'AKTIF'),
(NULL, 'RENDAL-OPS', 'PC', 'HP ELITEDESK 800 G1 SFF', '03OPS-051PC', 'RAGIL', 'Core i7 - 6700', '16 GB', '500 GB', '10.3.7.119', 'Windows 10 Pro', 'LICENSED', 'MS Office LSTC Professional Plus 2021', 'LICENSED', NULL, 'AKTIF'),
('INV-KMU01-0601-RJWA-2024', 'RENDAL-OPS', 'PC', 'LENOVO IDEACENTRE 510-15ICB', '3OPS-006PC', 'WAHYU HENDRA', 'Core i5 - 9400', '12 GB', '1 TB', '10.183.25.62', 'Windows 11 Pro', 'LICENSED', 'MS Office Home and Business 2019', 'LICENSED', NULL, 'AKTIF'),
('INV-KMU01-0597-RJWA-2024', 'RENDAL-OPS', 'PC', 'DELL OPTIPLEX 3070', '03OPS-050PC', 'FERDI', 'Core i5 - 9500', '16 GB', '500 GB', '10.3.7.109', 'Windows 11 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;
--
-- Dumping data for table `perangkat_lain`
--

INSERT INTO `perangkat_lain` (`id_inventaris`, `jenis_perangkat`, `merk_perangkat`, `unit_kerja`, `user`, `status`, `gambar`, `created_at`, `updated_at`, `last_edited_at`, `sync_at`, `edit_source`) VALUES
(NULL, 'MONITOR', 'LG', 'RENDAL OPS', 'ANDRID', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0677-RJWA-2024', 'KEYBOARD', 'ASUS', 'RENDAL OPS', 'ANDRID', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0761-RJWA-2024', 'MOUSE', 'LOGITECH', 'RENDAL OPS', 'ANDRID', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SPEAKER', 'LOGI', 'RENDAL OPS', 'ANDRID', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'WEBCAM', 'LOGI', 'RENDAL OPS', 'ANDRID', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0749-RJWA-2024', 'MONITOR', 'DELL', 'RENDAL OPS', 'RIFA NUR A', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0736-RJWA-2024', 'KEYBOARD', 'LOGITECH', 'RENDAL OPS', 'RIFA NUR A', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0758-RJWA-2024', 'MOUSE', 'DELL', 'RENDAL OPS', 'RIFA NUR A', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0368-RJWA-2024', 'MONITOR', 'DELL', 'RENDAL-OPS', 'ANIS', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0397-RJWA-2024', 'KEYBOARD', 'DELL', 'RENDAL-OPS', 'ANIS', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0758-RJWA-2024', 'MOUSE', 'DELL', 'RENDAL-OPS', 'ANIS', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'DELL', 'RENDAL-OPS', 'NINDA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'RENDAL-OPS', 'NINDA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0576-RJWA-2024', 'MOUSE', 'DELL', 'RENDAL-OPS', 'NINDA', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'WEBCAM', 'JOVITEK', 'RENDAL-OPS', 'NINDA', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0678-RJWA-2024', 'MONITOR', 'LENOVO', 'RENDAL-OPS', 'RICHARD FERDINAN', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0566-RJWA-2024', 'KEYBOARD', 'LENOVO', 'RENDAL-OPS', 'RICHARD FERDINAN', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0572-RJWA-2024', 'MOUSE', 'LENOVO', 'RENDAL-OPS', 'RICHARD FERDINAN', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-PRT02-0036-RJWA-2024', 'SPEAKER', 'LOGI', 'RENDAL-OPS', 'RICHARD FERDINAN', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'HP', 'RENDAL-OPS', 'IDA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'RENDAL-OPS', 'IDA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'RENDAL-OPS', 'IDA', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER', 'SCANSNAP', 'RENDAL-OPS', 'IDA', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'WEBCAM', 'EYESEC', 'RENDAL-OPS', 'IDA', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'HP', 'RENDAL-OPS', 'RAGIL', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'HP', 'RENDAL-OPS', 'RAGIL', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'HP', 'RENDAL-OPS', 'RAGIL', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PRINTER', 'SCANSNAP', 'RENDAL-OPS', 'RAGIL', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'Printer EPSON', NULL, 'RENDAL-OPS', 'RAGIL', 'AKTIF', 'images/inv-printer.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0603-RJWA-2024', 'MONITOR', 'HP', 'RENDAL-OPS', 'WAHYU HENDRA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0553-RJWA-2024', 'KEYBOARD', 'DELL', 'RENDAL-OPS', 'WAHYU HENDRA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0604-RJWA-2024', 'MOUSE', 'DELL', 'RENDAL-OPS', 'WAHYU HENDRA', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'DELL', 'RENDAL-OPS', 'FERDI', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0598-RJWA-2024', 'KEYBOARD', 'DELL', 'RENDAL-OPS', 'FERDI', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0600-RJWA-2024', 'MOUSE', 'DELL', 'RENDAL-OPS', 'FERDI', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual');
--
-- Database: `db_spmt_ruang_rapat_dan_branch_manager`
--
DROP DATABASE IF EXISTS `db_spmt_ruang_rapat_dan_branch_manager`;
CREATE DATABASE IF NOT EXISTS `db_spmt_ruang_rapat_dan_branch_manager` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_spmt_ruang_rapat_dan_branch_manager`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`) VALUES
('INV-KMU01-0671-RJWA-2024', 'RECEPTIONIST', 'PC', 'HP OMNI 200 PC', '03SMK-018PC', 'KUSUMAWARDANI', 'Pentium (R) Dual Core', '4 GB', '500 GB', '10.3.10.75', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
('INV-KMU01-0392-RJWA-2024', 'RAPAT TANJUNG TEMBAGA', 'PC', 'HP 280 G2 SFF', '03OPERASI-040PC', 'RAPAT', 'Core i3 - 6100', '8 GB', '500 GB', NULL, 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'OPSROOM TENAU KUPANG', 'PC', 'DELL OPTIPLEX 3070', '01PBJ1912041PSM', 'RAPAT OPSROOM', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.10.150', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'RAPAT GM', 'PC', 'ASUS', '03TI-001PC', 'RAPAT GM', 'AMD RYZEN 7 7735HS', '16 GB', '1 TB', '10.3.10.218', 'Windows 11 Pro', 'LICENSED', 'MS Office LTSC Professional Plus 2021', 'UNLICENSED', NULL, 'AKTIF'),
('INV-KMU01-0724-RJWA-2024', 'GM', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912009PSM', 'GM', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.10.167', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
('INV-KMU01-0723-RJWA-2024', 'GM', 'PC', 'DELL', 'DESKTOP-NEO047SP', 'MONITOR CCTV GM', 'Core i5 - 10400', '8 GB', '500 GB', '10.3.10.200', 'Windows 11 Pro', 'LICENSED', 'MS Office Professional Plus 2019', 'LICENSED', NULL, 'AKTIF'),
('INV-KMU01-0725-RJWA-2024', 'SEKRETARIS', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912049PSM', 'SEKRETARIS GM', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.10.159', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF'),
(NULL, 'SEKRETARIS', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912011PSM', 'SEKRETARIS GM', 'Core i5 - 9500', '8 GB', '500 GB', '127.0.0.1', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;
--
-- Dumping data for table `perangkat_lain`
--

INSERT INTO `perangkat_lain` (`id_inventaris`, `jenis_perangkat`, `merk_perangkat`, `unit_kerja`, `user`, `status`, `gambar`, `created_at`, `updated_at`, `last_edited_at`, `sync_at`, `edit_source`) VALUES
('INV-KMU01-0671-RJWA-2024', 'MONITOR', 'HP OMNI 200', 'RECEPTIONIST', 'KUSUMAWARDANI', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'DELL', 'RECEPTIONIST', 'KUSUMAWARDANI', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LENOVO', 'RECEPTIONIST', 'KUSUMAWARDANI', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'LENOVO', 'RAPAT TANJUNG TEMBAGA', 'RAPAT', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'LOGITECH', 'RAPAT TANJUNG TEMBAGA', 'RAPAT', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0394-RJWA-2024', 'MOUSE', 'DELL', 'RAPAT TANJUNG TEMBAGA', 'RAPAT', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PROYEKTOR', 'EPSON', 'RAPAT TANJUNG TEMBAGA', 'RAPAT', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'CLICKER', 'MIKUSO', 'RAPAT TANJUNG TEMBAGA', 'RAPAT', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'WEBCAM', 'LOGI HD 720p', 'RAPAT TANJUNG TEMBAGA', 'RAPAT', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'SAMSUNG', 'OPSROOM TENAU KUPANG', 'RAPAT OPSROOM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0673-RJWA-2024', 'KEYBOARD', 'LOGITECH', 'OPSROOM TENAU KUPANG', 'RAPAT OPSROOM', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LOGITECH', 'OPSROOM TENAU KUPANG', 'RAPAT OPSROOM', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SOUND', 'YAMAHA', 'OPSROOM TENAU KUPANG', 'RAPAT OPSROOM', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'WEBCAM', 'JETE', 'OPSROOM TENAU KUPANG', 'RAPAT OPSROOM', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PROYEKTOR', 'EPSON', 'OPSROOM TENAU KUPANG', 'RAPAT OPSROOM', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MONITOR', 'TCL', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'LOGITECH', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'LOGITECH', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-PRT02-0033-RJWA-2024', 'SOUND', 'JBL', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'REMOTE MONITOR', 'TCL', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'REMOTE CAMERA', 'POLYCOM', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'REMOTE SPEAKER', 'JBL', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'Wireless HDMI (2)', NULL, 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'PERANGKAT ZOOM', 'POLYCOM', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-AKM-0029-RJWA-2024', 'CAMERA', 'POLYCOM', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'WI-FI', 'Tp-Link', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'SWITCHER', 'Tp-Link', 'RAPAT GM', 'RAPAT GM', 'AKTIF', 'images/inv-default.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0716-RJWA-2024', 'MONITOR', 'DELL', 'GM', 'GM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0708-RJWA-2024', 'KEYBOARD', 'DELL', 'GM', 'GM', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0722-RJWA-2024', 'MOUSE', 'DELL', 'GM', 'GM', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0707-RJWA-2024', 'WEBCAM', 'LOGI', 'GM', 'GM', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-PRT01-0324-RJWA-2024', 'MONITOR', 'LG', 'GM', 'MONITOR CCTV GM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0712-RJWA-2024', 'KEYBOARD', 'LOGITECH', 'GM', 'MONITOR CCTV GM', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0721-RJWA-2024', 'MOUSE', 'LOGITECH', 'GM', 'MONITOR CCTV GM', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0715-RJWA-2024', 'MONITOR', 'DELL', 'SEKRETARIS', 'SEKRETARIS GM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0709-RJWA-2024', 'KEYBOARD', 'DELL', 'SEKRETARIS', 'SEKRETARIS GM', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0719-RJWA-2024', 'MOUSE', 'LOGITECH', 'SEKRETARIS', 'SEKRETARIS GM', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
('INV-KMU01-0717-RJWA-2024', 'MONITOR', 'DELL', 'SEKRETARIS', 'SEKRETARIS GM', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'KEYBOARD', 'DELL', 'SEKRETARIS', 'SEKRETARIS GM', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual'),
(NULL, 'MOUSE', 'DELL', 'SEKRETARIS', 'SEKRETARIS GM', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, NULL, 'manual');
--
-- Database: `db_spmt_spjm`
--
DROP DATABASE IF EXISTS `db_spmt_spjm`;
CREATE DATABASE IF NOT EXISTS `db_spmt_spjm` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_spmt_spjm`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `inventory_order` bigint(20) DEFAULT NULL,
  `inventory_created_at` datetime DEFAULT current_timestamp(),
  `inventory_updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`, `inventory_order`, `inventory_created_at`, `inventory_updated_at`) VALUES
('INV-KMU01-0577-RJWA-2024', 'SPJM', 'PC', 'HP 280 G4 MT', 'Putri PC', 'Putri Rossa', 'Core i3 - 9500', '8 GB', '256 GB', '10.12.34.123', 'WINDOWS 10 PRO', 'LICENSED', 'MS Office Professional Plus 2013', 'LICENSED', 'uploads/inventory/pc-20260503152528-4240fe62.png', 'AKTIF', NULL, '2026-05-03 20:24:10', '2026-05-03 20:25:28'),
('INV-KMU01-0653-RJWA-2024', 'Kepanduan', 'PC', 'DELL OPTIPLEX 3070', '03POCC-002PC', 'Putri Ananta', 'Core i5 - 9400', '8 GB', '256 GB', '10.12.32.120', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', 'uploads/inventory/pc-20260503152831-a603c7a0.png', 'AKTIF', 1, '2026-05-03 20:28:31', '2026-05-03 20:28:31');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;
--
-- Dumping data for table `perangkat_lain`
--

INSERT INTO `perangkat_lain` (`id_inventaris`, `jenis_perangkat`, `merk_perangkat`, `unit_kerja`, `user`, `status`, `gambar`, `created_at`, `updated_at`, `last_edited_at`, `sync_at`, `edit_source`) VALUES
('INV-KWU-RJWA', 'MOUSE', 'LENOVO', 'SPJM', 'Putri Rossa', 'AKTIF', 'uploads/inventory/mouse-20260503152448-174edff7.png', '2026-05-03 20:24:48', '2026-05-03 20:25:28', NULL, '2026-05-03 20:25:28', 'pc_user_sync'),
('INV-KMU01-0578-RJWA-2024', 'MONITOR', 'LENOVO', 'Kepanduan', 'Putri Ananta', 'AKTIF', 'uploads/inventory/monitor-20260503154125-56b785e6.png', '2026-05-03 20:41:25', '2026-05-03 20:41:25', NULL, NULL, 'manual');
--
-- Database: `db_spmt_teknik_dan_it`
--
DROP DATABASE IF EXISTS `db_spmt_teknik_dan_it`;
CREATE DATABASE IF NOT EXISTS `db_spmt_teknik_dan_it` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_spmt_teknik_dan_it`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `inventory_order` bigint(20) DEFAULT NULL,
  `inventory_created_at` datetime DEFAULT current_timestamp(),
  `inventory_updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`, `inventory_order`, `inventory_created_at`, `inventory_updated_at`, `created_at`, `updated_at`, `last_edited_at`, `sync_at`, `edit_source`) VALUES
('INV-KMU01-0654-RJWA-2024', 'TEKNIK', 'PC', 'DELL OPTIPLEX 3070', '03TEKNIK-022PC', 'EMMA', 'Core i5 - 9500', '16 GB', '1,5 TB', '10.3.11.52', 'Windows 11 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0652-RJWA-2024', 'TEKNIK', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912028PSM', 'AGUS TRI', 'Core i5 - 9500', '16 GB', '500 GB', '10.3.11.135', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0653-RJWA-2024', 'TEKNIK', 'PC', 'LENOVO ideacentre 300S-08IHH', '03TEKNIK-019PC', 'WINAR DHENY', 'Core i3 - 4170', '8 GB', '1 TB', '10.3.11.106', 'Windows 10 Pro', 'LICENSED', 'MS Office Professional Plus 2013', 'LICENSED', 'images/inv-pc.png', 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-05-01 15:17:40', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0655-RJWA-2024', 'TEKNIK', 'PC', 'DELL OPTIPLEX 3070', '03TEM2007013PSM', 'TISDA FANERFA', 'Core i5 - 9500', '8 GB', '500 GB', '10.3.11.109', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'LICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0656-RJWA-2024', 'TEKNIK', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912014PSM', 'TRI WAHYUDI', 'Core i5 - 6500', '16 GB', '500 GB', '10.3.11.51', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0688-RJWA-2024', 'IT', 'PC', 'DELL OPTIPLEX 3070', '03TPS1912029PSM', 'FENI RINASARI', 'Core i5 - 9500', '12 GB', '500 GB', '10.183.27.113', 'Windows 10 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'IT', 'PC', 'HP', 'ADITYA', 'ADITYA ARI S', 'Core i7 - 8700', '32 GB', '1 TB', '10.183.25.11', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0689-RJWA-2024', 'IT', 'PC', 'HP', '03TI-125PC', 'SUTRYONO', 'Core i7 - 8700', '8 GB', '500 GB', '10.183.27.205', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0685-RJWA-2024', 'IT', 'PC', 'HP', '03TI-057PC', 'BAYU AGUS', 'Core i5 - 9500', '16 GB', '1 TB', '10.3.2.7', 'Windows 11 Pro', 'LICENSED', 'MS Office 2013', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0687-RJWA-2024', 'IT', 'PC', 'DELL OPTIPLEX 3070', '05TIK-0042PC', '-', 'Core i5 - 12400F', '8 GB', '500 GB', '192.168.56.1', 'Windows 11 Pro', 'LICENSED', 'MS Office Proffesional Plus 2019', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'IT', 'PC', 'NEMESIS NYK SCYLLA T10', '03TI-000PC', 'MONITOR CCTV', 'Core i5 - 12400', '16 GB', '1,5 TB', '10.3.2.222', 'Windows 11 Pro', 'LICENSED', 'MS Office Proffesional Plus 2021', 'UNLICENSED', NULL, 'AKTIF', NULL, '2026-05-01 15:29:54', '2026-05-01 15:29:54', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KWU-0374-RJWA-2024', 'TEKNIK', 'PC', 'LENOVO ideacentre 300S-08IHH', 'Putri PC', 'Putri Rossa', 'Core i5 - 9500', '16 GB', '500 GB', '10.12.34.123', 'WINDOWS 11 PRO', 'LICENSED', 'MS Office Plus 2021', 'UNLICENSED', 'uploads/inventory/pc-20260501103821-422949ff.png', 'AKTIF', 1, '2026-05-01 15:38:21', '2026-05-01 15:42:06', '2026-05-01 15:38:21', '2026-05-01 15:42:06', NULL, NULL, 'manual');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;
--
-- Dumping data for table `perangkat_lain`
--

INSERT INTO `perangkat_lain` (`id_inventaris`, `jenis_perangkat`, `merk_perangkat`, `unit_kerja`, `user`, `status`, `gambar`, `created_at`, `updated_at`, `last_edited_at`, `sync_at`, `edit_source`) VALUES
(NULL, 'MONITOR', 'LENOVO', 'TEKNIK', 'EMMA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0788-RJWA-2024', 'KEYBOARD', 'LENOVO', 'TEKNIK', 'EMMA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0647-RJWA-2024', 'MONITOR', 'DELL', 'TEKNIK', 'AGUS TRI', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0637-RJWA-2024', 'KEYBOARD', 'DELL', 'TEKNIK', 'AGUS TRI', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0642-RJWA-2024', 'MOUSE', 'DELL', 'TEKNIK', 'AGUS TRI', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'WEBCAM', 'M-TECH', 'TEKNIK', 'AGUS TRI', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0648-RJWA-2024', 'MONITOR', 'LENOVO', 'TEKNIK', 'WINAR DHENY', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-05-01 15:17:40', NULL, '2026-05-01 15:17:40', 'pc_user_sync'),
('INV-KMU01-0638-RJWA-2024', 'KEYBOARD', 'LENOVO', 'TEKNIK', 'WINAR DHENY', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-05-01 15:17:40', NULL, '2026-05-01 15:17:40', 'pc_user_sync'),
('INV-KMU01-0643-RJWA-2024', 'MOUSE', 'LENOVO', 'TEKNIK', 'WINAR DHENY', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-05-01 15:17:40', NULL, '2026-05-01 15:17:40', 'pc_user_sync'),
(NULL, 'WEBCAM', 'OneSOS Webcam 1080 HD', 'TEKNIK', 'WINAR DHENY', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-05-01 15:17:40', NULL, '2026-05-01 15:17:40', 'pc_user_sync'),
('INV-KMU01-0650-RJWA-2024', 'MONITOR', 'DELL', 'TEKNIK', 'TISDA FANERFA', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0640-RJWA-2024', 'KEYBOARD', 'DELL', 'TEKNIK', 'TISDA FANERFA', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0651-RJWA-2024', 'MONITOR', 'DELL', 'TEKNIK', 'TRI WAHYUDI', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0641-RJWA-2024', 'KEYBOARD', 'DELL', 'TEKNIK', 'TRI WAHYUDI', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0646-RJWA-2024', 'MOUSE', 'LOGITECH', 'TEKNIK', 'TRI WAHYUDI', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'WEBCAM', 'OneSOS Webcam 1080 HD', 'TEKNIK', 'TRI WAHYUDI', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0631-RJWA-2024', 'MONITOR', 'LG', 'IT', 'FENI RINASARI', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0693-RJWA-2024', 'KEYBOARD', 'LOGITECH', 'IT', 'FENI RINASARI', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0700-RJWA-2024', 'MOUSE', 'LOGITECH', 'IT', 'FENI RINASARI', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'SPEAKER', 'LOGI', 'IT', 'FENI RINASARI', 'AKTIF', 'images/inv-speaker.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'MONITOR', 'HP', 'IT', 'ADITYA ARI S', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'KEYBOARD', 'HP', 'IT', 'ADITYA ARI S', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'MOUSE', 'HP', 'IT', 'ADITYA ARI S', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0695-RJWA-2024', 'MONITOR', 'LG', 'IT', 'SUTRYONO', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0694-RJWA-2024', 'KEYBOARD', 'LOGITECH', 'IT', 'SUTRYONO', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0702-RJWA-2024', 'MOUSE', 'LOGITECH', 'IT', 'SUTRYONO', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0696-RJWA-2024', 'MONITOR', 'LG', 'IT', 'BAYU AGUS', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0690-RJWA-2024', 'KEYBOARD', 'ASUS', 'IT', 'BAYU AGUS', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0698-RJWA-2024', 'MOUSE', 'LOGITECH', 'IT', 'BAYU AGUS', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0697-RJWA-2024', 'MONITOR', 'LG', 'IT', '-', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0692-RJWA-2024', 'KEYBOARD', 'LOGITECH', 'IT', '-', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
('INV-KMU01-0701-RJWA-2024', 'MOUSE', 'HP', 'IT', '-', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'WEBCAM', 'LOGI', 'IT', '-', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'MONITOR', 'LG', 'IT', 'MONITOR CCTV', 'AKTIF', 'images/inv-monitor.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'KEYBOARD', 'LENOVO', 'IT', 'MONITOR CCTV', 'AKTIF', 'images/inv-keyboard.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'MOUSE', 'LENOVO', 'IT', 'MONITOR CCTV', 'AKTIF', 'images/inv-mouse.png', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import'),
(NULL, 'WEBCAM', 'LOGI', 'IT', 'MONITOR CCTV', 'AKTIF', 'images/inv-webcam.jpg', '2026-04-30 13:10:31', '2026-04-30 13:10:31', NULL, '2026-04-30 13:10:31', 'import');
--
-- Database: `db_subreg_divisi_keuangan_finance`
--
DROP DATABASE IF EXISTS `db_subreg_divisi_keuangan_finance`;
CREATE DATABASE IF NOT EXISTS `db_subreg_divisi_keuangan_finance` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_subreg_divisi_keuangan_finance`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`) VALUES
(NULL, 'KEUANGAN', 'PC', 'HP 280 G4 MT', '03POCC-003PC', 'TRI SUNARNI', 'Core i5 - 9500', '16 GB DDR4', '1 TB (SSD SATA)', NULL, 'Windows 11 PRO', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'KEUANGAN', 'PC', 'HP 280 G4 MT', '03POCC-008PC', 'FITRI RACHMIATI', 'Core i5 - 9500', '16 GB DDR4', '1 TB (SSD SATA)', NULL, 'Windows 11 PRO', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'KEUANGAN', 'PC', 'HP 280 G4 MT', '03POCC-007PC', 'DINA PARAMITA', 'Core i5 - 9500', '16 GB DDR4', '1 TB (SSD SATA)', NULL, 'Windows 11 PRO', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'KEUANGAN', 'PC', 'HP 280 G4 MT', '03POCC-005PC', 'IKA BAGUS L', 'Core i5 - 9500', '16 GB DDR4', '1 TB (SSD SATA)', NULL, 'Windows 11 PRO', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'KEUANGAN', 'PC', 'DELL OPTIPLEX 3070', '03TPS1912005PSM', 'PEMAGANG', 'Core i5 - 9500', '8 GB DDR4', '500 GB', NULL, 'Windows 10 PRO', 'LICENSED', 'MS Office 2013', NULL, NULL, 'AKTIF'),
(NULL, 'KEUANGAN', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912023PSM', 'PEMAGANG', 'Core i5 - 9500', '8 GB DDR4', '500 GB', NULL, 'Windows 10 PRO', 'LICENSED', 'MS Office 2013', NULL, NULL, 'AKTIF'),
(NULL, 'KEUANGAN', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912007PSM', 'PEMAGANG', 'Core i5 - 9500', '4+4 GB DDR4', '500 GB', NULL, 'Windows 10 PRO', 'LICENSED', 'MS Office 2013', NULL, NULL, 'AKTIF'),
(NULL, 'KEUANGAN', 'PC', 'DELL OPTIPLEX 3070', '03KEU-025PC', 'PEMAGANG', 'Core i5 - 9500', '8 GB DDR4', '500 GB', NULL, 'Windows 10 PRO', 'LICENSED', 'MS Office 2013', NULL, NULL, 'AKTIF');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;--
-- Database: `db_subreg_integrated_pnc`
--
DROP DATABASE IF EXISTS `db_subreg_integrated_pnc`;
CREATE DATABASE IF NOT EXISTS `db_subreg_integrated_pnc` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_subreg_integrated_pnc`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`) VALUES
(NULL, 'PNC Subreg', 'PC', 'HP Pro SFF 400 G9', '03PNC-003PC', 'KO POCC -SUBREG', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2019', NULL, NULL, 'AKTIF'),
(NULL, 'PNC Subreg', 'PC', 'HP Pro SFF 400 G9', '03PNC-004PC', 'M. FUADI JUPRI - SUBREG', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2019', NULL, NULL, 'AKTIF'),
(NULL, 'PNC Subreg', 'PC', 'HP Pro SFF 400 G9', '03PNC-005PC', 'INGRID ADHI -SUBREG', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2019', NULL, NULL, 'AKTIF'),
(NULL, 'PNC Subreg', 'PC', 'HP Pro SFF 400 G9', '03PNC-006PC', 'AYU WURY - SUBREG', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2019', NULL, NULL, 'AKTIF'),
(NULL, 'PNC Subreg', 'PC', 'HP Pro SFF 400 G9', '03PNC-007PC', 'RISKI PUJI - SUBREG', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2019', NULL, NULL, 'AKTIF'),
(NULL, 'PNC Subreg', 'PC', 'HP Pro SFF 400 G9', '03PNC-008PC', 'JULIUS ANWAR - SUBREG', 'Core i5 - 12500', '8 GB DDR4 (3200Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2019', NULL, NULL, 'AKTIF');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;--
-- Database: `db_subreg_properti_sdm_umum`
--
DROP DATABASE IF EXISTS `db_subreg_properti_sdm_umum`;
CREATE DATABASE IF NOT EXISTS `db_subreg_properti_sdm_umum` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_subreg_properti_sdm_umum`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`) VALUES
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-101PC', 'SUSIANA KETY MAIT', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-103PC', 'SRI WAHYUNINGSIH', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-102PC', 'ESTY ARYANI', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-104PC', 'WIYOSI', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-105PC', 'INDIRA H', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-106PC', 'CAHAYA', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-107PC', 'HERY SUSANTO', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO IdeaCentre 510', '03OPERASI-045PC', 'PEMAGANG', 'Core i5 - 9400', '4+4 GB DDR4', '500 GB', NULL, 'Windows 10 Pro 64 bit', 'LICENSED', 'Professional Plus 2013', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO 10123', 'PCADMIN02', 'PC ADMIN RTK', 'Intel Pentium', '2 GB', '500 GB', NULL, 'Windows 7 Professional 32 bit SP1', 'LICENSED', 'Professional Plus 2013', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-108PC', 'ANASTASIA', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-109PC', 'ENDRY WAHYU', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-110PC', 'SRI RAHAYU YULIANTI', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-111PC', 'SRI RAHAYU', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-112PC', 'NUR LAILY', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-113PC', 'MIRA EKA PUTRI', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'SDM UMUM PROPERTI', 'PC', 'LENOVO ThinkCentre M70t', '03KOM-114PC', 'HERDYAN PURWANDITO', 'Core i5 - 14400', '8 GB DDR5 (4800Mhz)', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;--
-- Database: `db_subreg_teknik`
--
DROP DATABASE IF EXISTS `db_subreg_teknik`;
CREATE DATABASE IF NOT EXISTS `db_subreg_teknik` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_subreg_teknik`;

-- --------------------------------------------------------

--
-- Table structure for table `pc`
--

DROP TABLE IF EXISTS `pc`;
CREATE TABLE IF NOT EXISTS `pc` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `computer_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `kapasitas_harddisk` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sistem_operasi` varchar(255) DEFAULT NULL,
  `licensed_windows` varchar(255) DEFAULT NULL,
  `microsoft_office` varchar(255) DEFAULT NULL,
  `licensed_office` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `pc`
--

TRUNCATE TABLE `pc`;
--
-- Dumping data for table `pc`
--

INSERT INTO `pc` (`id_inventaris`, `unit_kerja`, `jenis_perangkat`, `merk_perangkat`, `computer_name`, `user`, `processor`, `ram`, `kapasitas_harddisk`, `ip_address`, `sistem_operasi`, `licensed_windows`, `microsoft_office`, `licensed_office`, `gambar`, `status`) VALUES
(NULL, 'TEKNIK', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912015PSM', 'PEMAGANG', 'Core i5 - 9500', '8 GB DDR4', '500 GB', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'Standard 2013', NULL, NULL, 'AKTIF'),
(NULL, 'TEKNIK', 'PC', 'DELL OPTIPLEX 3070', '03TEM1912038PSM', 'REZA ARFANY', 'Core i5 - 9500', '8 GB DDR4', '500 GB', NULL, 'Windows 10 Pro 64 bit', 'LICENSED', 'Standard 2013', NULL, NULL, 'AKTIF'),
(NULL, 'TEKNIK', 'PC', 'LENOVO LOQ', '03TEK-102PC', 'HIZKIA PANDEGA', 'Core i7 - 14xxx', '16 GB DDR5', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'TEKNIK', 'PC', 'LENOVO LOQ', '03TEK-100PC', 'ARTSSWINDA BUNGA', 'Core i7 - 14xxx', '16 GB DDR5', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF'),
(NULL, 'TEKNIK', 'PC', 'LENOVO LOQ', '03TEK-101PC', 'BIBIT K', 'Core i7 - 14xxx', '16 GB DDR5', '512 GB (SSD NVME)', NULL, 'Windows 11 Pro 64 bit', 'LICENSED', 'MS Office 2016', NULL, NULL, 'AKTIF');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat_lain`
--

DROP TABLE IF EXISTS `perangkat_lain`;
CREATE TABLE IF NOT EXISTS `perangkat_lain` (
  `id_inventaris` varchar(255) DEFAULT NULL,
  `jenis_perangkat` varchar(255) DEFAULT NULL,
  `merk_perangkat` varchar(255) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'AKTIF',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_edited_at` datetime DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `edit_source` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `perangkat_lain`
--

TRUNCATE TABLE `perangkat_lain`;SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
