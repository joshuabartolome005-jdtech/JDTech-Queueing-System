<?php
require_once __DIR__ . '/includes/auth.php';
require_role('admin');  
require_once __DIR__ . '/includes/queue.php';

$conn = db_connect();

$logged_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$logged_role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'ADMIN';

$counts = queue_counts($conn);
$total  = $counts['waiting'] + $counts['serving'] + $counts['served'];

$waitingByType = ['Priority' => 0, 'Regular' => 0, 'New Accounts' => 0, 'Other Services' => 0];
$r = $conn->query("SELECT client_type, COUNT(*) n FROM queue WHERE status = 'waiting' GROUP BY client_type");
while ($r && $row = $r->fetch_assoc()) {
    $waitingByType[$row['client_type']] = (int)$row['n'];
}

$tellers = [];
$r = $conn->query("SELECT username, last_seen FROM users WHERE role = 'employee' ORDER BY CAST(SUBSTRING_INDEX(username, '_', -1) AS UNSIGNED), username");
while ($r && $row = $r->fetch_assoc()) {
    $online = !empty($row['last_seen']) && (time() - strtotime($row['last_seen'])) < 120;
    $tellers[$row['username']] = ['serving' => null, 'served' => 0, 'noshow' => 0, 'online' => $online];
}
$r = $conn->query("SELECT teller, SUM(status = 'served' AND no_show = 0) AS served, SUM(no_show = 1) AS noshow, MAX(CASE WHEN status = 'serving' THEN customer_number END) AS serving FROM queue WHERE teller IS NOT NULL AND teller != '' GROUP BY teller");
while ($r && $row = $r->fetch_assoc()) {
    $name = $row['teller'];
    if (!isset($tellers[$name])) $tellers[$name] = ['serving' => null, 'served' => 0, 'noshow' => 0, 'online' => false];
    $tellers[$name]['serving'] = $row['serving'];
    $tellers[$name]['served']  = (int)$row['served'];
    $tellers[$name]['noshow']  = (int)$row['noshow'];
}

$avgWait = null;
$r = $conn->query("SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, called_at)) AS w FROM queue WHERE called_at IS NOT NULL");
if ($r && ($row = $r->fetch_assoc()) && $row['w'] !== null) $avgWait = round($row['w'] / 60, 1);

$warnings = [];
$r = $conn->query("SELECT username, password FROM users");
$weakUsers = [];
while ($u = $r->fetch_assoc()) {
    foreach (['123456', '654321', '12345678', 'password', 'admin', 'admin123', 'teller', 'teller123', '111111'] as $c) {
        if (password_check($c, $u['password'])) { $weakUsers[] = $u['username']; break; }
    }
}
if ($weakUsers) {
    $warnings[] = "Default o madaling hulaan pa ang password ng: " . implode(', ', $weakUsers) . ". "
        . (is_developer() ? "Palitan sa Manage Users." : "Sabihan ang developer na palitan ito.");
}
if (!empty($_SESSION['no_developer'])) $warnings[] = "Wala pang developer account. Habang wala, puwede pang pumasok ang admin sa Manage Users at Videos. Gumawa nito gamit ang tools/make_developer.php (tingnan ang README_SETUP.md).";

$withSlip = 0;
try {
    $r = $conn->query("SELECT COUNT(*) n FROM queue_slips");
    if ($r && $row = $r->fetch_assoc()) $withSlip = (int)$row['n'];
} catch (Throwable $e) { /* wala pa ang table na queue_slips */ }

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JDTech Queueing - Dashboard</title>
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
<?php admin_menu('dashboard'); ?>
    </div>
</div>

<div class="content">
    <h1>Dashboard</h1>

    <?php foreach ($warnings as $w): ?>
        <div class="alert-warn">⚠ <?php echo htmlspecialchars($w); ?></div>
    <?php endforeach; ?>

    <div class="cards">
        <div class="card c-wait"><div class="n"><?php echo $counts['waiting']; ?></div><div class="l">Waiting</div></div>
        <div class="card c-serv"><div class="n"><?php echo $counts['serving']; ?></div><div class="l">Being served</div></div>
        <div class="card c-done"><div class="n"><?php echo $counts['served']; ?></div><div class="l">Served</div></div>
        <div class="card c-no"><div class="n"><?php echo $counts['no_show']; ?></div><div class="l">No-show</div></div>
        <div class="card c-all"><div class="n"><?php echo $total; ?></div><div class="l">Total tickets</div></div>
        <div class="card c-wait2"><div class="n"><?php echo $avgWait === null ? '-' : $avgWait; ?></div><div class="l">Avg wait (min)</div></div>
        <div class="card c-slip"><div class="n"><?php echo $withSlip; ?></div><div class="l">With slip</div></div>
    </div>

    <div class="panel">
        <h2>Naghihintay ayon sa uri ng client</h2>
        <div class="chips">
            <?php foreach ($waitingByType as $type => $n): ?>
                <span class="chip"><?php echo htmlspecialchars($type); ?> <b><?php echo $n; ?></b></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel">
        <h2>Mga teller</h2>
        <div class="table-wrap"><table class="dash-table resp">
            <thead><tr><th>Teller</th><th>Status</th><th>Sine-serve ngayon</th><th>Natapos na</th><th>No-show</th></tr></thead>
            <tbody>
            <?php if ($tellers): foreach ($tellers as $name => $t): ?>
                <tr>
                    <td data-label="Teller"><?php echo htmlspecialchars($name); ?><?php echo $name === NEW_ACCOUNTS_TELLER ? ' <small>(New Accounts)</small>' : ''; ?></td>
                    <td data-label="Status"><span class="dot <?php echo $t['online'] ? 'on' : 'off'; ?>"></span><?php echo $t['online'] ? 'Online' : 'Offline'; ?></td>
                    <td data-label="Now serving"><?php echo $t['serving'] !== null ? '<b class="now">#' . htmlspecialchars($t['serving']) . '</b>' : '<span class="idle">idle</span>'; ?></td>
                    <td data-label="Served"><?php echo $t['served']; ?></td>
                    <td data-label="No-show"><?php echo $t['noshow']; ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5">Wala pang teller account. Magdagdag sa Manage Users.</td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
    </div>

    <div class="quick">
        <?php if (is_developer()): ?>
        <a href="admin">👥 Manage Users</a>
        <a href="video_management">🎬 Videos</a>
        <?php endif; ?>
        <a href="download_report">📊 Reports &amp; History</a>
        <a href="reset_ticket">🔄 End of Day</a>
        <a href="now_serving" target="_blank">📺 Open Now Serving screen</a>
    </div>

    <img src="assets/images/powerbyjd3.png" alt="Logo" class="bottom-right-image">
</div>

<script>setTimeout(function () { location.reload(); }, 10000);</script>
</body>
</html>
