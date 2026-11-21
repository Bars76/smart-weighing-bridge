-- ============================================================
-- SMART WEIGHING BRIDGE - Database Schema (PRD v1.1)
-- Database: smart_weighing
-- ============================================================

CREATE DATABASE IF NOT EXISTS smart_weighing
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_weighing;

-- ------------------------------------------------------------
-- 6.1 Tabel Users (akun Admin & Operator)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id_user      INT AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(50)  NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  role         ENUM('admin','operator') NOT NULL DEFAULT 'operator',
  status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  dibuat_pada  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6.2 Tabel Kendaraan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kendaraan (
  id_kendaraan     INT AUTO_INCREMENT PRIMARY KEY,
  nomor_kendaraan  VARCHAR(20)  NOT NULL UNIQUE,
  nama_kendaraan   VARCHAR(100) NOT NULL,
  jenis_kendaraan  VARCHAR(50)  NOT NULL,
  uid_rfid         VARCHAR(50)  NOT NULL UNIQUE,
  status           ENUM('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  dibuat_pada      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6.3 Tabel Penimbangan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS penimbangan (
  id_penimbangan     INT AUTO_INCREMENT PRIMARY KEY,
  id_kendaraan       INT NOT NULL,
  berat              DECIMAL(10,2) NOT NULL COMMENT 'gram',
  batas_berat        DECIMAL(10,2) NOT NULL COMMENT 'gram',
  status_berat       ENUM('Sesuai','Berlebih') NOT NULL,
  waktu_penimbangan  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_penimbangan_kendaraan FOREIGN KEY (id_kendaraan)
    REFERENCES kendaraan(id_kendaraan) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_waktu (waktu_penimbangan),
  INDEX idx_kendaraan (id_kendaraan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6.4 Tabel Pengaturan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pengaturan (
  id_pengaturan     INT AUTO_INCREMENT PRIMARY KEY,
  nama_pengaturan   VARCHAR(100) NOT NULL UNIQUE,
  nilai_pengaturan  VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA AWAL
-- ============================================================

-- Akun default. Ganti password setelah login pertama kali.
-- admin    / admin123     -> $2y$10$MnTUyWi6cpaE7h1QoXs9.O.PZtdx9qARS5WHCN710sahLOA/pSpr.
-- operator / operator123  -> $2y$10$GO1lPimqMGEXxMS1/orj7eNJwaD3wnLBSmU3xLzrh0AG6zUh6Jsei
INSERT INTO users (username, password, nama_lengkap, role, status) VALUES
  ('admin',    '$2y$10$MnTUyWi6cpaE7h1QoXs9.O.PZtdx9qARS5WHCN710sahLOA/pSpr.', 'Administrator', 'admin',    'aktif'),
  ('operator', '$2y$10$GO1lPimqMGEXxMS1/orj7eNJwaD3wnLBSmU3xLzrh0AG6zUh6Jsei', 'Operator Timbang', 'operator', 'aktif')
ON DUPLICATE KEY UPDATE username = username;

-- Column tetap ada untuk kompatibilitas, tapi tidak dipakai (tidak dipaksa ganti).
ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
UPDATE users SET must_change_password = 0 WHERE username IN ('admin', 'operator');

-- Batas berat default: 5000 gram (sesuai kapasitas Load Cell 5 kg).
INSERT INTO pengaturan (nama_pengaturan, nilai_pengaturan) VALUES
  ('batas_berat', '5000')
ON DUPLICATE KEY UPDATE nilai_pengaturan = nilai_pengaturan;

-- Status koneksi ESP32. Tanggal lama = "Terputus" sampai perangkat kirim heartbeat.
-- INSERT IGNORE agar import ulang tidak menimpa waktu heartbeat terakhir.
INSERT IGNORE INTO pengaturan (nama_pengaturan, nilai_pengaturan) VALUES
  ('esp32_last_seen', '1970-01-01 00:00:00'),
  ('esp32_device', 'esp32-1');

-- ------------------------------------------------------------
-- 6.5 Status live (satu baris, ditimpa setiap update ESP32)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS live_status (
  id            TINYINT(1)      PRIMARY KEY DEFAULT 1,
  berat         DECIMAL(10,2)   NOT NULL DEFAULT 0,
  state         VARCHAR(24)     NOT NULL DEFAULT 'STANDBY',
  palang_masuk  TINYINT(1)      NOT NULL DEFAULT 0,
  palang_keluar TINYINT(1)      NOT NULL DEFAULT 0,
  uid_rfid      VARCHAR(50)     NOT NULL DEFAULT '',
  rssi          INT             DEFAULT NULL,
  uptime_ms     BIGINT          NOT NULL DEFAULT 0,
  diperbarui    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO live_status (id) VALUES (1);

-- Kendaraan contoh (UID RFID contoh).
INSERT INTO kendaraan (nomor_kendaraan, nama_kendaraan, jenis_kendaraan, uid_rfid, status) VALUES
  ('B 1234 ABC', 'Mobil Pengangkut A', 'Truck'    , 'A1B2C3D4', 'Aktif'),
  ('B 5678 DEF', 'Mobil Pengangkut B', 'Pickup'   , 'E5F6A7B8', 'Aktif'),
  ('B 9012 GHI', 'Mobil Pengangkut C', 'Box'      , 'C9D0E1F2', 'Aktif'),
  ('B 3456 JKL', 'Mobil Pengangkut D', 'Truck'    , 'B4A5C6D7', 'Aktif')
ON DUPLICATE KEY UPDATE nomor_kendaraan = nomor_kendaraan;

-- Riwayat penimbangan contoh agar dashboard tidak kosong.
INSERT INTO penimbangan (id_kendaraan, berat, batas_berat, status_berat, waktu_penimbangan)
SELECT k.id_kendaraan, v.berat, v.batas, IF(v.berat > v.batas, 'Berlebih', 'Sesuai'), v.waktu
FROM kendaraan k
JOIN (
  SELECT 'A1B2C3D4' AS uid, 3200.00 AS berat, 5000.00 AS batas, '2026-11-20 08:15:00' AS waktu
  UNION ALL SELECT 'E5F6A7B8', 4800.00, 5000.00, '2026-11-20 09:42:00'
  UNION ALL SELECT 'C9D0E1F2', 5600.00, 5000.00, '2026-11-20 10:05:00'
  UNION ALL SELECT 'B4A5C6D7', 2100.00, 5000.00, '2026-11-20 11:30:00'
  UNION ALL SELECT 'A1B2C3D4', 4100.00, 5000.00, '2026-11-21 07:55:00'
) v ON v.uid = k.uid_rfid
WHERE NOT EXISTS (SELECT 1 FROM penimbangan p WHERE p.id_kendaraan = k.id_kendaraan AND p.waktu_penimbangan = v.waktu);
