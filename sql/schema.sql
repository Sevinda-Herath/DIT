-- Nebula Esports DB schema (MySQL 5.7+)
-- Change the database name if needed.

CREATE DATABASE IF NOT EXISTS `nebula`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `nebula`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_username` (`username`),
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profiles table
CREATE TABLE IF NOT EXISTS `profiles` (
  `user_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(191) NULL,
  `dob` DATE NULL,
  `location` VARCHAR(191) NULL,
  `university` VARCHAR(191) NULL,
  `nic` VARCHAR(64) NULL,
  `mobile` VARCHAR(64) NULL,
  `team_name` VARCHAR(191) NULL,
  `team_captain` VARCHAR(191) NULL,
  `players_count` TINYINT UNSIGNED NULL,
  `game_titles` JSON NULL,
  `team_logo_path` VARCHAR(255) NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Members table
CREATE TABLE IF NOT EXISTS `members` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `idx` TINYINT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NULL,
  `nic` VARCHAR(64) NULL,
  `email` VARCHAR(191) NULL,
  `phone` VARCHAR(64) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `idx_user_idx` (`user_id`, `idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: seed data example (commented out)
-- INSERT INTO users (username, email, password_hash, created_at)
-- VALUES ('demo', 'demo@example.com', '$2y$10$examplehash', NOW());


-- Remember tokens table (persistent login "remember me")
DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE `remember_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_selector` (`selector`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_remember_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notes:
--  * Aligns with application code (selector CHAR(12), token_hash column name).
--  * Drop table before create ensures updated structure during manual migrations.
--  * Increase selector length or expires window in code & here if future requirements change.