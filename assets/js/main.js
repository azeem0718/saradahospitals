/* Sarada Nursing Home — site behaviour. No dependencies. */
(function () {
  'use strict';

  /* Mobile navigation ------------------------------------------------- */
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('primary-nav');

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('open', !open);
    });

    // Close when a link is followed or focus leaves the menu entirely.
    nav.addEventListener('click', function (ev) {
      if (ev.target.closest('a')) {
        toggle.setAttribute('aria-expanded', 'false');
        nav.classList.remove('open');
      }
    });

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && nav.classList.contains('open')) {
        toggle.setAttribute('aria-expanded', 'false');
        nav.classList.remove('open');
        toggle.focus();
      }
    });
  }

  /* Print buttons ----------------------------------------------------- */
  document.querySelectorAll('[data-print]').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      window.print();
    });
  });
})();
