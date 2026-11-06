<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/queue.php';

require_staff(true);
header('Content-Type: application/json');
header('Cache-Control: no-store');

$conn = db_connect(true);
$sig = queue_signature($conn);
$counts = queue_counts($conn);
$conn->close();

echo json_encode(['ok' => true, 'sig' => $sig, 'counts' => $counts]);
