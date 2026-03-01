<?php
// ============================================
// Shared Utility Functions
// ============================================

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function sanitize(string $s): string {
    return trim(strip_tags($s));
}

function formatDate(string $datetime): string {
    if (!$datetime) return '-';
    return date('d M Y, H:i', strtotime($datetime));
}

function getGradeColor(float $pct): string {
    if ($pct >= 85) return 'green';
    if ($pct >= 70) return 'blue';
    if ($pct >= 55) return 'yellow';
    return 'red';
}

function getGradeLetter(float $pct): string {
    if ($pct >= 85) return 'A';
    if ($pct >= 70) return 'B';
    if ($pct >= 55) return 'C';
    if ($pct >= 40) return 'D';
    return 'E';
}

function questionTypeLabel(string $type): string {
    $labels = [
        'pg'              => 'Pilihan Ganda',
        'multiple_choice' => 'Multiple Choice',
        'essay'           => 'Essay',
    ];
    return $labels[$type] ?? $type;
}

function questionTypeBadge(string $type): string {
    $colors = [
        'pg'              => 'bg-blue-100 text-blue-800',
        'multiple_choice' => 'bg-purple-100 text-purple-800',
        'essay'           => 'bg-orange-100 text-orange-800',
    ];
    $color = $colors[$type] ?? 'bg-gray-100 text-gray-800';
    return '<span class="text-xs px-2 py-0.5 rounded-full font-medium ' . $color . '">' . questionTypeLabel($type) . '</span>';
}

function paginateQuery(PDO $pdo, string $sql, array $params, int $page, int $perPage = 15): array {
    // Count
    $countSql = "SELECT COUNT(*) FROM ({$sql}) AS sub";
    $stCount = $pdo->prepare($countSql);
    $stCount->execute($params);
    $total = (int)$stCount->fetchColumn();

    // Data
    $offset = ($page - 1) * $perPage;
    $stData = $pdo->prepare("{$sql} LIMIT {$perPage} OFFSET {$offset}");
    $stData->execute($params);
    $data = $stData->fetchAll();

    return [
        'data'       => $data,
        'total'      => $total,
        'page'       => $page,
        'perPage'    => $perPage,
        'totalPages' => (int)ceil($total / $perPage),
    ];
}
