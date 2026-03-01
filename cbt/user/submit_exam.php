<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireSiswa();

$db        = getDB();
$uid       = $_SESSION['user_id'];
$sessionId = (int)($_POST['session_id'] ?? 0);
$isTimeout = isset($_GET['timeout']);
$isAutoSave= isset($_POST['autosave']);

// Validate session
$session = $db->prepare("SELECT es.*, e.duration_minutes FROM exam_sessions es JOIN exams e ON es.exam_id=e.id WHERE es.id=? AND es.user_id=?");
$session->execute([$sessionId, $uid]); $session=$session->fetch();
if (!$session) { flash('error','Sesi tidak valid.'); redirect(BASE_URL.'/user/dashboard.php'); }
if ($session['status'] !== 'ongoing' && !$isAutoSave) {
    redirect(BASE_URL."/user/result.php?session_id=$sessionId");
}

$examId = $session['exam_id'];
$questions = $db->prepare("SELECT * FROM questions WHERE exam_id=? ORDER BY order_num, id"); $questions->execute([$examId]); $questions=$questions->fetchAll();

// Save / upsert answers
$stCheck  = $db->prepare("SELECT id FROM answers WHERE session_id=? AND question_id=?");
$stInsert = $db->prepare("INSERT INTO answers (session_id,question_id,answer_text,selected_options) VALUES (?,?,?,?)");
$stUpdate = $db->prepare("UPDATE answers SET answer_text=?,selected_options=? WHERE id=?");

foreach ($questions as $q) {
    $qid  = $q['id'];
    $type = $q['question_type'];

    $ansText  = null;
    $selOpts  = null;

    if ($type === 'essay') {
        $ansText = trim($_POST['essays'][$qid] ?? '');
        if ($ansText === '') $ansText = null;
    } elseif ($type === 'pg') {
        $sel    = strtoupper(trim($_POST['answers'][$qid] ?? ''));
        $selOpts = $sel !== '' ? $sel : null;
    } elseif ($type === 'multiple_choice') {
        $sel     = $_POST['answers_mc'][$qid] ?? [];
        $selOpts = !empty($sel) ? implode(',', array_map('strtoupper', $sel)) : null;
    }

    // Upsert
    $stCheck->execute([$sessionId, $qid]);
    $existing = $stCheck->fetch();
    if ($existing) {
        $stUpdate->execute([$ansText, $selOpts, $existing['id']]);
    } else {
        $stInsert->execute([$sessionId, $qid, $ansText, $selOpts]);
    }
}

// If autosave, stop here
if ($isAutoSave) {
    http_response_code(204); // No Content
    exit;
}

// ===== Full submit: grade auto-gradeable questions =====
$db->beginTransaction();
try {
    $totalScore = 0;
    $maxScore   = 0;
    $hasEssay   = false;

    foreach ($questions as $q) {
        $qid   = $q['id'];
        $type  = $q['question_type'];
        $pts   = (float)$q['points'];
        $maxScore += $pts;

        $aStmt = $db->prepare("SELECT * FROM answers WHERE session_id=? AND question_id=?");
        $aStmt->execute([$sessionId,$qid]); $ans=$aStmt->fetch();
        if (!$ans) continue;

        if ($type === 'essay') {
            $hasEssay = true;
            // Essay: 0 points until admin grades
            $db->prepare("UPDATE answers SET points_earned=0,is_graded=0 WHERE id=?")->execute([$ans['id']]);
        } else {
            // Get correct options
            $corrStmt = $db->prepare("SELECT option_label FROM options WHERE question_id=? AND is_correct=1");
            $corrStmt->execute([$qid]);
            $correct  = array_column($corrStmt->fetchAll(), 'option_label');
            sort($correct);

            $selected = !empty($ans['selected_options']) ? explode(',', strtoupper($ans['selected_options'])) : [];
            sort($selected);

            $earned = 0;
            if ($selected === $correct) $earned = $pts;

            $db->prepare("UPDATE answers SET points_earned=?,is_graded=1 WHERE id=?")->execute([$earned,$ans['id']]);
            $totalScore += $earned;
        }
    }

    $pct = $maxScore > 0 ? round($totalScore/$maxScore*100,2) : 0;
    $status = $isTimeout ? 'timeout' : 'submitted';
    $db->prepare("UPDATE exam_sessions SET status=?,submitted_at=NOW() WHERE id=?")->execute([$status,$sessionId]);

    // Upsert result
    $resCheck = $db->prepare("SELECT id FROM results WHERE session_id=?"); $resCheck->execute([$sessionId]);
    if ($resCheck->fetch()) {
        $db->prepare("UPDATE results SET total_score=?,max_score=?,percentage=?,graded_at=? WHERE session_id=?")
           ->execute([$totalScore,$maxScore,$pct,$hasEssay?null:date('Y-m-d H:i:s'),$sessionId]);
    } else {
        $db->prepare("INSERT INTO results (session_id,exam_id,user_id,total_score,max_score,percentage,graded_at) VALUES (?,?,?,?,?,?,?)")
           ->execute([$sessionId,$examId,$uid,$totalScore,$maxScore,$pct,$hasEssay?null:date('Y-m-d H:i:s')]);
    }

    $db->commit();
} catch(\Exception $e) {
    $db->rollBack();
    flash('error','Gagal submit: '.$e->getMessage());
    redirect(BASE_URL."/user/exam.php?exam_id=$examId");
}

redirect(BASE_URL."/user/result.php?session_id=$sessionId" . ($isTimeout ? '&timeout=1' : ''));
