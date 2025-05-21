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


-- Dumping database structure for e_library
CREATE DATABASE IF NOT EXISTS `e_library` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `e_library`;

-- Dumping structure for table e_library.barang
CREATE TABLE IF NOT EXISTS `barang` (
  `ID_BRG` varchar(10) NOT NULL,
  `NAMA_BRG` varchar(30) DEFAULT NULL,
  `STOK` int DEFAULT NULL,
  PRIMARY KEY (`ID_BRG`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table e_library.barang: ~0 rows (approximately)

-- Dumping structure for table e_library.barang2
CREATE TABLE IF NOT EXISTS `barang2` (
  `ID_BRG` varchar(10) NOT NULL,
  `NAMA_BRG` varchar(30) DEFAULT NULL,
  `STOK` int DEFAULT NULL,
  PRIMARY KEY (`ID_BRG`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table e_library.barang2: ~0 rows (approximately)

-- Dumping structure for table e_library.books
CREATE TABLE IF NOT EXISTS `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `published_year` year NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `stock` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table e_library.books: ~5 rows (approximately)
INSERT INTO `books` (`id`, `category_id`, `title`, `author`, `published_year`, `isbn`, `created_at`, `stock`) VALUES
	(1, 2, 'Bahasa Indonesia', 'Erlangga', '2021', '0', '2025-01-16 01:05:48', 0),
	(5, 2, 'Bahasa Inggris', 'Erlangga', '2021', '2', '2025-01-16 01:06:47', 1),
	(6, 9, 'Basis Data', 'SMK Negeri 7 Samarinda', '2024', '1', '2025-01-16 01:07:08', 1),
	(7, 9, 'Pemrograman Terstruktur', 'Erlangga', '2023', '3', '2025-01-16 01:07:45', 1),
	(9, 9, 'Rekayasa Perangkat Lunak', 'Erlangga', '2020', '4', '2025-01-24 00:46:39', 1),
	(10, 14, 'Buku1', 'Anomali', '2025', '645', '2025-05-21 00:27:04', 10);

-- Dumping structure for table e_library.borrow_cart
CREATE TABLE IF NOT EXISTS `borrow_cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_book_unique` (`user_id`,`book_id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table e_library.borrow_cart: ~0 rows (approximately)

-- Dumping structure for table e_library.borrow_history
CREATE TABLE IF NOT EXISTS `borrow_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `borrow_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('dipinjam','dikembalikan') DEFAULT 'dipinjam',
  `unique_code` varchar(255) NOT NULL,
  `return_condition` enum('baik','rusak_ringan','rusak_berat','hilang') DEFAULT NULL,
  `return_notes` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `borrow_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `borrow_history_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table e_library.borrow_history: ~3 rows (approximately)
INSERT INTO `borrow_history` (`id`, `user_id`, `book_id`, `borrow_date`, `return_date`, `status`, `unique_code`, `return_condition`, `return_notes`) VALUES
	(8, 17, 9, '2025-04-23', '2025-04-30', 'dipinjam', 'borrow_680868aa6d3c01.74991667', NULL, NULL),
	(9, 18, 7, '2025-05-02', '2025-05-06', 'dipinjam', 'borrow_68145d8eb19809.49656497', NULL, NULL),
	(10, 18, 7, '2025-05-02', '2025-05-06', 'dipinjam', 'borrow_68145d9e370454.96874688', NULL, NULL),
	(11, 18, 7, '2025-05-14', '2025-05-17', 'dipinjam', 'borrow_6824172f1e3148.02601364', NULL, NULL),
	(12, 18, 9, '2025-05-21', '2025-05-28', 'dipinjam', 'borrow_682d19eccb8113.71052834', NULL, NULL),
	(13, 18, 1, '2025-05-21', '2025-05-28', 'dipinjam', 'borrow_682d1d05ae7ac2.76491395', NULL, NULL);

-- Dumping structure for table e_library.category
CREATE TABLE IF NOT EXISTS `category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `total` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table e_library.category: ~11 rows (approximately)
INSERT INTO `category` (`id`, `name`, `description`, `total`) VALUES
	(1, 'Fiksi', 'Karya fiksi yang menarik', NULL),
	(2, 'Non-Fiksi', 'Karya non-fiksi yang informatif', 2),
	(3, 'Anak-anak', 'Buku anak-anak yang edukatif dan menghibur', NULL),
	(4, 'Remaja', 'Buku remaja yang seru', NULL),
	(5, 'Komik', 'Komik yang menghibur', NULL),
	(6, 'Novel', 'Novel dengan berbagai genre', NULL),
	(7, 'Biografi', 'Kisah hidup tokoh-tokoh inspiratif', NULL),
	(8, 'Sejarah', 'Buku-buku tentang sejarah dunia', NULL),
	(9, 'Sains', 'Buku-buku tentang sains dan teknologi', 3),
	(13, 'Matematika', 'Ya Matematika', NULL),
	(14, 'Desain', 'ya Desain sih apa lagi', NULL);

-- Dumping structure for table e_library.guestbook
CREATE TABLE IF NOT EXISTS `guestbook` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table e_library.guestbook: ~2 rows (approximately)
INSERT INTO `guestbook` (`id`, `name`, `message`, `created_at`) VALUES
	(1, 'Al-Ghani Desta Setyawan', 'Aaaa', '2025-02-12 05:34:27'),
	(2, 'Al-Ghani Desta Setyawan', 'Aaaa', '2025-02-12 05:35:05'),
	(3, 'tes', 'tes', '2025-04-15 09:34:39');

-- Dumping structure for table e_library.return_requests
CREATE TABLE IF NOT EXISTS `return_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `borrow_history_id` int NOT NULL,
  `user_id` int NOT NULL,
  `unique_code` varchar(255) NOT NULL,
  `request_date` datetime NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`unique_code`),
  KEY `borrow_history_id` (`borrow_history_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table e_library.return_requests: ~0 rows (approximately)
INSERT INTO `return_requests` (`id`, `borrow_history_id`, `user_id`, `unique_code`, `request_date`, `verified`) VALUES
	(1, 12, 18, 'return_682d1d11749d24.16128743', '2025-05-21 08:23:45', 0),
	(2, 13, 18, 'return_682d1e0845fc10.58510911', '2025-05-21 08:27:52', 0),
	(3, 11, 18, 'return_682d1e11f1cbe0.47618148', '2025-05-21 08:28:01', 0),
	(4, 10, 18, 'return_682d1e1b523bc5.95839259', '2025-05-21 08:28:11', 0),
	(5, 9, 18, 'return_682d1e317ffe59.17200596', '2025-05-21 08:28:33', 0);

-- Dumping structure for table e_library.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student','petugas') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `class` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table e_library.users: ~3 rows (approximately)
INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `class`, `created_at`) VALUES
	(3, 'admin', '$2y$10$Rx5w0nBDQeySdQdvzgEmHOUEfKHOS1UhG10Ez1H3.Q71MQyL4x.DC', 'admin', 'Al-Ghani Desta Setyawan', 'admin@smkn7-smr.sch.id', 'AdSMK7', '2025-01-16 01:05:14'),
	(4, 'ghani', '$2a$10$jCWMXd5.OsgUEsh7zwAWQOtEKGps4u3H7B/uSSfmjRYQ66OCvfYFu', 'student', 'Al-Ghani Desta', 'ghani@smkn7-smr.sch.id', 'XI PPLG 1', '2025-01-31 00:20:07'),
	(16, 'joe', '$2y$10$CJfAkJeItdrrSigEP5RuKeSirenS/kBxcSkmANIMzEt/gRAhRQv7K', 'petugas', 'Petugas Joe', 'joe@perpus.com', 'NULL', '2025-02-12 04:34:41'),
	(17, 'kousozo', '$2y$12$O/IWrd.mlYZxEsQgCV7Tv.90mo8pnIUhgTlEk5KuF2QSuipNND1NO', 'petugas', 'Al-Ghani', 'the323offcial@gmail.com', 'XI PPLG 1', '2025-04-23 04:11:47'),
	(18, 'jotas', '$2y$12$A7Wb6SjPU.m/doKeMUkUwOfjqGCzjygby6qTDx0cHU/B8QBYlQlDC', 'student', 'Joe Taslim', 'joetas123@gmail.com', 'AdSMK7', '2025-05-02 05:47:58'),
	(19, 'petugas1', '$2y$12$ymp0ktwrcSUQtNYRu3Ocv.0Awxp1jIowVF7PeP/1LOmZwRVPTMt5m', 'petugas', 'Petugas #1', 'petugas1@official.com', 'Petugas', '2025-05-14 03:51:41'),
	(20, 'suqime', '$2y$12$ov5NMIWPd56oXSMfY7HnRO7lFKB2x7L363jxlT10Y2/.u.xtQn35i', 'student', 'Wyatt Mccarty', 'dawupahoqi@mailinator.com', 'Deleniti eius fugiat', '2025-05-20 15:04:38');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
