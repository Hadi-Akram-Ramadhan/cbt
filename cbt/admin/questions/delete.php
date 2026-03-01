<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$id     = (int)($_GET['id'] ?? 0);
$examId = (int)($_GET['exam_id'] ?? 0);
$stmt = $db->prepare("SELECT id FROM questions WHERE id=?"); $stmt->execute([$id]);
if (!$stmt->fetch()) { flash('error','Soal tidak ditemukan.'); }
else {
    $db->prepare("DELETE FROM questions WHERE id=?")->execute([$id]);
    flash('success','Soal berhasil dihapus.');
}
redirect(BASE_URL."/admin/questions/index.php?exam_id=$examId");
