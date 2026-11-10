-- =============================================================================
-- Smart Weighing Bridge Database Schema
-- Database: smart_weighing_bridge
-- Impor melalui phpMyAdmin atau CLI MySQL
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+07:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Database: `smart_weighing_bridge`
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `smart_weighing_bridge` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smart_weighing_bridge`;

-- --------------------------------------------------------
-- Tabel: users (akun administrator)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','operator') DEFAULT 'operator',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: vehicles (data kendaraan terdaftar)
-- --------------------------------------------------------
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` varchar(20) NOT NULL,
  `uid` varchar(50) NOT NULL,
  `plate` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vehicle_id` (`vehicle_id`),
  UNIQUE KEY `uk_uid` (`uid`),
  UNIQUE KEY `uk_plate` (`plate`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: weighings (transaksi penimbangan)
-- --------------------------------------------------------
CREATE TABLE `weighings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(30) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `uid` varchar(50) NOT NULL,
  `plate` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `weight_kg` decimal(10,2) NOT NULL,
  `weighed_at` datetime NOT NULL,
  `status` enum('weighed','incomplete','void') DEFAULT 'weighed',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_transaction_code` (`transaction_code`),
  KEY `fk_weighing_vehicle` (`vehicle_id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_plate` (`plate`),
  KEY `idx_weighed_at` (`weighed_at`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_weighing_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: devices (perangkat IoT terdaftar)
-- --------------------------------------------------------
CREATE TABLE `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_name` varchar(100) NOT NULL,
  `device_token` varchar(255) NOT NULL,
  `last_seen` datetime DEFAULT NULL,
  `connection_status` enum('online','offline','unknown') DEFAULT 'unknown',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_device_token` (`device_token`),
  KEY `idx_connection_status` (`connection_status`),
  KEY `idx_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: device_logs (log status komponen perangkat)
-- --------------------------------------------------------
CREATE TABLE `device_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `component_name` varchar(50) NOT NULL,
  `component_status` enum('active','inactive','unknown','open','closed') DEFAULT 'unknown',
  `recorded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_device_log_device` (`device_id`),
  KEY `idx_recorded_at` (`recorded_at`),
  CONSTRAINT `fk_device_log_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Data admin default (username: admin, password: password)
-- Ganti password segera setelah login pertama kali!
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;