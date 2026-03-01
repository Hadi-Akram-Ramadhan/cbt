<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Manajemen Siswa';
$pageBreadcrumb = 'Admin / Siswa';

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$search = sanitize($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$sql = "SELECT * FROM users WHERE role='siswa'" . ($search ? " AND (name LIKE :q OR username LIKE :q OR nis LIKE :q OR kelas LIKE :q)" : "") . " ORDER BY created_at DESC";
$params = $search ? [':q' => "%$search%"] : [];
$result = paginateQuery($db, $sql, $params, $page);

include __DIR__ . '/../../includes/header.php';
?>
<div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex gap-2">
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama, username, NIS..."
                class="pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">Cari</button>
        <?php if($search): ?><a href="?" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Reset</a><?php endif; ?>
    </form>
    <a href="<?= BASE_URL ?>/admin/students/create.php" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
        <i class="fas fa-plus"></i> Tambah Siswa
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Username</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">NIS</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kelas</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php if (empty($result['data'])): ?>
            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">Tidak ada data siswa.</td></tr>
            <?php else: ?>
            <?php foreach ($result['data'] as $i => $s): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 text-gray-400"><?= ($page-1)*15+$i+1 ?></td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-xs">
                            <?= strtoupper(substr($s['name'],0,1)) ?>
                        </div>
                        <span class="font-medium text-gray-800"><?= e($s['name']) ?></span>
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-600 font-mono text-xs"><?= e($s['username']) ?></td>
                <td class="px-6 py-4 text-gray-600"><?= e($s['nis'] ?? '-') ?></td>
                <td class="px-6 py-4 text-gray-600"><?= e($s['kelas'] ?? '-') ?></td>
                <td class="px-6 py-4">
                    <span class="text-xs px-2 py-1 rounded-full <?= $s['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                        <?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <a href="<?= BASE_URL ?>/admin/students/edit.php?id=<?= $s['id'] ?>" class="text-blue-500 hover:text-blue-700 p-1.5 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                            <i class="fas fa-edit text-sm"></i>
                        </a>
                        <button onclick="confirmDelete(<?= $s['id'] ?>, '<?= e($s['name']) ?>')"
                            class="text-red-400 hover:text-red-600 p-1.5 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($result['totalPages'] > 1): ?>
    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
        <p class="text-sm text-gray-500">Menampilkan <?= count($result['data']) ?> dari <?= $result['total'] ?> siswa</p>
        <div class="flex gap-1">
            <?php for ($p = 1; $p <= $result['totalPages']; $p++): ?>
            <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"
                class="px-3 py-1.5 text-sm rounded-lg <?= $p === $page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition-colors">
                <?= $p ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Confirm Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-center text-gray-800">Hapus Siswa?</h3>
        <p class="text-sm text-gray-500 text-center mt-1" id="deleteMessage"></p>
        <div class="flex gap-3 mt-6">
            <button onclick="closeModal()" class="flex-1 py-2 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 text-sm transition-colors">Batal</button>
            <a id="deleteLink" href="#" class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm text-center font-medium transition-colors">Hapus</a>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteMessage').textContent = `Yakin ingin menghapus siswa "${name}"?`;
    document.getElementById('deleteLink').href = '<?= BASE_URL ?>/admin/students/delete.php?id=' + id;
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeModal() { document.getElementById('deleteModal').classList.add('hidden'); }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
