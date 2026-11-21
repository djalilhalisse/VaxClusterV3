/* VaxCluster form controls: themed dropdowns, date and time pickers, number steppers, search field and
   repeatable "+ / −" rows. Progressive enhancement: the native <select>/<input> stays in the page (hidden), so
   form submission, validation and the existing scripts keep working unchanged. French labels. */
(function () {
  'use strict';
  var L = { locale: 'fr-FR', weekStart: 1, pickDate: 'Choisir une date', pickTime: 'Choisir une heure', clear: 'Effacer', today: 'Aujourd’hui',
            hour: 'Heures', minute: 'Minutes', now: 'Maintenant', done: 'OK', removeRow: 'Supprimer cette ligne', addRow: 'Ajouter une ligne',
            less: 'Diminuer', more: 'Augmenter', prev: 'Précédent', next: 'Suivant' };
  var P = function (n) { return String(n).padStart(2, '0'); };
  var D = {
    chev: '<path d="m6 9 6 6 6-6"/>', cal: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>', left: '<path d="m15 18-6-6 6-6"/>', right: '<path d="m9 18 6-6-6-6"/>',
    check: '<path d="M20 6 9 17l-5-5"/>', plus: '<path d="M12 5v14M5 12h14"/>', minus: '<path d="M5 12h14"/>',
    search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>'
  };
  function svg(k, cls) { return '<svg class="i' + (cls ? ' ' + cls : '') + '" viewBox="0 0 24 24" aria-hidden="true">' + D[k] + '</svg>'; }
  function el(tag, cls, html) { var e = document.createElement(tag); if (cls) e.className = cls; if (html !== undefined) e.innerHTML = html; return e; }
  function btn(cls, html, label) { var b = el('button', cls, html); b.type = 'button'; if (label) { b.setAttribute('aria-label', label); b.title = label; } return b; }

  function closeAll(except) {
    Array.prototype.forEach.call(document.querySelectorAll('.ui-w.open'), function (w) {
      if (w !== except) { w.classList.remove('open'); w.querySelector('.ui-btn').setAttribute('aria-expanded', 'false'); }
    });
  }
  document.addEventListener('click', function (e) {
    var p = e.composedPath ? e.composedPath() : [];
    if (!p.some(function (n) { return n.classList && n.classList.contains('ui-w'); })) closeAll();
  });

  /* ---- shared shell: a button that opens a popup, wrapped around the native control ---- */
  function build(native, icon, popupRole) {
    var w = el('div', 'ui-w'), b = el('button', 'ui-btn'), txt = el('span'), pop = el('div', 'ui-pop');
    native.parentNode.insertBefore(w, native); w.appendChild(native);
    native.classList.add('ui-native'); native.tabIndex = -1;
    b.type = 'button'; b.setAttribute('aria-haspopup', popupRole); b.setAttribute('aria-expanded', 'false');
    b.appendChild(txt); b.insertAdjacentHTML('beforeend', icon);
    var lab = native.id && document.querySelector('label[for="' + native.id + '"]');
    if (lab) { lab.id = lab.id || native.id + '-l'; b.setAttribute('aria-labelledby', lab.id); lab.addEventListener('click', function (e) { e.preventDefault(); b.focus(); }); }
    native.addEventListener('invalid', function () { b.classList.add('bad'); });
    native.addEventListener('change', function () { b.classList.remove('bad'); });
    w.appendChild(b); w.appendChild(pop);
    var api = {
      w: w, b: b, txt: txt, pop: pop,
      isOpen: function () { return w.classList.contains('open'); },
      open: function (render) {
        closeAll(w); w.classList.remove('up', 'end'); render(); w.classList.add('open'); b.setAttribute('aria-expanded', 'true');
        var q = pop.getBoundingClientRect(), r = b.getBoundingClientRect();
        if (q.bottom > innerHeight - 8 && r.top > q.height + 16) w.classList.add('up');
        if (q.right > innerWidth - 8) w.classList.add('end');
      },
      close: function (focus) { w.classList.remove('open'); b.setAttribute('aria-expanded', 'false'); if (focus) b.focus(); },
      fire: function () { native.dispatchEvent(new Event('input', { bubbles: true })); native.dispatchEvent(new Event('change', { bubbles: true })); }
    };
    w.addEventListener('keydown', function (e) { if (e.key === 'Escape' && api.isOpen()) { e.stopPropagation(); api.close(true); } });
    return api;
  }

  /* ---- dropdown ---- */
  function selectUI(s) {
    var u = build(s, svg('chev', 'chev'), 'listbox'), all = s.hasAttribute('data-all'); u.pop.setAttribute('role', 'listbox');
    function sync() { var o = s.options[s.selectedIndex]; u.txt.textContent = o ? o.textContent : ''; u.b.classList.toggle('ph', !s.value && !all); }
    function items() { return Array.prototype.slice.call(u.pop.querySelectorAll('.ui-o')); }
    function render() {
      u.pop.innerHTML = '';
      Array.prototype.forEach.call(s.options, function (o, i) {
        if (!o.value && !all) return;                       // the empty "Choisir" entry is only a placeholder
        var d = el('div', 'ui-o'), sp = el('span'); sp.textContent = o.textContent; d.appendChild(sp); d.tabIndex = -1; d.setAttribute('role', 'option');
        var on = i === s.selectedIndex; d.setAttribute('aria-selected', on); if (on) d.insertAdjacentHTML('beforeend', svg('check'));
        d.onclick = function () { s.selectedIndex = i; sync(); u.fire(); u.close(true); };
        u.pop.appendChild(d);
      });
    }
    function openAt() { u.open(render); var it = items(); (it.filter(function (x) { return x.getAttribute('aria-selected') === 'true'; })[0] || it[0]).focus(); }
    u.b.onclick = function () { u.isOpen() ? u.close() : openAt(); };
    u.b.addEventListener('keydown', function (e) { if (['ArrowDown', 'ArrowUp', 'Enter', ' '].indexOf(e.key) > -1) { e.preventDefault(); openAt(); } });
    var typed = '', t;
    u.pop.addEventListener('keydown', function (e) {
      var it = items(), i = it.indexOf(document.activeElement);
      if (e.key === 'ArrowDown') { e.preventDefault(); it[Math.min(i + 1, it.length - 1)].focus(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); it[Math.max(i - 1, 0)].focus(); }
      else if (e.key === 'Home') { e.preventDefault(); it[0].focus(); }
      else if (e.key === 'End') { e.preventDefault(); it[it.length - 1].focus(); }
      else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); document.activeElement.click(); }
      else if (e.key === 'Tab') u.close();
      else if (e.key.length === 1) {                       // type-ahead
        typed += e.key.toLowerCase(); clearTimeout(t); t = setTimeout(function () { typed = ''; }, 600);
        var hit = it.filter(function (x) { return x.textContent.toLowerCase().indexOf(typed) === 0; })[0]; if (hit) hit.focus();
      }
    });
    s.addEventListener('change', sync); sync();
  }

  /* ---- date ---- */
  function dateUI(inp) {
    var u = build(inp, svg('cal'), 'dialog'); u.pop.classList.add('cal'); u.pop.setAttribute('role', 'dialog');
    var parse = function (v) { var m = /^(\d{4})-(\d\d)-(\d\d)$/.exec(v); return m ? new Date(+m[1], m[2] - 1, +m[3]) : null; };
    var iso = function (d) { return d.getFullYear() + '-' + P(d.getMonth() + 1) + '-' + P(d.getDate()); };
    var fmt = new Intl.DateTimeFormat(L.locale, { day: 'numeric', month: 'long', year: 'numeric' });
    var mfmt = new Intl.DateTimeFormat(L.locale, { month: 'long', year: 'numeric' }), mon = new Intl.DateTimeFormat(L.locale, { month: 'short' });
    var wd = new Intl.DateTimeFormat(L.locale, { weekday: 'narrow' });
    var min = parse(inp.min), max = parse(inp.max), today = new Date(), ti = iso(today), view, mode;
    var out = function (d) { return (min && d < min) || (max && d > max); };
    function sync() { var d = parse(inp.value); u.txt.textContent = d ? fmt.format(d) : L.pickDate; u.b.classList.toggle('ph', !d); }
    function pick(d) { inp.value = iso(d); sync(); u.fire(); u.close(true); }
    function render() {
      var p = u.pop; p.innerHTML = ''; var y = view.getFullYear(), m = view.getMonth(), base = Math.floor(y / 12) * 12;
      var nav = function (dir, fn) { return btn('cal-n', svg(dir < 0 ? 'left' : 'right'), dir < 0 ? L.prev : L.next); };
      var h = el('div', 'cal-h'), t = btn('t'), prev = nav(-1), next = nav(1);
      if (mode === 'days') { t.textContent = mfmt.format(view); t.onclick = function () { mode = 'months'; render(); };
        prev.onclick = function () { view = new Date(y, m - 1, 1); render(); }; next.onclick = function () { view = new Date(y, m + 1, 1); render(); }; }
      else if (mode === 'months') { t.textContent = y; t.onclick = function () { mode = 'years'; render(); };
        prev.onclick = function () { view = new Date(y - 1, m, 1); render(); }; next.onclick = function () { view = new Date(y + 1, m, 1); render(); }; }
      else { t.textContent = base + ' – ' + (base + 11); t.disabled = true;
        prev.onclick = function () { view = new Date(y - 12, m, 1); render(); }; next.onclick = function () { view = new Date(y + 12, m, 1); render(); }; }
      h.appendChild(prev); h.appendChild(t); h.appendChild(next); p.appendChild(h);
      var g = el('div', 'cal-g' + (mode === 'days' ? '' : ' m'));
      if (mode === 'days') {
        for (var i = 0; i < 7; i++) g.appendChild(el('div', 'cal-wd', wd.format(new Date(2024, 0, 7 + ((i + L.weekStart) % 7)))));
        var lead = (new Date(y, m, 1).getDay() - L.weekStart + 7) % 7; for (i = 0; i < lead; i++) g.appendChild(el('span'));
        var n = new Date(y, m + 1, 0).getDate(), sel = inp.value;
        for (var d = 1; d <= n; d++) (function (d) {
          var dt = new Date(y, m, d), b = btn('', d), s = iso(dt);
          if (s === ti) b.classList.add('today'); if (s === sel) { b.classList.add('sel'); b.setAttribute('aria-pressed', 'true'); }
          b.disabled = out(dt); b.setAttribute('aria-label', fmt.format(dt)); b.onclick = function () { pick(dt); }; g.appendChild(b);
        })(d);
      } else if (mode === 'months') {
        for (var k = 0; k < 12; k++) (function (k) {
          var b = btn('', mon.format(new Date(y, k, 1))); if (k === m) b.classList.add('sel');
          b.onclick = function () { view = new Date(y, k, 1); mode = 'days'; render(); }; g.appendChild(b);
        })(k);
      } else {
        for (var j = 0; j < 12; j++) (function (yy) {
          var b = btn('', yy); if (yy === y) b.classList.add('sel'); if (yy === today.getFullYear()) b.classList.add('today');
          b.onclick = function () { view = new Date(yy, m, 1); mode = 'months'; render(); }; g.appendChild(b);
        })(base + j);
      }
      p.appendChild(g);
      var f = el('div', 'cal-f'), c = btn('', L.clear), td = btn('', L.today); if (inp.required) c.style.visibility = 'hidden';
      td.disabled = out(new Date(today.getFullYear(), today.getMonth(), today.getDate()));
      c.onclick = function () { inp.value = ''; sync(); u.fire(); u.close(true); }; td.onclick = function () { pick(new Date()); };
      f.appendChild(c); f.appendChild(td); p.appendChild(f);
    }
    function openIt() { u.open(function () { var b = parse(inp.value) || today; if (max && b > max) b = max; if (min && b < min) b = min; view = new Date(b.getFullYear(), b.getMonth(), 1); mode = 'days'; render(); }); }
    u.b.onclick = function () { if (u.isOpen()) return u.close(); openIt(); var f = u.pop.querySelector('.sel,.today,.cal-g button:not(:disabled)'); if (f) f.focus(); };
    u.b.addEventListener('keydown', function (e) { if (e.key === 'ArrowDown' || e.key === 'Enter') { e.preventDefault(); openIt(); } });
    sync();
  }

  /* ---- time ---- */
  function timeUI(inp) {
    var u = build(inp, svg('clock'), 'dialog'); u.pop.classList.add('tp'); u.pop.setAttribute('role', 'dialog'); var H = null, M = null;
    function read() { var m = /^(\d\d):(\d\d)/.exec(inp.value); H = m ? +m[1] : null; M = m ? +m[2] : null; }
    function sync() { u.txt.textContent = inp.value ? inp.value.slice(0, 5).replace(':', ' h ') : L.pickTime; u.b.classList.toggle('ph', !inp.value); }
    function commit() { if (H === null) H = 0; if (M === null) M = 0; inp.value = P(H) + ':' + P(M); sync(); u.fire(); }
    function center(p) { Array.prototype.forEach.call(p.querySelectorAll('.tp-c .l'), function (c) { var s = c.querySelector('.sel'); if (s) c.scrollTop = s.offsetTop - c.clientHeight / 2 + s.offsetHeight / 2; }); }
    function render() {
      read(); var p = u.pop; p.innerHTML = ''; var row = el('div', 'tp-c');
      function col(lab, n, cur, set) {
        var c = el('div'), s = el('small', null, lab), l = el('div', 'l');
        for (var i = 0; i < n; i++) (function (i) { var b = btn(i === cur ? 'sel' : '', P(i)); b.onclick = function () { set(i); commit(); render(); center(p); }; l.appendChild(b); })(i);
        c.appendChild(s); c.appendChild(l); return c;
      }
      row.appendChild(col(L.hour, 24, H, function (v) { H = v; })); row.appendChild(col(L.minute, 60, M, function (v) { M = v; })); p.appendChild(row);
      var f = el('div', 'cal-f'), n = btn('', L.now), d = btn('', L.done);
      n.onclick = function () { var t = new Date(); H = t.getHours(); M = t.getMinutes(); commit(); render(); center(p); }; d.onclick = function () { u.close(true); };
      f.appendChild(n); f.appendChild(d); p.appendChild(f);
    }
    u.b.onclick = function () { if (u.isOpen()) return u.close(); u.open(render); center(u.pop); };
    u.b.addEventListener('keydown', function (e) { if (e.key === 'ArrowDown' || e.key === 'Enter') { e.preventDefault(); u.open(render); center(u.pop); } });
    sync();
  }

  /* ---- number stepper ---- */
  function numberUI(inp) {
    var w = el('div', 'ui-num'), less = btn('', svg('minus'), L.less), more = btn('', svg('plus'), L.more);
    inp.parentNode.insertBefore(w, inp); w.appendChild(less); w.appendChild(inp); w.appendChild(more);
    function step(dir) { try { dir > 0 ? inp.stepUp() : inp.stepDown(); } catch (e) { inp.value = (+inp.value || 0) + dir; } inp.dispatchEvent(new Event('input', { bubbles: true })); inp.dispatchEvent(new Event('change', { bubbles: true })); inp.focus(); }
    less.onclick = function () { step(-1); }; more.onclick = function () { step(1); };
  }

  /* ---- search field with icon ---- */
  function searchUI(inp) { var w = el('div', 'ui-search'); inp.parentNode.insertBefore(w, inp); w.appendChild(inp); w.insertAdjacentHTML('afterbegin', svg('search')); }

  /* ---- repeatable rows: "+" adds a row, "−" removes it ---- */
  function multi(m) {
    var MAX = 10, firstRow = m.firstElementChild, first = firstRow.querySelector('.mpl');
    first.innerHTML = svg('plus'); first.setAttribute('aria-label', L.addRow); first.title = L.addRow;     // set before cloning
    var tpl = firstRow.cloneNode(true), ti = tpl.querySelector('input'); ti.removeAttribute('id'); ti.value = '';
    function wire(row) {
      row.querySelector('.mpl').onclick = function () {
        if (m.children.length >= MAX) return;
        var n = tpl.cloneNode(true), i = n.querySelector('input'), rm = btn('mrm', svg('minus'), L.removeRow); n.classList.add('x');
        rm.onclick = function () { var near = n.previousElementSibling || n.nextElementSibling; n.remove(); if (near) near.querySelector('input').focus(); };
        n.insertBefore(rm, n.querySelector('.mpl')); row.after(n); wire(n); i.focus();
      };
    }
    wire(firstRow);
  }

  function init() {
    Array.prototype.forEach.call(document.querySelectorAll('select.ui'), selectUI);
    Array.prototype.forEach.call(document.querySelectorAll('input.ui[type=date]'), dateUI);
    Array.prototype.forEach.call(document.querySelectorAll('input.ui[type=time]'), timeUI);
    Array.prototype.forEach.call(document.querySelectorAll('input.ui[type=number]'), numberUI);
    Array.prototype.forEach.call(document.querySelectorAll('input.ui[type=search]'), searchUI);
    Array.prototype.forEach.call(document.querySelectorAll('.multi'), multi);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
