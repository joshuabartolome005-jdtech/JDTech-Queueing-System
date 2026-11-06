<?php
require_once __DIR__ . '/includes/queue.php';

$conn = db_connect();
$tellers = queue_board($conn);

$videoPath = "";
$videoResult = $conn->query("SELECT video_path FROM videos ORDER BY id DESC LIMIT 1");
if ($videoResult && $row = $videoResult->fetch_assoc()) {
    if (!empty($row['video_path']) && file_exists(__DIR__ . '/' . $row['video_path'])) {
        $videoPath = $row['video_path'];
    }
}
$conn->close();
$current_datetime = date("F j, Y | h:i:s A");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JDTech Queueing - now serving</title>
<link rel="stylesheet" href="assets/css/now_serving.css">
<link rel="icon" href="assets/images/jdlogo.png">
</head>
<body>

<div class="main-layout">

    <div class="left-content">
        <div class="logo-top">
            <img src="assets/images/bof_logo_white.png" alt="Logo">
        </div>

        <div class="video-section">
            <?php if (!empty($videoPath)): ?>
                <video id="promoVideo" src="<?= htmlspecialchars($videoPath); ?>" autoplay loop muted playsinline></video>
            <?php else: ?>
                <p class="no-video">No video uploaded</p>
            <?php endif; ?>
        </div>

        <div class="branch-info">
            <div class="branch-label"><?= htmlspecialchars(BRANCH_NAME); ?></div>
            <div class="datetime" id="datetime"><?= $current_datetime; ?></div>
        </div>
    </div>

    <div class="serving-section">
        <h1>NOW SERVING</h1>
        <div class="counter-container">
            <div class="counter-row" id="counterRow" data-keys="<?= htmlspecialchars(implode('|', array_column($tellers, 'key'))); ?>">
            <?php foreach ($tellers as $t): ?>
                <div class="teller-box" data-teller="<?= htmlspecialchars($t['key']); ?>">
                    <div class="counter-label"><?= htmlspecialchars($t['label']); ?></div>
                    <div class="counter">
                        <p data-len="<?= strlen($t['ticket']); ?>"><?= $t['ticket'] !== '' ? htmlspecialchars($t['ticket']) : '_'; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="marquee-container">
    <div class="marquee-track">
        <span>Attention: Please proceed to the counter when your number is called. &nbsp;|&nbsp; Need a Queuing System provider or custom small-scale software solutions? Contact us at: jdtech0526@gmail.com</span>
    </div>
    <img src="assets/images/powerbyjd3.png" class="bottom-right-image" alt="JD Tech">
</div>

<div id="soundBanner" class="sound-banner">🔊 Click anywhere to turn on the sound</div>
<div id="offlineBanner" class="offline-banner">Reconnecting to the system...</div>

<audio id="buzzerSound" src="assets/audio/buzzer.mp3" preload="auto"></audio>

<script>
window.NOW_SERVING = {
    initial: <?= json_encode($tellers); ?>,
    videoPath: <?= json_encode($videoPath); ?>
};
</script>
<script src="assets/js/now_serving.js"></script>
</body>
</html>
