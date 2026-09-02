<?php
/**
 * Hospital details used across every page.
 *
 * Everything here is taken directly from the nursing home's own signage,
 * letterhead and brochure. Nothing is invented — if a fact is not on those
 * materials it does not belong in this file.
 *
 * These are the *defaults*. Reception can override any of them from the admin
 * panel; content.php lays those edits over this array and defines the HOSPITAL
 * constant that the rest of the site reads. Editing this file therefore
 * changes what an un-edited field says, which is what it should do.
 */

const HOSPITAL_DEFAULTS = [
    'name'        => 'Sarada Nursing Home',
    'tagline'     => 'Your Health is Our Responsibility',
    'sub_brand'   => 'Good Health Diabetic Centre',
    'sub_tagline' => 'Hope for Better Life',
    'address'     => [
        'line1'    => 'Opposite ICICI Bank, Near Thyagarajaswamy Temple',
        'line2'    => 'Pamuru Road, Kandukur',
        'district' => 'Prakasam District, Andhra Pradesh',
        // Feeds the postal code in the search-engine markup, where a wrong one
        // is worse than none. Clear it if it is ever in doubt.
        'pin'      => '523105',
    ],
    // Emergency / mobile — shown as the primary 24x7 call button
    'mobile'      => '+918341254590',
    'mobile_display' => '83412 54590',
    // Landline — general enquiries and appointments
    'landline'    => '+918598222299',
    'landline_display' => '08598-222299',
    'whatsapp'    => '918341254590',
    'map' => [
        'lat'   => 15.2137371,
        'lng'   => 79.9028145,
        'link'  => 'https://maps.app.goo.gl/Timjzuzhry5KNYG79',
    ],
];

/** Consultation fees, exactly as displayed on the tariff board. */
const CONSULTATION_FEES_DEFAULTS = [
    ['label' => 'OP Consultation', 'amount' => 200, 'unit' => ''],
    ['label' => 'Emergency OP after 9 PM', 'amount' => 400, 'unit' => ''],
];

/** Room and service charges, exactly as displayed on the tariff board. */
const ROOM_CHARGES_DEFAULTS = [
    ['label' => 'ICU',                 'amount' => 3000, 'unit' => 'per day'],
    ['label' => 'Single Room (A/C)',   'amount' => 1700, 'unit' => 'per day'],
    ['label' => 'Sharing Room (A/C)',  'amount' => 1600, 'unit' => 'per day'],
    ['label' => 'General Ward (A/C)',  'amount' => 1400, 'unit' => 'per day'],
    ['label' => 'Infusion Charges',    'amount' => 1000, 'unit' => 'per day'],
    ['label' => 'Oxygen Charges',      'amount' => 200,  'unit' => 'per hour'],
];

/** Standing offers printed on the hospital letterhead. */
const OFFERS_DEFAULTS = [
    [
        'title' => 'Free OP Every Friday',
        'text'  => 'Outpatient consultations are provided free of charge every Friday.',
        'icon'  => 'calendar',
    ],
    [
        'title' => '20% Off for Seniors',
        'text'  => 'Patients above 60 years receive a 20% discount on blood tests.',
        'icon'  => 'discount',
    ],
];

/** General Medicine conditions treated, from the services brochure. */
const GENERAL_MEDICINE_DEFAULTS = [
    'Diabetes (Sugar) & Blood Pressure',
    'Heart & Kidney Problems',
    'Paralysis & Stroke',
    'Thyroid Disorders',
    'All Types of Fever',
    'Dengue & Malaria',
    'Snake Bite & Scorpion Sting',
    'Asthma & Tuberculosis',
    'Rheumatology',
    'Skin Diseases',
    'Neurological Problems',
    'Lung Problems',
    'Liver Problems',
    '2D Echo Scan',
];

/** Obstetrics & Gynaecology services, from the services brochure. */
const OBG_SERVICES_DEFAULTS = [
    'Normal Delivery',
    'Caesarean Section',
    'High Risk Pregnancy',
    'PCOD Treatment',
    'Menstrual Problems',
    'Hysterectomy',
    'Ectopic Pregnancy',
    'Laparoscopic Operations',
    'Infertility Treatment',
    'Tubectomy Operations',
    'Menopause Care',
    'Maternity Scans',
];

/** Core facilities, from the brochure footer. */
const FACILITIES_DEFAULTS = [
    [
        'title' => '24/7 Emergency Care',
        'text'  => 'Round-the-clock emergency treatment, every day of the year.',
        'icon'  => 'emergency',
    ],
    [
        'title' => 'ICU Care Facility',
        'text'  => 'Intensive care unit for patients who need close monitoring.',
        'icon'  => 'icu',
    ],
    [
        'title' => 'Modern Laboratory',
        'text'  => 'In-house laboratory for diagnostic and routine blood investigations.',
        'icon'  => 'lab',
    ],
    [
        'title' => '2D Echo Scan',
        'text'  => 'Cardiac echo scanning available on site.',
        'icon'  => 'scan',
    ],
    [
        'title' => 'A/C Rooms & Wards',
        'text'  => 'Air-conditioned single rooms, sharing rooms and general ward.',
        'icon'  => 'room',
    ],
    [
        'title' => 'Maternity Services',
        'text'  => 'Delivery suite and maternity scanning for expecting mothers.',
        'icon'  => 'maternity',
    ],
];
