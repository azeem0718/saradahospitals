<?php
/**
 * Handles a submitted list from _list-editor.php.
 *
 * Included by the screens that edit lists, before any output. The caller sets
 * $allowed to the list keys that screen is responsible for, so a form cannot
 * be pointed at a list belonging to another page. Leaves $errors for the
 * screen to render; on success it redirects and does not return.
 */

declare(strict_types=1);

/** @var list<string> $allowed */
/** @var string $redirectTo */
$errors = [];

// A screen may carry both text blocks and lists; only a list form sends a
// list_key, so anything else is somebody else's submission.
if (is_post() && post('list_key') !== '') {
    require_csrf();

    $key   = post('list_key');
    $lists = content_lists();

    if (!in_array($key, $allowed, true) || !isset($lists[$key])) {
        flash('That list does not exist.', 'error');
        redirect($redirectTo);
    }

    if (post('action') === 'reset') {
        list_reset($key);
        flash($lists[$key]['label'] . ' restored to the original.');
        redirect($redirectTo);
    }

    $uses    = $lists[$key]['uses'];
    $posted  = $_POST['rows'] ?? [];
    $cleaned = [];

    foreach (is_array($posted) ? $posted : [] as $row) {
        if (!is_array($row) || !empty($row['remove'])) {
            continue;
        }
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            continue;   // a blank line is how a spare row is left unused
        }

        if (in_array('amount', $uses, true)) {
            $amount = trim((string) ($row['amount'] ?? ''));
            if ($amount === '' || !ctype_digit($amount) || (int) $amount > 9999999) {
                $errors[] = 'Give "' . $title . '" a whole-rupee amount, digits only.';
                continue;
            }
            $row['amount'] = (int) $amount;
        }
        if (in_array('icon', $uses, true)
            && ($row['icon'] ?? '') !== ''
            && !isset(icon_feature_set()[$row['icon']])) {
            $errors[] = 'Choose a listed icon for "' . $title . '".';
            continue;
        }

        if (!empty($lists[$key]['uses_tone'])
            && !array_key_exists((string) ($row['tone'] ?? ''), content_tones())) {
            $errors[] = 'Choose a listed colour for "' . $title . '".';
            continue;
        }

        $cleaned[] = [
            'order'  => (int) ($row['order'] ?? 0),
            'tone'   => (string) ($row['tone'] ?? ''),
            'title'  => $title,
            'body'   => (string) ($row['body'] ?? ''),
            'icon'   => (string) ($row['icon'] ?? ''),
            'amount' => $row['amount'] ?? null,
            'unit'   => (string) ($row['unit'] ?? ''),
        ];
    }

    // A list emptied entirely would silently blank a public section, and is far
    // more likely to be a mistake than an intention.
    if (!$errors && !$cleaned) {
        $errors[] = 'A list cannot be left empty. Use "Restore original" instead.';
    }

    if (!$errors) {
        usort($cleaned, static fn ($a, $b) => $a['order'] <=> $b['order']);
        list_save($key, $cleaned);
        flash($lists[$key]['label'] . ' saved.');
        redirect($redirectTo);
    }
}
