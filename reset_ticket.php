<?php
require_once __DIR__ . '/includes/auth.php';
require_role('admin');   
require_once __DIR__ . '/includes/queue.php';

$conn = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_ticket_numbers'])) {
    require_csrf();

    $now_counts = queue_counts($conn);
    if (($now_counts['waiting'] + $now_counts['serving']) > 0 && empty($_POST['confirm_active'])) {
        $_SESSION['message'] = "Reset cancelled: there are still tickets waiting or being served. Please tick the confirmation box first.";
        header("Location: reset_ticket");
        exit();
    }

    try {
        $now = date('Y-m-d H:i:s');
        $conn->begin_transaction();
        $stmt = $conn->prepare("INSERT INTO queue_history
            (ticket_date, customer_number, client_type, teller, status, no_show, skip_count, slip_type, created_at, called_at, served_at, archived_at)
            SELECT DATE(IFNULL(q.created_at, ?)), q.customer_number, q.client_type, q.teller, q.status, q.no_show, q.skip_count, s.slip_type, q.created_at, q.called_at, q.served_at, ?
            FROM queue q LEFT JOIN queue_slips s ON s.queue_id = q.id");
        $stmt->bind_param("ss", $now, $now);
        $stmt->execute();
        $archived = $stmt->affected_rows;
        $stmt->close();
        $conn->query("DELETE FROM queue");        
        $conn->commit();
        $conn->query("ALTER TABLE queue AUTO_INCREMENT = 1");
        $_SESSION['message'] = "Done! " . $archived . " ticket(s) were saved to the history and the numbers restarted at 1.";
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        error_log('reset_ticket: ' . $e->getMessage());
        $_SESSION['message'] = "Error resetting ticket numbers. Nothing was deleted.";
    }

    header("Location: reset_ticket");
    exit();
}

$logged_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$logged_role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'ADMIN';

$counts = queue_counts($conn);
$active_tickets = $counts['waiting'] + $counts['serving'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDTech Queueing - End of Day</title>
<link rel="stylesheet" href="assets/css/reset_ticket.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="icon" type="image/png" href="assets/images/jdlogo.png">
</head>
<body>

    <div class="sidebar">
        <img src="assets/images/jdlogo.png" alt="JDTech Logo" class="sidebar-logo">
        <div class="sidebar-title">JDTech Solutions</div>

        <div class="user-card">
            <div class="user-name"><?php echo htmlspecialchars($logged_user); ?></div>
            <div class="user-subtitle">Branch Operation Officer</div>
            <span class="role-badge"><?php echo htmlspecialchars($logged_role); ?></span>
        </div>

        <div class="nav-section-label">Main</div>
        <div class="sidebar-menu">
<?php admin_menu('reset'); ?>
        </div>
    </div>

    <div class="content">
        <h1>End of Day</h1>

        <?php
        if (isset($_SESSION['message'])) {
            echo "<p class='message'>" . htmlspecialchars($_SESSION['message']) . "</p>";
            unset($_SESSION['message']);
        }
        ?>

        <p class="reset-note">Use this at the end of the day. All tickets will be saved to the history (and will remain accessible under Reports), after which the queue will reset, starting again from ticket number 1. The client slips will be cleared.</p>
        <div class="reset-counts">
            <span><b><?php echo $counts['waiting']; ?></b> waiting</span>
            <span><b><?php echo $counts['serving']; ?></b> being served</span>
            <span><b><?php echo $counts['served']; ?></b> served</span>
            <span><b><?php echo $counts['no_show']; ?></b> no-show</span>
        </div>

        <form action="reset_ticket" method="POST" onsubmit="return confirm('Save today to the history and restart at ticket number 1? The slips of the clients will be deleted.');">
            <?php echo csrf_field(); ?>
            <?php if ($active_tickets > 0): ?>
            <label class="reset-warn">
                <input type="checkbox" name="confirm_active" value="1" id="confirmActive" onchange="document.getElementById('resetBtn').disabled = !this.checked;">
                There are <?php echo $active_tickets; ?> tickets left that have not been processed yet. No more names will be called after this.
            </label>
            <?php endif; ?>
            <button type="submit" name="reset_ticket_numbers" id="resetBtn" <?php echo $active_tickets > 0 ? 'disabled' : ''; ?>>Save to history &amp; reset to 1</button>
        </form>

        <img src="assets/images/powerbyjd3.png" alt="Logo" class="bottom-right-image">
    </div>

</body>
</html>
