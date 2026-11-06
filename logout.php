<?php
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    $conn = db_connect();
    $stmt = $conn->prepare("UPDATE users SET session_token = NULL, last_seen = NULL WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

auth_destroy_session('bye');
header("Location: login");
exit();
