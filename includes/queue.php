<?php

require_once __DIR__ . '/db.php';

function queue_client_types() {
    return ['Regular', 'Priority', 'New Accounts', 'Other Services'];
}


function queue_counts($conn) {
    $c = ['waiting' => 0, 'serving' => 0, 'served' => 0, 'no_show' => 0];
    $r = $conn->query("SELECT status, COUNT(*) n, SUM(no_show = 1) ns FROM queue GROUP BY status");
    while ($r && $row = $r->fetch_assoc()) {
        if (isset($c[$row['status']])) $c[$row['status']] = (int)$row['n'];
        $c['no_show'] += (int)$row['ns'];
    }
    $c['served'] -= $c['no_show']; 
    if ($c['served'] < 0) $c['served'] = 0;
    return $c;
}

/*
 * Sino ang susunod na tatawagin:
 *  - Ang na-skip ay napupunta sa dulo ng pila (skip_count).
 *  - Priority muna, tapos first-come-first-served (ticket number).
 *  - Ang New Accounts teller ang unang kumukuha ng New Accounts; ang ibang teller ay kukuha lang
 *    nito kapag wala nang ibang naghihintay (para walang maiwan kung wala ang New Accounts teller).
 */
function queue_next_order($tellerUsername) {
    $newAccounts = ($tellerUsername === NEW_ACCOUNTS_TELLER)
        ? "(client_type = 'New Accounts') DESC"
        : "(client_type = 'New Accounts') ASC";
    return "skip_count ASC, $newAccounts, (client_type = 'Priority') DESC, customer_number ASC";
}

function queue_signature($conn) {
    $r = $conn->query("SELECT MD5(GROUP_CONCAT(CONCAT(id, ':', status, ':', IFNULL(teller, ''), ':', skip_count) ORDER BY id)) AS sig FROM queue WHERE status != 'served'");
    $row = $r ? $r->fetch_assoc() : null;
    return ($row && $row['sig']) ? $row['sig'] : '';
}

function queue_type_class($clientType) {
    $t = strtolower((string)$clientType);
    if ($t === 'priority') return 'client-type-priority';
    if ($t === 'new accounts') return 'client-type-new';
    if ($t === 'other services') return 'client-type-other';
    return 'client-type-regular';
}

function queue_teller_label($username) {
    if ($username === NEW_ACCOUNTS_TELLER) return 'NEW ACCOUNTS';
    if (preg_match('/^teller_(\d+)$/i', $username, $m)) return 'TELLER ' . (int)$m[1];
    return strtoupper(str_replace('_', ' ', $username));
}
function queue_teller_spoken($username) {
    if ($username === NEW_ACCOUNTS_TELLER) return 'New Accounts Counter';
    if (preg_match('/^teller_(\d+)$/i', $username, $m)) return 'Teller ' . (int)$m[1];
    return ucwords(str_replace('_', ' ', $username));
}

function queue_board($conn) {
    $tellers = [];
    $r = $conn->query("SELECT username FROM users WHERE role = 'employee' ORDER BY CAST(SUBSTRING_INDEX(username, '_', -1) AS UNSIGNED), username");
    while ($r && $row = $r->fetch_assoc()) {
        $u = $row['username'];
        $tellers[$u] = ['key' => $u, 'label' => queue_teller_label($u), 'spoken' => queue_teller_spoken($u), 'ticket' => '', 'recall' => 0];
    }
    $r = $conn->query("SELECT customer_number, teller, recall_count FROM queue WHERE status = 'serving' ORDER BY id ASC");
    while ($r && $row = $r->fetch_assoc()) {
        if (isset($tellers[$row['teller']])) {
            $tellers[$row['teller']]['ticket'] = (string)$row['customer_number'];
            $tellers[$row['teller']]['recall'] = (int)$row['recall_count'];
        }
    }
    return array_values($tellers);
}

/*
 * Gumagawa ng bagong ticket. Ang SERVER ang nagbibigay ng number at oras (hindi ang browser),
 * kaya hindi na puwedeng magkapareho ang number kahit maraming kiosk.
 * Returns ['ok'=>true, 'number'=>..., 'id'=>..., 'printed_at'=>...] o ['ok'=>false, 'code'=>..., 'message'=>...]
 */
function queue_create_ticket($conn, $clientType, $slipType = null, $slipJson = null) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

    if (KIOSK_ALLOWED_IPS && !in_array($ip, KIOSK_ALLOWED_IPS, true)) {
        return ['ok' => false, 'code' => 403, 'message' => 'This computer is not allowed to create tickets.'];
    }
    if (!in_array($clientType, queue_client_types(), true)) {
        return ['ok' => false, 'code' => 400, 'message' => 'Invalid client type.'];
    }

    $lock = $conn->query("SELECT GET_LOCK('bq_new_ticket', 5) AS l")->fetch_assoc();
    if ((int)$lock['l'] !== 1) {
        return ['ok' => false, 'code' => 503, 'message' => 'The system is busy. Please try again.'];
    }

    try {
        $since = date('Y-m-d H:i:s', time() - TICKET_COOLDOWN_SECONDS);
        $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM queue WHERE kiosk_ip = ? AND created_at > ?");
        $stmt->bind_param("ss", $ip, $since);
        $stmt->execute();
        $recent = (int)$stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();
        if ($recent > 0) {
            return ['ok' => false, 'code' => 429, 'message' => 'Please wait a few seconds before getting another ticket.'];
        }

        $conn->begin_transaction();

        $row = $conn->query("SELECT COALESCE(MAX(customer_number), 0) + 1 AS n FROM queue")->fetch_assoc();
        $number = (int)$row['n'];
        $now = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO queue (customer_number, client_type, status, created_at, kiosk_ip) VALUES (?, ?, 'waiting', ?, ?)");
        $stmt->bind_param("isss", $number, $clientType, $now, $ip);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();

        if ($slipJson !== null) {
            $stmt = $conn->prepare("INSERT INTO queue_slips (queue_id, slip_type, slip_data) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id, $slipType, $slipJson);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        return ['ok' => true, 'number' => $number, 'id' => $id, 'printed_at' => date('F j, Y \a\t h:i:s A', strtotime($now))];
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        error_log('queue_create_ticket: ' . $e->getMessage());
        return ['ok' => false, 'code' => 500, 'message' => 'Could not save the ticket. Please try again.'];
    } finally {
        $conn->query("SELECT RELEASE_LOCK('bq_new_ticket')");
    }
}

function queue_kiosk_info($conn) {
    $row = $conn->query("SELECT COALESCE(MAX(customer_number), 0) + 1 AS next_number, SUM(status = 'waiting') AS waiting FROM queue")->fetch_assoc();
    return ['next' => (int)$row['next_number'], 'waiting' => (int)$row['waiting']];
}
