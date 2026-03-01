<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Edit Soal';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$id     = (int)($_GET['id'] ?? 0);
$examId = (int)($_GET['exam_id'] ?? 0);

$qStmt = $db->prepare("SELECT * FROM questions WHERE id=?"); $qStmt->execute([$id]); $q = $qStmt->fetch();
if (!$q) { flash('error','Soal tidak ditemukan.'); redirect(BASE_URL.'/admin/questions/index.php'); }

$examId = $q['exam_id'];
$exam = $db->prepare("SELECT * FROM exams WHERE id=?"); $exam->execute([$examId]); $exam = $exam->fetch();

$optStmt = $db->prepare("SELECT * FROM options WHERE question_id=? ORDER BY option_label"); $optStmt->execute([$id]);
$existOpts = []; foreach($optStmt->fetchAll() as $o) { $existOpts[$o['option_label']] = $o; }

$errors = [];
$d = [
    'question_text' => $q['question_text'],
    'question_type' => $q['question_type'],
    'points'        => $q['points'],
    'options'       => ['A'=>$existOpts['A']['option_text']??'','B'=>$existOpts['B']['option_text']??'','C'=>$existOpts['C']['option_text']??'','D'=>$existOpts['D']['option_text']??'','E'=>$existOpts['E']['option_text']??''],
    'correct'       => array_keys(array_filter($existOpts, fn($o) => $o['is_correct']))
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['question_text'] = trim($_POST['question_text'] ?? '');
    $d['question_type'] = in_array($_POST['question_type']??'',['pg','multiple_choice','essay']) ? $_POST['question_type'] : 'pg';
    $d['points']        = max(0,(float)($_POST['points'] ?? 1));
    $d['correct']       = $_POST['correct'] ?? [];
    foreach(['A','B','C','D','E'] as $l) { $d['options'][$l] = trim($_POST['option_'.$l] ?? ''); }

    if (!$d['question_text']) $errors[] = 'Teks soal harus diisi.';
    if ($d['question_type'] !== 'essay' && !array_filter($d['options'])) $errors[] = 'Minimal dua opsi jawaban.';
    if ($d['question_type'] !== 'essay' && empty($d['correct'])) $errors[] = 'Pilih minimal satu jawaban benar.';
    if ($d['question_type'] === 'pg' && count($d['correct']) > 1) $errors[] = 'Pilihan ganda hanya boleh satu jawaban benar.';

    if (empty($errors)) {
        $db->prepare("UPDATE questions SET question_text=?,question_type=?,points=? WHERE id=?")->execute([$d['question_text'],$d['question_type'],$d['points'],$id]);
        $db->prepare("DELETE FROM options WHERE question_id=?")->execute([$id]);
        if ($d['question_type'] !== 'essay') {
            $stOpt = $db->prepare("INSERT INTO options (question_id,option_label,option_text,is_correct) VALUES (?,?,?,?)");
            foreach(['A','B','C','D','E'] as $l) {
                if ($d['options'][$l] !== '') $stOpt->execute([$id,$l,$d['options'][$l],in_array($l,$d['correct'])?1:0]);
            }
        }
        flash('success','Soal berhasil diperbarui!');
        redirect(BASE_URL."/admin/questions/index.php?exam_id=$examId");
    }
}

$pageBreadcrumb = 'Admin / Soal / ' . e($exam['title']);
include __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-2xl">
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Edit Soal</h2>
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
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Teks Soal *</label>
            <textarea name="question_text" rows="4" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($d['question_text']) ?></textarea>
        </div>
        <div id="optionsSection">
            <p class="text-sm font-medium text-gray-700 mb-3">Opsi Jawaban * <span class="text-gray-400 font-normal text-xs" id="optionsHintSub"></span></p>
            <div class="space-y-3">
            <?php foreach(['A','B','C','D','E'] as $lbl): ?>
            <div class="flex items-center gap-3">
                <input type="<?= $d['question_type']==='pg'?'radio':'checkbox' ?>" name="correct[]" value="<?= $lbl ?>"
                    <?= in_array($lbl,$d['correct'])?'checked':'' ?> class="correct-input w-4 h-4 text-blue-600 flex-shrink-0" <?= $d['question_type']==='pg'?'':'style=""' ?>>
                <span class="w-7 h-7 bg-gray-100 rounded text-xs font-bold flex items-center justify-center flex-shrink-0"><?= $lbl ?></span>
                <input type="text" name="option_<?= $lbl ?>" value="<?= e($d['options'][$lbl]) ?>" placeholder="Opsi <?= $lbl ?>"
                    class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <div class="flex gap-3 pt-4 border-t border-gray-100">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-sm"><i class="fas fa-save mr-1.5"></i> Simpan</button>
            <a href="<?= BASE_URL ?>/admin/questions/index.php?exam_id=<?= $examId ?>" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm">Batal</a>
        </div>
    </form>
</div>
</div>
<script>
function updateForm(){
    const type=document.getElementById('questionType').value;
    const section=document.getElementById('optionsSection');
    const hint=document.getElementById('optionsHintSub');
    if(type==='essay'){section.style.display='none';}
    else{
        section.style.display='block';
        hint.textContent=type==='pg'?'(pilih satu jawaban benar)':'(bisa pilih beberapa jawaban benar)';
        document.querySelectorAll('.correct-input').forEach(el=>{
            el.type=type==='pg'?'radio':'checkbox';
        });
    }
}
updateForm();
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
