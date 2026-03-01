<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$exam = $db->prepare("SELECT id FROM exams WHERE id=?");
$exam->execute([$id]); $exam = $exam->fetch();
if (!$exam) { flash('error','Ujian tidak ditemukan.'); }
else {
    $db->prepare("DELETE FROM exams WHERE id=?")->execute([$id]);
    flash('success','Ujian berhasil dihapus.');
}
redirect(BASE_URL.'/admin/exams/index.php');
