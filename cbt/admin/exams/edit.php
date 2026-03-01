<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Edit Ujian';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$exam = $db->prepare("SELECT * FROM exams WHERE id=?");
$exam->execute([$id]); $exam = $exam->fetch();
if (!$exam) { flash('error','Ujian tidak ditemukan.'); redirect(BASE_URL.'/admin/exams/index.php'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = sanitize($_POST['title'] ?? '');
    $desc     = sanitize($_POST['description'] ?? '');
    $dur      = max(1,(int)($_POST['duration_minutes'] ?? 60));
    $start    = sanitize($_POST['start_time'] ?? '');
    $end      = sanitize($_POST['end_time'] ?? '');
    $active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$title) $errors[] = 'Judul harus diisi.';
    if ($start && $end && $end <= $start) $errors[] = 'Waktu selesai harus setelah waktu mulai.';

    if (empty($errors)) {
        $db->prepare("UPDATE exams SET title=?,description=?,duration_minutes=?,start_time=?,end_time=?,is_active=? WHERE id=?")
           ->execute([$title,$desc,$dur,$start?:null,$end?:null,$active,$id]);
        flash('success','Ujian berhasil diperbarui!');
        redirect(BASE_URL.'/admin/exams/index.php');
    }
    $exam = array_merge($exam,['title'=>$title,'description'=>$desc,'duration_minutes'=>$dur,'start_time'=>$start,'end_time'=>$end,'is_active'=>$active]);
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-2xl">
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Edit Ujian</h2>
    <?php if ($errors): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
        <ul class="list-disc list-inside"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
    <form method="POST" class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Ujian *</label>
            <input type="text" name="title" value="<?= e($exam['title']) ?>" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"><?= e($exam['description']) ?></textarea>
        </div>
        <div class="grid grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Durasi (menit)</label>
                <input type="number" name="duration_minutes" value="<?= $exam['duration_minutes'] ?>" min="1" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Waktu Mulai</label>
                <input type="datetime-local" name="start_time" value="<?= e(str_replace(' ','T',$exam['start_time']??'')) ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Waktu Selesai</label>
                <input type="datetime-local" name="end_time" value="<?= e(str_replace(' ','T',$exam['end_time']??'')) ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" <?= $exam['is_active'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
            <label for="is_active" class="text-sm text-gray-700">Ujian aktif</label>
        </div>
        <div class="flex gap-3 pt-4 border-t border-gray-100">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-sm">
                <i class="fas fa-save mr-1.5"></i> Simpan Perubahan
            </button>
            <a href="<?= BASE_URL ?>/admin/exams/index.php" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm">Batal</a>
        </div>
    </form>
</div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
