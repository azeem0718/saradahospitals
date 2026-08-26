<?php
/**
 * Icon set.
 *
 * The path data lives in includes/icons.generated.php, vendored from Lucide
 * (ISC) by tools/build-icons.py. Vendored rather than linked, so the site still
 * makes no third-party request and still has no build step to run on deploy.
 *
 * Two tiers. Feature icons — the ones carrying weight on cards, quick links and
 * contact rows — name one moving part with .ico-anim and declare which keyframe
 * drives it. Utility icons (ticks, chevrons, close) are plain strokes: a tick
 * that animates is just noise. Nothing moves until an ancestor is hovered or
 * focused; see the "Icons" block in style.css.
 *
 * The logo and the portrait placeholder below are ours, not Lucide's.
 */

declare(strict_types=1);

require_once __DIR__ . '/icons.generated.php';

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
        $cls = trim('ico ico-feature ' . $class);
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
         . ' fill="none" stroke="currentColor" stroke-width="1.9"'
         . ' stroke-linecap="round" stroke-linejoin="round"'
         . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/**
 * The hospital's own mark — a heart held by two hands — on a white disc.
 *
 * Served as a raster badge (tools/make-logo.php) rather than traced: the
 * artwork's hands open out of the heart, so their white is the page's white,
 * and the disc is what lets the exact artwork sit on ivory, navy and red
 * grounds alike.
 *
 * @param string $prefix Path prefix for pages in admin/.
 */
function logo_mark(string $class = 'brand-mark', string $prefix = ''): string
{
    return '<img class="' . htmlspecialchars($class, ENT_QUOTES) . '"'
         . ' src="' . htmlspecialchars(asset('assets/img/logo/badge-96.png', $prefix), ENT_QUOTES) . '"'
         . ' srcset="' . htmlspecialchars(asset('assets/img/logo/badge-192.png', $prefix), ENT_QUOTES) . ' 2x"'
         . ' alt="" width="40" height="40" decoding="async">';
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
