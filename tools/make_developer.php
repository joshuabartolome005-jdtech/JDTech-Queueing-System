<?php
/*
 * Gumagawa (o nagpo-promote) ng DEVELOPER account.
 * Ang developer lang ang may access sa Manage Users at Videos.
 *
 * Paano gamitin (Command Prompt, sa loob ng folder ng system):
 *     C:\xampp\php\php.exe tools\make_developer.php juan_dev "MatibayNaPassword123"
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;                                   // hindi puwedeng patakbuhin sa browser
}

require_once __DIR__ . '/../includes/db.php';

$username = isset($argv[1]) ? trim($argv[1]) : '';
$password = isset($argv[2]) ? $argv[2] : '';

if ($username === '') { $username = trim((string)readline('Developer username: ')); }
if ($password === '') { $password = (string)readline('Password (at least ' . PASSWORD_MIN_LENGTH . ' characters, letters and numbers): '); }

if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) { fwrite(STDERR, "Invalid username (3-30 characters: letters, numbers, . - _).\n"); exit(1); }
if (strlen($password) < PASSWORD_MIN_LENGTH || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    fwrite(STDERR, "Password is too weak. At least " . PASSWORD_MIN_LENGTH . " characters with letters and numbers.\n");
    exit(1);
}

$conn = db_connect();
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $stmt = $conn->prepare("UPDATE users SET password = ?, role = 'developer', session_token = NULL WHERE id = ?");
    $stmt->bind_param("si", $hash, $existing['id']);
    $stmt->execute();
    echo "Updated: '$username' is now a developer.\n";
} else {
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'developer')");
    $stmt->bind_param("ss", $username, $hash);
    $stmt->execute();
    echo "Created developer account '$username'.\n";
}
$stmt->close();
$conn->close();
echo "Done. Mag-login sa login gamit ang account na ito.\n";
