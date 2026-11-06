<?php
require_once __DIR__ . '/includes/auth.php';
require_role('admin'); 

$conn = db_connect();

function valid_date($d) {
    return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
}
function csv_safe($v) {

    return (is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
}

function build_report($conn, $from, $to) {
    $sql = "SELECT d, teller, client_type, no_show, status, created_at, called_at, served_at FROM (
                SELECT ticket_date AS d, teller, client_type, no_show, status, created_at, called_at, served_at
                  FROM queue_history WHERE ticket_date BETWEEN ? AND ?
                UNION ALL
                SELECT DATE(created_at) AS d, teller, client_type, no_show, status, created_at, called_at, served_at
                  FROM queue WHERE DATE(created_at) BETWEEN ? AND ?
            ) t ORDER BY d, teller";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $from, $to, $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    $blank = ['served' => 0, 'regular' => 0, 'priority' => 0, 'new' => 0, 'other' => 0, 'noshow' => 0, 'unserved' => 0,
              'wait_sum' => 0, 'wait_n' => 0, 'svc_sum' => 0, 'svc_n' => 0];
    while ($t = $res->fetch_assoc()) {
        $teller = $t['teller'] ? $t['teller'] : '(not called)';
        $key = $t['d'] . '|' . $teller;
        if (!isset($rows[$key])) $rows[$key] = ['date' => $t['d'], 'teller' => $teller] + $blank;
        $r = &$rows[$key];

        if ($t['status'] !== 'served') { $r['unserved']++; unset($r); continue; }
        if ((int)$t['no_show'] === 1) {
            $r['noshow']++;
        } else {
            $r['served']++;
            if ($t['client_type'] === 'Regular') $r['regular']++;
            elseif ($t['client_type'] === 'Priority') $r['priority']++;
            elseif ($t['client_type'] === 'New Accounts') $r['new']++;
            else $r['other']++;
            if ($t['called_at'] && $t['served_at']) {
                $r['svc_sum'] += max(0, strtotime($t['served_at']) - strtotime($t['called_at']));
                $r['svc_n']++;
            }
        }
        if ($t['created_at'] && $t['called_at']) {
            $r['wait_sum'] += max(0, strtotime($t['called_at']) - strtotime($t['created_at']));
            $r['wait_n']++;
        }
        unset($r);
    }
    $stmt->close();
    return array_values($rows);
}

function minutes($sum, $n) {
    return $n > 0 ? round(($sum / $n) / 60, 1) : '';
}

$today = date('Y-m-d');
$src = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
$from = (isset($src['from']) && valid_date($src['from'])) ? $src['from'] : $today;
$to   = (isset($src['to'])   && valid_date($src['to']))   ? $src['to']   : $today;
if ($from > $to) { $tmp = $from; $from = $to; $to = $tmp; }
if ((strtotime($to) - strtotime($from)) > 366 * 86400) { $from = date('Y-m-d', strtotime($to . ' -366 days')); }

$rows = build_report($conn, $from, $to);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_csv'])) {
    require_csrf();
    $filename = "queue_report_" . $from . ($from === $to ? "" : "_to_" . $to) . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen("php://output", "w");
    fwrite($out, "\xEF\xBB\xBF");           // para tama ang labas sa Excel
    fputcsv($out, ['Date', 'Teller Userid', 'Total Number Clients Served', 'Regular Clients', 'Priority Clients',
                   'New Accounts Clients', 'Other Services Clients', 'No-show', 'Not Served', 'Avg Waiting (min)', 'Avg Service (min)']);
    $tot = ['served' => 0, 'regular' => 0, 'priority' => 0, 'new' => 0, 'other' => 0, 'noshow' => 0, 'unserved' => 0];
    foreach ($rows as $r) {
        fputcsv($out, [$r['date'], csv_safe($r['teller']), $r['served'], $r['regular'], $r['priority'], $r['new'], $r['other'],
                       $r['noshow'], $r['unserved'], minutes($r['wait_sum'], $r['wait_n']), minutes($r['svc_sum'], $r['svc_n'])]);
        foreach ($tot as $k => $v) $tot[$k] += $r[$k];
    }
    fputcsv($out, ['TOTAL', '', $tot['served'], $tot['regular'], $tot['priority'], $tot['new'], $tot['other'], $tot['noshow'], $tot['unserved'], '', '']);
    fclose($out);
    exit();
}

$logged_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$logged_role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'ADMIN';
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JDTech Queueing - Reports</title>

<link rel="stylesheet" href="assets/css/download_report.css">
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
<?php admin_menu('reports'); ?>
    </div>
</div>

<div class="content">
    <h1>Teller Performance Report</h1>

    <form method="GET" class="report-filter">
        <label>From <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" max="<?php echo $today; ?>"></label>
        <label>To <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" max="<?php echo $today; ?>"></label>
        <button type="submit">Show</button>
    </form>

    <div class="table-wrap">
    <table class="dash-table resp">
        <thead>
            <tr><th>Date</th><th>Teller</th><th>Served</th><th>Regular</th><th>Priority</th><th>New Acct</th><th>Other</th><th>No-show</th><th>Not served</th><th>Avg wait (min)</th><th>Avg service (min)</th></tr>
        </thead>
        <tbody>
        <?php if ($rows): foreach ($rows as $r): ?>
            <tr>
                <td data-label="Date"><?php echo htmlspecialchars($r['date']); ?></td>
                <td data-label="Teller"><?php echo htmlspecialchars($r['teller']); ?></td>
                <td data-label="Served"><b><?php echo $r['served']; ?></b></td>
                <td data-label="Regular"><?php echo $r['regular']; ?></td>
                <td data-label="Priority"><?php echo $r['priority']; ?></td>
                <td data-label="New Accounts"><?php echo $r['new']; ?></td>
                <td data-label="Other Services"><?php echo $r['other']; ?></td>
                <td data-label="No-show"><?php echo $r['noshow']; ?></td>
                <td data-label="Not served"><?php echo $r['unserved']; ?></td>
                <td data-label="Avg wait (min)"><?php echo minutes($r['wait_sum'], $r['wait_n']) === '' ? '-' : minutes($r['wait_sum'], $r['wait_n']); ?></td>
                <td data-label="Avg service (min)"><?php echo minutes($r['svc_sum'], $r['svc_n']) === '' ? '-' : minutes($r['svc_sum'], $r['svc_n']); ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="11">No tickets for the selected date.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <form method="POST" class="report-download">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="from" value="<?php echo htmlspecialchars($from); ?>">
        <input type="hidden" name="to" value="<?php echo htmlspecialchars($to); ?>">
        <button type="submit" name="download_csv">⬇ Download CSV Report</button>
    </form>

    <img src="assets/images/powerbyjd3.png" alt="Logo" class="bottom-right-image">
</div>

</body>
</html>
