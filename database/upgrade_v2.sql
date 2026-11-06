-- =====================================================================
--  UPGRADE v2  (para sa database na galing sa lumang version)
--  Hindi mo na kailangang i-run ito kung bubuksan mo na lang ang system:
--  kusa itong nag-a-upgrade sa unang bukas. Nandito ito kung gusto mong
--  gawin mano-mano sa phpMyAdmin (SQL tab). Ligtas ulit-ulitin.
--  Kailangan: MariaDB 10.0.2 pataas (XAMPP).
-- =====================================================================

-- 1) Mga slip ng client
CREATE TABLE IF NOT EXISTS `queue_slips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) NOT NULL,
  `slip_type` enum('cash_deposit','withdrawal','check_deposit','ecpay') NOT NULL,
  `slip_data` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `queue_id` (`queue_id`),
  CONSTRAINT `queue_slips_ibfk_1` FOREIGN KEY (`queue_id`) REFERENCES `queue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) Tamang oras at karagdagang impormasyon sa bawat ticket
ALTER TABLE `queue`
  ADD COLUMN IF NOT EXISTS `created_at` DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `called_at` DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `served_at` DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `skip_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `no_show` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `recall_count` INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `kiosk_ip` VARCHAR(45) NULL DEFAULT NULL;

-- 3) Ilipat ang lumang oras (kung mayroon pa ang column na `timestamp`)
SET @has_ts := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'queue' AND COLUMN_NAME = 'timestamp');
SET @sql := IF(@has_ts > 0,
  "UPDATE `queue` SET `created_at` = IF(`timestamp` > '2000-01-01', `timestamp`, NOW()) WHERE `created_at` IS NULL",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
UPDATE `queue` SET `created_at` = NOW() WHERE `created_at` IS NULL;

-- 4) Alisin ang mga column na hindi na ginagamit; mas mahabang pangalan ng teller
ALTER TABLE `queue`
  DROP COLUMN IF EXISTS `timestamp`,
  DROP COLUMN IF EXISTS `printed`,
  MODIFY `teller` VARCHAR(50) NULL DEFAULT NULL;

-- 5) History ng mga nakaraang araw (nilalagay dito tuwing End of Day)
CREATE TABLE IF NOT EXISTS `queue_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_date` DATE NOT NULL,
  `customer_number` int(11) NOT NULL,
  `client_type` varchar(20) NOT NULL,
  `teller` varchar(50) NULL,
  `status` enum('waiting','serving','served') NOT NULL,
  `no_show` TINYINT(1) NOT NULL DEFAULT 0,
  `skip_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `slip_type` varchar(20) NULL,
  `created_at` DATETIME NULL,
  `called_at` DATETIME NULL,
  `served_at` DATETIME NULL,
  `archived_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_date` (`ticket_date`),
  KEY `teller` (`teller`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6) Proteksyon sa login (limitasyon ng maling subok)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `username` varchar(50) NOT NULL,
  `attempted_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_time` (`ip`, `attempted_at`),
  KEY `user_time` (`username`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7) Mga user: online status. (Ang lumang `status` column ay inaalis; ang `session_token` ay ginagamit na.)
ALTER TABLE `users` DROP COLUMN IF EXISTS `status`;
ALTER TABLE `users` MODIFY `role` ENUM('admin','employee','developer') NOT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_seen` DATETIME NULL DEFAULT NULL;
