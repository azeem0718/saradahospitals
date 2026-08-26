<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Maternity & Women\'s Health';
$pageDescription = 'Maternity care at Sarada Nursing Home, Kandukur — normal delivery, caesarean, high-risk pregnancy, maternity scans, PCOD, laparoscopic surgery and infertility treatment.';
$activeNav       = 'maternity';

require __DIR__ . '/includes/header.php';
page_hero(
    'Maternity & Women\'s Health',
    'Care through pregnancy, delivery and beyond, under Dr. Maddipudi Brahmani, MBBS, MS (OBG).',
    'Maternity',
    'maternity'
);
?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow">Pregnancy &amp; Delivery</span>
        <h2>Safe delivery, close to home</h2>
        <p>
          Having a baby should not mean travelling far from your family. Our
          maternity service covers the whole journey — regular antenatal check-ups,
          maternity scanning, and delivery here at the nursing home with a
          qualified obstetrician present.
        </p>
        <p>
          Both <strong>normal delivery</strong> and <strong>caesarean section</strong>
          are carried out here. Pregnancies that need extra watching —
          <strong>high-risk pregnancy</strong> — are followed more closely, with ICU
          support available in the building if it is ever needed.
        </p>
        <p class="mb-0">
          Ectopic pregnancy and other urgent obstetric problems are treated as
          emergencies, at any hour.
        </p>
      </div>

      <div class="card">
        <span class="card-icon green"><?= icon('maternity') ?></span>
        <h3>Through your pregnancy</h3>
        <ul class="service-list mt-2" style="margin-bottom:0">
          <li><?= icon('check') ?><span>Antenatal consultation and monitoring</span></li>
          <li><?= icon('check') ?><span>Maternity scans</span></li>
          <li><?= icon('check') ?><span>High-risk pregnancy care</span></li>
          <li><?= icon('check') ?><span>Normal delivery</span></li>
          <li><?= icon('check') ?><span>Caesarean section</span></li>
          <li><?= icon('check') ?><span>Ectopic pregnancy treatment</span></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">Gynaecology</span>
      <h2>Women's health at every stage</h2>
      <p class="lede">
        From the teenage years through to menopause, many women's health problems
        are treatable once they are properly examined.
      </p>
    </div>

    <div class="grid grid-3">
      <div class="card">
        <span class="card-icon green"><?= icon('droplet') ?></span>
        <h3>Menstrual Problems &amp; PCOD</h3>
        <p>Irregular, heavy or painful periods, and polycystic ovarian disease (PCOD).</p>
      </div>
      <div class="card">
        <span class="card-icon green"><?= icon('heart') ?></span>
        <h3>Infertility Treatment</h3>
        <p>Assessment and treatment for couples having difficulty conceiving.</p>
      </div>
      <div class="card">
        <span class="card-icon green"><?= icon('stethoscope') ?></span>
        <h3>Laparoscopic Operations</h3>
        <p>Keyhole surgery, which usually means smaller wounds and a quicker recovery.</p>
      </div>
      <div class="card">
        <span class="card-icon green"><?= icon('shield') ?></span>
        <h3>Hysterectomy</h3>
        <p>Surgical removal of the uterus where it is medically indicated.</p>
      </div>
      <div class="card">
        <span class="card-icon green"><?= icon('users') ?></span>
        <h3>Tubectomy</h3>
        <p>Permanent family planning procedures, carried out here at the nursing home.</p>
      </div>
      <div class="card">
        <span class="card-icon green"><?= icon('clock') ?></span>
        <h3>Menopause Care</h3>
        <p>Support and treatment for the symptoms that come with menopause.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap wrap-narrow">
    <div class="notice notice-emergency mb-0">
      <?= icon('alert') ?>
      <p>
        <strong>Bleeding, severe pain, or labour starting?</strong>
        Do not wait for an appointment. Come to the hospital or call
        <a href="tel:<?= e(HOSPITAL['mobile']) ?>"><?= e(HOSPITAL['mobile_display']) ?></a>
        immediately. We are open 24 hours.
      </p>
    </div>
  </div>
</section>

<?php cta_band('Book with Dr. Brahmani', 'Antenatal check-ups and gynaecology consultations can be booked online.'); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
