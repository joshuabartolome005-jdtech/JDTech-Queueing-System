<?php
/*
 * includes/auth.php
 * Session, login/role checks, CSRF protection and the shared admin menu.
 *   require_login()          -> any logged-in user (teller or admin)
 *   require_role('admin')    -> admin only (tellers are sent back to their own page)
 * Pass $json = true in API files so they answer with JSON instead of redirecting.
 */
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('BQSESSID');                   
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function auth_home($role) {
    if ($role === 'developer') return 'admin';        
    if ($role === 'admin') return 'dashboard';
    return 'teller';
}

function auth_flash_get() {
    $k = isset($_SESSION['flash']) ? $_SESSION['flash'] : '';
    unset($_SESSION['flash']);
    return $k;
}

function auth_deny($json, $code, $message, $redirect) {
    if ($json) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => $message]);
        exit;
    }
    header('Location: ' . $redirect);
    exit;
}

function auth_destroy_session($flash = '') {
    $_SESSION = [];
    session_regenerate_id(true);
    if ($flash !== '') $_SESSION['flash'] = $flash;
}

function require_login($json = false) {
    if (empty($_SESSION['user_id'])) {
        auth_deny($json, 401, 'Please log in.', 'login');
    }

    $idle = SESSION_IDLE_MINUTES * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idle) {
        auth_destroy_session('idle');
        auth_deny($json, 401, 'You were logged out because of inactivity.', 'login');
    }
    $_SESSION['last_activity'] = time();

    $conn = db_connect($json);
    $stmt = $conn->prepare("SELECT username, role, session_token, last_seen FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$u) {
        $conn->close();
        auth_destroy_session();
        auth_deny($json, 401, 'This account no longer exists.', 'login');
    }
    if (empty($u['session_token']) || empty($_SESSION['token']) || !hash_equals($u['session_token'], $_SESSION['token'])) {
        $conn->close();
        auth_destroy_session('other');
        auth_deny($json, 401, 'This account was logged in on another device.', 'login');
    }

    $_SESSION['username'] = $u['username'];
    $_SESSION['role']     = $u['role'];

    $canDev = ($u['role'] === 'developer');
    if (!$canDev && $u['role'] === 'admin') {
        $n = (int)$conn->query("SELECT COUNT(*) AS n FROM users WHERE role = 'developer'")->fetch_assoc()['n'];
        $canDev = ($n === 0);
        $_SESSION['no_developer'] = ($n === 0);
    }
    $_SESSION['can_dev'] = $canDev;

    if (empty($u['last_seen']) || (time() - strtotime($u['last_seen'])) >= 30) {
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE users SET last_seen = ? WHERE id = ?");
        $stmt->bind_param("si", $now, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

function require_staff($json = false) {
    require_login($json);
    $mine = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if ($mine !== 'employee' && $mine !== 'admin') {
        auth_deny($json, 403, 'You are not allowed to do this.', auth_home($mine));
    }
}

function require_role($role, $json = false) {
    require_login($json);
    $mine = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if ($role === 'developer') {
        $allowed = !empty($_SESSION['can_dev']);     
    } else {
        $allowed = ($mine === $role);
    }
    if (!$allowed) {
        auth_deny($json, 403, 'You are not allowed to do this.', auth_home($mine));
    }
}

function is_admin_level() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
function is_developer() {
    return !empty($_SESSION['can_dev']);
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}
function csrf_valid() {
    $sent = isset($_POST['csrf']) ? $_POST['csrf'] : (isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '');
    return is_string($sent) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}
function require_csrf($json = false) {
    if (!csrf_valid()) {
        if ($json) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Security check failed. Please refresh the page and try again.']);
            exit;
        }
        http_response_code(403);
        exit('Security check failed. Please go back, refresh the page and try again.');
    }
}

function kiosk_token() {
    if (empty($_SESSION['kiosk_token'])) {
        $_SESSION['kiosk_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['kiosk_token'];
}

function password_is_hashed($stored) {
    $info = password_get_info((string)$stored);
    return !empty($info['algo']);
}
function password_check($plain, $stored) {
    if (password_is_hashed($stored)) return password_verify($plain, $stored);
    return hash_equals((string)$stored, (string)$plain);       
}
function password_strong_enough($p) {
    return strlen($p) >= PASSWORD_MIN_LENGTH && preg_match('/[A-Za-z]/', $p) && preg_match('/\d/', $p);
}
function password_rule_text() {
    return 'At least ' . PASSWORD_MIN_LENGTH . ' characters with letters and numbers.';
}

function admin_menu_items() {
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    $items = [];
    if ($role === 'admin') {
        $items['dashboard'] = ['dashboard', '🏠', 'Dashboard'];
    }
    if (is_developer()) {          
        $items['users']  = ['admin',            '👥', 'Manage Users'];
        $items['videos'] = ['video_management', '🎬', 'Videos'];
    }
    if ($role === 'admin') {
        $items['reports'] = ['download_report', '📊', 'Reports'];
        $items['reset']   = ['reset_ticket',    '🔄', 'End of Day'];
    }
    return $items;
}

function admin_menu($active) {
    foreach (admin_menu_items() as $key => $it) {
        echo '<a href="' . $it[0] . '"' . ($key === $active ? ' class="active"' : '') . '><span class="icon">' . $it[1] . '</span> ' . $it[2] . '</a>' . "\n";
    }
    echo '<a href="logout"><span class="icon">🚪</span> Logout</a>' . "\n";
}
