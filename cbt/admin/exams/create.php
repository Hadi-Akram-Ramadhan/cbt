<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Buat Ujian';

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$errors = [];
$data = ['title'=>'','description'=>'','duration_minutes'=>60,'start_time'=>'','end_time'=>'','is_active'=>1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['title']            = sanitize($_POST['title'] ?? '');
    $data['description']      = sanitize($_POST['description'] ?? '');
    $data['duration_minutes'] = max(1, (int)($_POST['duration_minutes'] ?? 60));
    $data['start_time']       = sanitize($_POST['start_time'] ?? '');
    $data['end_time']         = sanitize($_POST['end_time'] ?? '');
    $data['is_active']        = isset($_POST['is_active']) ? 1 : 0;

    if (!$data['title']) $errors[] = 'Judul ujian harus diisi.';
    if ($data['start_time'] && $data['end_time'] && $data['end_time'] <= $data['start_time'])
        $errors[] = 'Waktu selesai harus setelah waktu mulai.';

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO exams (title, description, duration_minutes, start_time, end_time, is_active, created_by) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$data['title'], $data['description'], $data['duration_minutes'],
            $data['start_time'] ?: null, $data['end_time'] ?: null, $data['is_active'], $_SESSION['user_id']]);
        $newId = $db->lastInsertId();
        flash('success', 'Ujian berhasil dibuat! Sekarang tambahkan soal.');
        redirect(BASE_URL . "/admin/questions/index.php?exam_id=$newId");
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-2xl">
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Form Buat Ujian Baru</h2>

    <?php if ($errors): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
        <ul class="list-disc list-inside space-y-1"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Ujian <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="<?= e($data['title']) ?>" required
                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
            <textarea name="description" rows="3"
                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"><?= e($data['description']) ?></textarea>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Durasi (menit) <span class="text-red-500">*</span></label>
                <input type="number" name="duration_minutes" value="<?= $data['duration_minutes'] ?>" min="1" required
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Waktu Mulai</label>
                <input type="datetime-local" name="start_time" value="<?= e($data['start_time']) ?>"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Waktu Selesai</label>
                <input type="datetime-local" name="end_time" value="<?= e($data['end_time']) ?>"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" <?= $data['is_active'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
            <label for="is_active" class="text-sm text-gray-700">Ujian aktif (visible untuk siswa)</label>
        </div>
        <div class="flex gap-3 pt-4 border-t border-gray-100">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-sm transition-colors">
                <i class="fas fa-save mr-1.5"></i> Buat & Lanjut ke Soal
            </button>
            <a href="<?= BASE_URL ?>/admin/exams/index.php" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm transition-colors">Batal</a>
        </div>
    </form>
</div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
