<?php
/**
 * JSON endpoint: the live queue, for the queue page and the waiting-hall
 * display. Refreshed every few seconds by a page left open all day, so it
 * stays cheap: a handful of COUNT/MAX queries, no session, no HTML.
 *
 * Privacy: this endpoint is public and serves token NUMBERS only. Patient
 * names, phone numbers and complaints never appear here — not even for the
 * person's own booking, since a reference can be read off someone's slip.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/booking.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$today = date('Y-m-d');
$out   = [
    'date'    => $today,
    'updated' => date('g:i a'),
    'doctors' => [],
];

foreach (get_doctors() as $doctor) {
    $queue = doctor_queue_today((int) $doctor['id']);
    if ($queue === null) {
        continue;
    }
    $out['doctors'][] = [
        'id'          => (int) $doctor['id'],
        'name'        => (string) $doctor['name'],
        'speciality'  => (string) $doctor['speciality'],
        'session'     => $queue['session'],
        'label'       => $queue['label'],
        'timing'      => $queue['timing'],
        'state'       => $queue['state'],
        'now_serving' => $queue['now_serving'],
        'waiting'     => $queue['waiting'],
        'issued'      => $queue['issued'],
    ];
}

// With ?ref=, add that booking's own place in the queue — token number and
// counts only, so the payload stays name-free.
$ref = strtoupper(trim(query('ref')));
if ($ref !== '') {
    $booking = preg_match('/^SN[2-9BCDFGHJKLMNPQRSTVWXZ]{6}$/', $ref) === 1
        ? get_booking_by_reference($ref)
        : null;

    if ($booking === null) {
        $out['booking'] = null;
    } else {
        $counts = queue_counts(
            (int) $booking['doctor_id'],
            (string) $booking['booking_date'],
            (string) $booking['session']
        );
        $out['booking'] = [
            'reference'    => (string) $booking['reference'],
            'doctor_id'    => (int) $booking['doctor_id'],
            'doctor'       => (string) $booking['doctor_name'],
            'date'         => (string) $booking['booking_date'],
            'today'        => $booking['booking_date'] === $today,
            'session'      => (string) $booking['session'],
            'label'        => session_label((string) $booking['session']),
            'token_no'     => (int) $booking['token_no'],
            'status'       => (string) $booking['status'],
            'status_label' => status_label((string) $booking['status']),
            'now_serving'  => $counts['now_serving'],
            'ahead'        => queue_position($booking),
        ];
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
