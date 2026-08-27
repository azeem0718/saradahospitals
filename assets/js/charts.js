/*
 * Chart tooltips for the analytics screen.
 *
 * The tooltips enhance the charts, they never gate them: every value shown
 * here is also in the chart's table twin. Names and labels go into the DOM
 * with textContent only.
 */
(function () {
  'use strict';

  var hits = document.querySelectorAll('.chart-hit');
  if (!hits.length) return;

  var tip = document.createElement('div');
  tip.className = 'chart-tip';
  tip.setAttribute('aria-hidden', 'true');
  document.body.appendChild(tip);

  function fill(group) {
    tip.textContent = '';

    var head = document.createElement('div');
    head.className = 'chart-tip-head';
    head.textContent = group.getAttribute('data-title') || '';
    tip.appendChild(head);

    var rows = [];
    try { rows = JSON.parse(group.getAttribute('data-rows') || '[]'); } catch (e) {}

    rows.forEach(function (row, i) {
      var line = document.createElement('div');
      line.className = 'chart-tip-row';

      var key = document.createElement('span');
      key.className = 'chart-tip-key chart-tip-key-' + i;
      line.appendChild(key);

      var value = document.createElement('strong');
      value.textContent = String(row.value);
      line.appendChild(value);

      var name = document.createElement('span');
      name.className = 'chart-tip-name';
      name.textContent = String(row.name);
      line.appendChild(name);

      tip.appendChild(line);
    });

    if (rows.length > 1) {
      var total = document.createElement('div');
      total.className = 'chart-tip-total';
      total.textContent = 'Total ' + (group.getAttribute('data-total') || '');
      tip.appendChild(total);
    }
  }

  function place(x, y) {
    var pad = 14;
    var w = tip.offsetWidth;
    var h = tip.offsetHeight;
    var left = x + pad;
    if (left + w > window.innerWidth - 8) left = x - w - pad;
    var top = y - h - pad;
    if (top < 8) top = y + pad;
    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
  }

  function show(group) {
    fill(group);
    tip.classList.add('is-on');
  }

  function hide() {
    tip.classList.remove('is-on');
  }

  Array.prototype.forEach.call(hits, function (hit) {
    var group = hit.parentNode;

    hit.addEventListener('pointerenter', function () { show(group); });
    hit.addEventListener('pointermove', function (ev) { place(ev.clientX, ev.clientY); });
    hit.addEventListener('pointerleave', hide);

    // Keyboard focus gets the same readout, anchored to the column itself.
    hit.addEventListener('focus', function () {
      show(group);
      var box = hit.getBoundingClientRect();
      place(box.left + box.width / 2, box.top + 20);
    });
    hit.addEventListener('blur', hide);
  });
})();
