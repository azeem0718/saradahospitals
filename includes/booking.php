<?php
/**
 * Token booking engine.
 *
 * A "slot" here is a (doctor, date, session) triple. Tokens are numbered
 * 1..cap within that triple; there are no clock times per patient.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const SESSIONS = ['morning', 'evening'];

/** Active doctors, ordered for display. */
function get_doctors(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM doctors' . ($activeOnly ? ' WHERE is_active = 1' : '')
         . ' ORDER BY sort_order, id';
    return db()->query($sql)->fetchAll();
}

function get_doctor(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM doctors WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_doctor_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM doctors WHERE slug = ? AND is_active = 1');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/**
 * Split a one-per-line profile field into a list, dropping blank lines.
 *
 * @return list<string>
 */
function profile_lines(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }
    $lines = preg_split('/\R/', $text) ?: [];
    return array_values(array_filter(array_map('trim', $lines), static fn ($l) => $l !== ''));
}

/**
 * The doctor's standing OP timings, read from the schedule rather than typed
 * out again, so they cannot drift from what the booking page actually offers.
 * Days sharing the same times are collapsed into one line.
 *
 * A doctor whose hours do not fit that shape can have opd_timings filled in on
 * their profile, and that wins.
 *
 * @return list<string>
 */
function doctor_opd_summary(int $doctorId): array
{
    $stmt = db()->prepare(
        'SELECT weekday, session, start_time, end_time
           FROM doctor_sessions
          WHERE doctor_id = ? AND is_active = 1
          ORDER BY session, weekday'
    );
    $stmt->execute([$doctorId]);

    // Group weekdays by session and times, so "the same every day" says so once.
    $groups = [];
    foreach ($stmt as $row) {
        $key = $row['session'] . '|' . $row['start_time'] . '|' . $row['end_time'];
        $groups[$key][] = (int) $row['weekday'];
    }

    $out = [];
    foreach ($groups as $key => $days) {
        [$session, $start, $end] = explode('|', $key);
        $out[] = session_label($session) . ' · ' . format_time($start) . ' – ' . format_time($end)
               . ' · ' . weekday_phrase($days);
    }

    return $out;
}

/**
 * Describe a set of weekday numbers: "Every day", "Mon – Sat", or a plain list.
 *
 * @param list<int> $days 0 = Sunday ... 6 = Saturday
 */
function weekday_phrase(array $days): string
{
    $days = array_values(array_unique($days));
    sort($days);

    if (count($days) === 7) {
        return 'Every day';
    }

    $short = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    // Contiguous runs read better as a range. Sunday-first ordering means a
    // Mon–Sat week is contiguous but a Sat–Sun weekend is not, which is fine:
    // the two-day case falls through to the list below.
    $contiguous = true;
    for ($i = 1, $n = count($days); $i < $n; $i++) {
        if ($days[$i] !== $days[$i - 1] + 1) {
            $contiguous = false;
            break;
        }
    }
    if ($contiguous && count($days) > 2) {
        return $short[$days[0]] . ' – ' . $short[end($days)];
    }

    return implode(', ', array_map(static fn (int $d) => $short[$d], $days));
}

/**
 * Dates a patient may book, from today to the booking window.
 * Returns Y-m-d strings.
 */
function bookable_dates(): array
{
    $days  = max(1, min(90, setting_int('booking_window_days', 7)));
    $today = new DateTimeImmutable('today');
    $out   = [];
    for ($i = 0; $i < $days; $i++) {
        $out[] = $today->modify("+{$i} days")->format('Y-m-d');
    }
    return $out;
}

function date_in_window(string $date): bool
{
    return valid_date($date) && in_array($date, bookable_dates(), true);
}

/** Blocked-day rows covering a doctor on a date (hospital-wide blocks included). */
function blocks_for(int $doctorId, string $date): array
{
    $stmt = db()->prepare(
        'SELECT session FROM blocked_days
          WHERE block_date = ? AND (doctor_id = ? OR doctor_id IS NULL)'
    );
    $stmt->execute([$date, $doctorId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function is_blocked(int $doctorId, string $date, string $session): bool
{
    foreach (blocks_for($doctorId, $date) as $blocked) {
        if ($blocked === 'both' || $blocked === $session) {
            return true;
        }
    }
    return false;
}

/** Tokens already taken in a slot. Cancelled bookings free their place. */
function tokens_used(int $doctorId, string $date, string $session): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM bookings
          WHERE doctor_id = ? AND booking_date = ? AND session = ?
            AND status <> "cancelled"'
    );
    $stmt->execute([$doctorId, $date, $session]);
    return (int) $stmt->fetchColumn();
}

/**
 * Full availability for one doctor on one date.
 *
 * Each entry: session, start_time, end_time, cap, used, remaining,
 * available (bool) and, when unavailable, a plain-English reason.
 */
function availability(int $doctorId, string $date): array
{
    if (!valid_date($date)) {
        return [];
    }

    $weekday = (int) (new DateTimeImmutable($date))->format('w');

    $stmt = db()->prepare(
        'SELECT session, start_time, end_time, token_cap, is_active
           FROM doctor_sessions
          WHERE doctor_id = ? AND weekday = ?
          ORDER BY FIELD(session, "morning", "evening")'
    );
    $stmt->execute([$doctorId, $weekday]);
    $rows = $stmt->fetchAll();

    $bookingsOpen = setting('bookings_enabled', '1') === '1';
    $cutoffMins   = max(0, setting_int('booking_cutoff_minutes', 60));
    $now          = new DateTimeImmutable('now');

    $out = [];
    foreach ($rows as $row) {
        $session   = (string) $row['session'];
        $cap       = (int) $row['token_cap'];
        $used      = tokens_used($doctorId, $date, $session);
        $remaining = max(0, $cap - $used);

        $reason = null;
        if (!$bookingsOpen) {
            $reason = 'Online booking is temporarily closed';
        } elseif (!$row['is_active']) {
            $reason = 'No consultation this session';
        } elseif (is_blocked($doctorId, $date, $session)) {
            $reason = 'Doctor unavailable';
        } elseif ($remaining === 0) {
            $reason = 'All tokens taken';
        } else {
            // Booking closes a set time before the session ends.
            $closesAt = new DateTimeImmutable($date . ' ' . $row['end_time']);
            $closesAt = $closesAt->modify("-{$cutoffMins} minutes");
            if ($now >= $closesAt) {
                $reason = 'Booking closed for this session';
            }
        }

        $out[] = [
            'session'    => $session,
            'label'      => session_label($session),
            'start_time' => (string) $row['start_time'],
            'end_time'   => (string) $row['end_time'],
            'timing'     => format_time((string) $row['start_time']) . ' – ' . format_time((string) $row['end_time']),
            'cap'        => $cap,
            'used'       => $used,
            'remaining'  => $remaining,
            'available'  => $reason === null,
            'reason'     => $reason,
        ];
    }

    return $out;
}

/** One availability entry, or null when the session does not exist. */
function session_availability(int $doctorId, string $date, string $session): ?array
{
    foreach (availability($doctorId, $date) as $entry) {
        if ($entry['session'] === $session) {
            return $entry;
        }
    }
    return null;
}

/**
 * "Dr. Gundavarapu Venkatesh" becomes "Dr. Venkatesh" — the form patients use
 * anyway, and short enough for a compact control like the hero's find bar.
 */
function doctor_short_name(string $full): string
{
    $parts = preg_split('/\s+/', trim($full)) ?: [];
    if (count($parts) < 2) {
        return $full;
    }
    $title = str_ends_with($parts[0], '.') ? $parts[0] : '';
    $last  = end($parts);
    return $title !== '' ? $title . ' ' . $last : $last;
}

/**
 * The soonest session a patient could actually book, across all doctors.
 *
 * Drives the live status line in the hero: a card that says "Morning session,
 * 27 tokens left" is doing real work, where a static list of doctors is only
 * decoration. Returns null when nothing in the booking window is open.
 *
 * @return array{date:string, session:string, label:string, timing:string,
 *                remaining:int, doctor:string, when:string, today:bool}|null
 */
function next_available(): ?array
{
    $doctors = get_doctors();
    if (!$doctors) {
        return null;
    }

    $today    = date('Y-m-d');
    $tomorrow = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');

    foreach (bookable_dates() as $date) {
        $best = null;

        // Within one day, prefer the session that starts soonest, and among
        // equal sessions the doctor with the most tokens left.
        foreach ($doctors as $doctor) {
            foreach (availability((int) $doctor['id'], $date) as $slot) {
                if (!$slot['available']) {
                    continue;
                }
                $rank = $slot['session'] === 'morning' ? 0 : 1;
                if ($best === null
                    || $rank < $best['rank']
                    || ($rank === $best['rank'] && $slot['remaining'] > $best['slot']['remaining'])) {
                    $best = ['rank' => $rank, 'slot' => $slot, 'doctor' => $doctor];
                }
            }
        }

        if ($best !== null) {
            $slot = $best['slot'];
            return [
                'date'      => $date,
                'session'   => $slot['session'],
                'label'     => $slot['label'],
                'timing'    => $slot['timing'],
                'remaining' => $slot['remaining'],
                'doctor'    => (string) $best['doctor']['name'],
                'when'      => match ($date) {
                    $today    => 'Today',
                    $tomorrow => 'Tomorrow',
                    default   => (new DateTimeImmutable($date))->format('D j M'),
                },
                'today'     => $date === $today,
            ];
        }
    }

    return null;
}

/** Short, unambiguous booking reference. No vowels, so no accidental words. */
function generate_reference(): string
{
    $alphabet = '23456789BCDFGHJKLMNPQRSTVWXZ';
    $out = 'SN';
    for ($i = 0; $i < 6; $i++) {
        $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $out;
}

/**
 * Rate limit: at most $limit bookings per IP per $windowMinutes.
 *
 * The limit is deliberately generous. Many patients here reach the internet
 * through mobile carrier NAT, so a whole town can share one public address —
 * a tight limit would lock out real people, not just bots. CSRF and the
 * honeypot do the real work of stopping crude spam; this only catches a
 * runaway script.
 *
 * Returns true when the request is within the limit.
 */
function rate_limit_ok(int $limit = 15, int $windowMinutes = 60): bool
{
    $ip = client_ip_binary();
    if ($ip === null) {
        return true;
    }

    $pdo = db();
    $pdo->prepare('DELETE FROM booking_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)')
        ->execute();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM booking_attempts
          WHERE ip_address = ? AND created_at > (NOW() - INTERVAL ? MINUTE)'
    );
    $stmt->execute([$ip, $windowMinutes]);

    if ((int) $stmt->fetchColumn() >= $limit) {
        return false;
    }

    $pdo->prepare('INSERT INTO booking_attempts (ip_address) VALUES (?)')->execute([$ip]);
    return true;
}

/**
 * Allocate the next token and store the booking.
 *
 * Runs in a transaction and locks the slot's existing rows, so two people
 * booking the same instant cannot receive the same token. The unique key on
 * (doctor, date, session, token_no) is the final backstop: on a collision we
 * retry rather than hand out a duplicate.
 *
 * @return array{ok:bool, booking?:array, error?:string}
 */
function create_booking(array $data, string $bookedVia = 'online'): array
{
    $pdo = db();

    for ($attempt = 0; $attempt < 3; $attempt++) {
        try {
            $pdo->beginTransaction();

            // Lock this slot's rows so the count below cannot shift underneath us.
            $lock = $pdo->prepare(
                'SELECT COUNT(*) FROM bookings
                  WHERE doctor_id = ? AND booking_date = ? AND session = ?
                    AND status <> "cancelled"
                  FOR UPDATE'
            );
            $lock->execute([$data['doctor_id'], $data['booking_date'], $data['session']]);
            $used = (int) $lock->fetchColumn();

            $capStmt = $pdo->prepare(
                'SELECT token_cap, is_active FROM doctor_sessions
                  WHERE doctor_id = ? AND weekday = ? AND session = ?'
            );
            $weekday = (int) (new DateTimeImmutable($data['booking_date']))->format('w');
            $capStmt->execute([$data['doctor_id'], $weekday, $data['session']]);
            $sessionRow = $capStmt->fetch();

            if (!$sessionRow || !$sessionRow['is_active']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'That session is not available. Please choose another.'];
            }

            if ($used >= (int) $sessionRow['token_cap']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'All tokens for that session have just been taken. Please choose another session.'];
            }

            // Next token is one past the highest issued, so cancelling does not
            // renumber anyone who already holds a slip.
            $maxStmt = $pdo->prepare(
                'SELECT COALESCE(MAX(token_no), 0) FROM bookings
                  WHERE doctor_id = ? AND booking_date = ? AND session = ?'
            );
            $maxStmt->execute([$data['doctor_id'], $data['booking_date'], $data['session']]);
            $tokenNo = (int) $maxStmt->fetchColumn() + 1;

            $reference = generate_reference();

            $insert = $pdo->prepare(
                'INSERT INTO bookings
                   (reference, doctor_id, booking_date, session, token_no,
                    patient_name, patient_age, patient_sex, phone, town, reason,
                    booked_via, ip_address)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $insert->execute([
                $reference,
                $data['doctor_id'],
                $data['booking_date'],
                $data['session'],
                $tokenNo,
                $data['patient_name'],
                $data['patient_age'],
                $data['patient_sex'],
                $data['phone'],
                $data['town'] ?: null,
                $data['reason'] ?: null,
                $bookedVia,
                client_ip_binary(),
            ]);

            $id = (int) $pdo->lastInsertId();
            $pdo->commit();

            return ['ok' => true, 'booking' => get_booking_by_id($id)];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // 23000 = integrity constraint: a duplicate token or reference.
            // Both are safe to retry with a freshly computed value.
            if ($e->getCode() === '23000' && $attempt < 2) {
                continue;
            }
            error_log('Booking failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'We could not save your booking. Please try again, or call ' . HOSPITAL['mobile_display'] . '.'];
        }
    }

    return ['ok' => false, 'error' => 'We could not save your booking. Please call ' . HOSPITAL['mobile_display'] . '.'];
}

/**
 * May the patient still cancel this booking themselves?
 *
 * Only a plain "booked" token, and only while showing up is still ahead of
 * them: any future date, or today before the session's end. Once reception
 * has marked them arrived the desk owns the booking, and past sessions are
 * history, not something to edit.
 */
function booking_cancellable(array $booking): bool
{
    if ($booking['status'] !== 'booked') {
        return false;
    }

    $today = date('Y-m-d');
    if ($booking['booking_date'] < $today) {
        return false;
    }
    if ($booking['booking_date'] > $today) {
        return true;
    }

    $stmt = db()->prepare(
        'SELECT end_time FROM doctor_sessions
          WHERE doctor_id = ? AND weekday = ? AND session = ?'
    );
    $stmt->execute([
        $booking['doctor_id'],
        (int) (new DateTimeImmutable($booking['booking_date']))->format('w'),
        $booking['session'],
    ]);
    $end = $stmt->fetchColumn();

    return $end === false
        || new DateTimeImmutable('now') < new DateTimeImmutable($today . ' ' . $end);
}

/**
 * Live counts for one slot (doctor, date, session).
 *
 * "Now serving" is the highest token reception has marked completed — the
 * number on the door has just changed to the one after it. The waiting count
 * is everyone still holding a live token (booked or arrived); cancellations
 * and no-shows are out of the queue.
 *
 * @return array{now_serving:int, waiting:int, issued:int}
 */
function queue_counts(int $doctorId, string $date, string $session): array
{
    $stmt = db()->prepare(
        'SELECT
            COALESCE(MAX(CASE WHEN status = "completed" THEN token_no END), 0) AS now_serving,
            COALESCE(SUM(status IN ("booked", "arrived")), 0)                  AS waiting,
            COALESCE(SUM(status <> "cancelled"), 0)                            AS issued
           FROM bookings
          WHERE doctor_id = ? AND booking_date = ? AND session = ?'
    );
    $stmt->execute([$doctorId, $date, $session]);
    $row = $stmt->fetch();

    return [
        'now_serving' => (int) $row['now_serving'],
        'waiting'     => (int) $row['waiting'],
        'issued'      => (int) $row['issued'],
    ];
}

/**
 * How many patients are still ahead of this booking: live tokens with a lower
 * number. A cancelled or no-show token ahead of you does not count — you will
 * not wait for them.
 */
function queue_position(array $booking): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM bookings
          WHERE doctor_id = ? AND booking_date = ? AND session = ?
            AND token_no < ? AND status IN ("booked", "arrived")'
    );
    $stmt->execute([
        $booking['doctor_id'],
        $booking['booking_date'],
        $booking['session'],
        $booking['token_no'],
    ]);
    return (int) $stmt->fetchColumn();
}

/**
 * The queue that matters for one doctor right now, today.
 *
 * Of the doctor's sessions today, pick the one in progress; failing that, one
 * past its end time with patients still waiting, since clinics run over
 * ("overrun" — treated as live); then the next one yet to start; then the last
 * one that ran. Null when the doctor has no session today or all are blocked.
 *
 * @return array{session:string, label:string, timing:string, state:string,
 *               now_serving:int, waiting:int, issued:int, cap:int}|null
 *         state is one of upcoming | running | overrun | ended.
 */
function doctor_queue_today(int $doctorId): ?array
{
    $date    = date('Y-m-d');
    $weekday = (int) date('w');

    $stmt = db()->prepare(
        'SELECT session, start_time, end_time, token_cap
           FROM doctor_sessions
          WHERE doctor_id = ? AND weekday = ? AND is_active = 1
          ORDER BY FIELD(session, "morning", "evening")'
    );
    $stmt->execute([$doctorId, $weekday]);

    $now        = new DateTimeImmutable('now');
    $candidates = [];

    foreach ($stmt as $row) {
        $session = (string) $row['session'];
        if (is_blocked($doctorId, $date, $session)) {
            continue;
        }

        $counts = queue_counts($doctorId, $date, $session);
        $start  = new DateTimeImmutable($date . ' ' . $row['start_time']);
        $end    = new DateTimeImmutable($date . ' ' . $row['end_time']);

        if ($now < $start) {
            $state = 'upcoming';
        } elseif ($now <= $end) {
            $state = 'running';
        } else {
            $state = $counts['waiting'] > 0 ? 'overrun' : 'ended';
        }

        $candidates[] = $counts + [
            'session' => $session,
            'label'   => session_label($session),
            'timing'  => format_time((string) $row['start_time']) . ' – '
                       . format_time((string) $row['end_time']),
            'state'   => $state,
            'cap'     => (int) $row['token_cap'],
        ];
    }

    foreach (['running', 'overrun', 'upcoming'] as $wanted) {
        foreach ($candidates as $candidate) {
            if ($candidate['state'] === $wanted) {
                return $candidate;
            }
        }
    }

    return $candidates ? end($candidates) : null;
}

function get_booking_by_id(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT b.*, d.name AS doctor_name, d.speciality AS doctor_speciality
           FROM bookings b JOIN doctors d ON d.id = b.doctor_id
          WHERE b.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_booking_by_reference(string $reference): ?array
{
    $stmt = db()->prepare(
        'SELECT b.*, d.name AS doctor_name, d.speciality AS doctor_speciality
           FROM bookings b JOIN doctors d ON d.id = b.doctor_id
          WHERE b.reference = ?'
    );
    $stmt->execute([strtoupper($reference)]);
    return $stmt->fetch() ?: null;
}
