-- =====================================================================
--  Mas ligtas na MySQL user para sa system (huwag gamitin ang "root")
--  1) Palitan ang CHANGE_THIS_PASSWORD ng matibay na password.
--  2) I-run sa phpMyAdmin (SQL tab), bilang root.
--  3) Gumawa ng config/local.php:
--        <?php
--        define('DB_USER', 'bq_app');
--        define('DB_PASS', 'ang-password-na-nilagay-mo');
-- =====================================================================
CREATE USER IF NOT EXISTS 'bq_app'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON `bank_queue_system`.* TO 'bq_app'@'localhost';
FLUSH PRIVILEGES;
