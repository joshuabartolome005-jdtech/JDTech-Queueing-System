<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/queue.php';
require_once __DIR__ . '/includes/slips.php';

$conn = db_connect();
$info = queue_kiosk_info($conn);
$conn->close();
$generated_time = date("F j, Y | h:i:s A");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JDTech Queueing - get ticket</title>

<link rel="stylesheet" href="assets/css/customer.css">
<link rel="stylesheet" href="assets/css/slips.css">
<link rel="icon" type="image/png" href="assets/images/jdlogo.png">
</head>

<body>

<div class="logo-container">
    <img src="assets/images/bof_logo.png" alt="BOF Logo">
</div>

<img src="assets/images/powerbyjd3.png" alt="Logo" class="bottom-right-image">

<div class="timestamp" id="ticket-timestamp"><?php echo $generated_time; ?></div>

<main class="kiosk">
    <h1>Mayap a Aldo!</h1>
    <h2>WELCOME TO BOF QUEUE SYSTEM</h2>

    <div class="number-display">
        Your Number:<br>
        <span class="big-number" id="nextNumber"><?php echo (int)$info['next']; ?></span>
        <div class="waiting-info" id="waitingInfo">
            <?php echo $info['waiting'] > 0 ? 'People waiting: ' . (int)$info['waiting'] : 'No one is waiting'; ?>
        </div>
    </div>

    <div class="down-arrow">↓</div>

    <div class="client-type">
        <label><input type="radio" name="client_type" value="Regular" checked> Regular</label>
        <label><input type="radio" name="client_type" value="Priority"> Priority</label>
        <label><input type="radio" name="client_type" value="New Accounts"> New Accounts</label>
        <label><input type="radio" name="client_type" value="Other Services"> Other Services</label>
    </div>

    <div class="client-type slip-type">
        <div class="slip-type-title">Fill out a slip (optional):</div>
        <label><input type="radio" name="slip_type" value="" checked> No slip</label>
        <label><input type="radio" name="slip_type" value="cash_deposit"> Cash Deposit</label>
        <label><input type="radio" name="slip_type" value="withdrawal"> Withdrawal</label>
        <label><input type="radio" name="slip_type" value="check_deposit"> Check Deposit</label>
        <label><input type="radio" name="slip_type" value="ecpay"> ECPay Payment</label>
    </div>

    <div id="kioskMsg" class="kiosk-msg" role="alert"></div>
    <button id="mainBtn" onclick="startTicket()">PRINT YOUR TICKET</button>
</main>

<div id="slipOverlay" class="slip-overlay">
    <div class="slip-top">
        <button type="button" class="slip-back" onclick="closeSlip()">&larr; Back</button>
        <div id="slipTitle" class="slip-heading"></div>
    </div>
    <div class="slip-scroll" id="slipScroll">
        <?php foreach (SLIP_TYPES as $stype => $sname): ?>
        <div class="slip-wrap" data-type="<?php echo $stype; ?>" style="display:none"><?php echo render_slip($stype); ?></div>
        <?php endforeach; ?>
    </div>
    <div class="slip-bottom">
        <div id="slipError" class="slip-error"></div>
        <button type="button" id="slipSubmit" class="slip-submit" onclick="submitSlip()">SUBMIT &amp; PRINT TICKET</button>
    </div>
</div>

<script src="assets/js/slips.js"></script>
<script>
var KIOSK_TOKEN = <?php echo json_encode(kiosk_token()); ?>;
var SLIP_NAMES  = <?php echo json_encode(SLIP_TYPES); ?>;
var currentSlip = null;
var sending = false;

var ticketLogo = new Image();
ticketLogo.src = 'assets/images/bof_logo1.png';

function updateClock() {
    var el = document.getElementById('ticket-timestamp');
    if (!el) return;
    var now = new Date();
    var d = now.toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Manila' });
    var t = now.toLocaleString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'Asia/Manila' });
    el.innerText = d + ' | ' + t;
}
setInterval(updateClock, 1000);
updateClock();

function refreshInfo() {
    if (sending) return;
    fetch('api/kiosk_info.php', { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            document.getElementById('nextNumber').innerText = d.next;
            document.getElementById('waitingInfo').innerText = d.waiting > 0 ? 'People waiting: ' + d.waiting : 'No one is waiting';
        })
        .catch(function () {});
}
setInterval(refreshInfo, 5000);

function showKioskMsg(text) {
    var el = document.getElementById('kioskMsg');
    el.innerText = text || '';
    el.style.display = text ? 'block' : 'none';
    if (text) setTimeout(function () { if (el.innerText === text) { el.style.display = 'none'; } }, 6000);
}

function updateMainButton() {
    var slip = document.querySelector('input[name="slip_type"]:checked').value;
    document.getElementById('mainBtn').innerText = slip ? 'NEXT: FILL OUT YOUR SLIP' : 'PRINT YOUR TICKET';
}
document.querySelectorAll('input[name="slip_type"]').forEach(function (r) { r.addEventListener('change', updateMainButton); });

var lastTouch = Date.now();
['pointerdown', 'keydown', 'input', 'scroll'].forEach(function (ev) {
    document.addEventListener(ev, function () { lastTouch = Date.now(); }, true);
});
setInterval(function () {
    var overlayOpen = document.getElementById('slipOverlay').style.display === 'flex';
    var idle = (Date.now() - lastTouch) / 1000;
    if (overlayOpen && idle > 180 && !sending) location.reload();
}, 5000);

function startTicket() {
    var slip = document.querySelector('input[name="slip_type"]:checked').value;
    if (!slip) { printNumber('', ''); return; }
    openSlip(slip);
}

function openSlip(type) {
    var overlay = document.getElementById('slipOverlay');
    document.querySelectorAll('.slip-wrap').forEach(function (w) {
        w.style.display = (w.getAttribute('data-type') === type) ? 'block' : 'none';
    });
    currentSlip = document.querySelector('.slip-wrap[data-type="' + type + '"] .sl');
    document.getElementById('slipTitle').innerText = SLIP_NAMES[type];
    document.getElementById('slipError').innerText = '';
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    BankSlips.init(currentSlip);
    fitSlip();
    document.getElementById('slipScroll').scrollTop = 0;
}

function fitSlip() {
    if (!currentSlip) return;
    BankSlips.fit(document.getElementById('slipScroll'), currentSlip, 1.6);
    BankSlips.sizeCanvases(currentSlip);
}
window.addEventListener('resize', fitSlip);
window.addEventListener('orientationchange', function () { setTimeout(fitSlip, 200); });
document.getElementById('slipScroll').addEventListener('input', function () { document.getElementById('slipError').innerText = ''; });

function closeSlip() {
    location.reload();
}

function submitSlip() {
    var type = currentSlip.getAttribute('data-slip');
    var data = BankSlips.collect(currentSlip);
    var err = BankSlips.validate(currentSlip, data);
    if (err) { document.getElementById('slipError').innerText = err; return; }
    document.getElementById('slipError').innerText = '';
    printNumber(type, JSON.stringify(data));
}

function printNumber(slipType, slipData) {
    if (sending) return;
    sending = true;
    var clientType = document.querySelector('input[name="client_type"]:checked').value;
    var submitBtn = document.getElementById('slipSubmit');
    document.getElementById('mainBtn').disabled = true;
    submitBtn.disabled = true;

    var payload = { token: KIOSK_TOKEN, client_type: clientType };
    if (slipType) { payload.slip_type = slipType; payload.slip_data = slipData; }

    function fail(message) {
        sending = false;
        document.getElementById('mainBtn').disabled = false;
        submitBtn.disabled = false;
        if (document.getElementById('slipOverlay').style.display === 'flex') {
            document.getElementById('slipError').innerText = message;
        } else {
            showKioskMsg(message);
        }
    }

    fetch('api/insert_ticket.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(payload)
    })
    .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Something went wrong. Please try again.' }; }); })
    .then(function (res) {
        if (!res.ok) { fail(res.message || 'Something went wrong. Please try again.'); return; }
        printTicket(res.number, res.printed_at, clientType);
    })
    .catch(function () {
        fail('Cannot reach the system. Please try again or ask the bank staff for help.');
    });
}

function printTicket(ticketNumber, timestamp, clientType) {

    var printContent = `
<div style="text-align:center;font-size:18px;font-weight:bold;line-height:1.2;">
    <img src="assets/images/bof_logo1.png" style="width:90px;margin:0px auto 0;margin-bottom:-10px"><br>
    <div>Your Number:</div>
    <div style="font-size:32px;margin:-2px;">${ticketNumber}</div>

    <!-- ✅ CLIENT TYPE UNDER NUMBER -->
    <div style="font-size:14px;margin:2px 0 6px 0;">
        ${clientType}
    </div>

    <div style="font-size:12px;margin:1px 0 0 0;">${timestamp}</div>
    <div style="font-size:10px;">Please be seated</div>
    <div style="font-size:10px;">You will be served shortly.</div>
</div>`;

    document.body.innerHTML = printContent;

    var logo = document.querySelector('img');
    var printed = false;
    function doPrint() {
        if (printed) return;
        printed = true;
        window.print();
        location.reload();
    }
    setTimeout(doPrint, 3000);  
    if (logo && logo.decode) { logo.decode().then(doPrint, doPrint); } else { doPrint(); }
}
</script>
</body>
</html>
