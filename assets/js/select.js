/*
 * Custom select.
 *
 * A native <select> hands its dropdown to the operating system, so the list
 * that opens looks nothing like the rest of the page. This replaces the list
 * — not the control. The real <select> stays in the DOM and remains the value
 * the form submits, so the field still works with JavaScript off, still
 * validates, and still restores on back-navigation.
 *
 * Implements the ARIA listbox pattern: roving focus across options, type-
 * ahead, Home/End, Escape to dismiss, and selection mirrored back to the
 * native element so a change event fires exactly as it would have.
 */
(function () {
  'use strict';

  var uid = 0;

  function build(wrap) {
    var native = wrap.querySelector('select');
    if (!native || wrap.dataset.csReady) return;

    var id = 'cs' + (++uid);
    var opts = Array.prototype.slice.call(native.options);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'cs-btn';
    btn.id = id + '-btn';
    btn.setAttribute('role', 'combobox');
    btn.setAttribute('aria-haspopup', 'listbox');
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-controls', id + '-list');
    // The visible label above the control names this field.
    var lbl = wrap.querySelector('.cs-label');
    if (lbl) {
      if (!lbl.id) lbl.id = id + '-label';
      btn.setAttribute('aria-labelledby', lbl.id + ' ' + id + '-btn');
    }
    btn.innerHTML = '<span class="cs-value"></span>' +
      '<svg class="cs-arrow" viewBox="0 0 24 24" width="16" height="16" fill="none" ' +
      'stroke="currentColor" stroke-width="2.2" stroke-linecap="round" ' +
      'stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

    var list = document.createElement('ul');
    list.className = 'cs-menu';
    list.id = id + '-list';
    list.setAttribute('role', 'listbox');
    if (lbl) list.setAttribute('aria-labelledby', lbl.id);
    list.hidden = true;

    opts.forEach(function (o, i) {
      var li = document.createElement('li');
      li.className = 'cs-opt';
      li.id = id + '-o' + i;
      li.setAttribute('role', 'option');
      li.setAttribute('tabindex', '-1');
      li.dataset.value = o.value;
      li.textContent = o.textContent.trim();
      li.setAttribute('aria-selected', o.selected ? 'true' : 'false');
      list.appendChild(li);
    });

    wrap.appendChild(btn);
    wrap.appendChild(list);
    wrap.dataset.csReady = '1';

    var options = Array.prototype.slice.call(list.children);
    var open = false;
    var typed = '';
    var typedAt = 0;

    function sync() {
      var i = native.selectedIndex < 0 ? 0 : native.selectedIndex;
      btn.querySelector('.cs-value').textContent = options[i] ? options[i].textContent : '';
      options.forEach(function (li, n) {
        li.setAttribute('aria-selected', n === i ? 'true' : 'false');
      });
    }

    function setOpen(next, focusOption) {
      open = next;
      list.hidden = !next;
      btn.setAttribute('aria-expanded', String(next));
      wrap.classList.toggle('is-open', next);
      if (next && focusOption !== false) {
        var sel = options[native.selectedIndex < 0 ? 0 : native.selectedIndex];
        if (sel) sel.focus();
      }
    }

    function choose(li) {
      native.selectedIndex = options.indexOf(li);
      // Mirror to the native element so anything listening behaves normally.
      native.dispatchEvent(new Event('change', { bubbles: true }));
      sync();
      setOpen(false);
      btn.focus();
    }

    function move(from, step) {
      var i = options.indexOf(from);
      var n = Math.min(options.length - 1, Math.max(0, i + step));
      options[n].focus();
    }

    btn.addEventListener('click', function () { setOpen(!open); });

    btn.addEventListener('keydown', function (ev) {
      if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp' || ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        setOpen(true);
      }
    });

    list.addEventListener('click', function (ev) {
      var li = ev.target.closest('.cs-opt');
      if (li) choose(li);
    });

    list.addEventListener('keydown', function (ev) {
      var li = ev.target.closest('.cs-opt');
      if (!li) return;
      switch (ev.key) {
        case 'ArrowDown': ev.preventDefault(); move(li, 1); break;
        case 'ArrowUp':   ev.preventDefault(); move(li, -1); break;
        case 'Home':      ev.preventDefault(); options[0].focus(); break;
        case 'End':       ev.preventDefault(); options[options.length - 1].focus(); break;
        case 'Enter':
        case ' ':         ev.preventDefault(); choose(li); break;
        case 'Escape':    ev.preventDefault(); setOpen(false); btn.focus(); break;
        case 'Tab':       setOpen(false); break;
        default:
          if (ev.key.length === 1) {
            var now = Date.now();
            typed = now - typedAt > 700 ? ev.key : typed + ev.key;
            typedAt = now;
            var hit = options.find(function (o) {
              return o.textContent.toLowerCase().indexOf(typed.toLowerCase()) === 0;
            });
            if (hit) hit.focus();
          }
      }
    });

    document.addEventListener('pointerdown', function (ev) {
      if (open && !wrap.contains(ev.target)) setOpen(false, false);
    });

    // Keep in step if the value is changed from outside, e.g. on back-nav.
    native.addEventListener('change', sync);
    sync();
  }

  function init() {
    document.querySelectorAll('[data-cs]').forEach(build);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
