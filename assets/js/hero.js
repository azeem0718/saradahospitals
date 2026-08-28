/*
 * Home hero slideshow.
 *
 * The markup already shows slide one in full, so this file's only job is to
 * turn one slide off and the next one on. If it never runs — no JavaScript, a
 * script error, a reader who prefers no motion — the hero stays a perfectly
 * good static banner, which is why nothing here is required for the page to
 * make sense.
 *
 * Deliberately absent: any horizontal movement. The slides crossfade.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-hero-show]');
  if (!root) return;

  var layers = root.querySelectorAll('[data-hs-layer]');
  var texts  = root.querySelectorAll('[data-hs-text]');
  var dots   = root.querySelectorAll('[data-hs-dot]');
  var copy   = root.querySelector('[data-hs-copy]');
  var count  = texts.length;
  if (count < 2) return;

  /* 3.5s a slide. The pictures take 1.8s to cross, so for more than half of
     every turn two of them are on screen together — which is the point. */
  var EVERY = 3500;
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
    if (dots[next]) { dots[next].classList.add('is-on'); dots[next].setAttribute('aria-current', 'true'); }

    at = next;
  }

  function start() {
    if (stopped || timer !== null) return;
    timer = window.setInterval(function () { show((at + 1) % count); }, EVERY);
  }

  function pause() {
    if (timer !== null) { window.clearInterval(timer); timer = null; }
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

  /* Choosing a slide by hand means the reader is driving now; stop rotating
     rather than yanking them onward a few seconds later. */
  Array.prototype.forEach.call(dots, function (dot, i) {
    dot.addEventListener('click', function () {
      stopped = true;
      pause();
      // From here the reader is choosing slides, so announcing them is help
      // rather than interruption.
      if (copy) copy.setAttribute('aria-live', 'polite');
      show(i);
    });
  });

  /* If the preference changes mid-visit, honour it. */
  var onChange = function () { if (still.matches) { stopped = true; pause(); } };
  if (still.addEventListener) { still.addEventListener('change', onChange); }
  else if (still.addListener) { still.addListener(onChange); }

  start();
})();
