<?php

require_once __DIR__ . '/includes/auth.php';
require_staff();

require_once __DIR__ . '/includes/queue.php';
require_once __DIR__ . '/includes/slips.php';

$conn = db_connect();

$tellerUsername = $_SESSION['username'];

$order = "(q.status = 'serving') DESC, q.skip_count ASC, (q.client_type = 'Priority') DESC, q.customer_number ASC";
$result = $conn->query("SELECT q.*, s.slip_type FROM queue q LEFT JOIN queue_slips s ON s.queue_id = q.id WHERE q.status != 'served' ORDER BY $order");
$tickets = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$waitingCount = 0;
$servingCount = 0;
$mine = null;                    
foreach ($tickets as $t) {
    if ($t['status'] === 'waiting') $waitingCount++;
    if ($t['status'] === 'serving') {
        $servingCount++;
        if ($mine === null && $t['teller'] === $tellerUsername) $mine = $t;
    }
}
$signature = queue_signature($conn);

$conn->close();

function slip_button($t) {
    if (!empty($t['slip_type']) && isset(SLIP_TYPES[$t['slip_type']])) {
        $name = SLIP_TYPES[$t['slip_type']];
        return '<button type="button" class="slip-view-btn" data-ticket-id="' . (int)$t['id'] . '" data-title="Ticket '
            . htmlspecialchars($t['customer_number']) . ' - ' . htmlspecialchars($name) . '">' . htmlspecialchars($name) . '</button>';
    }
    return '<span class="no-slip">-</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDTech Queueing - Teller</title>
<link rel="stylesheet" href="assets/css/teller.css">
<link rel="stylesheet" href="assets/css/slips.css">
<link rel="stylesheet" href="assets/css/teller_responsive.css">
    <link rel="icon" type="image/png" href="assets/images/jdlogo.png">
</head>
<body>

<div class="header">
    <div class="header-brand">
        <img src="assets/images/bof_logo.png" alt="BOF Logo" class="logo">

        <h1 class="page-title">Teller Page</h1>
    </div>

    <div class="teller-info">
        <span class="teller-label"><strong><?php echo htmlspecialchars($tellerUsername); ?></strong></span>
        <button class="logout-btn" onclick="logout()">Logout</button>
    </div>
</div>

<div class="queue-stats">
    <div class="stat stat-waiting"><span class="stat-n"><?php echo $waitingCount; ?></span> Waiting</div>
    <div class="stat stat-serving"><span class="stat-n"><?php echo $servingCount; ?></span> Being served</div>
</div>

<?php if ($mine): ?>
<div class="my-ticket serving">
    <div class="mt-label">You are serving</div>
    <div class="mt-number"><?php echo htmlspecialchars($mine['customer_number']); ?></div>
    <div class="mt-type <?php echo queue_type_class($mine['client_type']); ?>"><?php echo htmlspecialchars($mine['client_type']); ?></div>
    <?php if (!empty($mine['slip_type'])): ?><div class="mt-slip"><?php echo slip_button($mine); ?></div><?php endif; ?>
    <?php if ((int)$mine['skip_count'] > 0): ?><div class="mt-hint">Skipped <?php echo (int)$mine['skip_count']; ?> time/s already</div><?php endif; ?>
    <div class="mt-actions">
        <button type="button" class="btn-done" id="btnDone" data-ticket-id="<?php echo (int)$mine['id']; ?>">&#10004; Done</button>
        <button type="button" class="btn-next" id="btnDoneNext" <?php echo $waitingCount < 1 ? 'disabled' : ''; ?>>Done &amp; Call Next</button>
    </div>
    <div class="mt-actions mt-secondary">
        <button type="button" class="btn-recall" data-action="recall" data-ticket-id="<?php echo (int)$mine['id']; ?>">&#128266; Call again</button>
        <?php if ((int)$mine['skip_count'] < MAX_SKIPS): ?>
        <button type="button" class="btn-skip" data-action="skip" data-ticket-id="<?php echo (int)$mine['id']; ?>">&#8631; Skip (not here)</button>
        <?php endif; ?>
        <button type="button" class="btn-noshow" data-action="no_show" data-ticket-id="<?php echo (int)$mine['id']; ?>">&#10006; No-show</button>
    </div>
</div>
<?php else: ?>
<div class="my-ticket idle">
    <div class="mt-label">No ticket in service</div>
    <div class="mt-actions">
        <button type="button" class="btn-next" id="btnNext" <?php echo $waitingCount < 1 ? 'disabled' : ''; ?>>
            CALL NEXT <?php echo $waitingCount > 0 ? '(' . $waitingCount . ' waiting)' : ''; ?>
        </button>
    </div>
    <?php if ($waitingCount < 1): ?><div class="mt-hint">No one is waiting right now.</div><?php endif; ?>
</div>
<?php endif; ?>

<div id="statusMessage" class="message"></div>
<div id="updateBanner" class="update-banner" onclick="location.reload()">The queue has changed. Tap here to refresh.</div>

<div class="table-container">
    <table class="resp">
        <thead>
            <tr>
                <th>Ticket Number</th>
                <th>Status</th>
                <th>Client Type</th>
                <th>Slip</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tickets) {
                foreach ($tickets as $ticket) {
                    $isMine = ($ticket['status'] === 'serving' && $ticket['teller'] === $tellerUsername);
                    $otherTeller = ($ticket['status'] === 'serving' && !$isMine);
                    ?>
                    <tr id="ticket-<?php echo $ticket['id']; ?>" class="<?php echo $isMine ? 'row-mine' : ($otherTeller ? 'row-other' : ''); ?>">
                        <td data-label="Ticket"><strong><?php echo htmlspecialchars($ticket['customer_number']); ?></strong></td>

                        <td data-label="Status">
                            <span class="status status-<?php echo $ticket['status']; ?>">
                                <?php echo ucfirst($ticket['status']); ?>
                            </span>
                            <?php if ($ticket['status'] === 'serving'): ?>
                                <div class="by-teller"><?php echo $isMine ? 'by you' : 'by ' . htmlspecialchars($ticket['teller']); ?></div>
                            <?php elseif ((int)$ticket['skip_count'] > 0): ?>
                                <div class="by-teller">skipped <?php echo (int)$ticket['skip_count']; ?>x</div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Client" class="<?php echo queue_type_class($ticket['client_type']); ?>"><?php echo htmlspecialchars($ticket['client_type']); ?></td>
                        <td data-label="Slip" class="slip-cell"><?php echo slip_button($ticket); ?></td>
                        <td data-label="Action">
                            <?php if ($otherTeller): ?>
                                <span class="no-slip">Being served by another teller</span>
                            <?php else: ?>
                            <div class="action-cell">
                                <select class="status-dropdown" data-ticket-id="<?php echo $ticket['id']; ?>">
                                    <option value="waiting" <?php echo ($ticket['status'] == 'waiting') ? 'selected' : ''; ?>>Waiting</option>
                                    <option value="serving" <?php echo ($ticket['status'] == 'serving') ? 'selected' : ''; ?>>Serving</option>
                                    <option value="served" <?php echo ($ticket['status'] == 'served') ? 'selected' : ''; ?>>Served</option>
                                </select>
                                <button class="update-status-btn" data-ticket-id="<?php echo $ticket['id']; ?>">Update</button>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php }
            } else { echo "<tr><td colspan='5'>No tickets in the queue.</td></tr>"; } ?>
        </tbody>
    </table>
</div>

<div id="slipModal" class="slip-modal">
    <div class="slip-modal-top">
        <div id="slipModalTitle" class="slip-modal-title"></div>
        <button type="button" class="slip-modal-close" onclick="closeSlipModal()">Close</button>
    </div>
    <div id="slipModalBody" class="slip-modal-body"></div>
</div>

<img src="assets/images/powerbyjd3.png" alt="Logo" class="bottom-right-image">

<script src="assets/js/slips.js"></script>
<script>
function logout() {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = "logout";
    }
}

function showMessage(text) {
    var el = document.getElementById("statusMessage");
    el.innerText = text;
    el.style.display = text ? "block" : "none";
}

var CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
function postJson(url, params) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': CSRF_TOKEN },
        body: new URLSearchParams(params),
        credentials: 'same-origin'
    }).then(function (response) {
        if (response.status === 401) { window.location.href = "login?msg=idle"; return Promise.reject('session'); }
        return response.json();
    });
}

function updateTicketStatus(ticketId, newStatus) {
    showMessage("Updating...");
    postJson('api/update_status.php', { ticket_id: ticketId, status: newStatus })
    .then(function (data) {
        showMessage(data.message);
        if (data.ok) { setTimeout(function () { location.reload(); }, 400); }
    })
    .catch(function (error) {
        if (error !== 'session') showMessage("Failed to update status.");
    });
}
function callNext(finishCurrent) {
    showMessage("Calling next ticket...");
    var buttons = document.querySelectorAll('.btn-next, .btn-done');
    buttons.forEach(function (b) { b.disabled = true; });
    postJson('api/call_next.php', finishCurrent ? { finish_current: 1 } : {})
    .then(function (data) {
        showMessage(data.message);
        if (data.ok && !data.empty) { setTimeout(function () { location.reload(); }, 300); }
        else if (data.ok && data.empty) { setTimeout(function () { location.reload(); }, 900); }
        else { buttons.forEach(function (b) { b.disabled = false; }); }
    })
    .catch(function (error) {
        buttons.forEach(function (b) { b.disabled = false; });
        if (error !== 'session') showMessage("Could not call the next ticket. Please try again.");
    });
}

function ticketAction(action, ticketId) {
    if (action === 'no_show' && !confirm("Mark this ticket as No-show? It will be closed.")) return;
    if (action === 'skip' && !confirm("Move this ticket to the end of the line?")) return;
    showMessage("Please wait...");
    postJson('api/ticket_action.php', { action: action, ticket_id: ticketId })
    .then(function (data) {
        showMessage(data.message);
        if (data.ok && action !== 'recall') { setTimeout(function () { location.reload(); }, 400); }
    })
    .catch(function (error) { if (error !== 'session') showMessage("Could not do that. Please try again."); });
}

function finishMine(ticketId) {
    updateTicketStatus(ticketId, 'served');
}

function fitModalSlip() {
    var body = document.getElementById("slipModalBody");
    var slip = body.querySelector(".sl");
    if (slip) BankSlips.fit(body, slip, 1.25);
}
function openSlipModal(ticketId, title) {
    var modal = document.getElementById("slipModal");
    var body = document.getElementById("slipModalBody");
    document.getElementById("slipModalTitle").innerText = title;
    body.innerHTML = "Loading...";
    modal.classList.add("open");
    fetch("api/get_slip.php?ticket_id=" + encodeURIComponent(ticketId), { credentials: "same-origin" })
        .then(function (r) { return r.ok ? r.text() : Promise.reject(); })
        .then(function (html) { body.innerHTML = html; body.scrollTop = 0; fitModalSlip(); })
        .catch(function () { body.innerHTML = "Could not load the slip."; });
}
function closeSlipModal() {
    document.getElementById("slipModal").classList.remove("open");
    document.getElementById("slipModalBody").innerHTML = "";
}
window.addEventListener("resize", fitModalSlip);

var queueSignature = "<?php echo $signature; ?>";
function tellerIsBusy() {
    var modalOpen = document.getElementById("slipModal").classList.contains("open");
    var a = document.activeElement;
    var editing = a && (a.tagName === "SELECT");
    return modalOpen || editing;
}
setInterval(function () {
    fetch("api/teller_state.php", { credentials: "same-origin" })
    .then(function (r) {
        if (r.status === 401) { window.location.href = "login?msg=idle"; return null; }
        return r.json();
    })
    .then(function (d) {
        if (!d || !d.ok || d.sig === queueSignature) return;
        if (tellerIsBusy()) { document.getElementById("updateBanner").style.display = "block"; }
        else { location.reload(); }
    })
    .catch(function () {});
}, 4000);

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".slip-view-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            openSlipModal(this.getAttribute("data-ticket-id"), this.getAttribute("data-title"));
        });
    });
    document.querySelectorAll(".update-status-btn").forEach(button => {
        button.addEventListener("click", function() {
            const ticketId = this.getAttribute("data-ticket-id");
            const newStatus = this.previousElementSibling.value;
            updateTicketStatus(ticketId, newStatus);
        });
    });
    var next = document.getElementById("btnNext");
    if (next) next.addEventListener("click", function () { callNext(false); });
    var doneNext = document.getElementById("btnDoneNext");
    if (doneNext) doneNext.addEventListener("click", function () { callNext(true); });
    document.querySelectorAll("[data-action]").forEach(function (b) {
        b.addEventListener("click", function () { ticketAction(this.getAttribute("data-action"), this.getAttribute("data-ticket-id")); });
    });
    var done = document.getElementById("btnDone");
    if (done) done.addEventListener("click", function () { finishMine(this.getAttribute("data-ticket-id")); });
});
</script>
</body>
</html>
