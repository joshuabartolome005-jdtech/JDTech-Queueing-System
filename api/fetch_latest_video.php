<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
$conn = db_connect(true);

$videoResult = $conn->query("SELECT video_path FROM videos ORDER BY id DESC LIMIT 1");
$videoPath = "";
if ($videoResult && $row = $videoResult->fetch_assoc()) {
    if (!empty($row['video_path']) && file_exists(__DIR__ . '/../' . $row['video_path'])) {
        $videoPath = $row['video_path'];
    }
}

echo json_encode(["video_path" => $videoPath]);
$conn->close();
