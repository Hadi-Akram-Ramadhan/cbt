<?php
// ============================================
// Excel Template Downloader using PhpSpreadsheet
// ============================================

define('BASE_URL', '/brainstorm/cbt');
define('APP_NAME', 'CBT Online');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    // Fallback: deliver a CSV template
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="template_soal_cbt.csv"');
    $fp = fopen('php://output', 'w');
    fputcsv($fp, ['no','tipe_soal','pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e','jawaban_benar','poin']);
    fputcsv($fp, [1,'pg','Ibukota Indonesia adalah ...','Jakarta','Surabaya','Bandung','Medan','Makassar','A',1]);
    fputcsv($fp, [2,'multiple_choice','Pilih bilangan prima di bawah ini','2','4','5','6','7','A,C,E',2]);
    fputcsv($fp, [3,'essay','Jelaskan proses fotosintesis!','','','','','','',5]);
    fclose($fp);
    exit;
}

require_once $vendorPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template Soal CBT');

// Headers
$headers = ['No','Tipe Soal','Pertanyaan','Opsi A','Opsi B','Opsi C','Opsi D','Opsi E','Jawaban Benar','Poin'];
foreach ($headers as $i => $h) {
    $col = chr(65 + $i);
    $sheet->setCellValue($col.'1', $h);
}

// Header style
$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '93C5FD']]],
];
$sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

// Sample data
$samples = [
    [1, 'pg', 'Ibukota Indonesia adalah ...', 'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Makassar', 'A', 1],
    [2, 'multiple_choice', 'Pilih bilangan prima di bawah ini', '2', '4', '5', '6', '7', 'A,C,E', 2],
    [3, 'essay', 'Jelaskan proses fotosintesis secara singkat!', '', '', '', '', '', '', 5],
];
foreach ($samples as $row => $data) {
    foreach ($data as $col => $val) {
        $sheet->setCellValue(chr(65+$col).($row+2), $val);
    }
}

// Instructions sheet
$infoSheet = $spreadsheet->createSheet();
$infoSheet->setTitle('Panduan');
$infoSheet->setCellValue('A1', 'PANDUAN PENGISIAN TEMPLATE SOAL CBT');
$infoSheet->getStyle('A1')->applyFromArray(['font'=>['bold'=>true,'size'=>14],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'DBEAFE']]]);
$info = [
    ['Kolom', 'Keterangan', 'Contoh'],
    ['no', 'Nomor urut soal', '1'],
    ['tipe_soal', 'Jenis soal: pg / multiple_choice / essay', 'pg'],
    ['pertanyaan', 'Teks pertanyaan', 'Ibukota Indonesia adalah ...'],
    ['opsi_a s/d opsi_e', 'Pilihan jawaban (kosongkan jika essay)', 'Jakarta'],
    ['jawaban_benar', 'Label jawaban benar. PG: satu huruf (A). MC: huruf dipisah koma (A,C). Essay: kosong', 'A atau A,C'],
    ['poin', 'Bobot nilai untuk soal ini', '1'],
];
foreach ($info as $r => $row) {
    foreach ($row as $c => $val) {
        $infoSheet->setCellValue(chr(65+$c).($r+2), $val);
    }
}
$infoSheet->getColumnDimension('A')->setWidth(20);
$infoSheet->getColumnDimension('B')->setWidth(60);
$infoSheet->getColumnDimension('C')->setWidth(30);

// Column widths on main sheet
$widths = [5, 18, 50, 20, 20, 20, 20, 20, 18, 8];
foreach ($widths as $i => $w) { $sheet->getColumnDimension(chr(65+$i))->setWidth($w); }

// Output
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="template_soal_cbt.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
