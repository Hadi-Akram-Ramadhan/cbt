<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT id FROM users WHERE id=? AND role='siswa'");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    flash('error', 'Siswa tidak ditemukan.');
} else {
    $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    flash('success', 'Siswa berhasil dihapus.');
}
redirect(BASE_URL . '/admin/students/index.php');
