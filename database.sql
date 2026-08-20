CREATE DATABASE IF NOT EXISTS pdf_word CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pdf_word;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','disabled') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `conversions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `pdf_path` varchar(255) NOT NULL,
  `word_path` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `status` varchar(50) DEFAULT 'completed',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;