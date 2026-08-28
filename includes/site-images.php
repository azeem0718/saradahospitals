<?php
/**
 * Photographs reception uploads for the banners and the department cards.
 *
 * Every slot is optional. Where a photograph exists it is used; where one does
 * not, the page falls back to the drawn artwork or the icon it already had, so
 * a site with no photographs still looks finished and one photograph can be
 * added at a time without anything looking half-done in between.
 *
 * There are deliberately no stock photographs bundled here. A picture of some
 * other hospital's ward under a heading that says "Our Facilities" tells the
 * patient something untrue, and everything else on this site was held to what
 * the hospital's own signage and brochure actually say.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

const SITE_IMAGE_STORE = 'site';

/**
 * Every slot the admin panel offers, grouped for the screen that edits them.
 *
 * @return array<string, array{label: string, slots: array<string, string>}>
 */
function site_image_groups(): array
{
    return [
        'banners' => [
            'label' => 'Page banners',
            'hint'  => 'The wide strip at the top of each page. Landscape photographs '
                     . 'work best — roughly three times wider than tall. Without one, '
                     . 'the page keeps its drawn artwork.',
            'slots' => [
                'banner-about'      => 'About',
                'banner-services'   => 'Services',
                'banner-doctors'    => 'Doctors',
                'banner-diabetes'   => 'Diabetic Centre',
                'banner-maternity'  => 'Maternity',
                'banner-emergency'  => 'Emergency',
                'banner-facilities' => 'Facilities',
                'banner-tariff'     => 'Tariff',
                'banner-gallery'    => 'Gallery',
                'banner-contact'    => 'Contact',
                'banner-book'       => 'Book a Token',
            ],
        ],
        'hero' => [
            'label' => 'Home page slideshow',
            'hint'  => 'The five pictures that fade behind the headline at the top of '
                     . 'the home page. Wide, landscape photographs work best — anything '
                     . 'roughly 16:9. Faces and detail near the left edge get covered by '
                     . 'the text, so keep the subject to the right.',
            'slots' => [
                'hero-slide-1' => 'Slide 1',
                'hero-slide-2' => 'Slide 2',
                'hero-slide-3' => 'Slide 3',
                'hero-slide-4' => 'Slide 4',
                'hero-slide-5' => 'Slide 5',
            ],
        ],
        'cards' => [
            'label' => 'Home page department cards',
            'hint'  => 'The six cards under "What we do" on the home page. Roughly '
                     . 'landscape again. Without one, the card keeps its icon.',
            'slots' => [
                'card-medicine'  => 'General Medicine',
                'card-diabetes'  => 'Good Health Diabetic Centre',
                'card-maternity' => 'Maternity & Gynaecology',
                'card-emergency' => 'Emergency & ICU',
                'card-lab'       => 'Laboratory & Diagnostics',
                'card-tariff'    => 'Transparent Tariff',
            ],
        ],
    ];
}

/**
 * Slots that ship with a photograph already in place, and the file and wording
 * each arrives with.
 *
 * The hero slideshow borrows the department card photographs, which are already
 * on disk and already credited, so the slideshow arrives looking finished. The
 * migration seeds from this map and the content overview reads it, so there is
 * one statement of what shipped rather than two that could drift apart — which
 * is what lets the overview tell a shipped picture from one reception uploaded.
 *
 * @return array<string, array{file:string, alt:string}>
 */
function site_image_seeds(): array
{
    return [
        'hero-slide-1' => ['file' => 'card-emergency.jpg', 'alt' => 'An ambulance outside the hospital'],
        'hero-slide-2' => ['file' => 'card-medicine.jpg',  'alt' => 'A doctor holding a stethoscope'],
        'hero-slide-3' => ['file' => 'card-diabetes.jpg',  'alt' => 'A blood glucose meter being read'],
        'hero-slide-4' => ['file' => 'card-maternity.jpg', 'alt' => 'Maternity care'],
        'hero-slide-5' => ['file' => 'card-lab.jpg',       'alt' => 'A laboratory technician examining a sample'],
    ];
}

/**
 * True when the picture in a slot is reception's rather than the one the site
 * shipped with. A slot holding exactly its seeded file has not been touched.
 */
function site_image_is_custom(string $slot): bool
{
    $stored = site_image($slot);
    if ($stored === null) {
        return false;
    }
    $seed = site_image_seeds()[$slot] ?? null;
    return $seed === null || $stored['file'] !== $seed['file'];
}

/** Flat slot => label map, for validating what a form submitted. */
function site_image_slots(): array
{
    static $flat = null;
    if ($flat === null) {
        $flat = [];
        foreach (site_image_groups() as $group) {
            $flat += $group['slots'];
        }
    }
    return $flat;
}

/**
 * Every stored image, keyed by slot. Read once per request.
 *
 * @return array<string, array{file: string, alt: string}>
 */
function site_images(bool $reload = false): array
{
    static $cache = null;

    if ($cache === null || $reload) {
        $cache = [];
        try {
            foreach (db()->query('SELECT slot, file, alt FROM site_images') as $row) {
                $cache[$row['slot']] = ['file' => $row['file'], 'alt' => $row['alt']];
            }
        } catch (PDOException $e) {
            // The table arrives with a migration. A site that has not run it yet
            // should fall back to the drawn artwork, not stop serving.
            error_log('Site images unavailable: ' . $e->getMessage());
        }
    }

    return $cache;
}

/** Drop the cache so the next read sees what was just written. */
function site_images_forget(): void
{
    site_images(true);
}

/** The stored record for one slot, or null. */
function site_image(string $slot): ?array
{
    return site_images()[$slot] ?? null;
}

/**
 * Document-relative URL for a slot's image, or null when the slot is empty.
 *
 * @param string $prefix Prepended to the path, for pages in admin/.
 */
function site_image_url(string $slot, string $prefix = ''): ?string
{
    $image = site_image($slot);
    if ($image === null) {
        return null;
    }
    return asset('assets/img/' . SITE_IMAGE_STORE . '/' . $image['file'], $prefix);
}

/** Site-rooted URL, for use inside a CSS custom property. See asset_url(). */
function site_image_css_url(string $slot): ?string
{
    $image = site_image($slot);
    if ($image === null) {
        return null;
    }
    return asset_url('assets/img/' . SITE_IMAGE_STORE . '/' . $image['file']);
}

/**
 * The -sm phone variant's URL for a slot, or null when none exists.
 * Variants are made by tools/make-small-images.php for the shipped images and
 * by includes/uploads.php for reception's own uploads.
 */
function site_image_sm_url(string $slot, bool $forCss = false): ?string
{
    $image = site_image($slot);
    if ($image === null) {
        return null;
    }
    $dot = strrpos($image['file'], '.');
    $sm  = substr($image['file'], 0, $dot === false ? strlen($image['file']) : $dot) . '-sm.jpg';
    $rel = 'assets/img/' . SITE_IMAGE_STORE . '/' . $sm;
    if (!is_file(dirname(__DIR__) . '/' . $rel)) {
        return null;
    }
    return $forCss ? asset_url($rel) : asset($rel);
}

/**
 * srcset/sizes attributes for a slot's <img>, when a phone variant exists.
 * Returns '' otherwise, so callers can print it unconditionally.
 */
function site_image_srcset(string $slot, string $sizes): string
{
    $sm = site_image_sm_url($slot);
    if ($sm === null) {
        return '';
    }
    return ' srcset="' . e($sm) . ' 768w, ' . e(site_image_url($slot)) . ' 1200w"'
         . ' sizes="' . e($sizes) . '"';
}

/**
 * Class suffix and style attribute for a banner that may carry artwork.
 *
 * One place instead of three: page_hero(), the emergency page and the doctor
 * profile all put artwork behind their hero. A photograph wins over the drawn
 * fallback, and its phone variant rides along as --hero-art-sm for the
 * narrow-screen stylesheet to pick up.
 *
 * @return array{0: string, 1: string} [' has-art has-photo'-style class
 *                                      suffix, ' style="…"' attribute]
 */
function hero_art_attrs(string $slot, string $fallbackSvg = ''): array
{
    $photo = site_image_css_url('banner-' . $slot);

    if ($photo !== null) {
        $style = "--hero-art:url('" . e($photo) . "')";
        $sm    = site_image_sm_url('banner-' . $slot, true);
        if ($sm !== null) {
            $style .= ";--hero-art-sm:url('" . e($sm) . "')";
        }
        return [' has-art has-photo', ' style="' . $style . '"'];
    }

    if ($fallbackSvg !== '') {
        $file = 'assets/img/hero/' . $fallbackSvg . '.svg';
        if (is_file(dirname(__DIR__) . '/' . $file)) {
            return [' has-art', ' style="--hero-art:url(\'' . e(asset_url($file)) . '\')"'];
        }
    }

    return ['', ''];
}

/** Alt text for a slot, falling back to the slot's own label. */
function site_image_alt(string $slot): string
{
    $image = site_image($slot);
    if ($image !== null && trim($image['alt']) !== '') {
        return $image['alt'];
    }
    return site_image_slots()[$slot] ?? '';
}
