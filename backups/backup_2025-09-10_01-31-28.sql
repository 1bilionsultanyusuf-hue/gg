-- IT|CORE Database Backup
-- Created: 2025-09-10 01:31:28

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `apps`;
CREATE TABLE `apps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `apps` VALUES
('1','Aplikasi Keuangan','Digunakan untuk mencatat arus kas dan laporan keuangan.'),
('2','Aplikasi Inventaris','Mencatat stok barang dan pengeluaran inventaris kantor.'),
('3','Aplikasi CRM','Mengelola interaksi dan hubungan dengan pelanggan.'),
('4','Aplikasi HRIS','Mengatur data kepegawaian dan absensi.');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` enum('programmer','support','admin') DEFAULT 'support',
  `password` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `gender` enum('male','female') DEFAULT 'male',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` VALUES
('1','Budi Santoso','budi@example.com','programmer','programmer123',NULL,'2025-09-10 08:10:25','male'),
('2','Siti Aminah','siti@example.com','support','support123','uploads/profiles/profile_2_1757384354.png','2025-09-10 08:11:32','female'),
('3','Joko Widodo','joko@example.com','admin','admin123','uploads/profiles/profile_3_1757382949.png','2025-09-10 08:30:07','male'),
('4','Dewi Lestari','dewi@example.com','programmer','programmer123',NULL,NULL,'female');

DROP TABLE IF EXISTS `todos`;
CREATE TABLE `todos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `app_id` int DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NULL DEFAULT (now()),
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `app_id` (`app_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `todos_ibfk_1` FOREIGN KEY (`app_id`) REFERENCES `apps` (`id`) ON DELETE CASCADE,
  CONSTRAINT `todos_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `todos` VALUES
('1','1','Perbaiki fitur export PDF','Export PDF gagal jika data terlalu besar','low','2025-08-28 13:26:25','1'),
('2','1','Tambahkan grafik arus kas','Menampilkan grafik bulanan untuk arus kas masuk dan keluar','medium','2025-08-28 13:26:25','1'),
('3','2','Perbaikan filter stok','Filter tidak bisa memproses nama barang dengan karakter khusus','low','2025-08-28 13:26:25','4'),
('4','3','Integrasi dengan email client','Menambahkan fitur notifikasi otomatis ke email pelanggan','high','2025-08-28 13:26:25','2'),
('5','4','Bug absensi shift malam','Absensi shift malam tidak masuk ke laporan bulanan','medium','2025-08-28 13:26:25','4');

DROP TABLE IF EXISTS `taken`;
CREATE TABLE `taken` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_todos` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` enum('in_progress','done') DEFAULT 'in_progress',
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_todos` (`id_todos`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `taken_ibfk_1` FOREIGN KEY (`id_todos`) REFERENCES `todos` (`id`),
  CONSTRAINT `taken_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_data` text,
  `new_data` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `system_logs` VALUES
('1','3','BACKUP_CREATED','database',NULL,NULL,NULL,'::1',NULL,'2025-09-10 08:31:11');

SET FOREIGN_KEY_CHECKS=1;
