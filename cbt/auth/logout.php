<?php
require_once __DIR__ . '/../config/auth.php';
define('BASE_URL', '/brainstorm/cbt');

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
