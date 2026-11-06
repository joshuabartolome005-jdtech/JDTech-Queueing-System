<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/queue.php';
require_once __DIR__ . '/../includes/slips.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Invalid request.']);
    exit;
}

$sentToken = isset($_POST['token']) ? $_POST['token'] : '';
if (!is_string($sentToken) || !hash_equals(kiosk_token(), $sentToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Please refresh the page and try again.']);
    exit;
}

$clientType = isset($_POST['client_type']) ? $_POST['client_type'] : '';

$slipType = isset($_POST['slip_type']) ? $_POST['slip_type'] : '';
$slipJson = null;
if ($slipType !== '') {
    if (!isset(SLIP_TYPES[$slipType])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Invalid slip type.']);
        exit;
    }
    $clean = sanitize_slip_json(isset($_POST['slip_data']) ? $_POST['slip_data'] : '');
    if ($clean === false) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Invalid slip data.']);
        exit;
    }
    $slipJson = $clean;
} else {
    $slipType = null;
}

$conn = db_connect(true);
$result = queue_create_ticket($conn, $clientType, $slipType, $slipJson);
$conn->close();

if (!$result['ok']) {
    http_response_code($result['code']);
}
unset($result['code']);
echo json_encode($result);
