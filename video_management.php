<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_role('developer'); 

$conn = db_connect();
$uploadDir = __DIR__ . '/uploads/';
$message = '';
$message_ok = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_video'])) {
    require_csrf();
    $id = (int)$_POST['delete_video'];
    $stmt = $conn->prepare("SELECT video_path FROM videos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $real = realpath(__DIR__ . '/' . $row['video_path']);
        if ($real && strpos($real, realpath($uploadDir)) === 0 && is_file($real)) {
            unlink($real);
        }
        $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: video_management?page=" . max(1, (int)(isset($_POST['page']) ? $_POST['page'] : 1)));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["video"])) {
    require_csrf();
    $f = $_FILES["video"];
    $allowedTypes = ["mp4", "avi", "mov", "wmv"];
    $allowedMime  = ["video/mp4", "video/x-msvideo", "video/quicktime", "video/x-ms-wmv", "video/avi", "video/msvideo", "application/octet-stream", "video/x-m4v"];

    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        $message = "The file is too big. The server allows up to " . ini_get('upload_max_filesize') . " (change upload_max_filesize and post_max_size in php.ini).";
    } elseif ($f['error'] !== UPLOAD_ERR_OK) {
        $message = "Error uploading file.";
    } else {
        $videoName = basename($f["name"]);
        $fileType = strtolower(pathinfo($videoName, PATHINFO_EXTENSION));
        $mime = function_exists('mime_content_type') ? mime_content_type($f["tmp_name"]) : 'video/mp4';

        if (!in_array($fileType, $allowedTypes, true) || !in_array($mime, $allowedMime, true)) {
            $message = "Invalid format. Only MP4, AVI, MOV, WMV allowed.";
        } else {
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $uniqueName = uniqid("video_", true) . '.' . $fileType;
            $targetFilePath = "uploads/" . $uniqueName;

            if (move_uploaded_file($f["tmp_name"], __DIR__ . '/' . $targetFilePath)) {
                $today = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("INSERT INTO videos (filename, video_path, uploaded_at) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $videoName, $targetFilePath, $today);
                $stmt->execute();
                $stmt->close();
                header("Location: video_management?uploaded=1");
                exit();
            }
            $message = "Error uploading file.";
        }
    }
}
if (isset($_GET['uploaded'])) { $message = "Video uploaded. It will play on the Now Serving screen automatically."; $message_ok = true; }

$videosPerPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $videosPerPage;

$totalVideos = (int)$conn->query("SELECT COUNT(*) AS total FROM videos")->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalVideos / $videosPerPage));
$latestId = (int)($conn->query("SELECT MAX(id) AS m FROM videos")->fetch_assoc()['m']);

$stmt = $conn->prepare("SELECT * FROM videos ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $videosPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>
<?php layout_start('videos', 'Videos', ['assets/css/video_management.css']); ?>
    <h1>Videos</h1>

    <?php if ($message) echo "<div class='" . ($message_ok ? 'alert-ok' : 'alert-err') . "'>" . htmlspecialchars($message) . "</div>"; ?>

    <div class="panel">
        <h2>Upload a video</h2>
        <form action="video_management" method="POST" enctype="multipart/form-data" class="upload-form">
            <?php echo csrf_field(); ?>
            <input type="file" name="video" accept="video/mp4,video/*" required>
            <button type="submit" class="btn-primary">Upload Video</button>
        </form>
    </div>

    <div class="panel">
        <h2>Uploaded videos</h2>
        <div class="video-list">
            <?php if ($totalVideos === 0): ?><p class="hint">No video uploaded yet.</p><?php endif; ?>
            <?php while ($video = $result->fetch_assoc()): ?>
                <div class="video-item">
                    <span><?= htmlspecialchars($video['filename']) ?><?php if ((int)$video['id'] === $latestId): ?> <em class="playing">▶ Now playing</em><?php endif; ?></span>
                    <video controls preload="metadata">
                        <source src="<?= htmlspecialchars($video['video_path']) ?>" type="video/<?= htmlspecialchars(pathinfo($video['video_path'], PATHINFO_EXTENSION)) ?>">
                        Your browser does not support the video tag.
                    </video>
                    <div class="video-actions">
                        <a href="<?= htmlspecialchars($video['video_path']) ?>" download>⬇ Download</a>
                        <form action="video_management" method="POST" onsubmit="return confirm('Are you sure you want to delete this video?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="delete_video" value="<?= (int)$video['id'] ?>">
                            <input type="hidden" name="page" value="<?= $page ?>">
                            <button type="submit" class="link-danger">🗑 Delete</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">⬅ Previous</a><?php endif; ?>
            Page <?= $page ?> of <?= $totalPages ?>
            <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>">Next ➡</a><?php endif; ?>
        </div>
    </div>
<?php layout_end(); ?>
