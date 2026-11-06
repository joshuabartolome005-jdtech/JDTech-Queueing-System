<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/queue.php';

require_staff(true);
require_csrf(true);
header('Content-Type: application/json');

$teller = $_SESSION['username'];
$finish = !empty($_POST['finish_current']);
$conn = db_connect(true);

try {
    $conn->begin_transaction();
    $now = date('Y-m-d H:i:s');


    $stmt = $conn->prepare("SELECT id, customer_number FROM queue WHERE status = 'serving' AND teller = ? FOR UPDATE");
    $stmt->bind_param("s", $teller);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($current) {
        if (!$finish) {
            $conn->rollback();
            echo json_encode(['ok' => false, 'message' => 'Please finish ticket ' . $current[0]['customer_number'] . ' first (press Done).']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE queue SET status = 'served', served_at = ? WHERE status = 'serving' AND teller = ?");
        $stmt->bind_param("ss", $now, $teller);
        $stmt->execute();
        $stmt->close();
    }

    $order = queue_next_order($teller);
    $res = $conn->query("SELECT id, customer_number, client_type FROM queue WHERE status = 'waiting' ORDER BY $order LIMIT 1 FOR UPDATE");
    $next = $res ? $res->fetch_assoc() : null;

    if (!$next) {
        $conn->commit();
        echo json_encode(['ok' => true, 'empty' => true, 'message' => 'No tickets are waiting right now.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE queue SET status = 'serving', teller = ?, called_at = ?, served_at = NULL WHERE id = ? AND status = 'waiting'");
    $stmt->bind_param("ssi", $teller, $now, $next['id']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['ok' => true, 'number' => (int)$next['customer_number'], 'client_type' => $next['client_type'],
        'message' => 'Now serving ticket ' . $next['customer_number'] . '.']);
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $e2) {}
    error_log('call_next: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not call the next ticket. Please try again.']);
}
$conn->close();
