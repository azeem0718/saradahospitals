<?php
/**
 * Inline SVG icon set. Inline rather than a sprite file so icons inherit
 * currentColor and never flash unstyled.
 */

declare(strict_types=1);

function icon(string $name, string $class = ''): string
{
    static $paths = [
        'phone'      => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'whatsapp'   => '<path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.64-2.05-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.06 2.86 1.21 3.06c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35z"/><path d="M20.52 3.45A11.87 11.87 0 0 0 12.05 0C5.5 0 .18 5.32.18 11.87c0 2.09.55 4.13 1.59 5.93L0 24l6.35-1.66a11.82 11.82 0 0 0 5.7 1.45h.01c6.54 0 11.87-5.32 11.87-11.87 0-3.17-1.24-6.15-3.41-8.47zm-8.47 18.26h-.01a9.86 9.86 0 0 1-5.02-1.38l-.36-.21-3.76.99 1-3.67-.23-.38a9.82 9.82 0 0 1-1.51-5.25c0-5.44 4.43-9.87 9.88-9.87 2.64 0 5.12 1.03 6.98 2.9a9.81 9.81 0 0 1 2.89 6.98c0 5.45-4.43 9.89-9.87 9.89z"/>',
        'location'   => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'clock'      => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'calendar'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'check'      => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'arrow-right'=> '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'alert'      => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/>',
        'info'       => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'emergency'  => '<path d="M12 2 4 6v6c0 5.55 3.42 10.24 8 11.4 4.58-1.16 8-5.85 8-11.4V6l-8-4z"/><path d="M12 8v6M9 11h6"/>',
        'heart'      => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
        'stethoscope'=> '<path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6 6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.3.3 0 1 0 .2.3"/><path d="M8 15v1a6 6 0 0 0 6 6 6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/>',
        'baby'       => '<path d="M9 12h.01M15 12h.01M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/><path d="M12 3a9 9 0 0 0-9 9 9 9 0 0 0 9 9 9 9 0 0 0 9-9 9 9 0 0 0-9-9z"/>',
        'droplet'    => '<path d="M12 2.7 6.5 8.2a7.8 7.8 0 1 0 11 0L12 2.7z"/>',
        'lab'        => '<path d="M9 3h6M10 3v6.5L4.6 18a2 2 0 0 0 1.7 3h11.4a2 2 0 0 0 1.7-3L14 9.5V3"/><path d="M7 15h10"/>',
        'icu'        => '<path d="M3 12h4l2-5 3 10 2.5-5H21"/>',
        'scan'       => '<path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h2l1.5-3 2 6 1.5-3h3"/>',
        'room'       => '<path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6M3 18v2M21 18v2M3 14h18"/><path d="M6 10V7a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>',
        'maternity'  => '<circle cx="12" cy="5" r="2.5"/><path d="M12 8c-2 0-3.5 1.5-3.5 3.5 0 1.2.4 2 .4 3.5 0 1.2-.9 2-.9 4h8c0-2-.9-2.8-.9-4 0-1.5.4-2.3.4-3.5C15.5 9.5 14 8 12 8z"/>',
        'discount'   => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0l-8.2-8.2A2 2 0 0 1 2 11V4a2 2 0 0 1 2-2h7a2 2 0 0 1 1.4.6l8.2 8.2a2 2 0 0 1 0 2.6z"/><circle cx="7" cy="7" r="1.2"/>',
        'shield'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'users'      => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'menu'       => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close'      => '<path d="M18 6 6 18M6 6l12 12"/>',
        'print'      => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/>',
        'search'     => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
        'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'lock'       => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6 1.65 1.65 0 0 0 10 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.14.35.4.64.73.83.3.17.63.26.97.26H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'list'       => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'image'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'ticket'     => '<path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H5a2 2 0 0 1-2-2 2 2 0 0 0 0-4z"/><path d="M13 7v10"/>',
        'plus'       => '<path d="M12 5v14M5 12h14"/>',
        'undo'       => '<path d="M3 7v6h6"/><path d="M3.5 13a9 9 0 1 0 2.1-9.4L3 7"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right'=> '<path d="m9 18 6-6-6-6"/>',
        'award'      => '<circle cx="12" cy="8" r="6"/><path d="M15.5 13.5 17 22l-5-3-5 3 1.5-8.5"/>',
        'building'   => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M16 14h.01"/>',
    ];

    $path = $paths[$name] ?? $paths['info'];
    $cls  = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '';

    // width/height are presentation attributes, not styling: they stop the icon
    // filling its container if the stylesheet ever fails to load, and any CSS
    // rule still wins over them.
    return '<svg' . $cls . ' viewBox="0 0 24 24" width="20" height="20"'
         . ' fill="none" stroke="currentColor"'
         . ' stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"'
         . ' aria-hidden="true" focusable="false">' . $path . '</svg>';
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
