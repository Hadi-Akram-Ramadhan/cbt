<?php
define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
$pageTitle = 'Import Soal dari Excel';
$pageBreadcrumb = 'Admin / Import Soal';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db     = getDB();
$examId = (int)($_GET['exam_id'] ?? 0);
$exams  = $db->query("SELECT id, title FROM exams ORDER BY title")->fetchAll();
$errors = [];
$imported = 0;

// Check if PhpSpreadsheet is installed
$spreadsheetAvailable = file_exists(__DIR__ . '/../vendor/autoload.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = (int)($_POST['exam_id'] ?? 0);
    if (!$examId) { $errors[] = 'Pilih ujian terlebih dahulu.'; }
    elseif (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File Excel gagal diupload.';
    } else {
        $file = $_FILES['excel_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx','xls'])) {
            $errors[] = 'Format file harus .xlsx atau .xls';
        }
    }

    if (empty($errors)) {
        if (!$spreadsheetAvailable) {
            $errors[] = 'Library PhpSpreadsheet belum terinstall. Jalankan: composer require phpoffice/phpspreadsheet di folder cbt/';
        } else {
            require_once __DIR__ . '/../vendor/autoload.php';
            $uploadPath = __DIR__ . '/../uploads/' . uniqid('import_') . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $uploadPath);

            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($uploadPath);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($uploadPath);
                $sheet = $spreadsheet->getActiveSheet();
                $rows  = $sheet->toArray(null, true, true, false);

                $maxOrd = (int)$db->prepare("SELECT COALESCE(MAX(order_num),0) FROM questions WHERE exam_id=?")->execute([$examId]);
                $maxOrd = $db->prepare("SELECT COALESCE(MAX(order_num),0) FROM questions WHERE exam_id=?");
                $maxOrd->execute([$examId]);
                $orderStart = (int)$maxOrd->fetchColumn() + 1;

                $stQ   = $db->prepare("INSERT INTO questions (exam_id,question_text,question_type,order_num,points) VALUES (?,?,?,?,?)");
                $stOpt = $db->prepare("INSERT INTO options (question_id,option_label,option_text,is_correct) VALUES (?,?,?,?)");

                // Skip header row (row 0)
                foreach (array_slice($rows, 1) as $row) {
                    // Columns: 0=no, 1=tipe_soal, 2=pertanyaan, 3=A, 4=B, 5=C, 6=D, 7=E, 8=jawaban_benar, 9=poin
                    $type  = strtolower(trim($row[1] ?? 'pg'));
                    $qtext = trim($row[2] ?? '');
                    if (!$qtext) continue;
                    $type  = in_array($type,['pg','multiple_choice','essay']) ? $type : 'pg';
                    $poin  = max(0, (float)($row[9] ?? 1));

                    $stQ->execute([$examId, $qtext, $type, $orderStart, $poin]);
                    $qid = $db->lastInsertId();
                    $orderStart++;

                    if ($type !== 'essay') {
                        $correctRaw = strtoupper(trim($row[8] ?? ''));
                        $corrects   = array_map('trim', explode(',', $correctRaw));
                        foreach(['A','B','C','D','E'] as $i => $lbl) {
                            $optText = trim($row[$i+3] ?? '');
                            if ($optText === '') continue;
                            $stOpt->execute([$qid, $lbl, $optText, in_array($lbl,$corrects)?1:0]);
                        }
                    }
                    $imported++;
                }
                @unlink($uploadPath);
                flash('success', "$imported soal berhasil diimport!");
                redirect(BASE_URL."/admin/questions/index.php?exam_id=$examId");
            } catch (\Exception $e) {
                $errors[] = 'Gagal membaca file: ' . $e->getMessage();
                @unlink($uploadPath);
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-2xl space-y-6">

    <!-- Notice if spreadsheet not installed -->
    <?php if (!$spreadsheetAvailable): ?>
    <div class="flex gap-3 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm">
        <i class="fas fa-exclamation-triangle mt-0.5 text-amber-500 flex-shrink-0"></i>
        <div>
            <p class="font-medium">PhpSpreadsheet belum terinstall</p>
            <p class="mt-1">Jalankan perintah berikut di folder <code class="bg-amber-100 px-1 rounded">cbt/</code>:</p>
            <code class="block mt-2 bg-amber-100 p-2 rounded font-mono text-xs">composer require phpoffice/phpspreadsheet</code>
        </div>
    </div>
    <?php endif; ?>

    <!-- Download Template -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-1">Download Template Excel</h2>
        <p class="text-sm text-gray-500 mb-4">Download template Excel, isi dengan soal-soal, lalu upload kembali.</p>
        <a href="<?= BASE_URL ?>/assets/download_template.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium shadow-sm transition-colors">
            <i class="fas fa-download"></i> Download Template Excel
        </a>
        <div class="mt-4 text-xs text-gray-500">
            <p class="font-medium mb-1">Kolom template:</p>
            <div class="overflow-x-auto">
                <table class="text-xs border-collapse border border-gray-200 rounded">
                    <thead class="bg-gray-50"><tr>
                        <?php foreach(['no','tipe_soal','pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e','jawaban_benar','poin'] as $h): ?>
                        <th class="border border-gray-200 px-2 py-1 text-gray-600"><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr></thead>
                    <tbody><tr>
                        <td class="border border-gray-200 px-2 py-1">1</td>
                        <td class="border border-gray-200 px-2 py-1">pg</td>
                        <td class="border border-gray-200 px-2 py-1">Ibukota Indonesia?</td>
                        <td class="border border-gray-200 px-2 py-1">Jakarta</td>
                        <td class="border border-gray-200 px-2 py-1">Surabaya</td>
                        <td class="border border-gray-200 px-2 py-1">Bandung</td>
                        <td class="border border-gray-200 px-2 py-1">Medan</td>
                        <td class="border border-gray-200 px-2 py-1"></td>
                        <td class="border border-gray-200 px-2 py-1">A</td>
                        <td class="border border-gray-200 px-2 py-1">1</td>
                    </tr></tbody>
                </table>
            </div>
            <p class="mt-2"><strong>tipe_soal:</strong> pg / multiple_choice / essay &nbsp;|&nbsp; <strong>jawaban_benar:</strong> A atau A,C (multiple)</p>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Upload File Excel</h2>

        <?php if ($errors): ?>
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            <ul class="list-disc list-inside"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Pilih Ujian *</label>
                <select name="exam_id" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Ujian --</option>
                    <?php foreach ($exams as $ex): ?>
                    <option value="<?= $ex['id'] ?>" <?= $examId==$ex['id']?'selected':'' ?>><?= e($ex['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">File Excel (.xlsx / .xls) *</label>
                <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-blue-300 transition-colors cursor-pointer" onclick="document.getElementById('excelFile').click()">
                    <i class="fas fa-file-excel text-3xl text-green-500 mb-2"></i>
                    <p class="text-sm text-gray-500">Klik atau drag & drop file Excel</p>
                    <p class="text-xs text-gray-400 mt-1" id="fileLabel">Format: .xlsx, .xls</p>
                </div>
                <input type="file" name="excel_file" id="excelFile" accept=".xlsx,.xls" class="hidden" onchange="updateFileLabel(this)">
            </div>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-sm transition-colors">
                <i class="fas fa-upload mr-1.5"></i> Import Soal
            </button>
        </form>
    </div>
</div>
<script>
function updateFileLabel(input) {
    document.getElementById('fileLabel').textContent = input.files[0]?.name || 'Format: .xlsx, .xls';
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
