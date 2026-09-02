<?php
/**
 * Waiting-hall display board.
 *
 * Meant for a TV or monitor at reception, left open all day: which token each
 * doctor is serving right now, in figures large enough to read across the
 * hall. Deliberately public and login-free — a display board cannot type a
 * password — and it shows token numbers only, never patient names.
 *
 * The page refreshes its numbers every 12 seconds and reloads itself fully
 * once an hour, so an updated site rolls onto the screen without anyone
 * touching the TV.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/booking.php';

$board = [];
foreach (get_doctors() as $doctor) {
    $queue = doctor_queue_today((int) $doctor['id']);
    if ($queue !== null) {
        $board[] = ['doctor' => $doctor, 'queue' => $queue];
    }
}
?>
<!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<!-- The screen on the waiting-room wall, not a page anyone should find
     in a search result. -->
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Now Serving — <?= e(HOSPITAL['name']) ?></title>
<link rel="icon" href="assets/img/logo/badge-48.png">
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css')) ?>">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html { font-size: clamp(10px, 1.25vw, 22px); }
  body {
    min-height: 100vh; display: flex; flex-direction: column;
    background: linear-gradient(160deg, #061626 0%, #0a2540 55%, #103356 100%);
    color: #eef4fa; font-family: 'Inter', system-ui, sans-serif;
  }

  .top {
    display: flex; align-items: center; gap: 1rem;
    padding: 1.1rem 2rem; border-bottom: 1px solid rgba(185, 205, 226, .18);
  }
  .top img { width: 3.4rem; height: 3.4rem; }
  .brand { display: flex; flex-direction: column; }
  .brand b {
    font-family: 'Newsreader', Georgia, serif; font-weight: 750;
    font-size: 1.7rem; line-height: 1.1; letter-spacing: .01em;
  }
  .brand b .hl { color: #f0857f; }
  .brand span { font-size: .85rem; color: #b9cde2; letter-spacing: .14em; text-transform: uppercase; }
  .clock { margin-left: auto; text-align: right; }
  .clock b { font-size: 1.9rem; font-weight: 650; font-variant-numeric: tabular-nums; display: block; line-height: 1.15; }
  .clock span { font-size: .9rem; color: #b9cde2; }

  .board {
    flex: 1; display: grid; gap: 1.6rem; padding: 1.8rem 2rem; align-content: center;
    grid-template-columns: repeat(auto-fit, minmax(24rem, 1fr));
  }
  .panel {
    background: rgba(238, 244, 250, .06);
    border: 1px solid rgba(185, 205, 226, .22);
    border-radius: 1.1rem; padding: 1.6rem 1.8rem;
    display: flex; flex-direction: column; gap: 1.1rem;
  }
  .panel-head .doc {
    font-family: 'Newsreader', Georgia, serif; font-weight: 650;
    font-size: 1.75rem; line-height: 1.15;
  }
  .panel-head .sess { color: #b9cde2; font-size: 1rem; margin-top: .25rem; }

  .serving { display: flex; align-items: center; gap: 1.6rem; }
  .num {
    font-size: 7.5rem; line-height: 1; font-weight: 800;
    font-variant-numeric: tabular-nums; letter-spacing: -.02em;
    color: #e0b64e; text-shadow: 0 .2rem 1.4rem rgba(0, 0, 0, .35);
    min-width: 2ch; text-align: center;
  }
  .serving .cap { font-size: 1rem; letter-spacing: .18em; text-transform: uppercase; color: #b9cde2; }
  .serving .meta { display: flex; flex-direction: column; gap: .55rem; }
  .waiting { font-size: 1.35rem; font-weight: 600; }
  .state {
    align-self: flex-start; font-size: .85rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase;
    padding: .3rem .8rem; border-radius: 99px;
  }
  .state-running, .state-overrun { background: rgba(26, 131, 84, .35); color: #cfe9dc; }
  .state-upcoming { background: rgba(201, 151, 31, .3); color: #f6e8c8; }
  .state-ended { background: rgba(185, 205, 226, .18); color: #b9cde2; }

  .empty {
    grid-column: 1 / -1; text-align: center; color: #b9cde2;
    font-size: 1.6rem; font-family: 'Newsreader', Georgia, serif;
  }

  .foot {
    display: flex; justify-content: space-between; gap: 2rem; flex-wrap: wrap;
    padding: 1rem 2rem; border-top: 1px solid rgba(185, 205, 226, .18);
    color: #b9cde2; font-size: 1rem;
  }
  .foot b { color: #eef4fa; }

  /* No token served yet: a quiet dash, not a giant gold bar. */
  .num.is-none { font-size: 3.4rem; line-height: 2.2; color: #b9cde2; opacity: .7; }

  /* The number changing is the whole point — let it announce itself. */
  .num.bump { animation: bump .6s ease; }
  @keyframes bump {
    0% { transform: scale(1); }
    35% { transform: scale(1.18); color: #eef4fa; }
    100% { transform: scale(1); }
  }
</style>
</head>
<body>

<header class="top">
  <img src="assets/img/logo/badge-96.png" alt="" width="96" height="96">
  <div class="brand">
    <b><span class="hl">Sarada</span> Nursing Home</b>
    <span>Now serving</span>
  </div>
  <div class="clock">
    <b id="clock">--:--</b>
    <span id="date"><?= e(format_date(date('Y-m-d'))) ?></span>
  </div>
</header>

<main class="board" id="board">
  <?php if (!$board): ?>
    <p class="empty">No outpatient sessions today. Emergency care is open 24 hours.</p>
  <?php else: ?>
    <?php foreach ($board as $row): $q = $row['queue']; ?>
      <section class="panel" data-doctor="<?= (int) $row['doctor']['id'] ?>">
        <div class="panel-head">
          <div class="doc"><?= e($row['doctor']['name']) ?></div>
          <div class="sess"><?= e($q['label']) ?> session · <?= e($q['timing']) ?></div>
        </div>
        <div class="serving">
          <div>
            <div class="cap">Token</div>
            <div class="num<?= $q['now_serving'] > 0 ? '' : ' is-none' ?>"><?=
              $q['now_serving'] > 0 ? (int) $q['now_serving'] : '—' ?></div>
          </div>
          <div class="meta">
            <span class="state state-<?= e($q['state']) ?>"><?=
              $q['state'] === 'upcoming' ? 'Starts soon'
              : ($q['state'] === 'ended' ? 'Session over' : 'In progress') ?></span>
            <span class="waiting"><?= (int) $q['waiting'] ?> waiting</span>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</main>

<footer class="foot">
  <span>Please keep your token slip ready when your number is called.</span>
  <span>Emergency 24×7 · <b><?= e(HOSPITAL['mobile_display']) ?></b></span>
</footer>

<script>
(function () {
  'use strict';

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function clock() {
    var d = new Date(), h = d.getHours(), m = d.getMinutes();
    var ap = h >= 12 ? 'pm' : 'am';
    h = h % 12 || 12;
    document.getElementById('clock').textContent =
      h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap;
  }
  clock();
  setInterval(clock, 5000);

  var stateText = { upcoming: 'Starts soon', ended: 'Session over',
                    running: 'In progress', overrun: 'In progress' };

  function render(doctors) {
    var board = document.getElementById('board');
    if (!doctors.length) return;

    // Numbers that changed get the bump; remember the old ones first.
    var previous = {};
    board.querySelectorAll('.panel').forEach(function (p) {
      previous[p.getAttribute('data-doctor')] =
        p.querySelector('.num').textContent.trim();
    });

    var html = '';
    doctors.forEach(function (d) {
      var num = d.now_serving > 0 ? String(d.now_serving) : '—';
      var bump = previous[String(d.id)] !== undefined &&
                 previous[String(d.id)] !== num;
      html +=
        '<section class="panel" data-doctor="' + d.id + '">' +
          '<div class="panel-head">' +
            '<div class="doc">' + esc(d.name) + '</div>' +
            '<div class="sess">' + esc(d.label) + ' session · ' + esc(d.timing) + '</div>' +
          '</div>' +
          '<div class="serving">' +
            '<div><div class="cap">Token</div>' +
            '<div class="num' + (bump ? ' bump' : '') +
              (d.now_serving > 0 ? '' : ' is-none') + '">' + num + '</div></div>' +
            '<div class="meta">' +
              '<span class="state state-' + esc(d.state) + '">' +
                (stateText[d.state] || 'In progress') + '</span>' +
              '<span class="waiting">' + d.waiting + ' waiting</span>' +
            '</div>' +
          '</div>' +
        '</section>';
    });
    board.innerHTML = html;
  }

  function tick() {
    fetch('queue-status.php', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
      .then(function (data) { render(data.doctors || []); })
      .catch(function () { /* transient — next tick */ });
  }

  setInterval(tick, 12000);

  // A full reload once an hour picks up new code and rolls past any drift.
  setTimeout(function () { window.location.reload(); }, 60 * 60 * 1000);
})();
</script>
</body>
</html>
