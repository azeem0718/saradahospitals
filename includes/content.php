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

/* --------------------------------------------------------------------------
   Page text

   The wording on each public page, block by block. Structure stays in the
   templates — which card links where, which photograph slot it uses — because
   that is layout, not copy. What reception can change here is what the page
   says.
   -------------------------------------------------------------------------- */

/**
 * @return array<string, array{label:string, url:string, blocks:array}>
 */
function content_pages(): array
{
    return [
        'home' => [
            'label'  => 'Home',
            'url'    => 'index.php',
            'blocks' => [
                'home.hero.place' => [
                    'label' => 'Hero — location line', 'type' => 'text',
                    'default' => 'Pamuru Road, Kandukur · Prakasam District',
                ],
                'home.hero.title' => [
                    'label' => 'Hero — headline', 'type' => 'area',
                    'hint'  => 'Each new line becomes a line break on the page.',
                    'default' => "Let’s find you\na doctor.",
                ],
                'home.hero.lede' => [
                    'label' => 'Hero — opening sentence', 'type' => 'area',
                    'default' => 'General Medicine, Diabetology and Obstetrics & Gynaecology under one '
                               . 'roof — open every hour of every day.',
                ],

                'home.departments.eyebrow' => [
                    'label' => 'Departments — small heading', 'type' => 'text',
                    'default' => 'Our Departments',
                ],
                'home.departments.title' => [
                    'label' => 'Departments — heading', 'type' => 'text',
                    'default' => 'Complete care for your family',
                ],
                'home.departments.lede' => [
                    'label' => 'Departments — intro', 'type' => 'area',
                    'default' => 'From everyday fevers and long-term diabetes management to safe '
                               . 'deliveries and emergency treatment, we look after the whole family.',
                ],

                'home.card.medicine.title' => [
                    'label' => 'Card 1 — title', 'type' => 'text', 'default' => 'General Medicine',
                ],
                'home.card.medicine.body' => [
                    'label' => 'Card 1 — text', 'type' => 'area',
                    'default' => 'Diabetes and blood pressure, heart and kidney problems, all types of '
                               . 'fever, dengue and malaria, thyroid disorders, asthma, TB and more.',
                ],
                'home.card.diabetes.title' => [
                    'label' => 'Card 2 — title', 'type' => 'text', 'default' => 'Good Health Diabetic Centre',
                ],
                'home.card.diabetes.body' => [
                    'label' => 'Card 2 — text', 'type' => 'area',
                    'default' => 'Dedicated diabetes care led by a doctor with a Diploma in '
                               . 'Endocrinology & Diabetology — diagnosis, control and long-term follow-up.',
                ],
                'home.card.maternity.title' => [
                    'label' => 'Card 3 — title', 'type' => 'text', 'default' => 'Maternity & Gynaecology',
                ],
                'home.card.maternity.body' => [
                    'label' => 'Card 3 — text', 'type' => 'area',
                    'default' => 'Normal and caesarean delivery, high-risk pregnancy, maternity scans, '
                               . "PCOD, laparoscopic surgery and complete women's health care.",
                ],
                'home.card.emergency.title' => [
                    'label' => 'Card 4 — title', 'type' => 'text', 'default' => 'Emergency & ICU',
                ],
                'home.card.emergency.body' => [
                    'label' => 'Card 4 — text', 'type' => 'area',
                    'default' => 'Open 24 hours for accidents, chest pain, breathlessness, snake bite, '
                               . 'scorpion sting and any sudden illness, with ICU support.',
                ],
                'home.card.lab.title' => [
                    'label' => 'Card 5 — title', 'type' => 'text', 'default' => 'Laboratory & Diagnostics',
                ],
                'home.card.lab.body' => [
                    'label' => 'Card 5 — text', 'type' => 'area',
                    'default' => 'In-house laboratory for blood investigations, plus 2D Echo scanning '
                               . 'and maternity scans on site.',
                ],
                'home.card.tariff.title' => [
                    'label' => 'Card 6 — title', 'type' => 'text', 'default' => 'Transparent Tariff',
                ],
                'home.card.tariff.body' => [
                    'label' => 'Card 6 — text', 'type' => 'area',
                    'default' => 'Consultation fees and room charges published openly, so you know what '
                               . 'to expect before you arrive.',
                ],

                'home.doctors.eyebrow' => [
                    'label' => 'Doctors — small heading', 'type' => 'text', 'default' => 'Meet Our Doctors',
                ],
                'home.doctors.title' => [
                    'label' => 'Doctors — heading', 'type' => 'text',
                    'default' => 'Qualified doctors you can reach',
                ],
                'home.facilities.eyebrow' => [
                    'label' => 'Facilities — small heading', 'type' => 'text', 'default' => 'Facilities',
                ],
                'home.facilities.title' => [
                    'label' => 'Facilities — heading', 'type' => 'text',
                    'default' => 'Equipped to treat, admit and monitor',
                ],
                'home.find.eyebrow' => [
                    'label' => 'Find us — small heading', 'type' => 'text', 'default' => 'Find Us',
                ],
                'home.find.title' => [
                    'label' => 'Find us — heading', 'type' => 'text',
                    'default' => 'Easy to reach in Kandukur',
                ],
            ],
        ],
        'about' => [
            'label'  => 'About',
            'url'    => 'about.php',
            'lists'  => ['about.values'],
            'blocks' => [
                'about.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text',
                    'default' => 'About Sarada Nursing Home',
                ],
                'about.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => 'A neighbourhood nursing home on Pamuru Road, Kandukur, caring for '
                               . 'families across Prakasam district.',
                ],
                'about.who.eyebrow' => [
                    'label' => 'Who we are — small heading', 'type' => 'text', 'default' => 'Who We Are',
                ],
                'about.who.title' => [
                    'label' => 'Who we are — heading', 'type' => 'text',
                    'default' => 'Your health is our responsibility',
                ],
                'about.who.body' => [
                    'label' => 'Who we are — the main text', 'type' => 'area',
                    'hint'  => 'Leave a blank line between paragraphs. Wrap words in **stars** for bold.',
                    'default' => "Sarada Nursing Home is a full-service nursing home in Kandukur, Prakasam "
                               . "district. We bring together General Medicine, Diabetology and Obstetrics "
                               . "& Gynaecology in one place, so a family does not have to travel to a "
                               . "larger city for routine and urgent care.\n\n"
                               . "Our emergency department stays open twenty-four hours a day, every day of "
                               . "the year. Patients who need close monitoring can be admitted to our ICU, "
                               . "and our in-house laboratory means blood investigations are done on site "
                               . "rather than sent away and waited on.\n\n"
                               . "Alongside general practice, we run the **Good Health Diabetic Centre** "
                               . "for people living with diabetes — a condition that needs steady, "
                               . "long-term follow-up rather than one-off visits.\n\n"
                               . "We publish our consultation fees and room charges openly, because knowing "
                               . "the cost before you arrive is part of being treated with respect.",
                ],
                'about.values.title' => [
                    'label' => 'Principles card — heading', 'type' => 'text', 'default' => 'What guides us',
                ],
                'about.team.eyebrow' => [
                    'label' => 'Doctors — small heading', 'type' => 'text', 'default' => 'Our Team',
                ],
                'about.team.title' => [
                    'label' => 'Doctors — heading', 'type' => 'text',
                    'default' => 'The doctors who will see you',
                ],
                'about.onsite.eyebrow' => [
                    'label' => 'Facilities — small heading', 'type' => 'text', 'default' => 'On Site',
                ],
                'about.onsite.title' => [
                    'label' => 'Facilities — heading', 'type' => 'text', 'default' => 'What we have here',
                ],
            ],
        ],
        'contact' => [
            'label'  => 'Contact',
            'url'    => 'contact.php',
            'lists'  => ['contact.faq'],
            'blocks' => [
                'contact.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text', 'default' => 'Contact Us',
                ],
                'contact.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => 'Call us any time, or come and see us on Pamuru Road, Kandukur.',
                ],
                'contact.touch.eyebrow' => [
                    'label' => 'Get in touch — small heading', 'type' => 'text', 'default' => 'Get in touch',
                ],
                'contact.touch.title' => [
                    'label' => 'Get in touch — heading', 'type' => 'text', 'default' => 'We are open 24 hours',
                ],
                'contact.touch.lede' => [
                    'label' => 'Get in touch — intro', 'type' => 'area',
                    'default' => 'For emergencies call the mobile number — it is answered around the '
                               . 'clock. For appointments, tariff questions and general enquiries, the '
                               . 'landline reaches our reception.',
                ],
                'contact.find.title' => [
                    'label' => 'How to find us — heading', 'type' => 'text', 'default' => 'How to find us',
                ],
                'contact.find.body' => [
                    'label' => 'How to find us — directions', 'type' => 'area',
                    'hint'  => 'Wrap words in **stars** for bold.',
                    'default' => 'We are on **Pamuru Road** in Kandukur, directly **opposite ICICI Bank** '
                               . 'and close to **Thyagarajaswamy Temple**. Look for the Sarada Nursing '
                               . 'Home board above the entrance.',
                ],
                'contact.faq.eyebrow' => [
                    'label' => 'Questions — small heading', 'type' => 'text', 'default' => 'Before you call',
                ],
                'contact.faq.title' => [
                    'label' => 'Questions — heading', 'type' => 'text', 'default' => 'Common questions',
                ],
            ],
        ],
        'doctors' => [
            'label'  => 'Doctors',
            'url'    => 'doctors.php',
            'blocks' => [
                'doctors.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text', 'default' => 'Our Doctors',
                ],
                'doctors.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => "Two resident consultants covering general medicine, diabetes care "
                               . "and women's health.",
                ],
                'doctors.list.eyebrow' => [
                    'label' => 'List — small heading', 'type' => 'text', 'default' => 'Consultants',
                ],
                'doctors.list.title' => [
                    'label' => 'List — heading', 'type' => 'text', 'default' => 'Select your preferred doctor',
                ],
                'doctors.list.lede' => [
                    'label' => 'List — intro', 'type' => 'area',
                    'default' => 'Open a profile to see qualifications and the conditions each doctor '
                               . 'treats, or book a token straight away.',
                ],
            ],
        ],
        'gallery' => [
            'label'  => 'Gallery',
            'url'    => 'gallery.php',
            'blocks' => [
                'gallery.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text', 'default' => 'Gallery',
                ],
                'gallery.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => 'A look around Sarada Nursing Home.',
                ],
            ],
        ],
        'tariff' => [
            'label'  => 'Tariff',
            'url'    => 'tariff.php',
            'blocks' => [
                'tariff.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text', 'default' => 'Tariff & Charges',
                ],
                'tariff.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => 'Our fees are displayed at reception and published here, so you know '
                               . 'what to expect before you arrive.',
                ],
                'tariff.note.friday' => [
                    'label' => 'Notice — free OP', 'type' => 'area',
                    'hint'  => 'Wrap the opening words in **stars** to bold them.',
                    'default' => '**Free OP every Friday.** Outpatient consultations are provided free '
                               . 'of charge every Friday.',
                ],
                'tariff.note.seniors' => [
                    'label' => 'Notice — senior discount', 'type' => 'area',
                    'default' => '**20% off blood tests for patients above 60.** Please tell reception '
                               . 'your age when the test is ordered.',
                ],
                'tariff.note.small' => [
                    'label' => 'Notice — what is not included', 'type' => 'area',
                    'hint'  => '{landline} prints the current reception number.',
                    'default' => '**Please note** The charges above cover consultation, room occupancy '
                               . 'and the listed services. Medicines, laboratory investigations, '
                               . 'procedures and surgery are billed separately according to the '
                               . 'treatment given. For an estimate before admission, please ask at '
                               . 'reception or call [{landline}](tel:{landline_tel}).',
                ],
            ],
        ],
        'services' => [
            'label'  => 'Services',
            'url'    => 'services.php',
            'blocks' => [
                'services.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text', 'default' => 'Our Services',
                ],
                'services.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => "General medicine and women's health, delivered by resident "
                               . 'consultants with laboratory and ICU support on site.',
                ],
                'services.medicine.eyebrow' => [
                    'label' => 'Department 1 — small heading', 'type' => 'text', 'default' => 'Department 01',
                ],
                'services.medicine.title' => [
                    'label' => 'Department 1 — heading', 'type' => 'text', 'default' => 'General Medicine',
                ],
                'services.medicine.lede' => [
                    'label' => 'Department 1 — intro', 'type' => 'area',
                    'default' => 'Consultation, diagnosis and treatment for everyday illness and for '
                               . 'the long-term conditions that need watching year after year.',
                ],
                'services.medicine.noun' => [
                    'label' => 'Department 1 — what the list is called', 'type' => 'text',
                    'hint'  => 'Reads as "14 conditions treated".',
                    'default' => 'conditions treated',
                ],
                'services.obg.eyebrow' => [
                    'label' => 'Department 2 — small heading', 'type' => 'text', 'default' => 'Department 02',
                ],
                'services.obg.title' => [
                    'label' => 'Department 2 — heading', 'type' => 'text',
                    'default' => 'Obstetrics & Gynaecology',
                ],
                'services.obg.lede' => [
                    'label' => 'Department 2 — intro', 'type' => 'area',
                    'default' => "Pregnancy, delivery and complete women's health care, from the first "
                               . 'antenatal visit through to menopause.',
                ],
                'services.obg.noun' => [
                    'label' => 'Department 2 — what the list is called', 'type' => 'text',
                    'default' => 'procedures and services',
                ],
            ],
        ],
        'facilities' => [
            'label'  => 'Facilities',
            'url'    => 'facilities.php',
            'lists'  => ['facilities.diagnostics'],
            'blocks' => [
                'facilities.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text', 'default' => 'Our Facilities',
                ],
                'facilities.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => 'Everything on this page is available inside the building, so patients '
                               . 'are not sent elsewhere mid-treatment.',
                ],
                'facilities.rooms.eyebrow' => [
                    'label' => 'Rooms — small heading', 'type' => 'text', 'default' => 'Rooms & Admission',
                ],
                'facilities.rooms.title' => [
                    'label' => 'Rooms — heading', 'type' => 'text',
                    'default' => 'Air-conditioned rooms and wards',
                ],
                'facilities.rooms.body' => [
                    'label' => 'Rooms — text', 'type' => 'area',
                    'hint'  => 'Leave a blank line between paragraphs.',
                    'default' => "Patients who need to be admitted can choose from an air-conditioned "
                               . "single room, a sharing room, or the general ward. Every option is "
                               . "air-conditioned, and charges are published openly.\n\n"
                               . "Oxygen support and infusion are available for admitted patients, "
                               . "charged as listed on the tariff.",
                ],
                'facilities.diagnostics.eyebrow' => [
                    'label' => 'Diagnostics — small heading', 'type' => 'text', 'default' => 'Diagnostics',
                ],
                'facilities.diagnostics.title' => [
                    'label' => 'Diagnostics — heading', 'type' => 'text',
                    'default' => 'Tests done here, not sent away',
                ],
                'facilities.note.seniors' => [
                    'label' => 'Notice — senior discount', 'type' => 'area',
                    'default' => '**Patients above 60 receive 20% off blood tests.** Please mention your '
                               . 'age when the test is ordered.',
                ],
            ],
        ],
        'diabetic' => [
            'label'  => 'Diabetic Centre',
            'url'    => 'diabetic-centre.php',
            'lists'  => ['diabetic.conditions'],
            'blocks' => [
                'diabetic.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text',
                    'default' => 'Good Health Diabetic Centre',
                ],
                'diabetic.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => 'Hope for Better Life — dedicated diabetes care at Sarada Nursing Home.',
                ],
                'diabetic.why.eyebrow' => [
                    'label' => 'Why — small heading', 'type' => 'text', 'default' => 'Why a dedicated centre',
                ],
                'diabetic.why.title' => [
                    'label' => 'Why — heading', 'type' => 'text',
                    'default' => 'Diabetes needs follow-up, not just a prescription',
                ],
                'diabetic.why.body' => [
                    'label' => 'Why — text', 'type' => 'area',
                    'hint'  => 'Blank line between paragraphs. **bold**, *italic* and [links](book.php) all work.',
                    'default' => "Diabetes is not treated in a single visit. Sugar levels shift with "
                               . "diet, weight, illness, stress and age, and the medicines that worked "
                               . "last year may not be right this year. Left unchecked, it quietly "
                               . "damages the eyes, kidneys, nerves and heart.\n\n"
                               . "The Good Health Diabetic Centre exists so that patients in and around "
                               . "Kandukur have somewhere close by to be reviewed regularly, rather "
                               . "than only when something goes wrong.\n\n"
                               . "Care here is led by **Dr. Gundavarapu Venkatesh**, who holds an MD in "
                               . "General Medicine from SRM University, Chennai, and a **Diploma in "
                               . "Endocrinology & Diabetology**.",
                ],
                'diabetic.training.title' => [
                    'label' => 'Training card — heading', 'type' => 'text',
                    'default' => 'Trained in current diabetes practice',
                ],
                'diabetic.training.body' => [
                    'label' => 'Training card — text', 'type' => 'area',
                    'default' => "Dr. Venkatesh has completed *Changing the Paradigm in Type 2 Diabetes "
                               . "Mellitus Management*, a multidisciplinary diabetes self-study "
                               . "programme developed by Medical Trends and based on official "
                               . "resources of the **American Diabetes Association (ADA)**.\n\n"
                               . "It means the treatment you receive follows current international "
                               . "guidance, not habit.",
                ],
                'diabetic.cards.eyebrow' => [
                    'label' => 'Conditions — small heading', 'type' => 'text', 'default' => 'What we look after',
                ],
                'diabetic.cards.title' => [
                    'label' => 'Conditions — heading', 'type' => 'text',
                    'default' => 'Diabetes and related conditions',
                ],
            ],
        ],
        'maternity' => [
            'label'  => 'Maternity',
            'url'    => 'maternity.php',
            'lists'  => ['maternity.journey', 'maternity.gynae'],
            'blocks' => [
                'maternity.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text',
                    'default' => "Maternity & Women's Health",
                ],
                'maternity.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => 'Care through pregnancy, delivery and beyond, under Dr. Maddipudi '
                               . 'Brahmani, MBBS, MS (OBG).',
                ],
                'maternity.delivery.eyebrow' => [
                    'label' => 'Delivery — small heading', 'type' => 'text',
                    'default' => 'Pregnancy & Delivery',
                ],
                'maternity.delivery.title' => [
                    'label' => 'Delivery — heading', 'type' => 'text',
                    'default' => 'Safe delivery, close to home',
                ],
                'maternity.delivery.body' => [
                    'label' => 'Delivery — text', 'type' => 'area',
                    'hint'  => 'Blank line between paragraphs. **bold** and *italic* work.',
                    'default' => "Having a baby should not mean travelling far from your family. Our "
                               . "maternity service covers the whole journey — regular antenatal "
                               . "check-ups, maternity scanning, and delivery here at the nursing home "
                               . "with a qualified obstetrician present.\n\n"
                               . "Both **normal delivery** and **caesarean section** are carried out "
                               . "here. Pregnancies that need extra watching — **high-risk pregnancy** "
                               . "— are followed more closely, with ICU support available in the "
                               . "building if it is ever needed.\n\n"
                               . "Ectopic pregnancy and other urgent obstetric problems are treated as "
                               . "emergencies, at any hour.",
                ],
                'maternity.journey.title' => [
                    'label' => 'Pregnancy card — heading', 'type' => 'text',
                    'default' => 'Through your pregnancy',
                ],
                'maternity.gynae.eyebrow' => [
                    'label' => 'Gynaecology — small heading', 'type' => 'text', 'default' => 'Gynaecology',
                ],
                'maternity.gynae.title' => [
                    'label' => 'Gynaecology — heading', 'type' => 'text',
                    'default' => "Women's health at every stage",
                ],
                'maternity.gynae.lede' => [
                    'label' => 'Gynaecology — intro', 'type' => 'area',
                    'default' => "From the teenage years through to menopause, many women's health "
                               . 'problems are treatable once they are properly examined.',
                ],
                'maternity.urgent' => [
                    'label' => 'Urgent notice', 'type' => 'area',
                    'hint'  => '{mobile} and {mobile_tel} print the current emergency number.',
                    'default' => '**Bleeding, severe pain, or labour starting?** Do not wait for an '
                               . 'appointment. Come to the hospital or call '
                               . '[{mobile}](tel:{mobile_tel}) immediately. We are open 24 hours.',
                ],
            ],
        ],
        'emergency' => [
            'label'  => 'Emergency',
            'url'    => 'emergency.php',
            'lists'  => ['emergency.signs'],
            'blocks' => [
                'emergency.hero.title' => [
                    'label' => 'Banner — heading', 'type' => 'text', 'default' => '24/7 Emergency Care',
                ],
                'emergency.hero.lede' => [
                    'label' => 'Banner — sentence under it', 'type' => 'area',
                    'default' => 'Our emergency department is open every hour of every day, including '
                               . 'Sundays and festival days.',
                ],
                'emergency.notice' => [
                    'label' => 'Warning notice', 'type' => 'area',
                    'default' => '**Do not book a token for an emergency.** Online booking is only for '
                               . 'routine outpatient consultations. In an emergency, come straight to '
                               . 'the hospital or call us on the way.',
                ],
                'emergency.when.eyebrow' => [
                    'label' => 'When — small heading', 'type' => 'text', 'default' => 'Come in immediately',
                ],
                'emergency.when.title' => [
                    'label' => 'When — heading', 'type' => 'text',
                    'default' => 'When to treat it as an emergency',
                ],
                'emergency.when.lede' => [
                    'label' => 'When — intro', 'type' => 'area',
                    'default' => 'If any of the following is happening, do not wait for the morning '
                               . 'consultation. Bring the patient in at once.',
                ],
                'emergency.icu.eyebrow' => [
                    'label' => 'ICU — small heading', 'type' => 'text', 'default' => 'Intensive Care',
                ],
                'emergency.icu.title' => [
                    'label' => 'ICU — heading', 'type' => 'text', 'default' => 'ICU support in the building',
                ],
                'emergency.icu.body' => [
                    'label' => 'ICU — text', 'type' => 'area',
                    'hint'  => 'Blank line between paragraphs; [links](tariff.php) are allowed.',
                    'default' => "Patients who need continuous monitoring can be admitted to our ICU "
                               . "without being moved to another hospital. Oxygen and infusion support "
                               . "are available, and our in-house laboratory means urgent blood "
                               . "investigations are done on site.\n\n"
                               . "ICU and oxygen charges are published openly on our "
                               . "[tariff page](tariff.php).",
                ],
                'emergency.finding.title' => [
                    'label' => 'Directions card — heading', 'type' => 'text',
                    'default' => 'Finding us in a hurry',
                ],
                'emergency.finding.body' => [
                    'label' => 'Directions card — landmark line', 'type' => 'area',
                    'default' => 'We are on Pamuru Road, directly opposite ICICI Bank, near '
                               . 'Thyagarajaswamy Temple.',
                ],
            ],
        ],
    ];
}

/** Multi-line text for the page: escaped, with newlines becoming breaks. */
function text_html(string $key): string
{
    return nl2br(e(text($key)), false);
}

/**
 * A rich block split into paragraphs, each ready to print inside a <p>.
 *
 * Blank lines separate paragraphs — the way anyone types them — so a block of
 * several paragraphs stays one editable box rather than becoming four.
 *
 * @return list<string> HTML-safe paragraphs
 */
function text_paragraphs(string $key): array
{
    $parts = preg_split('/\R\s*\R/', trim(text($key))) ?: [];
    $out   = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $out[] = content_inline($part);
    }
    return $out;
}

/**
 * The one place raw wording becomes HTML.
 *
 * Escape first, so nothing typed into the admin panel can inject markup, then
 * put back a deliberately small set: **bold**, *italic*, [links](book.php), the live
 * figures below, and single newlines as breaks. A link's destination is
 * checked against a short allowlist of shapes — a page on this site, an
 * absolute http(s) address, a tel: or mailto: — so a pasted javascript: URL
 * is printed as text rather than honoured.
 */
function content_inline(string $raw): string
{
    $html = e(content_placeholders($raw));
    // Bold first: with ** already consumed, a lone * can only mean italic.
    $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html) ?? $html;
    // The marker has to hug its text, so "2 * 3 * 4" stays arithmetic.
    $html = preg_replace('/(?<![\*\w])\*(?!\*)(\S(?:.*?\S)?)\*(?![\*\w])/s', '<em>$1</em>', $html) ?? $html;

    $html = preg_replace_callback(
        '/\[([^\]]{1,120})\]\(([^)\s]{1,200})\)/',
        static function (array $m): string {
            $href = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            $ok = preg_match('#^https?://#i', $href)
               || preg_match('#^(tel:|mailto:)#i', $href)
               || preg_match('#^[a-z0-9\-]+\.php(\?[^\s]*)?$#i', $href)
               || preg_match('#^/[^\s]*$#', $href);
            if (!$ok) {
                return $m[1];   // keep the words, drop the destination
            }
            $external = (bool) preg_match('#^https?://#i', $href);
            return '<a href="' . e($href) . '"'
                 . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . $m[1] . '</a>';
        },
        $html
    ) ?? $html;

    return nl2br($html, false);
}

/**
 * Figures that must never drift from what the hospital actually charges.
 *
 * A sentence quoting a fee is wording reception should own, but the number in
 * it belongs to the tariff. These placeholders let them write the sentence
 * while the amount keeps coming from the tariff screen.
 */
function content_placeholders(string $raw): string
{
    if (!str_contains($raw, '{')) {
        return $raw;
    }
    $fees = defined('CONSULTATION_FEES') ? CONSULTATION_FEES : [];
    return strtr($raw, [
        '{op_fee}'        => isset($fees[0]) ? money((int) $fees[0]['amount']) : '',
        '{emergency_fee}' => isset($fees[1]) ? money((int) $fees[1]['amount']) : '',
        '{landline}'      => defined('HOSPITAL') ? HOSPITAL['landline_display'] : '',
        '{mobile}'        => defined('HOSPITAL') ? HOSPITAL['mobile_display'] : '',
        // The dialable forms, for the destination of a tel: link. The printed
        // and dialable numbers differ, so a link built from the printed one
        // would not actually call the hospital.
        '{landline_tel}'  => defined('HOSPITAL') ? HOSPITAL['landline'] : '',
        '{mobile_tel}'    => defined('HOSPITAL') ? HOSPITAL['mobile'] : '',
    ]);
}

/**
 * A block that may carry emphasis.
 *
 * Everything is escaped first, so nothing reception types can inject markup;
 * only then is a deliberately tiny subset put back — **bold**, and blank lines
 * as paragraph breaks. That is enough for the couple of places the original
 * wording emphasised a name, without handing a rich-text editor to a desk that
 * did not ask for one.
 */
function text_rich(string $key): string
{
    return content_inline(text($key));
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
        foreach (content_pages() as $page) {
            $flat += $page['blocks'];
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

/* --------------------------------------------------------------------------
   Editable lists

   Tariff rows, standing offers and the service lists are all sequences of
   short records, so one table and one editor serve all of them. A list that
   has no rows in the database is still showing the shipped defaults; saving
   one writes the whole sequence, and resetting deletes it.
   -------------------------------------------------------------------------- */

/**
 * Every editable list, with the columns it actually uses and the defaults it
 * falls back to.
 *
 * `shape` maps the stored row back into the array shape the page already
 * expects, so the templates that render these lists did not have to change.
 *
 * @return array<string, array{label:string, hint:string, uses:list<string>,
 *                             default:list<array>, shape:callable}>
 */
function content_lists(): array
{
    $money = static fn (array $r): array => [
        'label'  => $r['title'],
        'amount' => (int) $r['amount'],
        'unit'   => $r['unit'],
    ];

    return [
        'tariff.consultation' => [
            'label'   => 'Consultation fees',
            'hint'    => 'The doctor-visit charges, exactly as the tariff board reads.',
            'uses'    => ['title', 'amount'],
            'default' => array_map(
                static fn (array $r): array => ['title' => $r['label'], 'amount' => $r['amount'], 'unit' => $r['unit']],
                CONSULTATION_FEES_DEFAULTS
            ),
            'shape'   => $money,
        ],
        'tariff.rooms' => [
            'label'   => 'Room and service charges',
            'hint'    => 'Beds and per-day services. Rows marked "per day" also appear '
                       . 'on the Facilities page.',
            'uses'    => ['title', 'amount', 'unit'],
            'default' => array_map(
                static fn (array $r): array => ['title' => $r['label'], 'amount' => $r['amount'], 'unit' => $r['unit']],
                ROOM_CHARGES_DEFAULTS
            ),
            'shape'   => $money,
        ],
        'services.medicine' => [
            'label'   => 'General Medicine — conditions treated',
            'hint'    => 'The list on the Services page, and part of the structured '
                       . 'data search engines read. One condition per row.',
            'uses'    => ['title'],
            'default' => array_map(
                static fn (string $s): array => ['title' => $s],
                GENERAL_MEDICINE_DEFAULTS
            ),
            'shape'   => static fn (array $r): string => $r['title'],
        ],
        'services.obg' => [
            'label'   => 'Obstetrics & Gynaecology — procedures',
            'hint'    => 'The second Services list, and structured data alongside it.',
            'uses'    => ['title'],
            'default' => array_map(
                static fn (string $s): array => ['title' => $s],
                OBG_SERVICES_DEFAULTS
            ),
            'shape'   => static fn (array $r): string => $r['title'],
        ],
        'contact.faq' => [
            'label'   => 'Common questions',
            'hint'    => 'The questions on the Contact page. In an answer you may use '
                       . '**bold**, [links](book.php), and {op_fee}, {emergency_fee}, '
                       . '{landline} or {mobile}, which always print the current values.',
            'uses'    => ['title', 'body'],
            'default' => [
                [
                    'title' => 'Do I need an appointment?',
                    'body'  => 'Walk-in patients are always seen. Booking a token online simply saves '
                             . 'you waiting — you get a number in advance rather than taking one at the desk.',
                ],
                [
                    'title' => 'What are the OP timings?',
                    'body'  => 'Current session timings, and how many tokens are still free, are shown '
                             . 'live on the [booking page](book.php). Emergency care runs 24 hours '
                             . 'regardless of OP timings.',
                ],
                [
                    'title' => 'How much does a consultation cost?',
                    'body'  => 'OP consultation is {op_fee}, and {emergency_fee} for emergency OP after '
                             . '9 PM. Consultations are **free every Friday**. Full charges are on the '
                             . '[tariff page](tariff.php).',
                ],
                [
                    'title' => 'Can I cancel a token I booked?',
                    'body'  => 'Yes. You can [cancel it online](cancel.php) with your reference number '
                             . 'and the phone number you booked with, or call {landline} — either way '
                             . 'the token goes back to another patient.',
                ],
            ],
            'shape'   => static fn (array $r): array => ['title' => $r['title'], 'text' => $r['body']],
        ],
        'facilities.diagnostics' => [
            'label'   => 'Diagnostics on site',
            'hint'    => 'The three cards under "Tests done here" on the Facilities page.',
            'uses'    => ['title', 'body', 'icon'],
            'default' => [
                ['title' => 'Modern Laboratory', 'icon' => 'lab',
                 'body'  => 'Blood investigations and routine tests processed in our own laboratory, '
                          . 'so results reach the doctor quickly.'],
                ['title' => '2D Echo Scan', 'icon' => 'scan',
                 'body'  => 'Cardiac echo scanning on site for patients with heart complaints or '
                          . 'long-standing diabetes.'],
                ['title' => 'Maternity Scans', 'icon' => 'maternity',
                 'body'  => 'Pregnancy scanning as part of routine antenatal care and high-risk '
                          . 'pregnancy monitoring.'],
            ],
            'shape'   => static fn (array $r): array => [
                'title' => $r['title'], 'text' => $r['body'],
                'icon'  => $r['icon'] !== '' ? $r['icon'] : 'lab',
            ],
        ],
        'diabetic.conditions' => [
            'label'   => 'Diabetes centre — what we look after',
            'hint'    => 'The cards on the Diabetic Centre page.',
            'uses'    => ['title', 'body', 'icon'],
            'default' => [
                ['title' => 'Diabetes Management', 'icon' => 'droplet', 'tone' => 'gold',
                 'body'  => 'Diagnosis, medication review, sugar control and ongoing follow-up for type 2 diabetes.'],
                ['title' => 'Blood Pressure', 'icon' => 'heart', 'tone' => 'red',
                 'body'  => 'Hypertension so often travels with diabetes that the two are managed together here.'],
                ['title' => 'Thyroid Disorders', 'icon' => 'shield', 'tone' => '',
                 'body'  => 'Assessment and treatment of thyroid problems, which frequently overlap with diabetes.'],
                ['title' => 'Laboratory Investigations', 'icon' => 'lab', 'tone' => '',
                 'body'  => 'Blood sugar and related tests done in our own laboratory, so results come back quickly.'],
                ['title' => '2D Echo Scan', 'icon' => 'scan', 'tone' => '',
                 'body'  => 'Cardiac echo scanning on site, since long-standing diabetes affects the heart.'],
                ['title' => 'Complication Screening', 'icon' => 'stethoscope', 'tone' => 'green',
                 'body'  => 'Review of kidney, nerve and heart problems that can develop alongside diabetes.'],
            ],
            'uses_tone' => true,
            'shape'   => static fn (array $r): array => [
                'title' => $r['title'], 'text' => $r['body'], 'tone' => $r['tone'],
                'icon'  => $r['icon'] !== '' ? $r['icon'] : 'droplet',
            ],
        ],
        'maternity.journey' => [
            'label'   => 'Through your pregnancy',
            'hint'    => 'The checklist beside the delivery text. One item per row.',
            'uses'    => ['title'],
            'default' => array_map(static fn (string $t): array => ['title' => $t], [
                'Antenatal consultation and monitoring',
                'Maternity scans',
                'High-risk pregnancy care',
                'Normal delivery',
                'Caesarean section',
                'Ectopic pregnancy treatment',
            ]),
            'shape'   => static fn (array $r): string => $r['title'],
        ],
        'maternity.gynae' => [
            'label'   => "Women's health cards",
            'hint'    => 'The cards under "Women\'s health at every stage".',
            'uses'    => ['title', 'body', 'icon'],
            'uses_tone' => true,
            'default' => [
                ['title' => 'Menstrual Problems & PCOD', 'icon' => 'droplet', 'tone' => 'green',
                 'body'  => 'Irregular, heavy or painful periods, and polycystic ovarian disease (PCOD).'],
                ['title' => 'Infertility Treatment', 'icon' => 'heart', 'tone' => 'green',
                 'body'  => 'Assessment and treatment for couples having difficulty conceiving.'],
                ['title' => 'Laparoscopic Operations', 'icon' => 'stethoscope', 'tone' => 'green',
                 'body'  => 'Keyhole surgery, which usually means smaller wounds and a quicker recovery.'],
                ['title' => 'Hysterectomy', 'icon' => 'shield', 'tone' => 'green',
                 'body'  => 'Surgical removal of the uterus where it is medically indicated.'],
                ['title' => 'Tubectomy', 'icon' => 'users', 'tone' => 'green',
                 'body'  => 'Permanent family planning procedures, carried out here at the nursing home.'],
                ['title' => 'Menopause Care', 'icon' => 'clock', 'tone' => 'green',
                 'body'  => 'Support and treatment for the symptoms that come with menopause.'],
            ],
            'shape'   => static fn (array $r): array => [
                'title' => $r['title'], 'text' => $r['body'], 'tone' => $r['tone'],
                'icon'  => $r['icon'] !== '' ? $r['icon'] : 'heart',
            ],
        ],
        'emergency.signs' => [
            'label'   => 'Emergency warning signs',
            'hint'    => 'The cards telling patients when to come straight in.',
            'uses'    => ['title', 'body', 'icon'],
            'uses_tone' => true,
            'default' => [
                ['title' => 'Snake Bite & Scorpion Sting', 'icon' => 'droplet', 'tone' => 'red',
                 'body'  => 'Bring the patient in immediately. Keep them still and calm, and do not '
                          . 'cut, suck or tie the wound tightly.'],
                ['title' => 'Chest Pain', 'icon' => 'heart', 'tone' => 'red',
                 'body'  => 'Sudden chest pain, pain spreading to the arm or jaw, or heavy sweating with it.'],
                ['title' => 'Breathlessness', 'icon' => 'icu', 'tone' => 'red',
                 'body'  => 'Serious difficulty breathing, a severe asthma attack, or the lips turning blue.'],
                ['title' => 'Paralysis or Stroke', 'icon' => 'alert', 'tone' => 'red',
                 'body'  => 'Sudden weakness on one side, a drooping face, slurred speech or loss of consciousness.'],
                ['title' => 'High Fever & Fits', 'icon' => 'emergency', 'tone' => 'red',
                 'body'  => 'Very high fever, fits, severe dengue or malaria symptoms, or a child who has become drowsy.'],
                ['title' => 'Obstetric Emergency', 'icon' => 'maternity', 'tone' => 'red',
                 'body'  => 'Labour starting, bleeding in pregnancy, or severe abdominal pain.'],
            ],
            'shape'   => static fn (array $r): array => [
                'title' => $r['title'], 'text' => $r['body'], 'tone' => $r['tone'],
                'icon'  => $r['icon'] !== '' ? $r['icon'] : 'emergency',
            ],
        ],
        'hero.slides' => [
            'label'   => 'Home page slideshow',
            'hint'    => 'Each row is one slide, in order. The picture for slide 1 comes '
                       . 'from "Slide 1" under Pictures, and so on. Keep these to what the '
                       . 'hospital actually offers — this is the first thing a patient reads.',
            'uses'    => ['title', 'body', 'icon'],
            'default' => [
                ['title' => 'Emergency & ICU, around the clock', 'icon' => 'emergency',
                 'body'  => 'Open every hour of every day for accidents, chest pain, snake bite '
                          . 'and sudden illness — with ICU support in the building.'],
                ['title' => 'General Medicine', 'icon' => 'stethoscope',
                 'body'  => 'Fevers, dengue and malaria, blood pressure, thyroid, asthma, and '
                          . 'heart and kidney problems — seen by a resident MD physician.'],
                ['title' => 'Good Health Diabetic Centre', 'icon' => 'droplet',
                 'body'  => 'Dedicated diabetes care under a doctor with a Diploma in '
                          . 'Endocrinology & Diabetology — diagnosis, control and follow-up.'],
                ['title' => 'Maternity & Gynaecology', 'icon' => 'maternity',
                 'body'  => 'Antenatal care, normal and caesarean delivery, and complete '
                          . "women's health under a resident MS obstetrician."],
                ['title' => 'Laboratory & 2D Echo', 'icon' => 'lab',
                 'body'  => 'Blood investigations, 2D Echo and maternity scans done on site, '
                          . 'so results reach your doctor the same visit.'],
            ],
            'shape'   => static fn (array $r): array => [
                'title' => $r['title'], 'text' => $r['body'],
                'icon'  => $r['icon'] !== '' ? $r['icon'] : 'heart',
            ],
        ],
        'about.values' => [
            'label'   => 'What guides us',
            'hint'    => 'The principles listed on the About page. One per row.',
            'uses'    => ['title'],
            'default' => array_map(
                static fn (string $t): array => ['title' => $t],
                [
                    'Always open — emergencies do not keep office hours',
                    'Clear, published pricing with no surprises',
                    'Qualified consultants you can actually see',
                    'Care close to home, in your own town',
                    'Free OP every Friday, and senior discounts on tests',
                ]
            ),
            'shape'   => static fn (array $r): string => $r['title'],
        ],
        'facilities' => [
            'label'   => 'Facilities',
            'hint'    => 'The cards on the home page, the About page and Facilities. '
                       . 'Each needs a short title, a sentence and an icon.',
            'uses'    => ['title', 'body', 'icon'],
            'default' => array_map(
                static fn (array $r): array => ['title' => $r['title'], 'body' => $r['text'], 'icon' => $r['icon']],
                FACILITIES_DEFAULTS
            ),
            'shape'   => static fn (array $r): array => [
                'title' => $r['title'],
                'text'  => $r['body'],
                'icon'  => $r['icon'] !== '' ? $r['icon'] : 'shield',
            ],
        ],
        'offers' => [
            'label'   => 'Standing offers',
            'hint'    => 'The band shown across the home page. Keep these to what the '
                       . 'hospital actually offers — patients arrive expecting them.',
            'uses'    => ['title', 'body', 'icon'],
            'default' => array_map(
                static fn (array $r): array => ['title' => $r['title'], 'body' => $r['text'], 'icon' => $r['icon']],
                OFFERS_DEFAULTS
            ),
            'shape'   => static fn (array $r): array => [
                'title' => $r['title'],
                'text'  => $r['body'],
                'icon'  => $r['icon'] !== '' ? $r['icon'] : 'award',
            ],
        ],
    ];
}

/** The accent colours a card list may use. '' is the default navy. */
function content_tones(): array
{
    return ['' => 'Default', 'gold' => 'Gold', 'red' => 'Red', 'green' => 'Green'];
}

/** Raw stored rows for every list, keyed by list. Read once per request. */
function list_rows(bool $reload = false): array
{
    static $cache = null;

    if ($cache === null || $reload) {
        $cache = [];
        try {
            $sql = 'SELECT list_key, title, body, icon, tone, amount, unit
                      FROM list_items ORDER BY list_key, sort_order, id';
            foreach (db()->query($sql) as $row) {
                $cache[$row['list_key']][] = $row;
            }
        } catch (PDOException $e) {
            error_log('Lists unavailable: ' . $e->getMessage());
        }
    }

    return $cache;
}

function list_forget(): void
{
    list_rows(true);
}

/**
 * The rows to edit for a list: what reception saved, or the defaults when they
 * have not touched it yet.
 *
 * @return list<array{title:string, body:string, icon:string, amount:?int, unit:string}>
 */
function list_editable(string $key): array
{
    $stored = list_rows()[$key] ?? [];
    if ($stored) {
        return array_map(static fn (array $r): array => [
            'title'  => (string) $r['title'],
            'body'   => (string) $r['body'],
            'icon'   => (string) $r['icon'],
            'tone'   => (string) ($r['tone'] ?? ''),
            'amount' => $r['amount'] === null ? null : (int) $r['amount'],
            'unit'   => (string) $r['unit'],
        ], $stored);
    }

    return array_map(static fn (array $r): array => [
        'title'  => (string) ($r['title'] ?? ''),
        'body'   => (string) ($r['body'] ?? ''),
        'icon'   => (string) ($r['icon'] ?? ''),
        'tone'   => (string) ($r['tone'] ?? ''),
        'amount' => isset($r['amount']) ? (int) $r['amount'] : null,
        'unit'   => (string) ($r['unit'] ?? ''),
    ], content_lists()[$key]['default'] ?? []);
}

/** True once reception has saved their own version of a list. */
function list_is_edited(string $key): bool
{
    return !empty(list_rows()[$key]);
}

/**
 * Replace a list wholesale. Rewriting rather than reconciling row by row keeps
 * the editor honest: what the form showed is exactly what ends up stored, and
 * reordering needs no identity tracking.
 */
function list_save(string $key, array $rows): void
{
    if (!isset(content_lists()[$key])) {
        return;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM list_items WHERE list_key = ?')->execute([$key]);
        $insert = $pdo->prepare(
            'INSERT INTO list_items (list_key, sort_order, title, body, icon, tone, amount, unit)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        foreach (array_values($rows) as $i => $row) {
            $insert->execute([
                $key,
                $i,
                mb_substr(trim((string) ($row['title'] ?? '')), 0, 160),
                mb_substr(trim((string) ($row['body'] ?? '')), 0, 400),
                mb_substr(trim((string) ($row['icon'] ?? '')), 0, 40),
                mb_substr(trim((string) ($row['tone'] ?? '')), 0, 20),
                ($row['amount'] ?? '') === '' || $row['amount'] === null ? null : (int) $row['amount'],
                mb_substr(trim((string) ($row['unit'] ?? '')), 0, 40),
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    list_forget();
}

/** Drop reception's version so the list tracks the shipped defaults again. */
function list_reset(string $key): void
{
    db()->prepare('DELETE FROM list_items WHERE list_key = ?')->execute([$key]);
    list_forget();
}

/** A list in the array shape its page already expects. */
function list_shaped(string $key): array
{
    $shape = content_lists()[$key]['shape'] ?? null;
    $rows  = list_editable($key);
    return $shape === null ? $rows : array_map($shape, $rows);
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

    // Same trick for the lists: the pages keep reading CONSULTATION_FEES and
    // friends in the shape they always had, unaware that the rows may now come
    // from the database.
    if (SNH_CONFIGURED) {
        define('CONSULTATION_FEES', list_shaped('tariff.consultation'));
        define('ROOM_CHARGES', list_shaped('tariff.rooms'));
        define('OFFERS', list_shaped('offers'));
        define('GENERAL_MEDICINE', list_shaped('services.medicine'));
        define('OBG_SERVICES', list_shaped('services.obg'));
        define('FACILITIES', list_shaped('facilities'));
    } else {
        define('CONSULTATION_FEES', CONSULTATION_FEES_DEFAULTS);
        define('ROOM_CHARGES', ROOM_CHARGES_DEFAULTS);
        define('OFFERS', OFFERS_DEFAULTS);
        define('GENERAL_MEDICINE', GENERAL_MEDICINE_DEFAULTS);
        define('OBG_SERVICES', OBG_SERVICES_DEFAULTS);
        define('FACILITIES', FACILITIES_DEFAULTS);
    }
}
