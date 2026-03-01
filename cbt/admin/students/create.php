<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Tambah Siswa';
$pageBreadcrumb = 'Admin / Siswa / Tambah';

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$errors = [];
$data = ['name'=>'','username'=>'','nis'=>'','kelas'=>'','is_active'=>1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']      = sanitize($_POST['name'] ?? '');
    $data['username']  = sanitize($_POST['username'] ?? '');
    $data['nis']       = sanitize($_POST['nis'] ?? '');
    $data['kelas']     = sanitize($_POST['kelas'] ?? '');
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $password          = $_POST['password'] ?? '';
    $confirm           = $_POST['confirm_password'] ?? '';

    if (!$data['name'])     $errors[] = 'Nama harus diisi.';
    if (!$data['username']) $errors[] = 'Username harus diisi.';
    if (!$password)         $errors[] = 'Password harus diisi.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        // Check unique username
        $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username=?");
        $check->execute([$data['username']]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'Username sudah digunakan.';
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO users (name, username, password, role, nis, kelas, is_active) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$data['name'], $data['username'], password_hash($password, PASSWORD_DEFAULT), 'siswa', $data['nis'], $data['kelas'], $data['is_active']]);
        flash('success', 'Siswa berhasil ditambahkan!');
        redirect(BASE_URL . '/admin/students/index.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">Form Tambah Siswa</h2>

        <?php if ($errors): ?>
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= e($data['name']) ?>" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" value="<?= e($data['username']) ?>" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">NIS</label>
                    <input type="text" name="nis" value="<?= e($data['nis']) ?>"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kelas</label>
                    <input type="text" name="kelas" value="<?= e($data['kelas']) ?>" placeholder="e.g. XII-IPA-1"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Min. 6 karakter">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Password <span class="text-red-500">*</span></label>
                    <input type="password" name="confirm_password" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" <?= $data['is_active'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
                <label for="is_active" class="text-sm text-gray-700">Akun aktif</label>
            </div>
            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
                    <i class="fas fa-save mr-1.5"></i> Simpan
                </button>
                <a href="<?= BASE_URL ?>/admin/students/index.php" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm transition-colors">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
