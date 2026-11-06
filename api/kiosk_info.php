<?php

require_once __DIR__ . '/../includes/queue.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
$conn = db_connect(true);
echo json_encode(queue_kiosk_info($conn));
$conn->close();
