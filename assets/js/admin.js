/* Reception panel behaviour. */
(function () {
  'use strict';

  /* Confirm destructive actions before they submit. */
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        ev.preventDefault();
      }
    });
  });

  /* Print buttons (the OP register). */
  document.querySelectorAll('[data-print]').forEach(function (btn) {
    btn.addEventListener('click', function () { window.print(); });
  });

  /* Auto-submit filter selects so reception does not hunt for a button. */
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () {
      if (el.form) el.form.submit();
    });
  });
})();
