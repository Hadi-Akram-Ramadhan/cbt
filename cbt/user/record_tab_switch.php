<?php
// AJAX endpoint — record a tab switch for a session
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireSiswa();

header('Content-Type: application/json');

$db        = getDB();
$sessionId = (int)($_POST['session_id'] ?? 0);
$uid       = $_SESSION['user_id'];

// Verify session belongs to current user
$stmt = $db->prepare("SELECT id, tab_switch_count FROM exam_sessions WHERE id=? AND user_id=? AND status='ongoing'");
$stmt->execute([$sessionId, $uid]);
$session = $stmt->fetch();

if (!$session) {
    echo json_encode(['error' => 'Invalid session', 'count' => 0]);
    exit;
}

$newCount = $session['tab_switch_count'] + 1;
$db->prepare("UPDATE exam_sessions SET tab_switch_count=? WHERE id=?")->execute([$newCount, $sessionId]);

echo json_encode(['success' => true, 'count' => $newCount]);
