<?php
/**
 * Structured data for search engines.
 *
 * Every page used to emit the same MedicalClinic block. That is not wrong so
 * much as wasted: it told Google the same thing twelve times and told it
 * nothing about the page it was actually on. A search engine reading the
 * tariff page learnt the hospital's phone number and not that it was looking
 * at a price list.
 *
 * So this builds one @graph per page instead of one block per site. The graph
 * is the format Google's own documentation prefers for exactly this reason:
 * entities get an @id and then refer to each other, so the clinic is stated
 * once and the page, the breadcrumb trail and the doctor all point at it
 * rather than each restating it. One <script> tag, one connected description
 * of what this page is and who publishes it.
 *
 * A page adds to it by setting, before including the header:
 *
 *   $breadcrumb  = [['Our Doctors', 'doctors.php'], ['Dr Venkatesh', null]];
 *   $pageSchema  = [ ... extra entities ... ];
 *   $pageType    = 'MedicalWebPage';   // default 'WebPage'
 *   $pageNoIndex = true;               // keep this page out of the index
 *
 * Everything else is read from the hospital's own content, so the markup stays
 * true as the panel is edited. Nothing here is invented for search engines:
 * there are no ratings, no review counts and no awards, because the hospital
 * has not published any and structured data that says otherwise is both a
 * manual-action risk and a lie told on a hospital's behalf.
 */

declare(strict_types=1);

require_once __DIR__ . '/site.php';
require_once __DIR__ . '/content.php';

/** Absolute URL for a path, which every @id and every image needs. */
function seo_url(string $path = ''): string
{
    $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

/**
 * The hospital itself. Anchored at a stable @id so that every other entity —
 * on this page and on every other page — refers to one clinic rather than
 * asserting a new one each time.
 */
function seo_organization(bool $full = false): array
{
    $node = [
        '@type' => ['MedicalClinic', 'LocalBusiness'],
        '@id'   => seo_url() . '#organization',
        'name'  => HOSPITAL['name'],
        'url'   => seo_url(),
        'description' => HOSPITAL['tagline'],
        'telephone'   => HOSPITAL['mobile'],
        'address' => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => HOSPITAL['address']['line1'] . ', ' . HOSPITAL['address']['line2'],
            'addressLocality' => 'Kandukur',
            'addressRegion'   => 'Andhra Pradesh',
            'addressCountry'  => 'IN',
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => HOSPITAL['map']['lat'],
            'longitude' => HOSPITAL['map']['lng'],
        ],
        'logo' => [
            '@type'  => 'ImageObject',
            '@id'    => seo_url() . '#logo',
            'url'    => seo_url('assets/img/logo/badge-512.png'),
            'width'  => 512,
            'height' => 512,
            'caption'=> HOSPITAL['name'],
        ],
        /* 24 hours, every day. This is the single most useful fact a search
           engine can carry about an emergency service, so it is stated plainly
           rather than as a list of sessions — the OP timings are a subset of
           being open, and a patient searching at 2am needs the outer fact. */
        'openingHoursSpecification' => [[
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'opens'     => '00:00',
            'closes'    => '23:59',
        ]],
        'availableService' => seo_services($full),
        'isAcceptingNewPatients' => true,
        'currenciesAccepted'     => 'INR',
        /* Who this hospital is actually for. A nursing home in Kandukur serves
           the town and the mandals around it, and saying so is more honest and
           more useful than claiming a state. */
        'areaServed' => [
            ['@type' => 'City',           'name' => 'Kandukur'],
            ['@type' => 'AdministrativeArea', 'name' => 'Prakasam District, Andhra Pradesh'],
        ],
    ];

    /* Only if the hospital has actually recorded one. A postal code invented
       to fill the field would be a wrong address stated confidently, which is
       worse for a patient trying to reach a hospital than no address at all. */
    if (!empty(HOSPITAL['address']['pin'])) {
        $node['address']['postalCode'] = HOSPITAL['address']['pin'];
    }

    /* Only real second numbers, and only if they are set. */
    if (!empty(HOSPITAL['landline'])) {
        $node['telephone'] = [HOSPITAL['mobile'], HOSPITAL['landline']];
    }
    if (!empty(HOSPITAL['map']['link'])) {
        $node['hasMap'] = HOSPITAL['map']['link'];
    }

    /* Specialities, in schema.org's own vocabulary rather than free text, so
       they are matched rather than merely read. Each one is a department this
       hospital actually runs. */
    $node['medicalSpecialty'] = [
        'PrimaryCare',      // General Medicine
        'Emergency',        // 24-hour casualty and ICU
        'Endocrine',        // Good Health Diabetic Centre
        'Obstetric',
        'Gynecologic',
        'Midwifery',
        'LaboratoryScience',
    ];

    /* The price range comes from the published tariff, so it cannot drift away
       from the board on the wall. Lowest consultation fee to highest daily
       charge — the honest span of what a visit here costs. */
    $amounts = [];
    foreach ([
        defined('CONSULTATION_FEES') ? CONSULTATION_FEES : [],
        defined('ROOM_CHARGES') ? ROOM_CHARGES : [],
    ] as $table) {
        foreach ($table as $row) {
            if (isset($row['amount']) && (int) $row['amount'] > 0) {
                $amounts[] = (int) $row['amount'];
            }
        }
    }
    if ($amounts) {
        $node['priceRange'] = '₹' . number_format(min($amounts)) . '–₹' . number_format(max($amounts));
    }

    /* Real photographs of the real building, when the hospital has uploaded
       them. Google shows these beside a local result, and the slideshow images
       are the best ones the site has. */
    $photos = [];
    for ($i = 1; $i <= 5; $i++) {
        $url = function_exists('site_image_url') ? site_image_url('hero-slide-' . $i) : null;
        if ($url !== null) {
            $photos[] = seo_url(ltrim(preg_replace('/\?.*$/', '', $url), '/'));
        }
    }
    if ($photos) {
        $node['image'] = $photos;
    }

    return $node;
}

/**
 * Everything this hospital treats, named one procedure at a time.
 *
 * This is the list from the hospital's own board, which is the point: a search
 * for "snake bite treatment Kandukur" matches a clinic that has said it treats
 * snake bite, and generic prose about "quality healthcare" matches nothing.
 * The lists are admin-editable, so the markup widens as the hospital adds to
 * them.
 */
function seo_services(bool $full = false): array
{
    /* The whole list only where treatment is what the page is about — the home
       page, the services page, the department pages. Everywhere else the
       clinic still says it runs a 24-hour emergency and nothing more, because
       the organisation carries a stable @id and a search engine stitches the
       pages into one entity rather than reading each in isolation. Spending
       two kilobytes restating every procedure on the photo-credits page buys
       nothing and is paid for by a patient on mobile data. */
    $names = $full ? array_merge(
        defined('GENERAL_MEDICINE') ? GENERAL_MEDICINE : [],
        defined('OBG_SERVICES') ? OBG_SERVICES : []
    ) : [];

    $out = [[
        '@type' => 'MedicalProcedure',
        'name'  => 'Emergency care, 24 hours',
    ]];
    foreach ($names as $name) {
        $name = trim((string) $name);
        if ($name !== '') {
            $out[] = ['@type' => 'MedicalProcedure', 'name' => $name];
        }
    }
    return $out;
}

/** The site as a publication, so the clinic is named as its publisher. */
function seo_website(): array
{
    return [
        '@type'     => 'WebSite',
        '@id'       => seo_url() . '#website',
        'url'       => seo_url(),
        'name'      => HOSPITAL['name'],
        'publisher' => ['@id' => seo_url() . '#organization'],
        'inLanguage'=> 'en-IN',
    ];
}

/**
 * The page being looked at. Typed by the caller because a search engine treats
 * a MedicalWebPage differently from a contact page, and most of this site's
 * pages genuinely are medical information.
 */
function seo_webpage(string $canonical, string $title, string $description, string $type = 'WebPage'): array
{
    return [
        '@type'       => $type,
        '@id'         => $canonical . '#webpage',
        'url'         => $canonical,
        'name'        => $title,
        'description' => $description,
        'isPartOf'    => ['@id' => seo_url() . '#website'],
        'about'       => ['@id' => seo_url() . '#organization'],
        'inLanguage'  => 'en-IN',
    ];
}

/**
 * Breadcrumbs, which are the one piece of structured data whose effect you can
 * see with your own eyes: Google prints the trail in place of the raw URL.
 * Home is prepended here so no page has to remember it, and the final crumb
 * carries no URL because it is the page you are already on.
 */
function seo_breadcrumb(array $trail, string $canonical): ?array
{
    if (!$trail) {
        return null;
    }

    $items = [[
        '@type'    => 'ListItem',
        'position' => 1,
        'name'     => 'Home',
        'item'     => seo_url(),
    ]];

    $n = 1;
    foreach ($trail as [$label, $path]) {
        $n++;
        $item = ['@type' => 'ListItem', 'position' => $n, 'name' => $label];
        if ($path !== null && $path !== '') {
            $item['item'] = seo_url($path);
        }
        $items[] = $item;
    }

    return [
        '@type'           => 'BreadcrumbList',
        '@id'             => $canonical . '#breadcrumb',
        'itemListElement' => $items,
    ];
}

/**
 * A doctor, for their own profile page. Physician is a subtype of both Person
 * and MedicalOrganization member, so the qualification and speciality are
 * expressed as themselves rather than buried in prose.
 */
function seo_physician(array $doctor, string $canonical): array
{
    $node = [
        '@type'      => 'Physician',
        '@id'        => $canonical . '#physician',
        'name'       => $doctor['name'],
        'url'        => $canonical,
        'worksFor'   => ['@id' => seo_url() . '#organization'],
        'memberOf'   => ['@id' => seo_url() . '#organization'],
        'address'    => [
            '@type'           => 'PostalAddress',
            'addressLocality' => 'Kandukur',
            'addressRegion'   => 'Andhra Pradesh',
            'addressCountry'  => 'IN',
        ],
    ];

    if (!empty($doctor['speciality'])) {
        $node['medicalSpecialty'] = $doctor['speciality'];
        $node['jobTitle']         = $doctor['speciality'];
    }
    /* The letters after the name are a real credential, so they are marked up
       as one rather than left as decoration in the heading. */
    if (!empty($doctor['qualification'])) {
        $node['hasCredential'] = [
            '@type'                => 'EducationalOccupationalCredential',
            'credentialCategory'   => 'degree',
            'name'                 => $doctor['qualification'],
        ];
    }
    if (!empty($doctor['bio'])) {
        $node['description'] = content_excerpt((string) $doctor['bio'], 300);
    }

    return $node;
}

/**
 * The Contact page's questions, as a FAQPage.
 *
 * This is the highest-value markup on the site and it costs nothing extra,
 * because the hospital already answers these at the desk fifty times a day and
 * the answers are already written and already editable in the panel. "What are
 * the OP timings", "how much is a consultation" — those are the searches, and
 * an FAQ result answers them inside the search page.
 *
 * The answers run through the same inline formatter the page uses, then the
 * tags come off: Google wants the answer as text, and a half-rendered link is
 * worse than none.
 */
function seo_faq(array $faqs, string $canonical): ?array
{
    $items = [];
    foreach ($faqs as $faq) {
        $q = trim((string) ($faq['title'] ?? ''));
        $a = trim(strip_tags(content_inline((string) ($faq['text'] ?? ''))));
        if ($q === '' || $a === '') {
            continue;
        }
        $items[] = [
            '@type'          => 'Question',
            'name'           => $q,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
        ];
    }

    return $items ? [
        '@type'      => 'FAQPage',
        '@id'        => $canonical . '#faq',
        'mainEntity' => $items,
    ] : null;
}

/**
 * Assemble everything the current page should say and hand back one JSON-LD
 * document. Reads the page's own variables so a template only declares what is
 * special about it.
 */
function seo_json_ld(string $canonical, string $title, string $description): string
{
    $type = $GLOBALS['pageType'] ?? 'WebPage';

    /* A page that declares itself medical, or the front page, is a page where
       the full list of what this hospital treats is the point. */
    $isHome  = rtrim($canonical, '/') === rtrim(seo_url(), '/')
            || str_ends_with($canonical, '/index.php');
    $graph = [seo_organization($isHome || $type === 'MedicalWebPage'), seo_website()];
    $graph[] = seo_webpage($canonical, $title, $description, (string) $type);

    $crumbs = seo_breadcrumb($GLOBALS['breadcrumb'] ?? [], $canonical);
    if ($crumbs !== null) {
        $graph[] = $crumbs;
    }

    foreach (($GLOBALS['pageSchema'] ?? []) as $extra) {
        if (is_array($extra) && $extra) {
            $graph[] = $extra;
        }
    }

    /* Not pretty-printed. Only a machine reads this, and the indentation was
       costing about a third of the block's weight on every page load, over a
       mobile connection, for whitespace nobody sees. */
    return (string) json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}
