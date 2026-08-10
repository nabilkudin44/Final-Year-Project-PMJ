-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: newdb
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `newdb`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `newdb` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `newdb`;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'nabil','Nabil','nabilkudin44@gmail.com','0193120022','44',NULL);
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aduan`
--

DROP TABLE IF EXISTS `aduan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aduan` (
  `id_aduan` int NOT NULL AUTO_INCREMENT,
  `id_penyewa` int NOT NULL,
  `tajuk` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `aduan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Baru','Dalam Proses','Selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Baru',
  `tarikh` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_aduan`),
  KEY `idx_aduan_penyewa` (`id_penyewa`),
  CONSTRAINT `aduan_ibfk_1` FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aduan`
--

LOCK TABLES `aduan` WRITE;
/*!40000 ALTER TABLE `aduan` DISABLE KEYS */;
/*!40000 ALTER TABLE `aduan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bayaran`
--

DROP TABLE IF EXISTS `bayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bayaran` (
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
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bayaran`
--

LOCK TABLES `bayaran` WRITE;
/*!40000 ALTER TABLE `bayaran` DISABLE KEYS */;
INSERT INTO `bayaran` VALUES (42,5,'Julai',2026,NULL,350.00,'Pending',NULL,'SEWA-1784876713-2280'),(44,5,'Julai',2026,NULL,350.00,'Pending',NULL,'SEWA-1784879264-1208'),(45,5,'Julai',2026,NULL,350.00,'Pending',NULL,'SEWA-1785291828-2005'),(46,7,NULL,NULL,NULL,350.00,'Belum Lunas',NULL,NULL),(47,8,NULL,NULL,NULL,350.00,'Belum Lunas',NULL,NULL),(48,8,'Ogos',2026,NULL,350.00,'Pending','f6st7ont','SEWA-1786371833-5754'),(49,9,NULL,NULL,NULL,2.00,'Belum Lunas',NULL,NULL),(50,9,'Ogos',2026,'2026-08-10 14:37:14',2.00,'Lunas','aueq6irk','SEWA-1786372587-1082'),(51,5,'Ogos',2026,'2026-08-10 15:04:38',350.00,'Lunas',NULL,NULL),(52,3,'Ogos',2026,'2026-08-10 15:04:43',350.00,'Lunas',NULL,NULL);
/*!40000 ALTER TABLE `bayaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dokumen_penyewa`
--

DROP TABLE IF EXISTS `dokumen_penyewa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dokumen_penyewa` (
  `id_dokumen` int NOT NULL AUTO_INCREMENT,
  `id_penyewa` int NOT NULL,
  `ic_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `akuan_janji_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tarikh_upload` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dokumen`),
  UNIQUE KEY `unique_dokumen_penyewa` (`id_penyewa`),
  CONSTRAINT `dokumen_penyewa_ibfk_1` FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dokumen_penyewa`
--

LOCK TABLES `dokumen_penyewa` WRITE;
/*!40000 ALTER TABLE `dokumen_penyewa` DISABLE KEYS */;
/*!40000 ALTER TABLE `dokumen_penyewa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kewangan_bulanan`
--

DROP TABLE IF EXISTS `kewangan_bulanan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kewangan_bulanan` (
  `id_kewangan` int NOT NULL AUTO_INCREMENT,
  `bulan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tahun` int NOT NULL,
  `total_sewa_dijangka` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_kutipan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_tunggakan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bilangan_penyewa` int NOT NULL DEFAULT '0',
  `bilangan_rumah_disewa` int NOT NULL DEFAULT '0',
  `tarikh_dijana` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kewangan`),
  UNIQUE KEY `unique_bulan_tahun` (`bulan`,`tahun`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kewangan_bulanan`
--

LOCK TABLES `kewangan_bulanan` WRITE;
/*!40000 ALTER TABLE `kewangan_bulanan` DISABLE KEYS */;
INSERT INTO `kewangan_bulanan` VALUES (2,'Julai',2026,1052.00,0.00,1052.00,4,4,'2026-08-10 22:40:12'),(3,'September',2026,1052.00,0.00,1052.00,4,4,'2026-08-10 22:40:18'),(4,'Jun',2026,1052.00,0.00,1052.00,4,4,'2026-08-10 22:40:24'),(5,'Ogos',2026,1052.00,702.00,350.00,4,4,'2026-08-10 22:41:56');
/*!40000 ALTER TABLE `kewangan_bulanan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kewangan_penyewa`
--

DROP TABLE IF EXISTS `kewangan_penyewa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kewangan_penyewa` (
  `id_kewangan_penyewa` int NOT NULL AUTO_INCREMENT,
  `id_kewangan` int NOT NULL,
  `id_sewa` int NOT NULL,
  `id_penyewa` int NOT NULL,
  `nama_penyewa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `no_rumah` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sewa_bulanan` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status_bayaran` enum('Lunas','Belum Lunas','Pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Belum Lunas',
  `tarikh_bayar` datetime DEFAULT NULL,
  PRIMARY KEY (`id_kewangan_penyewa`),
  UNIQUE KEY `unique_kewangan_sewa` (`id_kewangan`,`id_sewa`),
  KEY `idx_kewangan_penyewa` (`id_penyewa`),
  KEY `kewangan_penyewa_ibfk_2` (`id_sewa`),
  CONSTRAINT `kewangan_penyewa_ibfk_1` FOREIGN KEY (`id_kewangan`) REFERENCES `kewangan_bulanan` (`id_kewangan`) ON DELETE CASCADE,
  CONSTRAINT `kewangan_penyewa_ibfk_2` FOREIGN KEY (`id_sewa`) REFERENCES `sewa` (`id_sewa`) ON DELETE CASCADE,
  CONSTRAINT `kewangan_penyewa_ibfk_3` FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kewangan_penyewa`
--

LOCK TABLES `kewangan_penyewa` WRITE;
/*!40000 ALTER TABLE `kewangan_penyewa` DISABLE KEYS */;
INSERT INTO `kewangan_penyewa` VALUES (5,2,7,6,'ADAM ZAHIN BIN ABDULLAH','44A',350.00,'Belum Lunas',NULL),(6,2,3,5,'MUHAMMAD NABIL BIN KHAIRUDIN','44B',350.00,'Belum Lunas',NULL),(7,2,5,7,'dexwins bin riboi','44E',350.00,'Pending',NULL),(8,2,9,9,'Alsa','55B',2.00,'Belum Lunas',NULL),(9,3,7,6,'ADAM ZAHIN BIN ABDULLAH','44A',350.00,'Belum Lunas',NULL),(10,3,3,5,'MUHAMMAD NABIL BIN KHAIRUDIN','44B',350.00,'Belum Lunas',NULL),(11,3,5,7,'dexwins bin riboi','44E',350.00,'Belum Lunas',NULL),(12,3,9,9,'Alsa','55B',2.00,'Belum Lunas',NULL),(13,4,7,6,'ADAM ZAHIN BIN ABDULLAH','44A',350.00,'Belum Lunas',NULL),(14,4,3,5,'MUHAMMAD NABIL BIN KHAIRUDIN','44B',350.00,'Belum Lunas',NULL),(15,4,5,7,'dexwins bin riboi','44E',350.00,'Belum Lunas',NULL),(16,4,9,9,'Alsa','55B',2.00,'Belum Lunas',NULL),(17,5,7,6,'ADAM ZAHIN BIN ABDULLAH','44A',350.00,'Belum Lunas',NULL),(18,5,3,5,'MUHAMMAD NABIL BIN KHAIRUDIN','44B',350.00,'Lunas','2026-08-10 15:04:43'),(19,5,5,7,'dexwins bin riboi','44E',350.00,'Lunas','2026-08-10 15:04:38'),(20,5,9,9,'Alsa','55B',2.00,'Lunas','2026-08-10 14:37:14');
/*!40000 ALTER TABLE `kewangan_penyewa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifikasi`
--

DROP TABLE IF EXISTS `notifikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifikasi` (
  `id_notif` int NOT NULL AUTO_INCREMENT,
  `id_penyewa` int DEFAULT NULL,
  `mesej` text NOT NULL,
  `jenis` enum('info','warning','success','danger') DEFAULT 'info',
  `tarikh` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Belum Baca','Sudah Baca') DEFAULT 'Belum Baca',
  PRIMARY KEY (`id_notif`),
  KEY `id_penyewa` (`id_penyewa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifikasi`
--

LOCK TABLES `notifikasi` WRITE;
/*!40000 ALTER TABLE `notifikasi` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifikasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penyewa`
--

DROP TABLE IF EXISTS `penyewa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penyewa` (
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penyewa`
--

LOCK TABLES `penyewa` WRITE;
/*!40000 ALTER TABLE `penyewa` DISABLE KEYS */;
INSERT INTO `penyewa` VALUES (5,'MUHAMMAD NABIL BIN KHAIRUDIN','050330-04-0201','0193120022','nabilkudin22@gmail.com','$2y$10$IamGAraXGQYWsNcrinHAieN8S75Pjwl7WsyHI4NODq6FkQe6YahwC',NULL),(6,'ADAM ZAHIN BIN ABDULLAH','060511031031','0196492394','adamzahin511@gmail.com','$2y$10$d7mhKca23H8Pq.DvB94gh.R.4izYekQ726VSBcerhcanXfNw4uali',NULL),(7,'dexwins bin riboi','050505-05-0505','013-987-6789','dex67@gmail.com','$2y$10$BTOI7Q3qBq6tK968oTbNSOR790Jc9iJyui4RN8BKFlNGHI57PbsB.',NULL),(8,'MUHAMAD ALSANUDIN','031219011735','0173045090','alsanudinnnn@gmail.com','$2y$10$n1ojEreg7GBgMXJyidQpteumO0HLhfvBvUP161eZ5OwSzxb1ZyvEC',NULL),(9,'Alsa','031219011736','0173045091','alsanudinnnnd@gmail.com','$2y$10$x7kmgv.AkQiGl9LfskbAReMAgdTllnykdGt4voodmVagKqU6cnPE6',NULL);
/*!40000 ALTER TABLE `penyewa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rumah`
--

DROP TABLE IF EXISTS `rumah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rumah` (
  `id_rumah` int NOT NULL AUTO_INCREMENT,
  `no_rumah` varchar(20) DEFAULT NULL,
  `harga_sewa` decimal(10,2) DEFAULT NULL,
  `status` enum('Kosong','Disewa') DEFAULT 'Kosong',
  PRIMARY KEY (`id_rumah`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rumah`
--

LOCK TABLES `rumah` WRITE;
/*!40000 ALTER TABLE `rumah` DISABLE KEYS */;
INSERT INTO `rumah` VALUES (1,'44A',350.00,'Disewa'),(2,'44B',350.00,'Disewa'),(3,'44C',350.00,'Disewa'),(4,'44D',4000.00,'Kosong'),(5,'44E',350.00,'Disewa'),(6,'44F',1.00,'Kosong'),(9,'55B',2.00,'Disewa');
/*!40000 ALTER TABLE `rumah` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sewa`
--

DROP TABLE IF EXISTS `sewa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sewa` (
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sewa`
--

LOCK TABLES `sewa` WRITE;
/*!40000 ALTER TABLE `sewa` DISABLE KEYS */;
INSERT INTO `sewa` VALUES (3,5,2,'2026-07-14',10000.00),(5,7,5,'2026-07-24',2000.00),(7,6,1,'2026-08-03',2000.00),(8,8,6,'2026-08-13',1.00),(9,9,9,'2026-08-10',0.00);
/*!40000 ALTER TABLE `sewa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'newdb'
--

--
-- Dumping routines for database 'newdb'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-10 23:40:34
