<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Nilai & Hasil Ujian';
$pageBreadcrumb = 'Admin / Hasil Ujian';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db     = getDB();
$examId = (int)($_GET['exam_id'] ?? 0);
$exams  = $db->query("SELECT id, title FROM exams ORDER BY title")->fetchAll();

$results = [];
if ($examId) {
    $results = $db->prepare("
        SELECT r.*, u.name AS student_name, u.nis, u.kelas,
               es.tab_switch_count, es.submitted_at, es.status
        FROM results r
        JOIN users u ON r.user_id = u.id
        JOIN exam_sessions es ON r.session_id = es.id
        WHERE r.exam_id = ?
        ORDER BY r.percentage DESC
    ");
    $results->execute([$examId]);
    $results = $results->fetchAll();
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="mb-6">
    <form method="GET" class="flex gap-3 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Pilih Ujian</label>
            <select name="exam_id" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[240px]">
                <option value="">-- Semua Ujian --</option>
                <?php foreach ($exams as $ex): ?>
                <option value="<?= $ex['id'] ?>" <?= $examId==$ex['id']?'selected':'' ?>><?= e($ex['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium">Filter</button>
    </form>
</div>

<?php if ($examId && empty($results)): ?>
<div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
    <i class="fas fa-chart-bar text-4xl text-gray-200 mb-3"></i>
    <p class="text-gray-400">Belum ada siswa yang menyelesaikan ujian ini.</p>
</div>
<?php elseif (!empty($results)): ?>
<!-- Summary cards -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <?php
    $scores     = array_column($results,'percentage');
    $avgScore   = count($scores) ? round(array_sum($scores)/count($scores),1) : 0;
    $maxScore   = count($scores) ? max($scores) : 0;
    $minScore   = count($scores) ? min($scores) : 0;
    $tabTotal   = array_sum(array_column($results,'tab_switch_count'));
    $summaries  = [
        ['label'=>'Peserta','value'=>count($results),'icon'=>'fa-users','color'=>'blue'],
        ['label'=>'Rata-rata','value'=>$avgScore.'%','icon'=>'fa-chart-line','color'=>'green'],
        ['label'=>'Tertinggi','value'=>$maxScore.'%','icon'=>'fa-arrow-up','color'=>'purple'],
        ['label'=>'Tab Switch Total','value'=>$tabTotal,'icon'=>'fa-exclamation-triangle','color'=>'orange'],
    ];
    foreach($summaries as $s): ?>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <p class="text-2xl font-bold text-gray-800"><?= $s['value'] ?></p>
        <p class="text-sm text-gray-500 mt-0.5"><?= $s['label'] ?></p>
    </div>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama Siswa</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kelas</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Skor</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Grade</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tab Switch</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Selesai</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
        <?php foreach ($results as $i => $r): ?>
        <?php $gc = getGradeColor($r['percentage']); $gl = getGradeLetter($r['percentage']); ?>
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-gray-400 font-medium"><?= $i+1 ?></td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold">
                        <?= strtoupper(substr($r['student_name'],0,1)) ?>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800"><?= e($r['student_name']) ?></p>
                        <p class="text-xs text-gray-400"><?= e($r['nis'] ?? '-') ?></p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 text-gray-600"><?= e($r['kelas'] ?? '-') ?></td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-<?= $gc ?>-500 rounded-full" style="width:<?= $r['percentage'] ?>%"></div>
                    </div>
                    <span class="font-semibold text-<?= $gc ?>-600"><?= $r['percentage'] ?>%</span>
                </div>
                <p class="text-xs text-gray-400"><?= $r['total_score'] ?>/<?= $r['max_score'] ?></p>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs font-bold rounded-lg bg-<?= $gc ?>-100 text-<?= $gc ?>-700"><?= $gl ?></span>
            </td>
            <td class="px-4 py-3">
                <?php if ($r['tab_switch_count'] > 0): ?>
                <span class="px-2 py-1 text-xs bg-orange-100 text-orange-700 rounded-full font-medium">
                    <i class="fas fa-exclamation-triangle mr-1"></i><?= $r['tab_switch_count'] ?>x
                </span>
                <?php else: ?>
                <span class="text-xs text-gray-400">–</span>
                <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-xs text-gray-500"><?= formatDate($r['submitted_at'] ?? '') ?></td>
            <td class="px-4 py-3">
                <a href="<?= BASE_URL ?>/admin/results/detail.php?session_id=<?= $r['session_id'] ?>" class="text-blue-500 hover:text-blue-700 text-xs font-medium hover:underline">Detail</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif (!$examId): ?>
<div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
    <i class="fas fa-filter text-4xl text-gray-200 mb-3"></i>
    <p class="text-gray-400">Pilih ujian untuk melihat hasil.</p>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
