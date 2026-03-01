<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Manajemen Soal';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$examId = (int)($_GET['exam_id'] ?? 0);

// If no exam selected, show list of exams to choose from
if (!$examId) {
    $exams = $db->query("SELECT e.*, (SELECT COUNT(*) FROM questions WHERE exam_id=e.id) AS q_count FROM exams e ORDER BY created_at DESC")->fetchAll();
    $pageBreadcrumb = 'Admin / Soal';
    include __DIR__ . '/../../includes/header.php';
    ?>
    <div class="mb-4">
        <p class="text-sm text-gray-500 mb-4">Pilih ujian untuk mengelola soal:</p>
        <div class="grid gap-3">
        <?php foreach ($exams as $ex): ?>
        <a href="?exam_id=<?= $ex['id'] ?>" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md hover:border-blue-200 transition-all flex items-center justify-between">
            <div>
                <p class="font-medium text-gray-800"><?= e($ex['title']) ?></p>
                <p class="text-sm text-gray-500 mt-0.5"><?= $ex['q_count'] ?> soal</p>
            </div>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </a>
        <?php endforeach; ?>
        <?php if (empty($exams)): ?><p class="text-center text-gray-400 py-8">Belum ada ujian.</p><?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../../includes/footer.php'; exit;
}

$exam = $db->prepare("SELECT * FROM exams WHERE id=?");
$exam->execute([$examId]); $exam = $exam->fetch();
if (!$exam) { flash('error','Ujian tidak ditemukan.'); redirect(BASE_URL.'/admin/questions/index.php'); }

$questions = $db->prepare("SELECT * FROM questions WHERE exam_id=? ORDER BY order_num, id");
$questions->execute([$examId]);
$questions = $questions->fetchAll();

$pageBreadcrumb = 'Admin / Soal / ' . e($exam['title']);
include __DIR__ . '/../../includes/header.php';
?>
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <p class="text-sm text-gray-500">Ujian: <span class="font-medium text-gray-700"><?= e($exam['title']) ?></span> — <?= count($questions) ?> soal</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/admin/import.php?exam_id=<?= $examId ?>" class="flex items-center gap-1.5 px-4 py-2 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-upload"></i> Import Excel
        </a>
        <a href="<?= BASE_URL ?>/admin/questions/create.php?exam_id=<?= $examId ?>" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors">
            <i class="fas fa-plus"></i> Tambah Soal
        </a>
    </div>
</div>

<div class="space-y-3">
    <?php if (empty($questions)): ?>
    <div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
        <i class="fas fa-question-circle text-5xl text-gray-200 mb-3"></i>
        <p class="text-gray-400 mb-3">Belum ada soal. Mulai tambahkan soal atau import dari Excel.</p>
        <div class="flex gap-3 justify-center">
            <a href="<?= BASE_URL ?>/admin/questions/create.php?exam_id=<?= $examId ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Tambah Manual</a>
            <a href="<?= BASE_URL ?>/admin/import.php?exam_id=<?= $examId ?>" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm hover:bg-green-200">Import Excel</a>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($questions as $i => $q): ?>
    <?php $opts = $db->prepare("SELECT * FROM options WHERE question_id=? ORDER BY option_label"); $opts->execute([$q['id']]); $opts = $opts->fetchAll(); ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <span class="flex-shrink-0 w-7 h-7 bg-blue-100 text-blue-600 rounded-full text-xs font-bold flex items-center justify-center"><?= $i+1 ?></span>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <?= questionTypeBadge($q['question_type']) ?>
                            <span class="text-xs text-gray-400"><?= $q['points'] ?> poin</span>
                        </div>
                        <p class="text-sm text-gray-800 leading-relaxed"><?= nl2br(e($q['question_text'])) ?></p>
                        <?php if (!empty($opts)): ?>
                        <div class="mt-3 space-y-1">
                            <?php foreach ($opts as $opt): ?>
                            <div class="flex items-start gap-2 text-xs <?= $opt['is_correct'] ? 'text-green-700 font-medium' : 'text-gray-600' ?>">
                                <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0 <?= $opt['is_correct'] ? 'bg-green-100' : 'bg-gray-100' ?>">
                                    <?= e($opt['option_label']) ?>
                                </span>
                                <?= e($opt['option_text']) ?>
                                <?php if ($opt['is_correct']): ?><i class="fas fa-check-circle text-green-500 ml-1"></i><?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <a href="<?= BASE_URL ?>/admin/questions/edit.php?id=<?= $q['id'] ?>&exam_id=<?= $examId ?>" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"><i class="fas fa-edit text-sm"></i></a>
                    <button onclick="confirmDelete(<?= $q['id'] ?>)" class="p-2 text-red-400 hover:bg-red-50 rounded-lg transition-colors"><i class="fas fa-trash text-sm"></i></button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="delModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl">
        <p class="text-center text-gray-700 mb-4">Yakin ingin menghapus soal ini?</p>
        <div class="flex gap-3">
            <button onclick="document.getElementById('delModal').classList.add('hidden')" class="flex-1 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Batal</button>
            <a id="delLink" href="#" class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm text-center font-medium">Hapus</a>
        </div>
    </div>
</div>
<script>
function confirmDelete(id) {
    document.getElementById('delLink').href='<?= BASE_URL ?>/admin/questions/delete.php?id='+id+'&exam_id=<?= $examId ?>';
    document.getElementById('delModal').classList.remove('hidden');
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
