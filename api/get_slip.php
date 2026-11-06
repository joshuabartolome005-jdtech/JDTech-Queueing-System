<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/slips.php';

require_staff(true);

$conn = db_connect(true);
$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

$stmt = $conn->prepare("SELECT slip_type, slip_data FROM queue_slips WHERE queue_id = ?");
$stmt->bind_param("i", $ticketId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();
$conn->close();

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
if (!$row) {
    echo '<div class="slip-none">No slip was filled out for this ticket.</div>';
    exit;
}

$data = json_decode($row['slip_data'], true);
echo render_slip($row['slip_type'], is_array($data) ? $data : [], true);
