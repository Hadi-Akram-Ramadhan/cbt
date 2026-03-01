<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Tambah Soal';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$examId = (int)($_GET['exam_id'] ?? 0);
$exam = $db->prepare("SELECT * FROM exams WHERE id=?"); $exam->execute([$examId]); $exam = $exam->fetch();
if (!$exam) { flash('error','Ujian tidak ditemukan.'); redirect(BASE_URL.'/admin/questions/index.php'); }

$errors = [];
$d = ['question_text'=>'','question_type'=>'pg','points'=>1,'options'=>['A'=>'','B'=>'','C'=>'','D'=>'','E'=>''],'correct'=>[]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['question_text'] = trim($_POST['question_text'] ?? '');
    $d['question_type'] = in_array($_POST['question_type']??'',['pg','multiple_choice','essay']) ? $_POST['question_type'] : 'pg';
    $d['points']        = max(0, (float)($_POST['points'] ?? 1));
    $d['correct']       = $_POST['correct'] ?? [];
    foreach(['A','B','C','D','E'] as $lbl) {
        $d['options'][$lbl] = trim($_POST['option_'.$lbl] ?? '');
    }

    if (!$d['question_text']) $errors[] = 'Teks soal harus diisi.';
    if ($d['question_type'] !== 'essay' && !array_filter($d['options'])) $errors[] = 'Minimal dua opsi jawaban.';
    if ($d['question_type'] !== 'essay' && empty($d['correct'])) $errors[] = 'Pilih minimal satu jawaban benar.';
    if ($d['question_type'] === 'pg' && count($d['correct']) > 1) $errors[] = 'Pilihan ganda hanya boleh satu jawaban benar.';

    if (empty($errors)) {
        $nextOrder = ($db->prepare("SELECT COALESCE(MAX(order_num),0)+1 FROM questions WHERE exam_id=?") && ($db->prepare("SELECT COALESCE(MAX(order_num),0)+1 FROM questions WHERE exam_id=?")->execute([$examId])));
        $maxOrd = $db->prepare("SELECT COALESCE(MAX(order_num),0)+1 FROM questions WHERE exam_id=?");
        $maxOrd->execute([$examId]);
        $orderNum = (int)$maxOrd->fetchColumn();

        $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, question_type, order_num, points) VALUES (?,?,?,?,?)");
        $stmt->execute([$examId, $d['question_text'], $d['question_type'], $orderNum, $d['points']]);
        $qid = $db->lastInsertId();

        if ($d['question_type'] !== 'essay') {
            $stOpt = $db->prepare("INSERT INTO options (question_id, option_label, option_text, is_correct) VALUES (?,?,?,?)");
            foreach(['A','B','C','D','E'] as $lbl) {
                if ($d['options'][$lbl] !== '') {
                    $stOpt->execute([$qid, $lbl, $d['options'][$lbl], in_array($lbl,$d['correct']) ? 1 : 0]);
                }
            }
        }
        flash('success','Soal berhasil ditambahkan!');
        if (isset($_POST['save_add'])) redirect(BASE_URL."/admin/questions/create.php?exam_id=$examId");
        redirect(BASE_URL."/admin/questions/index.php?exam_id=$examId");
    }
}

$pageBreadcrumb = 'Admin / Soal / ' . e($exam['title']);
include __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-2xl">
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Tambah Soal Baru</h2>

    <?php if ($errors): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
        <ul class="list-disc list-inside"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Tipe Soal</label>
                <select name="question_type" id="questionType" onchange="updateForm()" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="pg" <?= $d['question_type']==='pg'?'selected':'' ?>>Pilihan Ganda</option>
                    <option value="multiple_choice" <?= $d['question_type']==='multiple_choice'?'selected':'' ?>>Multiple Choice</option>
                    <option value="essay" <?= $d['question_type']==='essay'?'selected':'' ?>>Essay</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Poin</label>
                <input type="number" name="points" value="<?= $d['points'] ?>" min="0" step="0.5" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Teks Soal <span class="text-red-500">*</span></label>
            <textarea name="question_text" rows="4" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($d['question_text']) ?></textarea>
        </div>

        <!-- Options section (hidden for essay) -->
        <div id="optionsSection">
            <p class="text-sm font-medium text-gray-700 mb-3" id="optionsHint">Opsi Jawaban <span class="text-red-500">*</span> <span class="text-gray-400 font-normal text-xs" id="optionsHintSub">(pilih satu jawaban benar)</span></p>
            <div class="space-y-3">
            <?php foreach(['A','B','C','D','E'] as $lbl): ?>
            <div class="flex items-center gap-3">
                <?php if ($d['question_type'] === 'pg'): ?>
                <input type="radio" name="correct[]" value="<?= $lbl ?>" <?= in_array($lbl,$d['correct'])?'checked':'' ?> class="correct-radio w-4 h-4 text-blue-600 flex-shrink-0">
                <?php else: ?>
                <input type="checkbox" name="correct[]" value="<?= $lbl ?>" <?= in_array($lbl,$d['correct'])?'checked':'' ?> class="correct-check w-4 h-4 text-blue-600 rounded flex-shrink-0">
                <?php endif; ?>
                <span class="w-7 h-7 bg-gray-100 text-gray-600 rounded flex items-center justify-center text-xs font-bold flex-shrink-0"><?= $lbl ?></span>
                <input type="text" name="option_<?= $lbl ?>" value="<?= e($d['options'][$lbl]) ?>"
                    placeholder="Opsi <?= $lbl ?>"
                    class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="flex gap-3 pt-4 border-t border-gray-100">
            <button type="submit" name="save_back" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-sm">
                <i class="fas fa-save mr-1.5"></i> Simpan
            </button>
            <button type="submit" name="save_add" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium shadow-sm">
                <i class="fas fa-plus mr-1.5"></i> Simpan & Tambah Lagi
            </button>
            <a href="<?= BASE_URL ?>/admin/questions/index.php?exam_id=<?= $examId ?>" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm">Batal</a>
        </div>
    </form>
</div>
</div>
<script>
function updateForm() {
    const type = document.getElementById('questionType').value;
    const section = document.getElementById('optionsSection');
    const hint = document.getElementById('optionsHintSub');

    if (type === 'essay') {
        section.style.display = 'none';
    } else {
        section.style.display = 'block';
        hint.textContent = type === 'pg' ? '(pilih satu jawaban benar)' : '(bisa pilih beberapa jawaban benar)';
        // Toggle radio vs checkbox
        document.querySelectorAll('.correct-radio, .correct-check').forEach(el => {
            if (type === 'pg') { el.type = 'radio'; el.name = 'correct[]'; }
            else { el.type = 'checkbox'; el.name = 'correct[]'; }
        });
    }
}
updateForm();
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
