<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireSiswa();

$db     = getDB();
$uid    = $_SESSION['user_id'];
$examId = (int)($_GET['exam_id'] ?? 0);

// Verify participant
$part = $db->prepare("SELECT 1 FROM exam_participants WHERE exam_id=? AND user_id=?");
$part->execute([$examId,$uid]);
if (!$part->fetch()) { flash('error','Kamu tidak terdaftar di ujian ini.'); redirect(BASE_URL.'/user/dashboard.php'); }

$exam = $db->prepare("SELECT * FROM exams WHERE id=? AND is_active=1"); $exam->execute([$examId]); $exam=$exam->fetch();
if (!$exam) { flash('error','Ujian tidak ditemukan.'); redirect(BASE_URL.'/user/dashboard.php'); }

// Get or create session
$session = $db->prepare("SELECT * FROM exam_sessions WHERE exam_id=? AND user_id=?");
$session->execute([$examId,$uid]); $session=$session->fetch();

if (!$session) {
    $db->prepare("INSERT INTO exam_sessions (exam_id,user_id,status) VALUES (?,?,'ongoing')")->execute([$examId,$uid]);
    $sessionId = $db->lastInsertId();
    $session   = $db->prepare("SELECT * FROM exam_sessions WHERE id=?")->execute([$sessionId]) ? $session : null;
    $session   = $db->prepare("SELECT * FROM exam_sessions WHERE id=?");
    $session->execute([$sessionId]); $session=$session->fetch();
}

if ($session['status'] !== 'ongoing') {
    flash('error','Ujian sudah selesai.'); redirect(BASE_URL.'/user/result.php?session_id='.$session['id']);
}

$sessionId = $session['id'];

// Questions
$questions = $db->prepare("SELECT * FROM questions WHERE exam_id=? ORDER BY order_num, id"); $questions->execute([$examId]); $questions=$questions->fetchAll();

// Existing answers for this session (for resuming)
$existingAnswers = [];
$aStmt = $db->prepare("SELECT * FROM answers WHERE session_id=?"); $aStmt->execute([$sessionId]);
foreach ($aStmt->fetchAll() as $a) { $existingAnswers[$a['question_id']] = $a; }

// Calculate remaining time
$startedAt    = strtotime($session['started_at']);
$durationSecs = $exam['duration_minutes'] * 60;
$elapsed      = time() - $startedAt;
$remaining    = max(0, $durationSecs - $elapsed);

// If time's up, auto-submit
if ($remaining <= 0) {
    redirect(BASE_URL."/user/submit_exam.php?session_id=$sessionId&timeout=1");
}

$pageTitle = $exam['title'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($exam['title']) ?> — <?= APP_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
<style>
body { font-family:'Inter',sans-serif; user-select:none; }
* { -webkit-user-select:none; -moz-user-select:none; -ms-user-select:none; }
input, textarea { user-select:text !important; -webkit-user-select:text !important; }
.tab-warning { animation: pulse 1s ease-in-out; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
.q-btn.answered { background:#dbeafe; color:#1d4ed8; border-color:#93c5fd; }
.q-btn.current  { background:#1d4ed8; color:#fff; }
</style>
</head>
<body class="bg-gray-50" oncontextmenu="return false">

<!-- Tab Switch Warning Overlay -->
<div id="tabWarningOverlay" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-8 max-w-sm mx-4 text-center shadow-2xl">
        <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-triangle text-orange-500 text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Peringatan!</h3>
        <p class="text-gray-600 mb-1">Kamu berpindah tab/jendela.</p>
        <p class="text-sm text-orange-600 font-medium mb-1">Perpindahan ke-<span id="switchCount">0</span></p>
        <p class="text-xs text-gray-400 mb-6">Aktivitas ini dicatat dan akan muncul di laporan ujian.</p>
        <button onclick="dismissWarning()" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition-colors">
            Kembali ke Ujian
        </button>
    </div>
</div>

<!-- Full page layout: Header + Main + Sidebar -->
<div class="flex flex-col h-screen">
    <!-- Sticky Header -->
    <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 flex items-center justify-between shadow-sm z-20 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-graduation-cap text-white text-sm"></i>
            </div>
            <div>
                <h1 class="font-semibold text-gray-800 text-sm sm:text-base leading-tight truncate max-w-[200px] sm:max-w-none"><?= e($exam['title']) ?></h1>
                <p class="text-xs text-gray-400"><?= count($questions) ?> soal · <?= e($_SESSION['user_name']) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- No-copy notice -->
            <span class="hidden sm:flex items-center gap-1 text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded-full border border-orange-200">
                <i class="fas fa-ban"></i> Copy-paste nonaktif
            </span>
            <!-- Timer -->
            <div id="timerBox" class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-xl shadow-sm">
                <i class="fas fa-clock text-sm"></i>
                <span id="timerDisplay" class="font-bold text-sm font-mono tracking-wider">--:--</span>
            </div>
        </div>
    </header>

    <!-- Content area -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Questions (scrollable) -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            <form id="examForm" method="POST" action="<?= BASE_URL ?>/user/submit_exam.php">
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">

                <div class="space-y-6 max-w-3xl">
                <?php foreach ($questions as $i => $q): ?>
                <?php
                $opts     = [];
                if ($q['question_type'] !== 'essay') {
                    $oStmt = $db->prepare("SELECT * FROM options WHERE question_id=? ORDER BY option_label"); $oStmt->execute([$q['id']]); $opts=$oStmt->fetchAll();
                }
                $existing = $existingAnswers[$q['id']] ?? null;
                $selOpts  = $existing ? explode(',', $existing['selected_options'] ?? '') : [];
                $essayVal = $existing['answer_text'] ?? '';
                ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden question-card" id="q-<?= $q['id'] ?>">
                    <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
                        <span class="w-7 h-7 bg-blue-100 text-blue-600 rounded-full text-xs font-bold flex items-center justify-center flex-shrink-0"><?= $i+1 ?></span>
                        <?= questionTypeBadge($q['question_type']) ?>
                        <span class="text-xs text-gray-400 ml-auto"><?= $q['points'] ?> poin</span>
                        <?php if ($q['question_type'] === 'multiple_choice'): ?>
                        <span class="text-xs text-purple-500 bg-purple-50 px-2 py-0.5 rounded-full">Boleh pilih lebih dari satu</span>
                        <?php endif; ?>
                    </div>
                    <div class="px-5 py-5">
                        <p class="text-sm sm:text-base text-gray-800 leading-relaxed mb-4"><?= nl2br(e($q['question_text'])) ?></p>

                        <?php if ($q['question_type'] === 'pg'): ?>
                        <div class="space-y-2">
                            <?php foreach ($opts as $opt): ?>
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50 cursor-pointer transition-all group has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt['option_label'] ?>"
                                    <?= in_array($opt['option_label'], $selOpts) ? 'checked' : '' ?>
                                    onchange="markAnswered(<?= $q['id'] ?>)"
                                    class="mt-0.5 w-4 h-4 text-blue-600 flex-shrink-0">
                                <span class="w-6 h-6 rounded bg-gray-100 group-has-[:checked]:bg-blue-100 text-gray-600 group-has-[:checked]:text-blue-600 text-xs font-bold flex items-center justify-center flex-shrink-0"><?= $opt['option_label'] ?></span>
                                <span class="text-sm text-gray-700 leading-relaxed"><?= e($opt['option_text']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <?php elseif ($q['question_type'] === 'multiple_choice'): ?>
                        <div class="space-y-2">
                            <?php foreach ($opts as $opt): ?>
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:border-purple-300 hover:bg-purple-50 cursor-pointer transition-all group has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50">
                                <input type="checkbox" name="answers_mc[<?= $q['id'] ?>][]" value="<?= $opt['option_label'] ?>"
                                    <?= in_array($opt['option_label'], $selOpts) ? 'checked' : '' ?>
                                    onchange="markAnswered(<?= $q['id'] ?>)"
                                    class="mt-0.5 w-4 h-4 text-purple-600 rounded flex-shrink-0">
                                <span class="w-6 h-6 rounded bg-gray-100 group-has-[:checked]:bg-purple-100 text-gray-600 group-has-[:checked]:text-purple-600 text-xs font-bold flex items-center justify-center flex-shrink-0"><?= $opt['option_label'] ?></span>
                                <span class="text-sm text-gray-700 leading-relaxed"><?= e($opt['option_text']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <?php else: // essay ?>
                        <textarea name="essays[<?= $q['id'] ?>]" rows="5"
                            placeholder="Tulis jawaban kamu di sini..."
                            onkeyup="markAnswered(<?= $q['id'] ?>)"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm resize-y focus:outline-none focus:ring-2 focus:ring-orange-400"><?= e($essayVal) ?></textarea>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>

                <!-- Submit button -->
                <div class="max-w-3xl mt-8 mb-6">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h3 class="font-semibold text-gray-800 mb-2">Selesai Mengerjakan?</h3>
                        <p class="text-sm text-gray-500 mb-4">Pastikan semua soal sudah dijawab sebelum submit. Setelah submit, jawaban tidak bisa diubah.</p>
                        <button type="button" onclick="submitExam()" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold shadow-md transition-colors flex items-center gap-2">
                            <i class="fas fa-paper-plane"></i> Submit Ujian
                        </button>
                    </div>
                </div>
            </form>
        </main>

        <!-- Sidebar: Question Navigator -->
        <aside class="hidden lg:flex flex-col w-56 bg-white border-l border-gray-200 flex-shrink-0 overflow-y-auto">
            <div class="p-4 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Navigasi Soal</p>
            </div>
            <div class="p-3 grid grid-cols-4 gap-1.5">
                <?php foreach ($questions as $i => $q): ?>
                <?php $existing = $existingAnswers[$q['id']] ?? null; ?>
                <button type="button" onclick="scrollToQuestion('q-<?= $q['id'] ?>')"
                    class="q-btn w-9 h-9 rounded-lg border text-xs font-semibold transition-all border-gray-200 bg-gray-50 text-gray-600 hover:border-blue-300"
                    id="qbtn-<?= $q['id'] ?>"
                    data-qid="<?= $q['id'] ?>"
                    <?= ($existing && ($existing['selected_options'] || $existing['answer_text'])) ? 'data-answered="true"' : '' ?>>
                    <?= $i+1 ?>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="p-4 mt-auto border-t border-gray-100 space-y-2">
                <div class="flex items-center gap-2 text-xs text-gray-500"><span class="w-4 h-4 bg-blue-100 border border-blue-300 rounded"></span> Dijawab</div>
                <div class="flex items-center gap-2 text-xs text-gray-500"><span class="w-4 h-4 bg-gray-50 border border-gray-200 rounded"></span> Belum</div>
            </div>
        </aside>
    </div>
</div>

<script>
// ========== TIMER ==========
let remainingSeconds = <?= $remaining ?>;
const timerEl   = document.getElementById('timerDisplay');
const timerBox  = document.getElementById('timerBox');

function pad(n) { return String(n).padStart(2,'0'); }
function updateTimer() {
    if (remainingSeconds <= 0) {
        timerEl.textContent = '00:00';
        document.getElementById('examForm').submit();
        return;
    }
    const m = Math.floor(remainingSeconds / 60);
    const s = remainingSeconds % 60;
    timerEl.textContent = pad(m) + ':' + pad(s);
    if (remainingSeconds <= 300) { // Last 5 min: red
        timerBox.classList.replace('bg-blue-600','bg-red-600');
        timerBox.classList.add('animate-pulse');
    }
    remainingSeconds--;
}
updateTimer();
setInterval(updateTimer, 1000);

// ========== TAB SWITCH DETECTION ==========
let tabSwitchCount = 0;

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Tab left — record via AJAX
        fetch('<?= BASE_URL ?>/user/record_tab_switch.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'session_id=<?= $sessionId ?>'
        }).then(r => r.json()).then(data => {
            tabSwitchCount = data.count ?? tabSwitchCount + 1;
        }).catch(() => { tabSwitchCount++; });
    } else {
        // Tab returned — show warning
        if (tabSwitchCount > 0 || true) {
            // We increment optimistically and show the warning
            showTabWarning();
        }
    }
});

function showTabWarning() {
    document.getElementById('switchCount').textContent = tabSwitchCount + 1;
    document.getElementById('tabWarningOverlay').classList.remove('hidden');
}

function dismissWarning() {
    document.getElementById('tabWarningOverlay').classList.add('hidden');
}

// ========== ANTI COPY-PASTE ==========
['copy','cut','paste'].forEach(evt => {
    document.addEventListener(evt, e => {
        e.preventDefault();
        return false;
    });
});

// Disable some keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+A, Ctrl+U, Ctrl+S
    if (e.ctrlKey && ['c','v','x','a','u','s','p'].includes(e.key.toLowerCase())) {
        if (!['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) {
            e.preventDefault();
        }
    }
    // F12 (dev tools)
    if (e.key === 'F12') e.preventDefault();
});

// Disable drag
document.addEventListener('dragstart', e => e.preventDefault());

// ========== QUESTION NAVIGATION ==========
function scrollToQuestion(id) {
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior:'smooth', block:'start' });
}

function markAnswered(qid) {
    const btn = document.getElementById('qbtn-' + qid);
    if (btn) btn.classList.add('answered');
}

// Initialize answered state from pre-filled answers
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.q-btn[data-answered="true"]').forEach(btn => {
        btn.classList.add('answered');
    });
});

// ========== AUTO-SAVE (every 30s) ==========
function autoSave() {
    const form      = document.getElementById('examForm');
    const formData  = new FormData(form);
    formData.append('autosave', '1');
    fetch('<?= BASE_URL ?>/user/submit_exam.php', { method:'POST', body: formData });
}
setInterval(autoSave, 30000);

// ========== SUBMIT ==========
function submitExam() {
    const total     = <?= count($questions) ?>;
    const answered  = document.querySelectorAll('.q-btn.answered').length;
    const unanswered = total - answered;
    let msg = 'Apakah kamu yakin ingin submit ujian?';
    if (unanswered > 0) msg += `\n\n⚠️ Masih ada ${unanswered} soal yang belum dijawab.`;
    if (confirm(msg)) {
        document.getElementById('examForm').submit();
    }
}
</script>
</body>
</html>
