<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireSiswa();

$db        = getDB();
$sessionId = (int)($_GET['session_id'] ?? 0);
$uid       = $_SESSION['user_id'];
$isTimeout = isset($_GET['timeout']);

$session = $db->prepare("
    SELECT es.*, e.title AS exam_title, e.duration_minutes, e.id AS exam_id
    FROM exam_sessions es JOIN exams e ON es.exam_id=e.id
    WHERE es.id=? AND es.user_id=?
");
$session->execute([$sessionId,$uid]); $session=$session->fetch();
if (!$session) { flash('error','Sesi tidak ditemukan.'); redirect(BASE_URL.'/user/dashboard.php'); }

$result = $db->prepare("SELECT * FROM results WHERE session_id=?"); $result->execute([$sessionId]); $result=$result->fetch();
$answers = $db->prepare("
    SELECT a.*, q.question_text, q.question_type, q.points AS max_points, q.order_num
    FROM answers a JOIN questions q ON a.question_id=q.id
    WHERE a.session_id=? ORDER BY q.order_num
");
$answers->execute([$sessionId]); $answers=$answers->fetchAll();

$pct  = $result ? (float)$result['percentage'] : 0;
$gc   = getGradeColor($pct);
$gl   = getGradeLetter($pct);
$pendingEssay = !empty(array_filter($answers, fn($a) => $a['question_type']==='essay' && !$a['is_graded']));

$pageTitle = 'Hasil Ujian';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hasil Ujian — <?= APP_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
<style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-2xl mx-auto px-4 py-8">
    <!-- Back link -->
    <a href="<?= BASE_URL ?>/user/dashboard.php" class="inline-flex items-center gap-2 text-blue-600 hover:underline text-sm mb-6">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
    </a>

    <?php if ($isTimeout): ?>
    <div class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm mb-6">
        <i class="fas fa-clock text-red-500"></i>
        <div><p class="font-medium">Waktu ujian habis!</p><p class="text-xs mt-0.5">Ujian otomatis diselesaikan saat timer mencapai nol.</p></div>
    </div>
    <?php endif; ?>

    <!-- Score Card -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-brand-700 to-brand-500 p-8 text-center text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-white/5"></div>
            <div class="relative">
                <p class="text-blue-100 text-sm mb-2"><?= e($session['exam_title']) ?></p>
                <?php if ($pendingEssay): ?>
                <p class="text-5xl font-bold">–</p>
                <p class="text-blue-100 mt-1 text-sm">Menunggu penilaian essay</p>
                <?php else: ?>
                <p class="text-6xl font-bold"><?= $pct ?>%</p>
                <p class="text-blue-100 mt-1">Grade <?= $gl ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="p-6 grid grid-cols-3 gap-6 text-center">
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= $result ? $result['total_score'] : 0 ?></p>
                <p class="text-xs text-gray-400 mt-0.5">Skor Benar</p>
            </div>
            <div>
                <p class="text-2xl font-bold <?= $session['tab_switch_count'] > 0 ? 'text-orange-600' : 'text-gray-800' ?>"><?= $session['tab_switch_count'] ?></p>
                <p class="text-xs text-gray-400 mt-0.5">Tab Switch</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= count($answers) ?></p>
                <p class="text-xs text-gray-400 mt-0.5">Total Soal</p>
            </div>
        </div>
    </div>

    <!-- Tab switch warning -->
    <?php if ($session['tab_switch_count'] > 0): ?>
    <div class="flex items-start gap-3 p-4 bg-orange-50 border border-orange-200 rounded-xl text-sm text-orange-800 mb-6">
        <i class="fas fa-exclamation-triangle text-orange-500 mt-0.5"></i>
        <div>
            <p class="font-medium">Aktivitas mencurigakan terdeteksi</p>
            <p class="text-xs mt-0.5">Kamu berpindah tab/jendela sebanyak <strong><?= $session['tab_switch_count'] ?> kali</strong> selama ujian. Aktivitas ini tercatat di laporan guru.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Essay pending -->
    <?php if ($pendingEssay): ?>
    <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-700 mb-6">
        <i class="fas fa-info-circle mt-0.5"></i>
        <div>
            <p class="font-medium">Menunggu Penilaian Essay</p>
            <p class="text-xs mt-0.5">Soal essay belum dinilai oleh guru. Nilai akhir akan diperbarui setelah penilaian selesai.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Answer Summary -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Ringkasan Jawaban</h2>
        </div>
        <div class="divide-y divide-gray-50">
        <?php foreach ($answers as $i => $ans): ?>
        <?php
        $opts = [];
        if ($ans['question_type'] !== 'essay') {
            $oStmt = $db->prepare("SELECT * FROM options WHERE question_id=? ORDER BY option_label");
            $oStmt->execute([$ans['question_id']]); $opts=$oStmt->fetchAll();
        }
        $isCorrect = false;
        if ($ans['question_type'] !== 'essay') {
            $corrLabels = implode(',', array_column(array_filter($opts,fn($o)=>$o['is_correct']),'option_label'));
            $selRaw     = strtoupper(trim($ans['selected_options'] ?? ''));
            $isCorrect  = strtoupper($corrLabels) === $selRaw;
        }
        ?>
        <div class="px-5 py-4">
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 rounded bg-gray-100 text-gray-600 text-xs font-bold flex items-center justify-center flex-shrink-0"><?= $i+1 ?></span>
                <div class="flex-1">
                    <p class="text-sm text-gray-800 mb-2"><?= nl2br(e($ans['question_text'])) ?></p>
                    <?php if ($ans['question_type'] !== 'essay'): ?>
                    <div class="flex items-center gap-2">
                        <span class="text-xs">Jawaban: <strong><?= e($ans['selected_options'] ?? '–') ?></strong></span>
                        <?php if ($isCorrect): ?>
                        <span class="text-xs text-green-600"><i class="fas fa-check-circle"></i> Benar (+<?= $ans['points_earned'] ?> poin)</span>
                        <?php else: ?>
                        <span class="text-xs text-red-500"><i class="fas fa-times-circle"></i> Salah (0 poin)</span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-xs text-gray-500 flex items-center gap-1">
                        <i class="fas fa-pen"></i> Essay
                        <?= $ans['is_graded'] ? '<span class="text-green-600 ml-1">(' . $ans['points_earned'] . ' poin)</span>' : '<span class="text-orange-500 ml-1">(Menunggu penilaian)</span>' ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <div class="mt-6 flex justify-center">
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-sm text-sm transition-colors">
            <i class="fas fa-home mr-1.5"></i> Kembali ke Dashboard
        </a>
    </div>
</div>
</body>
</html>
