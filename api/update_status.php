<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/queue.php';

require_staff(true);
require_csrf(true);
header('Content-Type: application/json');

$conn = db_connect(true);

$id      = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$status  = isset($_POST['status']) ? $_POST['status'] : '';
$teller  = $_SESSION['username'];               
$isAdmin = is_admin_level();
$now     = date('Y-m-d H:i:s');

if (!in_array($status, ['waiting', 'serving', 'served'], true)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid status.']);
    exit;
}

$stmt = $conn->prepare("SELECT customer_number, status, teller FROM queue WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cur = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cur) {
    echo json_encode(['ok' => false, 'message' => 'Ticket not found.']);
    exit;
}

if (!$isAdmin && $cur['status'] === 'serving' && $cur['teller'] && $cur['teller'] !== $teller) {
    echo json_encode(['ok' => false, 'message' => 'Ticket ' . $cur['customer_number'] . ' is being served by ' . $cur['teller'] . '.']);
    exit;
}

if ($status === 'serving') {
    $stmt = $conn->prepare("SELECT customer_number FROM queue WHERE status = 'serving' AND teller = ? AND id <> ? LIMIT 1");
    $stmt->bind_param("si", $teller, $id);
    $stmt->execute();
    $other = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($other) {
        echo json_encode(['ok' => false, 'message' => 'Please finish ticket ' . $other['customer_number'] . ' first.']);
        exit;
    }
}

if ($status === 'serving') {
    $stmt = $conn->prepare("UPDATE queue SET status = 'serving', teller = ?, called_at = IF(status = 'serving', called_at, ?), served_at = NULL WHERE id = ?");
    $stmt->bind_param("ssi", $teller, $now, $id);
} elseif ($status === 'served') {
    $stmt = $conn->prepare("UPDATE queue SET status = 'served', teller = ?, served_at = ?, called_at = IFNULL(called_at, ?) WHERE id = ?");
    $stmt->bind_param("sssi", $teller, $now, $now, $id);
} else {
    $stmt = $conn->prepare("UPDATE queue SET status = 'waiting', teller = NULL, called_at = NULL, served_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
}
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'message' => 'Ticket updated']);
