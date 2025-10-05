-- Written by Amir Hossin Moulodi
CREATE DATABASE IF NOT EXISTS `url_shortener_db`;

USE `url_shortener_db`;

CREATE TABLE `urls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `long_url` text NOT NULL,
  `long_url_hash` binary(32) NOT NULL,
  `short_code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `short_code` (`short_code`),
  UNIQUE KEY `long_url_hash` (`long_url_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backward-compatible migration helpers (safe to run multiple times on MySQL 8+)
ALTER TABLE `urls` ADD COLUMN IF NOT EXISTS `long_url_hash` BINARY(32) NOT NULL AFTER `long_url`;
ALTER TABLE `urls` ADD UNIQUE KEY `long_url_hash` (`long_url_hash`);