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

  /* Pause the hero illustration when it is scrolled out of view --------
     The loop costs little (measured ~2.5fps under 6x CPU throttling), but
     browsers do not reliably pause CSS animations for off-screen elements,
     and there is no reason to repaint it while nobody is looking. */
  var art = document.querySelector('.draw-art');
  if (art && 'IntersectionObserver' in window) {
    var targets = [art].concat(Array.prototype.slice.call(art.querySelectorAll('path')));
    new IntersectionObserver(function (entries) {
      var visible = entries[0].isIntersecting;
      targets.forEach(function (el) {
        el.style.animationPlayState = visible ? '' : 'paused';
      });
    }, { threshold: 0 }).observe(art);
  }

  /* Print buttons ----------------------------------------------------- */
  document.querySelectorAll('[data-print]').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      window.print();
    });
  });
})();
