<?php
require_once __DIR__ . '/includes/auth.php';
header('Location: ' . (!empty($_SESSION['role']) ? auth_home($_SESSION['role']) : 'login'));
exit;
