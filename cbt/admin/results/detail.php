<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Detail Hasil Ujian';
$pageBreadcrumb = 'Admin / Hasil Ujian / Detail';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db        = getDB();
$sessionId = (int)($_GET['session_id'] ?? 0);

$session = $db->prepare("
    SELECT es.*, u.name AS student_name, u.nis, u.kelas, e.title AS exam_title, e.id AS exam_id
    FROM exam_sessions es
    JOIN users u ON es.user_id = u.id
    JOIN exams e ON es.exam_id = e.id
    WHERE es.id = ?
");
$session->execute([$sessionId]); $session = $session->fetch();
if (!$session) { flash('error','Sesi tidak ditemukan.'); redirect(BASE_URL.'/admin/results/index.php'); }

// Handle essay grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $db->beginTransaction();
    try {
        $totalScore = 0;
        $maxScore   = 0;
        foreach ($_POST['essay_score'] as $answerId => $score) {
            $score = max(0,(float)$score);
            $ans   = $db->prepare("SELECT a.*, q.points FROM answers a JOIN questions q ON a.question_id=q.id WHERE a.id=?");
            $ans->execute([$answerId]); $ansRow = $ans->fetch();
            $maxPts = (float)($ansRow['points'] ?? 0);
            $score  = min($score, $maxPts);
            $db->prepare("UPDATE answers SET points_earned=?,is_graded=1 WHERE id=?")->execute([$score,$answerId]);
        }
        // Recalculate total
        $totals = $db->prepare("SELECT SUM(a.points_earned) AS total, SUM(q.points) AS max FROM answers a JOIN questions q ON a.question_id=q.id WHERE a.session_id=?");
        $totals->execute([$sessionId]); $totals = $totals->fetch();
        $totalScore = (float)($totals['total'] ?? 0);
        $maxScore   = (float)($totals['max'] ?? 0);
        $pct        = $maxScore > 0 ? round($totalScore/$maxScore*100,2) : 0;
        $db->prepare("UPDATE results SET total_score=?,max_score=?,percentage=?,graded_at=NOW() WHERE session_id=?")->execute([$totalScore,$maxScore,$pct,$sessionId]);
        $db->commit();
        flash('success','Nilai berhasil disimpan!');
        redirect(BASE_URL."/admin/results/detail.php?session_id=$sessionId");
    } catch(\Exception $e) { $db->rollBack(); flash('error','Gagal menyimpan nilai: '.$e->getMessage()); }
}

$result = $db->prepare("SELECT * FROM results WHERE session_id=?"); $result->execute([$sessionId]); $result = $result->fetch();

$answers = $db->prepare("
    SELECT a.*, q.question_text, q.question_type, q.points AS max_points, q.order_num
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    WHERE a.session_id = ?
    ORDER BY q.order_num
");
$answers->execute([$sessionId]); $answers = $answers->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>
<!-- Student Info Card -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl font-bold">
            <?= strtoupper(substr($session['student_name'],0,1)) ?>
        </div>
        <div>
            <p class="font-semibold text-gray-800"><?= e($session['student_name']) ?></p>
            <p class="text-sm text-gray-500"><?= e($session['nis']??'-') ?> · <?= e($session['kelas']??'-') ?></p>
            <p class="text-sm text-gray-500 mt-0.5"><?= e($session['exam_title']) ?></p>
        </div>
    </div>
    <div class="flex gap-6 text-center">
        <?php if ($result): $gc=getGradeColor($result['percentage']); ?>
        <div>
            <p class="text-2xl font-bold text-<?= $gc ?>-600"><?= $result['percentage'] ?>%</p>
            <p class="text-xs text-gray-400">Nilai</p>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= getGradeLetter($result['percentage']) ?></p>
            <p class="text-xs text-gray-400">Grade</p>
        </div>
        <?php endif; ?>
        <div>
            <p class="text-2xl font-bold <?= $session['tab_switch_count']>0?'text-orange-600':'text-gray-800' ?>"><?= $session['tab_switch_count'] ?></p>
            <p class="text-xs text-gray-400">Tab Switch</p>
        </div>
    </div>
</div>

<!-- Answers -->
<form method="POST">
<div class="space-y-4">
<?php foreach ($answers as $i => $ans): ?>
<?php
$opts = [];
if ($ans['question_type'] !== 'essay') {
    $oStmt = $db->prepare("SELECT * FROM options WHERE question_id=? ORDER BY option_label");
    $oStmt->execute([$ans['question_id']]); $opts = $oStmt->fetchAll();
}
$isCorrect = false;
if ($ans['question_type'] === 'pg' || $ans['question_type'] === 'multiple_choice') {
    $correctLabels = implode(',', array_column(array_filter($opts, fn($o)=>$o['is_correct']), 'option_label'));
    $selectedRaw   = strtoupper(trim($ans['selected_options'] ?? ''));
    $correctRaw    = strtoupper(trim($correctLabels));
    $isCorrect     = $selectedRaw === $correctRaw;
}
?>
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center gap-3 px-5 py-3 bg-gray-50 border-b border-gray-100">
        <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded text-xs font-bold flex items-center justify-center"><?= $i+1 ?></span>
        <?= questionTypeBadge($ans['question_type']) ?>
        <span class="text-xs text-gray-400 ml-auto"><?= $ans['max_points'] ?> poin</span>
    </div>
    <div class="px-5 py-4">
        <p class="text-sm text-gray-800 mb-3"><?= nl2br(e($ans['question_text'])) ?></p>

        <?php if ($ans['question_type'] !== 'essay'): ?>
        <div class="space-y-1.5 mb-3">
            <?php foreach ($opts as $opt): ?>
            <?php
            $selected  = strpos(strtoupper($ans['selected_options']??''), $opt['option_label']) !== false;
            $correct   = (bool)$opt['is_correct'];
            $cls = 'bg-gray-50 text-gray-600';
            if ($correct && $selected) $cls = 'bg-green-50 text-green-700 border-green-200';
            elseif ($correct)           $cls = 'bg-green-50 text-green-700 border-green-200';
            elseif ($selected)          $cls = 'bg-red-50 text-red-700 border-red-200';
            ?>
            <div class="flex items-start gap-2 text-xs p-2 rounded-lg border <?= $cls ?>">
                <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0 font-bold bg-white/60"><?= $opt['option_label'] ?></span>
                <span><?= e($opt['option_text']) ?></span>
                <?php if ($selected && $correct): ?><i class="fas fa-check-circle text-green-500 ml-auto"></i>
                <?php elseif ($selected): ?><i class="fas fa-times-circle text-red-400 ml-auto"></i>
                <?php elseif ($correct): ?><i class="fas fa-check text-green-400 ml-auto opacity-50"></i><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-xs font-medium text-gray-700">
            Poin: <span class="<?= $isCorrect?'text-green-600':'text-red-500' ?>"><?= $ans['points_earned'] ?>/<?= $ans['max_points'] ?></span>
        </p>
        <?php else: // essay ?>
        <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700 mb-3 border border-gray-200">
            <?= $ans['answer_text'] ? nl2br(e($ans['answer_text'])) : '<span class="text-gray-400 italic">Tidak dijawab</span>' ?>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Nilai Essay:</label>
            <input type="number" name="essay_score[<?= $ans['id'] ?>]" value="<?= $ans['points_earned'] ?>"
                min="0" max="<?= $ans['max_points'] ?>" step="0.5"
                class="w-24 px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <span class="text-sm text-gray-400">/ <?= $ans['max_points'] ?></span>
            <?php if ($ans['is_graded']): ?><span class="text-xs text-green-600"><i class="fas fa-check-circle"></i> Sudah dinilai</span><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php $hasEssay = !empty(array_filter($answers, fn($a)=>$a['question_type']==='essay')); ?>
<?php if ($hasEssay): ?>
<div class="mt-6 flex gap-3">
    <button type="submit" name="grade" value="1" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-sm">
        <i class="fas fa-save mr-1.5"></i> Simpan Nilai Essay
    </button>
    <a href="<?= BASE_URL ?>/admin/results/index.php?exam_id=<?= $session['exam_id'] ?>" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-sm hover:bg-gray-200">Kembali</a>
</div>
<?php else: ?>
<div class="mt-4">
    <a href="<?= BASE_URL ?>/admin/results/index.php?exam_id=<?= $session['exam_id'] ?>" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-sm hover:bg-gray-200">← Kembali</a>
</div>
<?php endif; ?>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
