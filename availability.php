<?php
/**
 * JSON endpoint: live session availability for one doctor on one date.
 * Consumed by assets/js/booking.js.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/booking.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$doctorId = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT) ?: 0;
$date     = query('date');

if ($doctorId < 1 || !date_in_window($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid doctor or date.']);
    exit;
}

if (!get_doctor($doctorId)) {
    http_response_code(404);
    echo json_encode(['error' => 'Doctor not found.']);
    exit;
}

echo json_encode([
    'date'      => $date,
    'doctor_id' => $doctorId,
    'free_op'   => is_free_op_day($date),
    'sessions'  => availability($doctorId, $date),
], JSON_UNESCAPED_UNICODE);
