<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Dashboard Siswa';
$pageBreadcrumb = 'Ujian Tersedia';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireSiswa();

$db  = getDB();
$uid = $_SESSION['user_id'];

// Exams assigned to this student + status
$exams = $db->prepare("
    SELECT e.*,
        (SELECT COUNT(*) FROM questions WHERE exam_id=e.id) AS total_soal,
        es.id AS session_id, es.status AS session_status, es.started_at,
        r.percentage, r.total_score, r.max_score
    FROM exam_participants ep
    JOIN exams e ON ep.exam_id = e.id
    LEFT JOIN exam_sessions es ON es.exam_id=e.id AND es.user_id=?
    LEFT JOIN results r ON r.session_id=es.id
    WHERE ep.user_id = ? AND e.is_active = 1
    ORDER BY e.created_at DESC
");
$exams->execute([$uid,$uid]);
$exams = $exams->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<!-- Welcome banner -->
<div class="bg-gradient-to-r from-brand-700 to-brand-500 rounded-2xl p-6 text-white mb-6 relative overflow-hidden">
    <div class="absolute right-0 top-0 w-40 h-40 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4"></div>
    <div class="absolute right-20 bottom-0 w-24 h-24 bg-white/10 rounded-full translate-y-1/2"></div>
    <h2 class="text-xl font-bold relative">Halo, <?= e($_SESSION['user_name']) ?>! 👋</h2>
    <p class="text-blue-100 mt-1 text-sm relative">Selamat datang di portal ujian. Kerjakan ujian dengan jujur dan penuh semangat!</p>
    <?php if (!empty($_SESSION['kelas'])): ?>
    <p class="text-blue-200 text-xs mt-2 relative"><i class="fas fa-graduation-cap mr-1"></i><?= e($_SESSION['kelas']) ?></p>
    <?php endif; ?>
</div>

<h2 class="text-base font-semibold text-gray-800 mb-4">Ujian Saya (<?= count($exams) ?>)</h2>

<div class="grid gap-4">
    <?php if (empty($exams)): ?>
    <div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
        <i class="fas fa-clipboard-list text-5xl text-gray-200 mb-3"></i>
        <p class="text-gray-400">Belum ada ujian yang ditugaskan untukmu.</p>
    </div>
    <?php else: foreach ($exams as $ex):
        $status     = $ex['session_status'] ?? 'belum';
        $sessionId  = $ex['session_id'] ?? null;
        $pct        = $ex['percentage'] ?? null;
        $gc         = $pct !== null ? getGradeColor((float)$pct) : 'gray';
    ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
        <div class="p-5">
            <div class="flex flex-col sm:flex-row justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800"><?= e($ex['title']) ?></h3>
                        <?php if ($status === 'submitted' || $status === 'timeout'): ?>
                        <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full">Selesai</span>
                        <?php elseif ($status === 'ongoing'): ?>
                        <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full animate-pulse">Sedang Berlangsung</span>
                        <?php else: ?>
                        <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full">Belum Dikerjakan</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($ex['description']): ?>
                    <p class="text-sm text-gray-500 mb-3"><?= e($ex['description']) ?></p>
                    <?php endif; ?>
                    <div class="flex flex-wrap gap-3 text-sm text-gray-500">
                        <span><i class="fas fa-clock mr-1 text-blue-400"></i><?= $ex['duration_minutes'] ?> menit</span>
                        <span><i class="fas fa-question-circle mr-1 text-purple-400"></i><?= $ex['total_soal'] ?> soal</span>
                        <?php if ($ex['start_time']): ?>
                        <span><i class="fas fa-calendar mr-1 text-gray-400"></i><?= formatDate($ex['start_time']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex flex-col items-end justify-between gap-3 flex-shrink-0">
                    <?php if ($pct !== null): ?>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-<?= $gc ?>-600"><?= $pct ?>%</p>
                        <p class="text-xs text-gray-400">Grade <?= getGradeLetter((float)$pct) ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="flex gap-2">
                        <?php if ($status === 'submitted' || $status === 'timeout'): ?>
                        <a href="<?= BASE_URL ?>/user/result.php?session_id=<?= $sessionId ?>" class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-sm font-medium transition-colors">
                            <i class="fas fa-chart-bar mr-1"></i> Lihat Hasil
                        </a>
                        <?php elseif ($status === 'ongoing'): ?>
                        <a href="<?= BASE_URL ?>/user/exam.php?exam_id=<?= $ex['id'] ?>" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-sm transition-colors">
                            <i class="fas fa-play mr-1"></i> Lanjutkan
                        </a>
                        <?php else: ?>
                        <?php
                        // Check time window
                        $canStart = true;
                        $now = time();
                        if ($ex['start_time'] && strtotime($ex['start_time']) > $now) $canStart = false;
                        if ($ex['end_time'] && strtotime($ex['end_time']) < $now) $canStart = false;
                        ?>
                        <?php if ($canStart): ?>
                        <a href="<?= BASE_URL ?>/user/exam.php?exam_id=<?= $ex['id'] ?>" onclick="return confirmStart()"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-sm transition-colors">
                            <i class="fas fa-play mr-1"></i> Mulai Ujian
                        </a>
                        <?php else: ?>
                        <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-xl text-sm cursor-not-allowed">
                            <i class="fas fa-lock mr-1"></i>
                            <?= $ex['start_time'] && strtotime($ex['start_time']) > $now ? 'Belum Dibuka' : 'Sudah Ditutup' ?>
                        </span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<script>
function confirmStart() {
    return confirm('Apakah kamu siap memulai ujian?\n\n⚠️ Perhatian:\n• Perpindahan tab akan dicatat\n• Copy-paste dinonaktifkan\n• Ujian berjalan dengan timer otomatis\n\nTekan OK untuk mulai.');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
