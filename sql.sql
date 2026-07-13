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
DROP DATABASE IF EXISTS `rumah_sewa`;
CREATE DATABASE IF NOT EXISTS `rumah_sewa` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `rumah_sewa`;

-- Dumping structure for table rumah_sewa.bayaran
DROP TABLE IF EXISTS `bayaran`;
CREATE TABLE IF NOT EXISTS `bayaran` (
  `id_bayaran` int NOT NULL AUTO_INCREMENT,
  `id_sewa` int DEFAULT NULL,
  `bulan` varchar(20) DEFAULT NULL,
  `tahun` int DEFAULT NULL,
  `tarikh_bayar` date DEFAULT NULL,
  `jumlah` decimal(10,2) DEFAULT NULL,
  `status` enum('Lunas','Belum Lunas') DEFAULT NULL,
  PRIMARY KEY (`id_bayaran`),
  KEY `id_sewa` (`id_sewa`),
  CONSTRAINT `bayaran_ibfk_1` FOREIGN KEY (`id_sewa`) REFERENCES `sewa` (`id_sewa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.bayaran: ~0 rows (approximately)

-- Dumping structure for table rumah_sewa.penyewa
DROP TABLE IF EXISTS `penyewa`;
CREATE TABLE IF NOT EXISTS `penyewa` (
  `id_penyewa` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `no_ic` varchar(20) NOT NULL,
  `no_telefon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id_penyewa`),
  UNIQUE KEY `no_ic` (`no_ic`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.penyewa: ~3 rows (approximately)
REPLACE INTO `penyewa` (`id_penyewa`, `nama`, `no_ic`, `no_telefon`, `email`, `password`) VALUES
	(1, 'amar', '050330-04-0202', '0193120022', 'nabilkudin44@gmail.com', 'nabil_44'),
	(4, 'amarah', '050330-04-3030', '0193120022', 'nabilkudin33@gmail.com', '$2y$10$kf5FKZBrnDAS59Wuyy7wnudHQqmPUMSVRsEBRXqHX7uAJnNeNmxWC'),
	(5, 'MUHAMMAD NABIL BIN KHAIRUDIN', '050330-04-0201', '0193120022', 'nabilkudin22@gmail.com', '$2y$10$IamGAraXGQYWsNcrinHAieN8S75Pjwl7WsyHI4NODq6FkQe6YahwC');

-- Dumping structure for table rumah_sewa.rumah
DROP TABLE IF EXISTS `rumah`;
CREATE TABLE IF NOT EXISTS `rumah` (
  `id_rumah` int NOT NULL AUTO_INCREMENT,
  `no_rumah` varchar(20) DEFAULT NULL,
  `harga_sewa` decimal(10,2) DEFAULT NULL,
  `status` enum('Kosong','Disewa') DEFAULT 'Kosong',
  PRIMARY KEY (`id_rumah`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.rumah: ~2 rows (approximately)
REPLACE INTO `rumah` (`id_rumah`, `no_rumah`, `harga_sewa`, `status`) VALUES
	(1, '44A', 350.00, 'Disewa'),
	(2, '44B', 350.00, 'Kosong'),
	(3, '44C', 350.00, 'Kosong');

-- Dumping structure for table rumah_sewa.sewa
DROP TABLE IF EXISTS `sewa`;
CREATE TABLE IF NOT EXISTS `sewa` (
  `id_sewa` int NOT NULL AUTO_INCREMENT,
  `id_penyewa` int DEFAULT NULL,
  `id_rumah` int DEFAULT NULL,
  `tarikh_masuk` date DEFAULT NULL,
  `tarikh_tamat` date DEFAULT NULL,
  `deposit` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_sewa`),
  KEY `id_penyewa` (`id_penyewa`),
  KEY `id_rumah` (`id_rumah`),
  CONSTRAINT `sewa_ibfk_1` FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`),
  CONSTRAINT `sewa_ibfk_2` FOREIGN KEY (`id_rumah`) REFERENCES `rumah` (`id_rumah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rumah_sewa.sewa: ~1 rows (approximately)
REPLACE INTO `sewa` (`id_sewa`, `id_penyewa`, `id_rumah`, `tarikh_masuk`, `tarikh_tamat`, `deposit`) VALUES
	(1, 1, 1, '2026-07-13', '2026-07-13', 2000.00);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
