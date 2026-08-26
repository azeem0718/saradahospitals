/* Sarada Nursing Home — site behaviour. No dependencies. */
(function () {
  'use strict';

  /* Mobile navigation ------------------------------------------------- */
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('primary-nav');

  if (toggle && nav) {
    var setNav = function (open) {
      toggle.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('open', open);
      // Drives the backdrop and the scroll lock.
      document.documentElement.classList.toggle('nav-open', open);
    };

    toggle.addEventListener('click', function () {
      setNav(toggle.getAttribute('aria-expanded') !== 'true');
    });

    // Following a link closes it; so does tapping the dimmed page behind it.
    nav.addEventListener('click', function (ev) {
      if (ev.target.closest('a')) setNav(false);
    });
    document.addEventListener('click', function (ev) {
      if (!nav.classList.contains('open')) return;
      if (ev.target.closest('.nav, .nav-toggle')) return;
      setNav(false);
    });

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && nav.classList.contains('open')) {
        setNav(false);
        toggle.focus();
      }
    });

    // Growing past the breakpoint with the menu open would leave the page
    // scroll-locked behind a menu that no longer exists.
    var wide = window.matchMedia('(min-width: 1181px)');
    var onWide = function (m) { if (m.matches) setNav(false); };
    if (wide.addEventListener) { wide.addEventListener('change', onWide); }
    else if (wide.addListener) { wide.addListener(onWide); }
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
