<?php
/**
 * Icon set.
 *
 * Two tiers. Feature icons — the ones carrying visual weight on cards, quick
 * links and contact rows — are duotone: a soft fill of currentColor behind a
 * crisp stroke, which reads far richer than a flat single-weight line. Utility
 * icons (ticks, chevrons, close) stay plain strokes, because a tick with a
 * shadow behind it is just noise.
 *
 * Feature icons also name one moving part with .ico-anim. The animation is
 * declared per icon as a custom property and only runs while an ancestor is
 * hovered or focused, so nothing moves until someone reaches for it. See the
 * "Animated icons" block in style.css.
 */

declare(strict_types=1);

/**
 * Feature icons: [custom-property animation name, markup].
 * `F` marks the duotone fill, `L` the stroke layer, `A` the moving part.
 */
function icon_feature_set(): array
{
    return [
        // --- booking and wayfinding -------------------------------------
        'ticket' => ['ico-ticket',
            '<path class="ico-f" d="M3 8.5A1.5 1.5 0 0 1 4.5 7h15A1.5 1.5 0 0 1 21 8.5v2a2 2 0 0 0 0 3v2A1.5 1.5 0 0 1 19.5 17h-15A1.5 1.5 0 0 1 3 15.5v-2a2 2 0 0 0 0-3z"/>
             <path class="ico-l" d="M3 8.5A1.5 1.5 0 0 1 4.5 7h15A1.5 1.5 0 0 1 21 8.5v2a2 2 0 0 0 0 3v2A1.5 1.5 0 0 1 19.5 17h-15A1.5 1.5 0 0 1 3 15.5v-2a2 2 0 0 0 0-3z"/>
             <path class="ico-l ico-anim" d="M14 7v10" stroke-dasharray="2.4 2.4"/>'],

        'calendar' => ['ico-calendar',
            '<path class="ico-f" d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
             <rect class="ico-l" x="3" y="5" width="18" height="16" rx="2"/>
             <path class="ico-l" d="M3 9.5h18M8 3v4M16 3v4"/>
             <path class="ico-l ico-anim" d="M8.5 14.5h3.5"/>'],

        'clock' => ['ico-clock',
            '<circle class="ico-f" cx="12" cy="12" r="9"/>
             <circle class="ico-l" cx="12" cy="12" r="9"/>
             <path class="ico-l ico-anim" d="M12 7.5V12l3.2 1.9"/>'],

        'location' => ['ico-location',
            '<path class="ico-f" d="M12 21.5s7.2-6.1 7.2-11.3A7.2 7.2 0 0 0 4.8 10.2C4.8 15.4 12 21.5 12 21.5z"/>
             <path class="ico-l ico-anim" d="M12 21.5s7.2-6.1 7.2-11.3A7.2 7.2 0 0 0 4.8 10.2C4.8 15.4 12 21.5 12 21.5z"/>
             <circle class="ico-l" cx="12" cy="10" r="2.6"/>'],

        'search' => ['ico-search',
            '<circle class="ico-f" cx="10.8" cy="10.8" r="6.8"/>
             <circle class="ico-l" cx="10.8" cy="10.8" r="6.8"/>
             <path class="ico-l ico-anim" d="m20 20-4.4-4.4"/>'],

        // --- clinical ---------------------------------------------------
        'stethoscope' => ['ico-stetho',
            '<circle class="ico-f" cx="18.5" cy="10.5" r="2.6"/>
             <path class="ico-l" d="M6 3v5a4.5 4.5 0 0 0 9 0V3"/>
             <path class="ico-l" d="M4.6 3h2.8M13.6 3h2.8"/>
             <path class="ico-l" d="M10.5 12.5v3.2a4.3 4.3 0 0 0 8 2"/>
             <circle class="ico-l ico-anim" cx="18.5" cy="10.5" r="2.6"/>'],

        'heart' => ['ico-heart',
            '<path class="ico-f ico-anim" d="M12 20.4 4.6 13a4.9 4.9 0 0 1 6.9-7l.5.5.5-.5a4.9 4.9 0 0 1 6.9 7z"/>
             <path class="ico-l" d="M12 20.4 4.6 13a4.9 4.9 0 0 1 6.9-7l.5.5.5-.5a4.9 4.9 0 0 1 6.9 7z"/>'],

        'icu' => ['ico-icu',
            '<rect class="ico-f" x="2.5" y="5" width="19" height="14" rx="2.2"/>
             <rect class="ico-l" x="2.5" y="5" width="19" height="14" rx="2.2"/>
             <path class="ico-l ico-anim" d="M5.5 12.4h3l1.8-4 2.6 8 1.8-4h3.8"/>'],

        'droplet' => ['ico-droplet',
            '<path class="ico-f" d="M12 3.2 6.9 9a7.1 7.1 0 1 0 10.2 0z"/>
             <path class="ico-l" d="M12 3.2 6.9 9a7.1 7.1 0 1 0 10.2 0z"/>
             <path class="ico-l ico-anim" d="M9.4 13.6a3.2 3.2 0 0 0 2.8 3.1"/>'],

        'lab' => ['ico-lab',
            '<path class="ico-f" d="M9.6 12.6h4.8l3.9 6.2a1.7 1.7 0 0 1-1.4 2.6H7.1a1.7 1.7 0 0 1-1.4-2.6z"/>
             <path class="ico-l" d="M9.5 3h5M10 3v7.1L5.7 18.8a1.7 1.7 0 0 0 1.4 2.6h9.8a1.7 1.7 0 0 0 1.4-2.6L14 10.1V3"/>
             <circle class="ico-l ico-anim" cx="10.4" cy="16.6" r="1.05"/>'],

        'scan' => ['ico-scan',
            '<path class="ico-f" d="M10.2 20.6h3.6a1.1 1.1 0 0 0 1.1-1.1v-2.6H9.1v2.6a1.1 1.1 0 0 0 1.1 1.1z"/>
             <path class="ico-l" d="M10.2 20.6h3.6a1.1 1.1 0 0 0 1.1-1.1v-2.6H9.1v2.6a1.1 1.1 0 0 0 1.1 1.1z"/>
             <g class="ico-anim">
               <path class="ico-l" d="M8.6 14.4a4.8 4.8 0 0 1 6.8 0"/>
               <path class="ico-l" d="M6.4 11.2a8.4 8.4 0 0 1 11.2 0"/>
               <path class="ico-l" d="M4.2 8a12 12 0 0 1 15.6 0"/>
             </g>'],

        'maternity' => ['ico-maternity',
            '<path class="ico-f ico-anim" d="M8.7 8.1c-2 0-3 1.4-3 3.1v8.2h5c3.2 0 5.4-2.1 5.4-4.9 0-3-2.4-5.3-5.6-5.6z"/>
             <circle class="ico-l" cx="8.9" cy="4.3" r="2.5"/>
             <path class="ico-l" d="M8.7 8.1c-2 0-3 1.4-3 3.1v8.2h5c3.2 0 5.4-2.1 5.4-4.9 0-3-2.4-5.3-5.6-5.6z"/>
             <path class="ico-l" d="M6.4 19.4v2.3M10.6 19.4v2.3"/>'],

        'baby' => ['ico-baby',
            '<circle class="ico-f" cx="12" cy="12" r="9"/>
             <circle class="ico-l" cx="12" cy="12" r="9"/>
             <path class="ico-l ico-anim" d="M9.2 10.6h.02M14.8 10.6h.02M9.6 15c.7.5 1.5.7 2.4.7s1.7-.2 2.4-.7"/>'],

        'emergency' => ['ico-emergency',
            '<path class="ico-f" d="M12 2.6 4.4 6v6c0 5.2 3.2 9.6 7.6 10.8 4.4-1.2 7.6-5.6 7.6-10.8V6z"/>
             <path class="ico-l" d="M12 2.6 4.4 6v6c0 5.2 3.2 9.6 7.6 10.8 4.4-1.2 7.6-5.6 7.6-10.8V6z"/>
             <path class="ico-l ico-anim" d="M12 8.6v6M9 11.6h6"/>'],

        'shield' => ['ico-shield',
            '<path class="ico-f" d="M12 2.6 4.4 6v6c0 5.2 3.2 9.6 7.6 10.8 4.4-1.2 7.6-5.6 7.6-10.8V6z"/>
             <path class="ico-l" d="M12 2.6 4.4 6v6c0 5.2 3.2 9.6 7.6 10.8 4.4-1.2 7.6-5.6 7.6-10.8V6z"/>
             <path class="ico-l ico-anim" d="m8.8 12.2 2.3 2.3 4.1-4.5"/>'],

        // --- people and admin -------------------------------------------
        'users' => ['ico-users',
            '<circle class="ico-f" cx="9.4" cy="7.4" r="3.6"/>
             <circle class="ico-l" cx="9.4" cy="7.4" r="3.6"/>
             <path class="ico-l" d="M2.6 20.4v-1.5a4.4 4.4 0 0 1 4.4-4.4h4.8a4.4 4.4 0 0 1 4.4 4.4v1.5"/>
             <path class="ico-l ico-anim" d="M16.6 4.2a3.6 3.6 0 0 1 0 6.9M18.2 14.8a4.4 4.4 0 0 1 3.2 4.2v1.4"/>'],

        'list' => ['ico-list',
            '<path class="ico-f" d="M5 2.8h14v16.9l-2.3-1.4-2.4 1.4-2.3-1.4-2.4 1.4-2.3-1.4L5 19.7z"/>
             <path class="ico-l" d="M5 2.8h14v16.9l-2.3-1.4-2.4 1.4-2.3-1.4-2.4 1.4-2.3-1.4L5 19.7z"/>
             <path class="ico-l ico-anim" d="M8.4 7.6h7.2M8.4 11.4h7.2M8.4 15.2h4.4"/>'],

        'discount' => ['ico-discount',
            '<path class="ico-f" d="M11.2 2.6H4.4a1.8 1.8 0 0 0-1.8 1.8v6.8a1.8 1.8 0 0 0 .5 1.3l7.6 7.6a1.8 1.8 0 0 0 2.6 0l6.8-6.8a1.8 1.8 0 0 0 0-2.6l-7.6-7.6a1.8 1.8 0 0 0-1.3-.5z"/>
             <path class="ico-l" d="M11.2 2.6H4.4a1.8 1.8 0 0 0-1.8 1.8v6.8a1.8 1.8 0 0 0 .5 1.3l7.6 7.6a1.8 1.8 0 0 0 2.6 0l6.8-6.8a1.8 1.8 0 0 0 0-2.6l-7.6-7.6a1.8 1.8 0 0 0-1.3-.5z"/>
             <circle class="ico-l ico-anim" cx="7.4" cy="7.4" r="1.5"/>'],

        'award' => ['ico-award',
            '<circle class="ico-f" cx="12" cy="8.6" r="5.8"/>
             <circle class="ico-l" cx="12" cy="8.6" r="5.8"/>
             <path class="ico-l ico-anim" d="m8.6 13.6-1.2 8L12 19l4.6 2.6-1.2-8"/>'],

        'building' => ['ico-building',
            '<rect class="ico-f" x="4" y="2.5" width="16" height="19" rx="2"/>
             <rect class="ico-l" x="4" y="2.5" width="16" height="19" rx="2"/>
             <path class="ico-l ico-anim" d="M9.5 21.5v-4h5v4"/>
             <path class="ico-l" d="M8 6.5h.02M12 6.5h.02M16 6.5h.02M8 10.5h.02M12 10.5h.02M16 10.5h.02M8 14.5h.02M16 14.5h.02"/>'],

        'room' => ['ico-room',
            '<path class="ico-f" d="M3.4 12.4h17.2v5.4H3.4z"/>
             <path class="ico-l" d="M3.4 17.8v3M20.6 17.8v3"/>
             <path class="ico-l" d="M3.4 17.8v-6.2a1 1 0 0 1 1-1h15.2a1 1 0 0 1 1 1v6.2z"/>
             <path class="ico-l" d="M3.4 14.4h17.2"/>
             <rect class="ico-l ico-anim" x="5.4" y="6.6" width="5.6" height="3.8" rx="1.3"/>'],

        'phone' => ['ico-phone',
            '<path class="ico-f" d="M21.5 16.9v2.9a2 2 0 0 1-2.2 2 19.6 19.6 0 0 1-8.5-3A19.3 19.3 0 0 1 4.9 13 19.6 19.6 0 0 1 1.8 4.4a2 2 0 0 1 2-2.2h2.9a2 2 0 0 1 2 1.7c.13.95.36 1.88.7 2.77a2 2 0 0 1-.45 2.1L7.7 10.1a15.8 15.8 0 0 0 6 6l1.3-1.26a2 2 0 0 1 2.1-.45c.9.34 1.82.57 2.77.7a2 2 0 0 1 1.7 2z"/>
             <path class="ico-l ico-anim" d="M21.5 16.9v2.9a2 2 0 0 1-2.2 2 19.6 19.6 0 0 1-8.5-3A19.3 19.3 0 0 1 4.9 13 19.6 19.6 0 0 1 1.8 4.4a2 2 0 0 1 2-2.2h2.9a2 2 0 0 1 2 1.7c.13.95.36 1.88.7 2.77a2 2 0 0 1-.45 2.1L7.7 10.1a15.8 15.8 0 0 0 6 6l1.3-1.26a2 2 0 0 1 2.1-.45c.9.34 1.82.57 2.77.7a2 2 0 0 1 1.7 2z"/>'],
    ];
}

/** Utility icons: single-weight strokes, no fill, no motion. */
function icon_utility_set(): array
{
    return [
        'check'         => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle'  => '<circle cx="12" cy="12" r="9"/><path d="m8.6 12.2 2.3 2.3 4.5-4.9"/>',
        'arrow-right'   => '<path d="M4 12h15M13 6l6 6-6 6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
        'close'         => '<path d="M18 6 6 18M6 6l12 12"/>',
        'plus'          => '<path d="M12 5v14M5 12h14"/>',
        'edit'          => '<path d="M11 4H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-6"/><path d="M18.4 2.6a2 2 0 0 1 2.8 2.8L12 14.6l-3.8.8.8-3.8z"/>',
        'menu'          => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'info'          => '<circle cx="12" cy="12" r="9"/><path d="M12 16.5v-5M12 8h.02"/>',
        'alert'         => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9.5v4M12 17.2h.02"/>',
        'lock'          => '<rect x="3.5" y="10.5" width="17" height="10.5" rx="2"/><path d="M7.5 10.5V7a4.5 4.5 0 0 1 9 0v3.5"/>',
        'logout'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'settings'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6 1.65 1.65 0 0 0 10 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.14.35.4.64.73.83.3.17.63.26.97.26H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'print'         => '<path d="M6.5 9V3h11v6M6.5 18H5a2 2 0 0 1-2-2v-4.5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2V16a2 2 0 0 1-2 2h-1.5"/><rect x="6.5" y="14" width="11" height="7" rx="1"/>',
        'undo'          => '<path d="M3 7.5v5.5h5.5"/><path d="M3.6 13a9 9 0 1 0 2.1-9.4L3 7.5"/>',
        'image'         => '<rect x="3" y="3.5" width="18" height="17" rx="2.2"/><circle cx="8.6" cy="9.2" r="1.6"/><path d="m21 15.5-4.8-4.8L6 20.5"/>',
        'whatsapp'      => '<path d="M12.04 2.5a9.4 9.4 0 0 0-8.1 14.1L2.5 21.5l5-1.4a9.4 9.4 0 1 0 4.54-17.6z"/><path d="M8.6 8c.2-.5.4-.5.7-.5h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2 0 .4-.1.6l-.5.6c-.1.2-.3.3-.1.6a8 8 0 0 0 3.6 3.1c.3.2.5.1.7-.1l.7-.8c.2-.2.4-.2.6-.1l1.9.9c.3.1.4.3.4.5a2 2 0 0 1-1.4 1.6 3.4 3.4 0 0 1-2.4-.2 12 12 0 0 1-6.4-5.7 3.9 3.9 0 0 1-.6-2.4c.1-.7.4-1.1.6-1.4z"/>',
    ];
}

/**
 * Render an icon.
 *
 * @param string $name  Icon key. Unknown names fall back to 'info'.
 * @param string $class Extra classes on the <svg>.
 */
function icon(string $name, string $class = ''): string
{
    static $feature = null, $utility = null;
    $feature ??= icon_feature_set();
    $utility ??= icon_utility_set();

    if (isset($feature[$name])) {
        [$anim, $body] = $feature[$name];
        $cls = trim('ico ico-duo ' . $class);
        $style = ' style="--ico-anim:' . $anim . '"';
    } else {
        $body  = $utility[$name] ?? $utility['info'];
        $body  = '<g class="ico-l">' . $body . '</g>';
        $cls   = trim('ico ' . $class);
        $style = '';
    }

    // width/height are presentation attributes, not styling: without them an
    // SVG with only a viewBox expands to fill its container if the stylesheet
    // fails to load. Any CSS rule still wins over them.
    return '<svg class="' . htmlspecialchars($cls, ENT_QUOTES) . '"' . $style
         . ' viewBox="0 0 24 24" width="20" height="20"'
         . ' fill="none" stroke="currentColor" stroke-width="1.7"'
         . ' stroke-linecap="round" stroke-linejoin="round"'
         . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/**
 * The heart-and-caring-hands mark, redrawn from the hospital's own logo:
 * two hands cupping a heart with a medical cross.
 *
 * Drawn with flat fills and no <defs>. The mark is rendered several times on
 * a page (masthead, footer, token slip), and duplicated gradient or clipPath
 * ids would be invalid markup.
 */
function logo_mark(string $class = 'brand-mark'): string
{
    return <<<SVG
    <svg class="{$class}" viewBox="0 0 64 64" width="40" height="40" role="img" aria-label="Sarada Nursing Home">
      <circle cx="32" cy="32" r="31" fill="#be1622"/>
      <circle cx="32" cy="32" r="28" fill="none" stroke="#ffffff" stroke-opacity=".2" stroke-width="1"/>
      <path d="M14.8 43.6c0-2.4 1.6-4.2 3.8-4.2 1.2 0 2.3.5 3.1 1.3l5 4.9-2 2.1-4.2-4.1a1.3 1.3 0 0 0-1.8 1.8l5.6 5.6h-7.3c-1.3 0-2.2-1-2.2-2.2z" fill="#ffffff" fill-opacity=".82"/>
      <path d="M49.2 43.6c0-2.4-1.6-4.2-3.8-4.2-1.2 0-2.3.5-3.1 1.3l-5 4.9 2 2.1 4.2-4.1a1.3 1.3 0 0 1 1.8 1.8l-5.6 5.6h7.3c1.3 0 2.2-1 2.2-2.2z" fill="#ffffff" fill-opacity=".82"/>
      <path d="M32 45.2c-8.2-5.8-13.7-10.7-13.7-16.4 0-4.1 3.1-7.2 7-7.2 2.5 0 4.7 1.3 6.1 3.3l.6.9.6-.9a7.2 7.2 0 0 1 6.1-3.3c3.9 0 7 3.1 7 7.2 0 5.7-5.5 10.6-13.7 16.4z" fill="#ffffff"/>
      <path d="M32 26v9.2M27.4 30.6h9.2" stroke="#be1622" stroke-width="3" stroke-linecap="round"/>
    </svg>
    SVG;
}

/**
 * Neutral doctor portrait used until real photographs are supplied.
 *
 * Sized to fill a circular frame, which the caller clips. No clipPath here,
 * for the same duplicate-id reason as the logo above.
 */
function doctor_avatar(string $class = 'avatar'): string
{
    return <<<SVG
    <svg class="{$class}" viewBox="0 0 64 64" width="40" height="40" role="img" aria-label="Portrait placeholder">
      <rect width="64" height="64" fill="#eef4fa"/>
      <circle cx="32" cy="25" r="10" fill="#3573a9"/>
      <path d="M32 37.5c-10.4 0-18.5 6.6-18.5 16.6V64h37V54.1c0-10-8.1-16.6-18.5-16.6z" fill="#ffffff"/>
      <path d="M24.9 39 32 46l7.1-7c5.6 2 9.4 6.7 10.4 12.6L50.6 64H13.4l1.1-12.4C15.5 45.7 19.3 41 24.9 39z" fill="#17456f"/>
      <path d="M27.8 38.2v4.4a4.2 4.2 0 0 0 8.4 0v-4.4" stroke="#ffffff" stroke-width="1.9" fill="none" stroke-linecap="round"/>
    </svg>
    SVG;
}
