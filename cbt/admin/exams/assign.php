<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Peserta Ujian';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$exam = $db->prepare("SELECT * FROM exams WHERE id=?");
$exam->execute([$id]); $exam = $exam->fetch();
if (!$exam) { flash('error','Ujian tidak ditemukan.'); redirect(BASE_URL.'/admin/exams/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assigned = $_POST['students'] ?? [];
    // Delete all existing and re-insert
    $db->prepare("DELETE FROM exam_participants WHERE exam_id=?")->execute([$id]);
    if (!empty($assigned)) {
        $stmt = $db->prepare("INSERT INTO exam_participants (exam_id, user_id) VALUES (?,?)");
        foreach ($assigned as $uid) {
            $stmt->execute([$id, (int)$uid]);
        }
    }
    flash('success','Peserta ujian berhasil diperbarui!');
    redirect(BASE_URL.'/admin/exams/index.php');
}

$allStudents = $db->query("SELECT * FROM users WHERE role='siswa' AND is_active=1 ORDER BY kelas, name")->fetchAll();
$assignedIds = $db->prepare("SELECT user_id FROM exam_participants WHERE exam_id=?");
$assignedIds->execute([$id]);
$assignedIds = array_column($assignedIds->fetchAll(), 'user_id');

include __DIR__ . '/../../includes/header.php';
$pageBreadcrumb = 'Admin / Ujian / Peserta';
?>
<div class="max-w-3xl">
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Atur Peserta Ujian</h2>
        <p class="text-sm text-gray-500 mt-1">Ujian: <span class="font-medium text-gray-700"><?= e($exam['title']) ?></span></p>
    </div>
    <form method="POST">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 rounded" onchange="toggleAll(this)">
                <label for="selectAll" class="text-sm font-medium text-gray-700">Pilih Semua (<span id="selectedCount"><?= count($assignedIds) ?></span> terpilih)</label>
            </div>
            <input type="text" id="searchStudent" placeholder="Cari siswa..." oninput="filterStudents(this.value)"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-52">
        </div>

        <?php $kelasGroups = []; foreach ($allStudents as $s) { $kelasGroups[$s['kelas'] ?? 'Tanpa Kelas'][] = $s; } ?>
        <div class="space-y-4 max-h-[450px] overflow-y-auto pr-1" id="studentList">
            <?php foreach ($kelasGroups as $kelas => $students): ?>
            <div class="student-group">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2"><?= e($kelas) ?></p>
                <div class="space-y-1">
                    <?php foreach ($students as $s): ?>
                    <label class="student-row flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors border border-transparent hover:border-gray-200">
                        <input type="checkbox" name="students[]" value="<?= $s['id'] ?>"
                            <?= in_array($s['id'], $assignedIds) ? 'checked' : '' ?>
                            class="student-cb w-4 h-4 text-blue-600 rounded" onchange="updateCount()">
                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0">
                            <?= strtoupper(substr($s['name'],0,1)) ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800 student-name"><?= e($s['name']) ?></p>
                            <p class="text-xs text-gray-400"><?= e($s['nis'] ?? '-') ?> · <?= e($s['username']) ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($allStudents)): ?>
            <p class="text-center text-sm text-gray-400 py-8">Belum ada siswa. <a href="<?= BASE_URL ?>/admin/students/create.php" class="text-blue-600 hover:underline">Tambah siswa</a></p>
            <?php endif; ?>
        </div>

        <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-sm">
                <i class="fas fa-save mr-1.5"></i> Simpan Peserta
            </button>
            <a href="<?= BASE_URL ?>/admin/exams/index.php" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm">Batal</a>
        </div>
    </form>
</div>
</div>
<script>
function updateCount() {
    document.getElementById('selectedCount').textContent = document.querySelectorAll('.student-cb:checked').length;
}
function toggleAll(cb) {
    document.querySelectorAll('.student-cb').forEach(c => { c.checked = cb.checked; });
    updateCount();
}
function filterStudents(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.student-row').forEach(row => {
        const name = row.querySelector('.student-name').textContent.toLowerCase();
        row.style.display = name.includes(q) ? '' : 'none';
    });
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
