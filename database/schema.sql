-- ApexSMM Enterprise Database Schema
-- Compatible with MySQL 8.0+ and MariaDB 10.5+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'staff', 'admin') NOT NULL DEFAULT 'user',
  `balance` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
  `spent` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active', 'suspended', 'banned') NOT NULL DEFAULT 'active',
  `api_key` VARCHAR(64) NULL UNIQUE,
  `custom_rates` JSON NULL,
  `two_factor_secret` VARCHAR(64) NULL,
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `referral_code` VARCHAR(32) NULL UNIQUE,
  `referred_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(128) NOT NULL,
  `icon` VARCHAR(64) NOT NULL DEFAULT 'folder',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_categories_sort` (`sort_order`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: providers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `providers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(128) NOT NULL,
  `api_url` VARCHAR(255) NOT NULL,
  `api_key` VARCHAR(255) NOT NULL,
  `balance` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
  `currency` VARCHAR(8) NOT NULL DEFAULT 'USD',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: services
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'default',
  `rate` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `min_quantity` INT UNSIGNED NOT NULL DEFAULT 10,
  `max_quantity` INT UNSIGNED NOT NULL DEFAULT 100000,
  `description` TEXT NULL,
  `dripfeed` TINYINT(1) NOT NULL DEFAULT 0,
  `refill` TINYINT(1) NOT NULL DEFAULT 0,
  `cancel` TINYINT(1) NOT NULL DEFAULT 0,
  `provider_id` INT UNSIGNED NULL,
  `provider_service_id` VARCHAR(64) NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_services_category` (`category_id`),
  INDEX `idx_services_provider` (`provider_id`),
  CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: orders
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED NULL,
  `provider_order_id` VARCHAR(64) NULL,
  `link` TEXT NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `start_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `remains` INT UNSIGNED NOT NULL DEFAULT 0,
  `charge` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('pending', 'processing', 'inprogress', 'completed', 'partial', 'canceled', 'fail') NOT NULL DEFAULT 'pending',
  `order_type` VARCHAR(32) NOT NULL DEFAULT 'default',
  `custom_comments` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_orders_user` (`user_id`),
  INDEX `idx_orders_status` (`status`),
  INDEX `idx_orders_service` (`service_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: payments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `method` VARCHAR(64) NOT NULL,
  `transaction_id` VARCHAR(128) NOT NULL UNIQUE,
  `amount` DECIMAL(12, 4) NOT NULL,
  `fee` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(12, 4) NOT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'canceled') NOT NULL DEFAULT 'pending',
  `raw_data` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_payments_user` (`user_id`),
  INDEX `idx_payments_status` (`status`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: transactions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(12, 4) NOT NULL,
  `type` ENUM('credit', 'debit', 'refund') NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `balance_after` DECIMAL(14, 4) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_txns_user` (`user_id`),
  CONSTRAINT `fk_txns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: tickets
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `subject` VARCHAR(191) NOT NULL,
  `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  `status` ENUM('pending', 'answered', 'closed') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tickets_user` (`user_id`),
  INDEX `idx_tickets_status` (`status`),
  CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: ticket_messages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ticket_msgs` (`ticket_id`),
  CONSTRAINT `fk_ticket_msgs_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `setting_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Initial Data
-- --------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'ApexSMM Enterprise'),
('site_currency', '$'),
('site_currency_code', 'USD'),
('min_deposit', '5.00'),
('max_deposit', '5000.00'),
('maintenance_mode', '0'),
('referral_percent', '5.00')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- Insert Default Admin: admin / Admin@123456
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `balance`, `spent`, `status`, `api_key`)
VALUES 
(1, 'admin', 'admin@apexsmm.com', '$2y$12$E2F.yQ8t790kYxlV7A21DOZ8htaXGve2DG4qbQe8BX0d.sud8jRlm', 'admin', 500.0000, 0.0000, 'active', 'smm_admin_enterprise_key_998811'),
(2, 'demouser', 'demo@apexsmm.com', '$2y$12$NhycMHSzuk/WnV/r3h5Ye.nERI.Nl/WlRIbYyl0f00wIe.OFdqGFO', 'user', 84.5000, 165.2000, 'active', 'smm_demo_client_key_112233')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Insert Sample Categories
INSERT INTO `categories` (`id`, `name`, `icon`, `sort_order`, `status`) VALUES
(1, 'Instagram Followers [Guaranteed / Real]', 'instagram', 1, 1),
(2, 'Instagram Likes & Engagements', 'heart', 2, 1),
(3, 'YouTube Views & Watch Time', 'youtube', 3, 1),
(4, 'TikTok Followers & Likes', 'video', 4, 1),
(5, 'Telegram Members & Channel Boost', 'send', 5, 1),
(6, 'Twitter / X Followers & Retweets', 'twitter', 6, 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Insert Sample Services
INSERT INTO `services` (`id`, `category_id`, `name`, `type`, `rate`, `min_quantity`, `max_quantity`, `description`, `dripfeed`, `refill`, `cancel`, `status`, `sort_order`) VALUES
(101, 1, 'Instagram Followers [HQ - 30 Days Auto-Refill - Non-Drop]', 'default', 0.8500, 50, 50000, 'High quality worldwide followers. Instant start (0-15m), speed: 10K/Day. Auto refill 30 days button enabled.', 1, 1, 0, 1, 1),
(102, 1, 'Instagram Followers [Real Active - Lifetime Guarantee]', 'default', 1.4500, 100, 20000, '100% Real active looking profiles with posts and stories. 0% Drop rate with lifetime guarantee.', 1, 1, 0, 1, 2),
(103, 2, 'Instagram Likes [Instant - Super Fast 50K/Day]', 'default', 0.1800, 20, 100000, 'Instant delivery right after placing order. High quality accounts.', 0, 0, 0, 1, 3),
(104, 2, 'Instagram Custom Comments [English / Positive]', 'custom_comments', 3.2000, 10, 2000, 'Add custom comments per line. Delivered from verified-looking profiles.', 0, 0, 0, 1, 4),
(105, 3, 'YouTube High Retention Views [Monetizable - Real Traffic]', 'default', 1.8000, 500, 1000000, 'High watch time retention (3-5 minutes average). 100% safe for AdSense monetization.', 1, 1, 0, 1, 5),
(106, 3, 'YouTube Subscribers [Non-Drop - Organic Delivery]', 'default', 12.5000, 50, 10000, 'Real organic subscribers. Drop safe with 60 days refill protection.', 1, 1, 0, 1, 6),
(107, 4, 'TikTok Followers [Instant Delivery - Worldwide]', 'default', 0.9500, 100, 50000, 'Super fast startup, instant followers, top notch delivery speed.', 1, 1, 0, 1, 7),
(108, 4, 'TikTok Video Views [100% Real / Algorithmic Push]', 'default', 0.0300, 500, 5000000, 'Triggers ForYou page recommendations. Ultra cheap and instant.', 0, 0, 0, 1, 8),
(109, 5, 'Telegram Channel Members [0% Drop - Permanent]', 'default', 0.6500, 100, 100000, 'High quality Telegram channel & group members. Stable and permanent.', 1, 1, 0, 1, 9),
(110, 6, 'X / Twitter Followers [Real NFT / Crypto Profiles]', 'default', 2.1000, 50, 30000, 'Targeted Crypto / Tech followers for accounts and projects.', 1, 1, 0, 1, 10)
ON DUPLICATE KEY UPDATE `id` = `id`;

SET FOREIGN_KEY_CHECKS = 1;
