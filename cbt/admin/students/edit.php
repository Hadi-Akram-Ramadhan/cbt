<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Edit Siswa';
$pageBreadcrumb = 'Admin / Siswa / Edit';

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$data = $db->prepare("SELECT * FROM users WHERE id=? AND role='siswa'");
$data->execute([$id]);
$student = $data->fetch();
if (!$student) { flash('error', 'Siswa tidak ditemukan.'); redirect(BASE_URL.'/admin/students/index.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $nis      = sanitize($_POST['nis'] ?? '');
    $kelas    = sanitize($_POST['kelas'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name)     $errors[] = 'Nama harus diisi.';
    if (!$username) $errors[] = 'Username harus diisi.';
    if ($password && strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password && $password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username=? AND id!=?");
        $check->execute([$username, $id]);
        if ($check->fetchColumn() > 0) $errors[] = 'Username sudah digunakan.';
    }

    if (empty($errors)) {
        if ($password) {
            $stmt = $db->prepare("UPDATE users SET name=?, username=?, password=?, nis=?, kelas=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $nis, $kelas, $isActive, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET name=?, username=?, nis=?, kelas=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $username, $nis, $kelas, $isActive, $id]);
        }
        flash('success', 'Data siswa berhasil diperbarui!');
        redirect(BASE_URL.'/admin/students/index.php');
    }
    // Refill form with submitted data
    $student = array_merge($student, ['name'=>$name,'username'=>$username,'nis'=>$nis,'kelas'=>$kelas,'is_active'=>$isActive]);
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">Edit Data Siswa</h2>

        <?php if ($errors): ?>
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= e($student['name']) ?>" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" value="<?= e($student['username']) ?>" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">NIS</label>
                    <input type="text" name="nis" value="<?= e($student['nis'] ?? '') ?>"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kelas</label>
                    <input type="text" name="kelas" value="<?= e($student['kelas'] ?? '') ?>"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Password Baru <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" name="password"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Password</label>
                    <input type="password" name="confirm_password"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" <?= $student['is_active'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
                <label for="is_active" class="text-sm text-gray-700">Akun aktif</label>
            </div>
            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
                    <i class="fas fa-save mr-1.5"></i> Simpan Perubahan
                </button>
                <a href="<?= BASE_URL ?>/admin/students/index.php" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm transition-colors">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
