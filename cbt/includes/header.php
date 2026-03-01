<?php
// Base URL — adjust if project is in a subfolder
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = currentUser();
$currentPath = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe',
                            300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6',
                            600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { @apply bg-brand-700 text-white; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<?php if (isLoggedIn()): ?>
<!-- ===== Sidebar Layout ===== -->
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-brand-900 text-white flex flex-col fixed inset-y-0 left-0 z-30 shadow-xl">
        <!-- Logo -->
        <div class="flex items-center gap-3 px-6 py-5 border-b border-brand-800">
            <div class="w-9 h-9 bg-brand-500 rounded-lg flex items-center justify-center">
                <i class="fas fa-graduation-cap text-white text-lg"></i>
            </div>
            <div>
                <div class="font-bold text-lg leading-tight"><?= APP_NAME ?></div>
                <div class="text-brand-300 text-xs"><?= $currentUser['role'] === 'admin' ? 'Admin Panel' : 'Portal Siswa' ?></div>
            </div>
        </div>

        <!-- User info -->
        <div class="px-4 py-3 border-b border-brand-800 bg-brand-800">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-brand-500 rounded-full flex items-center justify-center text-sm font-bold">
                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                </div>
                <div>
                    <div class="text-sm font-medium truncate max-w-[140px]"><?= e($currentUser['name']) ?></div>
                    <div class="text-brand-400 text-xs"><?= ucfirst($currentUser['role']) ?></div>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <?php if ($currentUser['role'] === 'admin'): ?>
                <?php
                $adminLinks = [
                    ['icon'=>'fa-chart-pie',     'label'=>'Dashboard',     'href'=> BASE_URL.'/admin/dashboard.php'],
                    ['icon'=>'fa-users',          'label'=>'Siswa',         'href'=> BASE_URL.'/admin/students/index.php'],
                    ['icon'=>'fa-file-alt',       'label'=>'Ujian',         'href'=> BASE_URL.'/admin/exams/index.php'],
                    ['icon'=>'fa-question-circle','label'=>'Soal',          'href'=> BASE_URL.'/admin/questions/index.php'],
                    ['icon'=>'fa-upload',         'label'=>'Import Soal',   'href'=> BASE_URL.'/admin/import.php'],
                    ['icon'=>'fa-chart-bar',      'label'=>'Nilai & Hasil', 'href'=> BASE_URL.'/admin/results/index.php'],
                ];
                foreach ($adminLinks as $link):
                    $active = strpos($currentPath, basename($link['href'])) !== false ? 'bg-brand-700' : 'hover:bg-brand-800';
                ?>
                <a href="<?= $link['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-brand-100 transition-all <?= $active ?>">
                    <i class="fas <?= $link['icon'] ?> w-5 text-center text-brand-300"></i>
                    <?= $link['label'] ?>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <?php
                $siswaLinks = [
                    ['icon'=>'fa-home',      'label'=>'Dashboard',  'href'=> BASE_URL.'/user/dashboard.php'],
                    ['icon'=>'fa-clipboard-list','label'=>'Ujian Saya','href'=> BASE_URL.'/user/dashboard.php'],
                ];
                foreach ($siswaLinks as $link):
                    $active = strpos($currentPath, basename($link['href'])) !== false ? 'bg-brand-700' : 'hover:bg-brand-800';
                ?>
                <a href="<?= $link['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-brand-100 transition-all <?= $active ?>">
                    <i class="fas <?= $link['icon'] ?> w-5 text-center text-brand-300"></i>
                    <?= $link['label'] ?>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>

        <!-- Logout -->
        <div class="px-3 py-4 border-t border-brand-800">
            <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-300 hover:bg-red-900/30 transition-all">
                <i class="fas fa-sign-out-alt w-5 text-center"></i>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 ml-64">
        <!-- Top bar -->
        <header class="bg-white border-b border-gray-200 px-6 py-4 sticky top-0 z-10 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800"><?= isset($pageTitle) ? e($pageTitle) : APP_NAME ?></h1>
                    <?php if (isset($pageBreadcrumb)): ?>
                    <p class="text-sm text-gray-500 mt-0.5"><?= $pageBreadcrumb ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-sm text-gray-500">
                    <i class="far fa-clock mr-1"></i>
                    <span id="currentTime"><?= date('H:i') ?></span> &nbsp;|&nbsp; <?= date('d M Y') ?>
                </div>
            </div>
        </header>

        <!-- Flash messages -->
        <?php if ($success = flash('success')): ?>
        <div class="mx-6 mt-4 flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm" id="flashMsg">
            <i class="fas fa-check-circle text-green-500"></i> <?= e($success) ?>
            <button onclick="this.parentElement.remove()" class="ml-auto text-green-500 hover:text-green-700"><i class="fas fa-times"></i></button>
        </div>
        <?php endif; ?>
        <?php if ($error = flash('error')): ?>
        <div class="mx-6 mt-4 flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm" id="flashMsg">
            <i class="fas fa-exclamation-circle text-red-500"></i> <?= e($error) ?>
            <button onclick="this.parentElement.remove()" class="ml-auto text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
        </div>
        <?php endif; ?>

        <!-- Page content -->
        <div class="p-6">
<?php endif; // isLoggedIn ?>
<?php if (!isLoggedIn()): ?>
<div class="min-h-screen">
<?php endif; ?>
