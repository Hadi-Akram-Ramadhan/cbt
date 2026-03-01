<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Manajemen Ujian';
$pageBreadcrumb = 'Admin / Ujian';

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$exams = $db->query("
    SELECT e.*,
        (SELECT COUNT(*) FROM exam_participants WHERE exam_id=e.id) AS total_peserta,
        (SELECT COUNT(*) FROM questions WHERE exam_id=e.id) AS total_soal,
        (SELECT COUNT(*) FROM exam_sessions WHERE exam_id=e.id AND status='submitted') AS total_selesai
    FROM exams e ORDER BY e.created_at DESC
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500"><?= count($exams) ?> ujian tersedia</p>
    <a href="<?= BASE_URL ?>/admin/exams/create.php" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
        <i class="fas fa-plus"></i> Buat Ujian
    </a>
</div>

<div class="grid gap-4">
    <?php if (empty($exams)): ?>
    <div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
        <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-400">Belum ada ujian. <a href="<?= BASE_URL ?>/admin/exams/create.php" class="text-blue-600 hover:underline">Buat ujian baru</a></p>
    </div>
    <?php else: foreach ($exams as $ex): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-semibold text-gray-800 truncate"><?= e($ex['title']) ?></h3>
                    <span class="flex-shrink-0 text-xs px-2 py-0.5 rounded-full <?= $ex['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                        <?= $ex['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </div>
                <?php if ($ex['description']): ?>
                <p class="text-sm text-gray-500 mb-3 line-clamp-2"><?= e($ex['description']) ?></p>
                <?php endif; ?>
                <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                    <span><i class="fas fa-clock mr-1 text-blue-400"></i><?= $ex['duration_minutes'] ?> menit</span>
                    <span><i class="fas fa-question-circle mr-1 text-purple-400"></i><?= $ex['total_soal'] ?> soal</span>
                    <span><i class="fas fa-users mr-1 text-green-400"></i><?= $ex['total_peserta'] ?> peserta</span>
                    <span><i class="fas fa-check-circle mr-1 text-orange-400"></i><?= $ex['total_selesai'] ?> selesai</span>
                    <?php if ($ex['start_time']): ?>
                    <span><i class="fas fa-calendar mr-1"></i><?= formatDate($ex['start_time']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex flex-row sm:flex-col gap-2 flex-shrink-0">
                <a href="<?= BASE_URL ?>/admin/questions/index.php?exam_id=<?= $ex['id'] ?>" class="flex items-center gap-1.5 px-3 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-xs font-medium transition-colors">
                    <i class="fas fa-question-circle"></i> Soal
                </a>
                <a href="<?= BASE_URL ?>/admin/exams/assign.php?id=<?= $ex['id'] ?>" class="flex items-center gap-1.5 px-3 py-2 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-xs font-medium transition-colors">
                    <i class="fas fa-user-plus"></i> Peserta
                </a>
                <a href="<?= BASE_URL ?>/admin/exams/edit.php?id=<?= $ex['id'] ?>" class="flex items-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-medium transition-colors">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button onclick="confirmDelete(<?= $ex['id'] ?>, '<?= e($ex['title']) ?>')" class="flex items-center gap-1.5 px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-medium transition-colors">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-center text-gray-800">Hapus Ujian?</h3>
        <p class="text-sm text-gray-500 text-center mt-1" id="deleteMessage"></p>
        <p class="text-xs text-red-500 text-center mt-1">Semua soal dan data peserta akan ikut terhapus!</p>
        <div class="flex gap-3 mt-6">
            <button onclick="document.getElementById('deleteModal').classList.add('hidden')" class="flex-1 py-2 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 text-sm">Batal</button>
            <a id="deleteLink" href="#" class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm text-center font-medium">Hapus</a>
        </div>
    </div>
</div>
<script>
function confirmDelete(id, name){
    document.getElementById('deleteMessage').textContent = `Ujian "${name}" akan dihapus.`;
    document.getElementById('deleteLink').href='<?= BASE_URL ?>/admin/exams/delete.php?id='+id;
    document.getElementById('deleteModal').classList.remove('hidden');
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
