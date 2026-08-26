/* Token booking — loads live session availability as the patient chooses. */
(function () {
  'use strict';

  var form = document.getElementById('booking-form');
  if (!form) return;

  var sessionBox = document.getElementById('session-options');
  if (!sessionBox) return;

  var submitBtn = form.querySelector('[type="submit"]');
  var requestId = 0;

  function selected(name) {
    var el = form.querySelector('input[name="' + name + '"]:checked');
    return el ? el.value : '';
  }

  function setBusy(busy) {
    if (submitBtn) submitBtn.disabled = busy;
  }

  function render(sessions) {
    if (!sessions.length) {
      sessionBox.innerHTML =
        '<div class="notice notice-warn mb-0">' +
        '<p>There is no consultation scheduled on this day for the doctor you selected. ' +
        'Please choose another date, or call us to confirm.</p></div>';
      return;
    }

    var html = '<div class="choice-grid cols-2">';

    sessions.forEach(function (s) {
      var id = 'sess-' + s.session;
      var disabled = s.available ? '' : ' disabled';
      var meta;

      if (s.available) {
        var few = s.remaining <= 5;
        meta = '<span class="pill ' + (few ? 'pill-gold' : 'pill-green') + '">' +
               '<span class="tokens-left">' + s.remaining + '</span> token' +
               (s.remaining === 1 ? '' : 's') + ' left</span>';
      } else {
        meta = '<span class="pill pill-grey">' + escapeHtml(s.reason || 'Unavailable') + '</span>';
      }

      html +=
        '<div class="choice">' +
          '<input type="radio" name="session" id="' + id + '" value="' +
            escapeHtml(s.session) + '"' + disabled + ' required>' +
          '<label class="choice-label" for="' + id + '">' +
            '<span>' +
              '<span class="choice-title">' + escapeHtml(s.label) + '</span>' +
              '<span class="choice-sub">' + escapeHtml(s.timing) + '</span>' +
              '<span class="choice-meta">' + meta + '</span>' +
            '</span>' +
          '</label>' +
        '</div>';
    });

    html += '</div>';
    sessionBox.innerHTML = html;
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function refresh() {
    var doctorId = selected('doctor_id');
    var date = selected('booking_date');

    if (!doctorId || !date) {
      sessionBox.innerHTML =
        '<div class="notice notice-info mb-0"><p>Choose a doctor and a date to see available sessions.</p></div>';
      return;
    }

    var myRequest = ++requestId;
    setBusy(true);
    sessionBox.innerHTML =
      '<div class="session-loading"><span class="spinner"></span>Checking available tokens…</div>';

    fetch('availability.php?doctor_id=' + encodeURIComponent(doctorId) +
          '&date=' + encodeURIComponent(date), {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function (data) {
        // A slower earlier request must not overwrite a newer answer.
        if (myRequest !== requestId) return;
        render(data.sessions || []);
        setBusy(false);
      })
      .catch(function () {
        if (myRequest !== requestId) return;
        sessionBox.innerHTML =
          '<div class="notice notice-warn mb-0"><p>We could not load the available sessions. ' +
          'Please check your connection and try again, or call the hospital to book.</p></div>';
        setBusy(false);
      });
  }

  form.addEventListener('change', function (ev) {
    var name = ev.target.name;
    if (name === 'doctor_id' || name === 'booking_date') {
      refresh();
    }
  });

  refresh();
})();
