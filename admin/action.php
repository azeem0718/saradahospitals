<?php
/**
 * Single POST handler for booking status changes.
 * Always redirects back, so a refresh cannot repeat an action.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';

$user = require_login();

if (!is_post()) {
    redirect('index.php');
}
require_csrf();

// Only relative paths, so the redirect cannot be pointed off-site.
$back = post('back', 'index.php');
if ($back === '' || !preg_match('#^[A-Za-z0-9_.?=&%/-]+$#', $back) || str_contains($back, '..')) {
    $back = 'index.php';
}

$id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$status = post('status');

$allowed = ['booked', 'arrived', 'completed', 'no_show', 'cancelled'];

if ($id < 1 || !in_array($status, $allowed, true)) {
    flash('That action was not understood.', 'error');
    redirect($back);
}

$booking = get_booking_by_id($id);
if (!$booking) {
    flash('That booking no longer exists.', 'error');
    redirect($back);
}

try {
    $stmt = db()->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);

    flash(sprintf(
        'Token %d (%s) marked as %s.',
        (int) $booking['token_no'],
        $booking['patient_name'],
        strtolower(status_label($status))
    ));
} catch (PDOException $e) {
    error_log('Status update failed: ' . $e->getMessage());
    flash('Could not update that booking. Please try again.', 'error');
}

redirect($back);
