<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/queue.php';

require_staff(true);
require_csrf(true);
header('Content-Type: application/json');

$teller = $_SESSION['username'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$id     = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$now    = date('Y-m-d H:i:s');

if (!in_array($action, ['recall', 'skip', 'no_show'], true)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid action.']);
    exit;
}

$conn = db_connect(true);
try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT customer_number, skip_count FROM queue WHERE id = ? AND status = 'serving' AND teller = ? FOR UPDATE");
    $stmt->bind_param("is", $id, $teller);
    $stmt->execute();
    $t = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$t) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'message' => 'You are not serving this ticket.']);
        exit;
    }

    if ($action === 'recall') {
        $stmt = $conn->prepare("UPDATE queue SET recall_count = recall_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $msg = 'Calling ticket ' . $t['customer_number'] . ' again.';
    } elseif ($action === 'skip') {
        if ((int)$t['skip_count'] >= MAX_SKIPS) {
            $conn->rollback();
            echo json_encode(['ok' => false, 'message' => 'This ticket was already skipped ' . MAX_SKIPS . ' times. Please mark it as No-show.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE queue SET status = 'waiting', teller = NULL, called_at = NULL, skip_count = skip_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $msg = 'Ticket ' . $t['customer_number'] . ' was moved to the end of the line.';
    } else {
        $stmt = $conn->prepare("UPDATE queue SET status = 'served', served_at = ?, no_show = 1 WHERE id = ?");
        $stmt->bind_param("si", $now, $id);
        $msg = 'Ticket ' . $t['customer_number'] . ' was marked as No-show.';
    }
    $stmt->execute();
    $stmt->close();
    $conn->commit();
    echo json_encode(['ok' => true, 'message' => $msg]);
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $e2) {}
    error_log('ticket_action: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not do that. Please try again.']);
}
$conn->close();
