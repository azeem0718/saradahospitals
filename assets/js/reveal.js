/*
 * Scroll-triggered reveals.
 *
 * Elements are tagged here rather than in the templates, so a new page gets
 * the behaviour by using the components it already uses. An IntersectionObserver
 * toggles .is-in both ways: entering the viewport plays the entrance, leaving
 * it re-arms the element, so scrolling back replays it in either direction.
 * The un-arming happens while the element is out of view, which is why the
 * reverse never needs its own animation — there is nobody to see it.
 *
 * Siblings stagger through --rv-d, capped so a long list does not spend three
 * seconds arriving. The scroll-linked parallax lives in CSS (scroll-driven
 * animations, where supported); this file only handles the reveals.
 *
 * A reduced-motion preference does NOT switch this off, and that is a
 * correction. It used to return here, which meant the preference did not
 * reduce motion on this site — it removed every animation on every page and
 * left a completely inert document. That is the wrong reading twice over. The
 * preference asks for less motion, not for no animation, and a cross-fade is
 * the substitution the guidance actually recommends: opacity is not motion,
 * nothing travels and nothing scales. It also matters because Android turns
 * the preference on by itself in battery saver, so a phone in power-saving
 * mode was being served a site that looked broken rather than calm.
 *
 * So the tagging still happens and the stylesheet decides what a reveal means:
 * a rise or a slide normally, a plain dissolve under the preference.
 *
 * The one genuine opt-out left is a browser with no IntersectionObserver,
 * where the page simply stays still and visible.
 */
(function () {
  'use strict';

  if (!('IntersectionObserver' in window)) {
    return;
  }

  /* What animates, and how. Order matters: once an element is tagged, a later
     selector will not re-tag it, and a container is skipped when something
     inside it is already animating — two nested reveals read as a wobble. */
  var TARGETS = [
    /* Before the generic '.card' rule below, so the facilities grids take the
       wave instead of the alternating side-slide every other card grid uses. */
    ['.grid-wave > *',        'wave'],
    ['.hero-copy > *',        'up'],
    ['.hero-art',             'fade'],
    ['.quick',                'zoom'],
    ['.section-head',         'up'],
    ['.card',                 'side-alt'],
    ['.dept',                 'up'],
    ['.doc-card',             'side-alt'],
    ['.profile-card',         'up'],
    ['.profile-block',        'up'],
    ['.offer-item',           'zoom'],
    ['.stat',                 'up'],
    ['.contact-list > li',    'left'],
    ['.table-wrap',           'up'],
    ['.grid-split > *',       'up'],
    ['.notice',               'fade'],
    ['.credit-list > li',     'up'],
    ['.btn-row',              'up'],
  ];

  var MAX_STAGGER = 7;   /* the eighth sibling onwards arrives with the seventh */
  var STEP_MS     = 75;

  /* The wave is the one variant whose whole point is the gap between cards, so
     it steps wider and is not capped: capping it would land the last three
     cards together and the wave would stop being a wave halfway across. Nine
     cards at 110ms is under a second from first to last. */
  var WAVE_STEP_MS = 110;

  try {
    var tagged = [];

    /* 'side-alt' resolves per element: first card in a row of siblings comes
       from the left, the next from the right, and so on down the grid. */
    var sides = new Map();

    TARGETS.forEach(function (pair) {
      var selector = pair[0], variant = pair[1];

      document.querySelectorAll(selector).forEach(function (el) {
        if (el.hasAttribute('data-reveal')
            || el.querySelector('[data-reveal]')
            || (el.parentElement && el.parentElement.closest('[data-reveal]'))
            || el.closest('.token-slip, form')) {
          return;
        }
        var resolved = variant;
        if (variant === 'side-alt') {
          var parent = el.parentElement || document.body;
          var n = sides.get(parent) || 0;
          sides.set(parent, n + 1);
          resolved = n % 2 === 0 ? 'slide-left' : 'slide-right';
        }
        el.setAttribute('data-reveal', resolved);
        tagged.push(el);
      });
    });

    if (!tagged.length) {
      return;
    }

    /* Stagger siblings within their parent. */
    var counters = new Map();
    tagged.forEach(function (el) {
      var parent = el.parentElement || document.body;
      var i = counters.get(parent) || 0;
      counters.set(parent, i + 1);
      if (i === 0) {
        return;
      }
      if (el.getAttribute('data-reveal') === 'wave') {
        el.style.setProperty('--rv-d', (i * WAVE_STEP_MS) + 'ms');
      } else {
        el.style.setProperty('--rv-d', (Math.min(i, MAX_STAGGER) * STEP_MS) + 'ms');
      }
    });

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        entry.target.classList.toggle('is-in', entry.isIntersecting);
      });
    }, { rootMargin: '0px 0px -9% 0px', threshold: 0 });

    /* Hide only now that tagging succeeded, so a failure above leaves the
       page fully visible rather than half-dressed. */
    document.documentElement.classList.add('js-reveal');
    tagged.forEach(function (el) { io.observe(el); });
  } catch (err) {
    document.documentElement.classList.remove('js-reveal');
  }
})();
