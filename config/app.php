<?php

if (file_exists(__DIR__ . '/local.php')) {
    require_once __DIR__ . '/local.php';
}
if (!defined('APP_TIMEZONE'))            define('APP_TIMEZONE', 'Asia/Manila');
if (!defined('BRANCH_NAME'))             define('BRANCH_NAME', 'Floridablanca Poblacion Branch');

if (!defined('NEW_ACCOUNTS_TELLER'))     define('NEW_ACCOUNTS_TELLER', 'teller_7');

if (!defined('TICKET_COOLDOWN_SECONDS')) define('TICKET_COOLDOWN_SECONDS', 5);

if (!defined('KIOSK_ALLOWED_IPS'))       define('KIOSK_ALLOWED_IPS', []);

if (!defined('LOGIN_MAX_ATTEMPTS'))      define('LOGIN_MAX_ATTEMPTS', 5);
if (!defined('LOGIN_LOCK_MINUTES'))      define('LOGIN_LOCK_MINUTES', 10);

if (!defined('SESSION_IDLE_MINUTES'))    define('SESSION_IDLE_MINUTES', 60);

if (!defined('MAX_SKIPS'))               define('MAX_SKIPS', 2);

if (!defined('PASSWORD_MIN_LENGTH'))     define('PASSWORD_MIN_LENGTH', 8);
