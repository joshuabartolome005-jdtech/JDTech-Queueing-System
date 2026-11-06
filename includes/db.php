<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

date_default_timezone_set(APP_TIMEZONE);

function db_connect($json = false) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        $conn->query("SET time_zone = '+08:00'");
    } catch (Throwable $e) {
        error_log('db_connect: ' . $e->getMessage());
        http_response_code(500);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Cannot reach the database. Please tell the administrator.']);
        } else {
            echo 'Cannot reach the database. Please check that MySQL is running in XAMPP.';
        }
        exit;
    }
    schema_ensure($conn);
    return $conn;
}

function schema_statements($conn) {
    $st = [];
    $st[] = "CREATE TABLE IF NOT EXISTS `queue_slips` (
        `id` int(11) NOT NULL AUTO_INCREMENT, `queue_id` int(11) NOT NULL,
        `slip_type` enum('cash_deposit','withdrawal','check_deposit','ecpay') NOT NULL,
        `slip_data` longtext NOT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`), UNIQUE KEY `queue_id` (`queue_id`),
        CONSTRAINT `queue_slips_ibfk_1` FOREIGN KEY (`queue_id`) REFERENCES `queue` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $st[] = "ALTER TABLE `queue`
        ADD COLUMN IF NOT EXISTS `created_at` DATETIME NULL DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `called_at` DATETIME NULL DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `served_at` DATETIME NULL DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `skip_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `no_show` TINYINT(1) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `recall_count` INT UNSIGNED NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `kiosk_ip` VARCHAR(45) NULL DEFAULT NULL";

    $hasOld = $conn->query("SHOW COLUMNS FROM `queue` LIKE 'timestamp'");
    if ($hasOld && $hasOld->num_rows) {
        $st[] = "UPDATE `queue` SET `created_at` = IF(`timestamp` > '2000-01-01', `timestamp`, NOW()) WHERE `created_at` IS NULL";
    }
    $st[] = "UPDATE `queue` SET `created_at` = NOW() WHERE `created_at` IS NULL";
    $st[] = "ALTER TABLE `queue` DROP COLUMN IF EXISTS `timestamp`, DROP COLUMN IF EXISTS `printed`, MODIFY `teller` VARCHAR(50) NULL DEFAULT NULL";
    $st[] = "CREATE TABLE IF NOT EXISTS `queue_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT, `ticket_date` DATE NOT NULL, `customer_number` int(11) NOT NULL,
        `client_type` varchar(20) NOT NULL, `teller` varchar(50) NULL,
        `status` enum('waiting','serving','served') NOT NULL,
        `no_show` TINYINT(1) NOT NULL DEFAULT 0, `skip_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
        `slip_type` varchar(20) NULL, `created_at` DATETIME NULL, `called_at` DATETIME NULL, `served_at` DATETIME NULL,
        `archived_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`), KEY `ticket_date` (`ticket_date`), KEY `teller` (`teller`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $st[] = "CREATE TABLE IF NOT EXISTS `login_attempts` (
        `id` int(11) NOT NULL AUTO_INCREMENT, `ip` varchar(45) NOT NULL, `username` varchar(50) NOT NULL,
        `attempted_at` DATETIME NOT NULL, PRIMARY KEY (`id`), KEY `ip_time` (`ip`, `attempted_at`),
        KEY `user_time` (`username`, `attempted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $st[] = "ALTER TABLE `users` DROP COLUMN IF EXISTS `status`";
    $st[] = "ALTER TABLE `users` MODIFY `role` ENUM('admin','employee','developer') NOT NULL";
    $st[] = "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_seen` DATETIME NULL DEFAULT NULL";  
    return $st;
}

function schema_ensure($conn) {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    $r = $conn->query("SHOW COLUMNS FROM `users` LIKE 'last_seen'");
    $role = $conn->query("SHOW COLUMNS FROM `users` LIKE 'role'");
    $roleRow = $role ? $role->fetch_assoc() : null;
    $hasDeveloper = $roleRow && strpos($roleRow['Type'], 'developer') !== false;
    if ($r && $r->num_rows && $hasDeveloper) return;   // updated na
    foreach (schema_statements($conn) as $sql) {
        try {
            $conn->query($sql);
        } catch (Throwable $e) {
            error_log('schema_ensure: ' . $e->getMessage() . ' | ' . substr($sql, 0, 80));
        }
    }
}
