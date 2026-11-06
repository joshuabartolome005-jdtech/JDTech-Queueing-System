<?php
require_once __DIR__ . '/../includes/queue.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$conn = db_connect(true);
echo json_encode(['ok' => true, 'tellers' => queue_board($conn)]);
$conn->close();
