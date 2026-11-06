<?php

function layout_start($active, $title, $extraCss = []) {
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    $subtitle = ($role === 'developer') ? 'System Developer' : 'Branch Operation Officer';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JDTech Queueing - <?php echo htmlspecialchars($title); ?></title>
<link rel="stylesheet" href="assets/css/reset_ticket.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<?php foreach ($extraCss as $css): ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
<?php endforeach; ?>
<link rel="icon" type="image/png" href="assets/images/jdlogo.png">
</head>
<body>

<div class="sidebar">
    <img src="assets/images/jdlogo.png" alt="JDTech Logo" class="sidebar-logo">
    <div class="sidebar-title">JDTech Solutions</div>

    <div class="user-card">
        <div class="user-name"><?php echo htmlspecialchars($user); ?></div>
        <div class="user-subtitle"><?php echo htmlspecialchars($subtitle); ?></div>
        <span class="role-badge"><?php echo htmlspecialchars(strtoupper($role)); ?></span>
    </div>

    <div class="nav-section-label">Main</div>
    <div class="sidebar-menu">
<?php admin_menu($active); ?>
    </div>
</div>


<div class="content">
<?php
}

function layout_end() {
    ?>
    <img src="assets/images/powerbyjd3.png" alt="Logo" class="bottom-right-image">
</div>
</body>
</html>
<?php
}
