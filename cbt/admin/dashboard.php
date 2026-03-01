<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Dashboard';
$pageBreadcrumb = 'Selamat datang di panel admin';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();

// Stats
$totalSiswa  = $db->query("SELECT COUNT(*) FROM users WHERE role='siswa' AND is_active=1")->fetchColumn();
$totalExams  = $db->query("SELECT COUNT(*) FROM exams WHERE is_active=1")->fetchColumn();
$totalSoal   = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$avgScore    = $db->query("SELECT ROUND(AVG(percentage),1) FROM results")->fetchColumn() ?? 0;

// Recent exams
$recentExams = $db->query("
    SELECT e.*, 
        (SELECT COUNT(*) FROM exam_participants WHERE exam_id=e.id) AS total_peserta,
        (SELECT COUNT(*) FROM questions WHERE exam_id=e.id) AS total_soal,
        (SELECT COUNT(*) FROM exam_sessions WHERE exam_id=e.id AND status='submitted') AS total_selesai
    FROM exams e ORDER BY e.created_at DESC LIMIT 5
")->fetchAll();

// Recent results
$recentResults = $db->query("
    SELECT r.*, u.name AS student_name, e.title AS exam_title
    FROM results r
    JOIN users u ON r.user_id = u.id
    JOIN exams e ON r.exam_id = e.id
    ORDER BY r.created_at DESC LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Stats cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <?php
    $stats = [
        ['label'=>'Total Siswa', 'value'=>$totalSiswa, 'icon'=>'fa-users', 'color'=>'blue', 'bg'=>'bg-blue-100','ic'=>'text-blue-600'],
        ['label'=>'Ujian Aktif', 'value'=>$totalExams, 'icon'=>'fa-file-alt', 'color'=>'green', 'bg'=>'bg-green-100','ic'=>'text-green-600'],
        ['label'=>'Total Soal', 'value'=>$totalSoal, 'icon'=>'fa-question-circle', 'color'=>'purple', 'bg'=>'bg-purple-100','ic'=>'text-purple-600'],
        ['label'=>'Rata-rata Nilai', 'value'=>$avgScore.'%', 'icon'=>'fa-chart-line', 'color'=>'orange', 'bg'=>'bg-orange-100','ic'=>'text-orange-600'],
    ];
    foreach ($stats as $s): ?>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-14 h-14 <?= $s['bg'] ?> rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas <?= $s['icon'] ?> <?= $s['ic'] ?> text-xl"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $s['value'] ?></p>
            <p class="text-sm text-gray-500 mt-0.5"><?= $s['label'] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Exams -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Ujian Terbaru</h2>
            <a href="<?= BASE_URL ?>/admin/exams/index.php" class="text-sm text-blue-600 hover:underline">Lihat semua</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($recentExams)): ?>
            <p class="px-6 py-8 text-center text-sm text-gray-400">Belum ada ujian.</p>
            <?php else: foreach ($recentExams as $ex): ?>
            <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-gray-800 text-sm"><?= e($ex['title']) ?></p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <?= $ex['total_soal'] ?> soal &nbsp;·&nbsp; <?= $ex['total_peserta'] ?> peserta &nbsp;·&nbsp; <?= $ex['total_selesai'] ?> selesai
                        </p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full <?= $ex['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?> whitespace-nowrap">
                        <?= $ex['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Recent Results -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Hasil Ujian Terbaru</h2>
            <a href="<?= BASE_URL ?>/admin/results/index.php" class="text-sm text-blue-600 hover:underline">Lihat semua</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($recentResults)): ?>
            <p class="px-6 py-8 text-center text-sm text-gray-400">Belum ada hasil ujian.</p>
            <?php else: foreach ($recentResults as $r): ?>
            <div class="px-6 py-4 hover:bg-gray-50">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-medium text-sm text-gray-800"><?= e($r['student_name']) ?></p>
                        <p class="text-xs text-gray-400 mt-0.5"><?= e($r['exam_title']) ?></p>
                    </div>
                    <?php $gc = getGradeColor($r['percentage']); $gl = getGradeLetter($r['percentage']); ?>
                    <div class="text-right">
                        <span class="text-lg font-bold text-<?= $gc ?>-600"><?= $r['percentage'] ?>%</span>
                        <p class="text-xs text-gray-400">Grade <?= $gl ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
