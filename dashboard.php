<?php
session_start();
require_once __DIR__ . '/includes/auth_check.php';
requireLogin();

$path = getRoleDashboardPath($_SESSION['user_role']);
header('Location: ' . $path);
exit;
