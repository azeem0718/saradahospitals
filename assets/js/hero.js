/*
 * Home hero slideshow.
 *
 * The markup already shows slide one in full, so this file's only job is to
 * turn one slide off and the next one on. If it never runs — no JavaScript, a
 * script error, a reader who prefers no motion — the hero stays a perfectly
 * good static banner, which is why nothing here is required for the page to
 * make sense.
 *
 * Three ways to drive it: the bars underneath, a swipe across the photograph,
 * and the arrow keys once anything in the hero has focus. All three mean the
 * same thing — the reader is choosing now — so all three stop the clock.
 *
 * Deliberately absent: any horizontal movement of the slides themselves. A
 * swipe changes which picture is showing; it does not drag one across.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-hero-show]');
  if (!root) return;

  var layers = root.querySelectorAll('[data-hs-layer]');
  var texts  = root.querySelectorAll('[data-hs-text]');
  var dots   = root.querySelectorAll('[data-hs-dot]');
  var copy   = root.querySelector('[data-hs-copy]');
  var frame  = root.querySelector('[data-hs-frame]');
  var count  = texts.length;
  if (count < 2) return;

  /* 2s a slide. That is fast for a crossfade, so the budget is spent
     deliberately: the pictures take 0.9s to cross and the words settle by
     about 0.85s, leaving roughly a second where the slide is simply still and
     readable. Anything slower to cross and the hero would never stop moving.
     The bar under the words is told the same number, so what it draws is the
     truth rather than a guess. */
  var EVERY = 2000;
  root.style.setProperty('--hs-turn', EVERY + 'ms');

  var at = 0;
  var timer = null;
  var stopped = false;

  var still = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (still.matches) return;   // one slide, no motion, nothing to do

  function show(next) {
    if (next === at) return;

    if (layers[at]) layers[at].classList.remove('is-on');
    if (layers[next]) layers[next].classList.add('is-on');

    // The outgoing text is hidden rather than faded out: two blocks fading
    // across each other in the same grid cell reads as a smear, and the
    // incoming rise is what the eye actually follows.
    texts[at].classList.remove('is-on');
    texts[at].hidden = true;
    texts[next].hidden = false;
    // Reading offsetWidth restarts the entrance animation, which otherwise
    // would not replay when the class is re-added in the same frame.
    void texts[next].offsetWidth;
    texts[next].classList.add('is-on');

    if (dots[at]) { dots[at].classList.remove('is-on'); dots[at].removeAttribute('aria-current'); }
    if (dots[next]) {
      // Same trick for the progress bar: without the reflow it would keep the
      // finished state of the previous turn instead of filling again.
      var fill = dots[next].querySelector('i');
      if (fill) { fill.style.animation = 'none'; void fill.offsetWidth; fill.style.animation = ''; }
      dots[next].classList.add('is-on');
      dots[next].setAttribute('aria-current', 'true');
    }

    at = next;
  }

  function step(by) {
    show((at + by + count) % count);
  }

  function start() {
    if (stopped || timer !== null) return;
    root.classList.remove('is-held');
    timer = window.setInterval(function () { step(1); }, EVERY);
  }

  function pause() {
    if (timer !== null) { window.clearInterval(timer); timer = null; }
    if (!stopped) root.classList.add('is-held');
  }

  /* The reader is driving from here on: stop rotating rather than yanking them
     onward a few seconds later, and start announcing changes, because a change
     they asked for is one they should be told about. */
  function takeOver() {
    stopped = true;
    pause();
    root.classList.remove('is-held');
    root.classList.add('is-stopped');
    if (copy) copy.setAttribute('aria-live', 'polite');
  }

  /* Reading the slide you are on should not have it snatched away. */
  root.addEventListener('pointerenter', pause);
  root.addEventListener('pointerleave', function () { if (!stopped) start(); });
  root.addEventListener('focusin', pause);
  root.addEventListener('focusout', function () {
    if (!stopped && !root.contains(document.activeElement)) start();
  });

  /* A background tab should not be running a timer. */
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) { pause(); } else if (!stopped) { start(); }
  });

  Array.prototype.forEach.call(dots, function (dot, i) {
    dot.addEventListener('click', function () { takeOver(); show(i); });
  });

  /* Arrow keys, once something in the hero has focus. Left and right are what
     a reader already expects of anything with these bars under it. */
  root.addEventListener('keydown', function (ev) {
    if (ev.key !== 'ArrowLeft' && ev.key !== 'ArrowRight') return;
    // Never steal the arrow keys from somewhere they already mean something.
    var el = ev.target;
    if (el && el.closest && el.closest('input, textarea, select, [contenteditable]')) return;
    ev.preventDefault();
    takeOver();
    step(ev.key === 'ArrowRight' ? 1 : -1);
  });

  /* Swipe, on the photograph. The gesture is only claimed once it is clearly
     sideways — a mostly-vertical drag is the reader scrolling the page, and
     taking that would make the hero a trap on a phone. CSS declares
     touch-action: pan-y on the frame for the same reason, so the browser keeps
     scrolling smooth rather than waiting to see what this handler decides. */
  if (frame && window.PointerEvent) {
    var startX = 0, startY = 0, tracking = false, decided = false;
    var THRESHOLD = 45;   // px across before it counts as a swipe
    var SLOPE = 1.2;      // and how much more sideways than vertical

    frame.addEventListener('pointerdown', function (ev) {
      if (ev.pointerType === 'mouse') return;   // dragging a picture with a mouse means nothing here
      tracking = true; decided = false;
      startX = ev.clientX; startY = ev.clientY;
    });

    frame.addEventListener('pointermove', function (ev) {
      if (!tracking || decided) return;
      var dx = ev.clientX - startX;
      var dy = ev.clientY - startY;
      if (Math.abs(dx) < THRESHOLD || Math.abs(dx) < Math.abs(dy) * SLOPE) return;
      decided = true;
      takeOver();
      step(dx < 0 ? 1 : -1);
    });

    var release = function () { tracking = false; decided = false; };
    frame.addEventListener('pointerup', release);
    frame.addEventListener('pointercancel', release);
    frame.addEventListener('pointerleave', release);
  }

  /* If the preference changes mid-visit, honour it. */
  var onChange = function () { if (still.matches) { stopped = true; pause(); } };
  if (still.addEventListener) { still.addEventListener('change', onChange); }
  else if (still.addListener) { still.addListener(onChange); }

  start();
})();
