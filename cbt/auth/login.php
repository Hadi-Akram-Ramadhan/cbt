<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');

if (session_status() === PHP_SESSION_NONE) session_start();

if (isLoggedIn()) {
    redirect(BASE_URL . ($_SESSION['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['kelas']     = $user['kelas'] ?? '';
            redirect(BASE_URL . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php'));
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi semua kolom.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter','sans-serif'] } } } }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-login { background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #2563eb 100%); }
        .card-glass { background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); }
        .input-field { width:100%; padding:0.75rem 1rem 0.75rem 2.5rem; border:1px solid #d1d5db; border-radius:0.75rem; font-size:0.875rem; outline:none; transition:all 0.15s; background:white; }
        .input-field:focus { box-shadow:0 0 0 2px #3b82f6; border-color:transparent; }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
        .float { animation: float 3s ease-in-out infinite; }
    </style>
</head>
<body class="bg-login min-h-screen flex items-center justify-center p-4">

<!-- Background decorations -->
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute top-1/4 left-10 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
    <div class="absolute top-10 right-1/4 w-32 h-32 bg-blue-400/20 rounded-full blur-2xl"></div>
</div>

<div class="relative w-full max-w-md">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="float inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-2xl mb-4 shadow-lg">
            <i class="fas fa-graduation-cap text-white text-4xl"></i>
        </div>
        <h1 class="text-3xl font-bold text-white"><?= APP_NAME ?></h1>
        <p class="text-blue-200 mt-1 text-sm">Computer Based Test Platform</p>
    </div>

    <!-- Card -->
    <div class="card-glass rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Masuk ke Akun</h2>

        <?php if ($error): ?>
        <div class="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm mb-6">
            <i class="fas fa-exclamation-circle text-red-500"></i>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="username" id="username"
                        value="<?= e($_POST['username'] ?? '') ?>"
                        class="input-field" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="password" name="password" id="password"
                        class="input-field" placeholder="Masukkan password" required>
                    <button type="button" onclick="togglePass()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye" id="passIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg active:scale-95 flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
        </p>
    </div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('password');
    const icon = document.getElementById('passIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye','fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash','fa-eye');
    }
}
</script>
</body>
</html>
