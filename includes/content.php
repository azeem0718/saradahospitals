<?php
/**
 * Editable text.
 *
 * Every wording on the public site that reception might reasonably want to
 * change lives here as a *key* with a default. The database stores only the
 * keys someone has actually edited, which has three consequences worth having:
 * a fresh install renders exactly what shipped, an un-edited field keeps
 * improving when the code does, and "reset to default" is simply deleting a
 * row.
 *
 * The defaults are not repeated here — they are read from the constants in
 * site.php, which remain the single source of truth for what the hospital's
 * own signage and brochure say.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Every editable field, grouped for the screen that edits it.
 *
 * type: 'text' single line · 'area' multi-line · 'tel' a dialable number.
 *
 * @return array<string, array{label: string, hint: string, fields: array}>
 */
function content_groups(): array
{
    $h = HOSPITAL_DEFAULTS;

    return [
        'identity' => [
            'label' => 'Name and tagline',
            'hint'  => 'How the hospital is named across the site — the masthead, '
                     . 'the footer, printed token slips and the page titles search '
                     . 'engines show.',
            'fields' => [
                'hospital.name' => [
                    'label' => 'Hospital name', 'type' => 'text', 'default' => $h['name'],
                    'hint'  => 'The first word is highlighted in red wherever the name appears.',
                ],
                'hospital.tagline' => [
                    'label' => 'Tagline', 'type' => 'text', 'default' => $h['tagline'],
                ],
                'hospital.sub_brand' => [
                    'label' => 'Diabetic centre name', 'type' => 'text', 'default' => $h['sub_brand'],
                ],
                'hospital.sub_tagline' => [
                    'label' => 'Diabetic centre tagline', 'type' => 'text', 'default' => $h['sub_tagline'],
                ],
            ],
        ],

        'address' => [
            'label' => 'Address',
            'hint'  => 'Shown in the footer, on the contact page and on every printed '
                     . 'token slip, so it is worth getting exactly right.',
            'fields' => [
                'hospital.address.line1' => [
                    'label' => 'Address line 1', 'type' => 'text', 'default' => $h['address']['line1'],
                    'hint'  => 'Landmarks help — this is what a driver is told.',
                ],
                'hospital.address.line2' => [
                    'label' => 'Address line 2', 'type' => 'text', 'default' => $h['address']['line2'],
                ],
                'hospital.address.district' => [
                    'label' => 'District and state', 'type' => 'text', 'default' => $h['address']['district'],
                ],
            ],
        ],

        'phones' => [
            'label' => 'Phone numbers',
            'hint'  => 'Each number is stored twice: the dialable form behind the '
                     . 'button, and the readable form printed on the page. Change '
                     . 'both together or the button will call the wrong number.',
            'fields' => [
                'hospital.mobile' => [
                    'label' => 'Emergency mobile — dialable', 'type' => 'tel', 'default' => $h['mobile'],
                    'hint'  => 'Country code and no spaces, like +918341254590.',
                ],
                'hospital.mobile_display' => [
                    'label' => 'Emergency mobile — as printed', 'type' => 'text', 'default' => $h['mobile_display'],
                ],
                'hospital.landline' => [
                    'label' => 'Reception landline — dialable', 'type' => 'tel', 'default' => $h['landline'],
                ],
                'hospital.landline_display' => [
                    'label' => 'Reception landline — as printed', 'type' => 'text', 'default' => $h['landline_display'],
                ],
                'hospital.whatsapp' => [
                    'label' => 'WhatsApp number', 'type' => 'tel', 'default' => $h['whatsapp'],
                    'hint'  => 'Digits only, with country code and no plus, like 918341254590.',
                ],
            ],
        ],

        'map' => [
            'label' => 'Map location',
            'hint'  => 'Drives the "Get directions" links and the map panel.',
            'fields' => [
                'hospital.map.link' => [
                    'label' => 'Google Maps link', 'type' => 'text', 'default' => $h['map']['link'],
                ],
                'hospital.map.lat' => [
                    'label' => 'Latitude', 'type' => 'text', 'default' => (string) $h['map']['lat'],
                ],
                'hospital.map.lng' => [
                    'label' => 'Longitude', 'type' => 'text', 'default' => (string) $h['map']['lng'],
                ],
            ],
        ],
    ];
}

/** Flat key => spec map for validating and looking up a single field. */
function content_specs(): array
{
    static $flat = null;
    if ($flat === null) {
        $flat = [];
        foreach (content_groups() as $group) {
            $flat += $group['fields'];
        }
    }
    return $flat;
}

/**
 * Every stored override, keyed by content key. Read once per request.
 *
 * A site whose migration has not run yet — or whose database is briefly
 * unreachable — falls back to the shipped defaults rather than serving a page
 * full of blanks.
 */
function content_overrides(bool $reload = false): array
{
    static $cache = null;

    if ($cache === null || $reload) {
        $cache = [];
        try {
            foreach (db()->query('SELECT content_key, content_value FROM content') as $row) {
                $cache[$row['content_key']] = $row['content_value'];
            }
        } catch (PDOException $e) {
            error_log('Content unavailable: ' . $e->getMessage());
        }
    }

    return $cache;
}

/** Drop the cache so the next read sees what was just written. */
function content_forget(): void
{
    content_overrides(true);
}

/** The shipped default for a key, or '' if the key is not registered. */
function content_default(string $key): string
{
    return (string) (content_specs()[$key]['default'] ?? '');
}

/**
 * The live value for a key: reception's wording if they set one, otherwise
 * the default the site shipped with.
 */
function text(string $key): string
{
    $stored = content_overrides()[$key] ?? null;
    return $stored !== null && trim($stored) !== '' ? $stored : content_default($key);
}

/** True when reception has overridden this key. Drives the admin's "edited" mark. */
function content_is_edited(string $key): bool
{
    $stored = content_overrides()[$key] ?? null;
    return $stored !== null && trim($stored) !== '' && $stored !== content_default($key);
}

/**
 * Save a batch of keys. A value equal to the default, or blank, deletes the
 * row instead of storing it — so a field that was reset genuinely goes back to
 * tracking the shipped wording rather than freezing today's copy of it.
 *
 * Unregistered keys are ignored rather than trusted.
 */
function content_save(array $values): void
{
    $specs  = content_specs();
    $pdo    = db();
    $set    = $pdo->prepare(
        'INSERT INTO content (content_key, content_value) VALUES (?,?)
         ON DUPLICATE KEY UPDATE content_value = VALUES(content_value)'
    );
    $unset  = $pdo->prepare('DELETE FROM content WHERE content_key = ?');

    foreach ($values as $key => $value) {
        if (!isset($specs[$key])) {
            continue;
        }
        $value = trim((string) $value);
        if ($value === '' || $value === trim(content_default($key))) {
            $unset->execute([$key]);
        } else {
            $set->execute([$key, $value]);
        }
    }

    content_forget();
}

/**
 * Define HOSPITAL from the defaults with reception's edits laid over the top.
 *
 * Doing it here rather than in site.php means the ninety-odd places that read
 * HOSPITAL['mobile'] and friends carry on working untouched, and the constant
 * stays a constant — resolved once per request, not re-queried per lookup.
 */
function hospital_boot(): void
{
    if (defined('HOSPITAL')) {
        return;
    }

    $h = HOSPITAL_DEFAULTS;

    if (SNH_CONFIGURED) {
        foreach (['name', 'tagline', 'sub_brand', 'sub_tagline',
                  'mobile', 'mobile_display', 'landline', 'landline_display',
                  'whatsapp'] as $field) {
            $h[$field] = text('hospital.' . $field);
        }
        foreach (['line1', 'line2', 'district'] as $field) {
            $h['address'][$field] = text('hospital.address.' . $field);
        }
        $h['map']['link'] = text('hospital.map.link');
        $h['map']['lat']  = (float) text('hospital.map.lat');
        $h['map']['lng']  = (float) text('hospital.map.lng');
    }

    define('HOSPITAL', $h);
}
