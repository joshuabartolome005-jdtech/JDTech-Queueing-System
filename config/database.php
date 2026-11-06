<?php

if (file_exists(__DIR__ . '/local.php')) {
    require_once __DIR__ . '/local.php';
}
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'p@ssw0rd');
if (!defined('DB_NAME')) define('DB_NAME', 'bank_queue_system');
