-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for rumah_sewa
CREATE DATABASE IF NOT EXISTS `rumah_sewa` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `rumah_sewa`;

-- Jadual induk mesti wujud sebelum jadual bayaran yang mempunyai foreign key.
-- Bahagian ini juga membolehkan dump diimport terus ke database yang masih kosong.
CREATE TABLE IF NOT EXISTS `penyewa` (
  `id_penyewa` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `no_ic` varchar(20) NOT NULL,
  `no_telefon` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_penyewa`),
  UNIQUE KEY `no_ic` (`no_ic`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `rumah` (
  `id_rumah` int NOT NULL AUTO_INCREMENT,
  `no_rumah` varchar(20) DEFAULT NULL,
  `harga_sewa` decimal(10,2) DEFAULT NULL,
  `status` enum('Kosong','Disewa') DEFAULT 'Kosong',
  PRIMARY KEY (`id_rumah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `sewa` (
  `id_sewa` int NOT NULL AUTO_INCREMENT,
  `id_penyewa` int DEFAULT NULL,
  `id_rumah` int DEFAULT NULL,
  `tarikh_masuk` date DEFAULT NULL,
  `deposit` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_sewa`),
  KEY `id_penyewa` (`id_penyewa`),
  KEY `id_rumah` (`id_rumah`),
  CONSTRAINT `sewa_ibfk_1` FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`),
  CONSTRAINT `sewa_ibfk_2` FOREIGN KEY (`id_rumah`) REFERENCES `rumah` (`id_rumah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping structure for table rumah_sewa.admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id_admin` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telefon` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.admin: ~1 rows (approximately)
DELETE FROM `admin`;
INSERT INTO `admin` (`id_admin`, `username`, `nama`, `email`, `no_telefon`, `password`) VALUES
	(1, 'nabil', 'Nabil', 'nabilkudin44@gmail.com', '0193120022', '44');

-- Dumping structure for table rumah_sewa.bayaran
CREATE TABLE IF NOT EXISTS `bayaran` (
  `id_bayaran` int NOT NULL AUTO_INCREMENT,
  `id_sewa` int DEFAULT NULL,
  `bulan` varchar(20) DEFAULT NULL,
  `tahun` int DEFAULT NULL,
  `tarikh_bayar` datetime DEFAULT NULL,
  `jumlah` decimal(10,2) DEFAULT NULL,
  `status` enum('Lunas','Belum Lunas','Pending') DEFAULT 'Pending',
  `bill_code` varchar(50) DEFAULT NULL,
  `ref_no` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_bayaran`),
  KEY `id_sewa` (`id_sewa`),
  CONSTRAINT `bayaran_ibfk_1` FOREIGN KEY (`id_sewa`) REFERENCES `sewa` (`id_sewa`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.bayaran: ~2 rows (approximately)
DELETE FROM `bayaran`;
INSERT INTO `bayaran` (`id_bayaran`, `id_sewa`, `bulan`, `tahun`, `tarikh_bayar`, `jumlah`, `status`, `bill_code`, `ref_no`) VALUES
	(42, 5, 'Julai', 2026, NULL, 350.00, 'Pending', NULL, 'SEWA-1784876713-2280'),
	(44, 5, 'Julai', 2026, NULL, 350.00, 'Pending', NULL, 'SEWA-1784879264-1208'),
	(45, 5, 'Julai', 2026, NULL, 350.00, 'Pending', NULL, 'SEWA-1785291828-2005'),
	(46, 7, NULL, NULL, NULL, 350.00, 'Belum Lunas', NULL, NULL);

-- Dumping structure for table rumah_sewa.notifikasi
CREATE TABLE IF NOT EXISTS `notifikasi` (
  `id_notif` int NOT NULL AUTO_INCREMENT,
  `id_penyewa` int DEFAULT NULL,
  `mesej` text NOT NULL,
  `jenis` enum('info','warning','success','danger') DEFAULT 'info',
  `tarikh` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Belum Baca','Sudah Baca') DEFAULT 'Belum Baca',
  PRIMARY KEY (`id_notif`),
  KEY `id_penyewa` (`id_penyewa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.notifikasi: ~0 rows (approximately)
DELETE FROM `notifikasi`;

-- Dumping structure for table rumah_sewa.penyewa
CREATE TABLE IF NOT EXISTS `penyewa` (
  `id_penyewa` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `no_ic` varchar(20) NOT NULL,
  `no_telefon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_penyewa`),
  UNIQUE KEY `no_ic` (`no_ic`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.penyewa: ~3 rows (approximately)
DELETE FROM `penyewa`;
INSERT INTO `penyewa` (`id_penyewa`, `nama`, `no_ic`, `no_telefon`, `email`, `password`) VALUES
	(5, 'MUHAMMAD NABIL BIN KHAIRUDIN', '050330-04-0201', '0193120022', 'nabilkudin22@gmail.com', '$2y$10$IamGAraXGQYWsNcrinHAieN8S75Pjwl7WsyHI4NODq6FkQe6YahwC'),
	(6, 'ADAM ZAHIN BIN ABDULLAH', '060511031031', '0196492394', 'adamzahin511@gmail.com', '$2y$10$d7mhKca23H8Pq.DvB94gh.R.4izYekQ726VSBcerhcanXfNw4uali'),
	(7, 'dexwins bin riboi', '050505-05-0505', '013-987-6789', 'dex67@gmail.com', '$2y$10$BTOI7Q3qBq6tK968oTbNSOR790Jc9iJyui4RN8BKFlNGHI57PbsB.');

-- Dumping structure for table rumah_sewa.rumah
CREATE TABLE IF NOT EXISTS `rumah` (
  `id_rumah` int NOT NULL AUTO_INCREMENT,
  `no_rumah` varchar(20) DEFAULT NULL,
  `harga_sewa` decimal(10,2) DEFAULT NULL,
  `status` enum('Kosong','Disewa') DEFAULT 'Kosong',
  PRIMARY KEY (`id_rumah`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.rumah: ~6 rows (approximately)
DELETE FROM `rumah`;
INSERT INTO `rumah` (`id_rumah`, `no_rumah`, `harga_sewa`, `status`) VALUES
	(1, '44A', 350.00, 'Disewa'),
	(2, '44B', 350.00, 'Disewa'),
	(3, '44C', 350.00, 'Kosong'),
	(4, '44D', 4000.00, 'Kosong'),
	(5, '44E', 350.00, 'Disewa'),
	(6, '44F', 350.00, 'Kosong');

-- Dumping structure for table rumah_sewa.sewa
CREATE TABLE IF NOT EXISTS `sewa` (
  `id_sewa` int NOT NULL AUTO_INCREMENT,
  `id_penyewa` int DEFAULT NULL,
  `id_rumah` int DEFAULT NULL,
  `tarikh_masuk` date DEFAULT NULL,
  `deposit` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_sewa`),
  KEY `id_penyewa` (`id_penyewa`),
  KEY `id_rumah` (`id_rumah`),
  CONSTRAINT `sewa_ibfk_1` FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`),
  CONSTRAINT `sewa_ibfk_2` FOREIGN KEY (`id_rumah`) REFERENCES `rumah` (`id_rumah`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.sewa: ~5 rows (approximately)
DELETE FROM `sewa`;
INSERT INTO `sewa` (`id_sewa`, `id_penyewa`, `id_rumah`, `tarikh_masuk`, `deposit`) VALUES
	(3, 5, 2, '2026-07-14', 10000.00),
	(5, 7, 5, '2026-07-24', 2000.00),
	(7, 6, 1, '2026-08-03', 2000.00);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;


-- ============================================================
-- SMART RENT HUB: TENANT COMPLAINTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `aduan` (
  `id_aduan` int NOT NULL AUTO_INCREMENT,
  `id_penyewa` int NOT NULL,
  `tajuk` varchar(150) NOT NULL,
  `aduan` text NOT NULL,
  `status` enum('Baru','Dalam Proses','Selesai') NOT NULL DEFAULT 'Baru',
  `tarikh` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_aduan`),
  KEY `idx_aduan_penyewa` (`id_penyewa`),
  CONSTRAINT `aduan_ibfk_1`
    FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- SMART RENT HUB: TENANT DOCUMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `dokumen_penyewa` (
  `id_dokumen` int NOT NULL AUTO_INCREMENT,
  `id_penyewa` int NOT NULL,
  `ic_file` varchar(255) DEFAULT NULL,
  `akuan_janji_file` varchar(255) DEFAULT NULL,
  `tarikh_upload` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dokumen`),
  UNIQUE KEY `unique_dokumen_penyewa` (`id_penyewa`),
  CONSTRAINT `dokumen_penyewa_ibfk_1`
    FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- SMART RENT HUB: MONTHLY FINANCE
-- ============================================================

CREATE TABLE IF NOT EXISTS `kewangan_bulanan` (
  `id_kewangan` int NOT NULL AUTO_INCREMENT,
  `bulan` varchar(20) NOT NULL,
  `tahun` int NOT NULL,
  `total_sewa_dijangka` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_kutipan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_tunggakan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bilangan_penyewa` int NOT NULL DEFAULT 0,
  `bilangan_rumah_disewa` int NOT NULL DEFAULT 0,
  `tarikh_dijana` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kewangan`),
  UNIQUE KEY `unique_bulan_tahun` (`bulan`, `tahun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `kewangan_penyewa` (
  `id_kewangan_penyewa` int NOT NULL AUTO_INCREMENT,
  `id_kewangan` int NOT NULL,
  `id_sewa` int NOT NULL,
  `id_penyewa` int NOT NULL,
  `nama_penyewa` varchar(100) NOT NULL,
  `no_rumah` varchar(20) NOT NULL,
  `sewa_bulanan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status_bayaran` enum('Lunas','Belum Lunas','Pending') NOT NULL DEFAULT 'Belum Lunas',
  `tarikh_bayar` datetime DEFAULT NULL,
  PRIMARY KEY (`id_kewangan_penyewa`),
  UNIQUE KEY `unique_kewangan_sewa` (`id_kewangan`, `id_sewa`),
  KEY `idx_kewangan_penyewa` (`id_penyewa`),
  CONSTRAINT `kewangan_penyewa_ibfk_1` FOREIGN KEY (`id_kewangan`)
    REFERENCES `kewangan_bulanan` (`id_kewangan`) ON DELETE CASCADE,
  CONSTRAINT `kewangan_penyewa_ibfk_2` FOREIGN KEY (`id_sewa`)
    REFERENCES `sewa` (`id_sewa`) ON DELETE CASCADE,
  CONSTRAINT `kewangan_penyewa_ibfk_3` FOREIGN KEY (`id_penyewa`)
    REFERENCES `penyewa` (`id_penyewa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
