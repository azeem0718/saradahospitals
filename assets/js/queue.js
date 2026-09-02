/*
 * Live queue — keeps queue.php current without anyone pressing reload.
 *
 * The page arrives fully rendered from the server; this only re-fetches the
 * same numbers every 20 seconds and writes them back in. If the patient's own
 * booking changes status (reception marks them arrived, completed…), the page
 * reloads once so the server can re-render the notices around the numbers.
 */
(function () {
  'use strict';

  var root = document.getElementById('queue-root');
  if (!root || !window.fetch) return;

  var board = document.getElementById('queue-board');
  var ref = root.getAttribute('data-ref') || '';
  var lastStatus = root.getAttribute('data-status') || '';
  var EVERY = 20000;
  var timer = null;

  function esc(str) {
    return String(str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function set(name, text) {
    var el = root.querySelector('[data-q="' + name + '"]');
    if (el) el.textContent = text;
  }

  function statePill(state) {
    if (state === 'upcoming') return ['pill-gold', 'Starts soon'];
    if (state === 'ended') return ['pill-grey', 'Session over'];
    return ['pill-green', 'In progress'];
  }

  function renderBoard(doctors) {
    if (!board || !doctors.length) return;

    var html = '';
    doctors.forEach(function (d) {
      var pill = statePill(d.state);
      html +=
        '<article class="queue-card is-' + esc(d.state) + '">' +
          '<div class="queue-card-head">' +
            '<span class="q-doctor">' + esc(d.name) + '</span>' +
            '<span class="q-session">' + esc(d.label) + ' · ' + esc(d.timing) + '</span>' +
          '</div>' +
          '<div class="queue-card-body">' +
            '<div class="q-stat">' +
              '<span class="q-label">Now serving</span>' +
              '<span class="q-num">' + (d.now_serving > 0 ? d.now_serving : '—') + '</span>' +
            '</div>' +
            '<div class="q-side">' +
              '<span class="pill ' + pill[0] + '">' + pill[1] + '</span>' +
              '<span class="q-waiting">' + d.waiting + ' waiting</span>' +
            '</div>' +
          '</div>' +
        '</article>';
    });
    board.innerHTML = html;
  }

  function renderMine(b) {
    if (!b) return;

    // Status changed since the page was rendered: the surrounding notices are
    // the server's job, so take one full round trip.
    if (lastStatus && b.status !== lastStatus) {
      window.location.reload();
      return;
    }

    if (!b.today) return;
    set('now-serving', b.now_serving > 0 ? String(b.now_serving) : '—');

    var live = b.status === 'booked' || b.status === 'arrived';
    set('ahead', live ? String(b.ahead) : '—');
    if (live) {
      set('hint', b.ahead === 0
        ? 'You are next — please be near the consultation room.'
        : (b.ahead <= 3
          ? 'Almost your turn — please be at the hospital.'
          : 'Reference ' + b.reference + ' · this page updates itself every few seconds.'));
    }
  }

  function tick() {
    fetch('queue-status.php' + (ref ? '?ref=' + encodeURIComponent(ref) : ''), {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function (data) {
        renderBoard(data.doctors || []);
        renderMine(data.booking);
        if (data.updated) set('updated', data.updated);
      })
      .catch(function () { /* transient — the next tick tries again */ });
  }

  function start() {
    if (timer === null) timer = window.setInterval(tick, EVERY);
  }

  function stop() {
    if (timer !== null) { window.clearInterval(timer); timer = null; }
  }

  // A phone in a pocket should not keep polling; catch up the moment the page
  // is looked at again.
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) { stop(); } else { tick(); start(); }
  });

  start();
})();
