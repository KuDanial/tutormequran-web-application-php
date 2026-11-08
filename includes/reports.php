<?php
declare(strict_types=1);

/** Malaysia record-date month, converted to a half-open UTC range. */
function tmq_report_month(mixed $month): array
{
    if ($month === '') { return []; }
    if (!is_string($month) || !preg_match('/\A[1-9][0-9]{3}-(0[1-9]|1[0-2])\z/', $month)) {
        throw new DomainException('Enter a valid month in YYYY-MM format.');
    }
    $start = new DateTimeImmutable($month . '-01 00:00:00', new DateTimeZone('Asia/Kuala_Lumpur'));
    $utc = new DateTimeZone('UTC');
    return [$start->setTimezone($utc)->format('Y-m-d H:i:s'),
        $start->modify('+1 month')->setTimezone($utc)->format('Y-m-d H:i:s')];
}

/** Fixed queries shared by the dashboard and export; no stored report records. */
function tmq_report_data(PDO $db, array $range): array
{
    $bookings = $db->prepare("SELECT t.tutor_id,
        COALESCE(NULLIF(TRIM(t.display_name), ''), CONCAT(u.first_name, ' ', u.last_name)) AS tutor_name,
        COUNT(*) AS total_bookings
        FROM bookings b JOIN tutors t ON t.tutor_id = b.tutor_id JOIN users u ON u.user_id = t.user_id"
        . ($range ? ' WHERE b.created_at >= ? AND b.created_at < ?' : '')
        . ' GROUP BY t.tutor_id, t.display_name, u.first_name, u.last_name ORDER BY total_bookings DESC, t.tutor_id ASC');
    $bookings->execute($range);
    $comparison = $bookings->fetchAll();
    $payments = $db->prepare('SELECT status, COUNT(*) AS payment_count FROM payments'
        . ($range ? ' WHERE created_at >= ? AND created_at < ?' : '') . ' GROUP BY status ORDER BY status ASC');
    $payments->execute($range);
    $composition = $payments->fetchAll();
    $total = array_sum(array_column($composition, 'payment_count'));
    foreach ($composition as &$row) {
        $row['percentage'] = number_format($total ? $row['payment_count'] * 100 / $total : 0, 2, '.', '');
    }
    unset($row);
    return ['comparison' => $comparison, 'composition' => $composition, 'payment_total' => $total];
}

function tmq_report_csv_cell(string $value): string
{
    // Also protect formulas hidden behind whitespace/control characters.
    return preg_match('/\A(?:[\x00-\x20]*[=+@-]|[\t\r\n])/', $value) ? "'" . $value : $value;
}

function tmq_report_csv(array $report, string $month): never
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tutormequran-reports-' . ($month ?: 'all-time') . '.csv"');
    header('X-Content-Type-Options: nosniff');
    $stream = fopen('php://output', 'wb');
    $write = static function (array $row) use ($stream): void {
        fputcsv($stream, array_map(static fn($cell) => tmq_report_csv_cell((string)$cell), $row), ',', '"', '', "\r\n");
    };
    $write(['Report', 'Period (Malaysia record month)', 'Tutor ID', 'Tutor / Payment Status', 'Count', 'Percentage']);
    foreach ($report['comparison'] as $row) {
        $write(['Total Bookings', $month ?: 'All time', $row['tutor_id'], $row['tutor_name'], $row['total_bookings'], '']);
    }
    foreach ($report['composition'] as $row) {
        $write(['Payment Status Composition', $month ?: 'All time', '', $row['status'], $row['payment_count'], $row['percentage']]);
    }
    fclose($stream);
    exit;
}
