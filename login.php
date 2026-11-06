<?php
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    header("Location: " . auth_home($_SESSION['role']));
    exit();
}

$conn = db_connect();
$error = '';

$notices = [
    'idle'  => 'You were logged out because of inactivity. Please log in again.',
    'other' => 'This account was logged in on another device, so this session was closed.',
    'bye'   => 'You have been logged out.',
];
$flash = auth_flash_get();           
$notice = isset($notices[$flash]) ? $notices[$flash] : '';

$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $window   = date('Y-m-d H:i:s', time() - LOGIN_LOCK_MINUTES * 60);

    $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM login_attempts WHERE ip = ? AND attempted_at > ?");
    $stmt->bind_param("ss", $ip, $window);
    $stmt->execute();
    $failed = (int)$stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();

    if ($failed >= LOGIN_MAX_ATTEMPTS) {
        $error = "Too many failed attempts. Please wait " . LOGIN_LOCK_MINUTES . " minutes and try again.";
    } else {
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $ok = false;
        if ($user) {
            $ok = password_check($password, $user['password']);
        } else {
            password_verify($password, '$2y$10$usesomesillystringforsaltuQ0eQ0eQ0eQ0eQ0eQ0eQ0eQ0eQ0e');
        }

        if ($ok) {

            session_regenerate_id(true);
            $token = bin2hex(random_bytes(32));
            $now = date('Y-m-d H:i:s');

            if (!password_is_hashed($user['password']) || password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $newHash, $user['id']);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $conn->prepare("UPDATE users SET session_token = ?, last_seen = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $now, $user['id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip = ?");
            $stmt->bind_param("s", $ip);
            $stmt->execute();
            $stmt->close();

            $_SESSION['user_id']       = (int)$user['id'];
            $_SESSION['username']      = $username;
            $_SESSION['role']          = $user['role'];
            $_SESSION['token']         = $token;
            $_SESSION['last_activity'] = time();
            $conn->close();

            header("Location: " . auth_home($user['role']));
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO login_attempts (ip, username, attempted_at) VALUES (?, ?, ?)");
        $now = date('Y-m-d H:i:s');
        $short = substr($username, 0, 50);
        $stmt->bind_param("sss", $ip, $short, $now);
        $stmt->execute();
        $stmt->close();
        $error = "Invalid username or password.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDTech Queueing - Login</title>
<link rel="stylesheet" href="assets/css/login.css">
    <link rel="icon" type="image/png" href="assets/images/jdlogo.png">
</head>
<body>

<img src="assets/images/bof_logo.png" alt="BOF Logo" class="top-right-logo">

<div class="login-container">

    <img src="assets/images/jdlogo.png" alt="JDTech Logo" class="header-logo">
    
    <h1>JDTech Queueing<br>System</h1>
    
    <?php if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; } elseif ($notice) { echo "<p class='notice'>" . htmlspecialchars($notice) . "</p>"; } ?>
    
    <form action="login" method="POST">
        <input type="text" name="username" placeholder="Username" required autocomplete="username" autocapitalize="none" autofocus>
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        <button type="submit">Login</button>
    </form>
</div>

<div class="footer-copyright">
    &copy; 2025 JoshDeveryTech Solutions
</div>

</body>
</html>