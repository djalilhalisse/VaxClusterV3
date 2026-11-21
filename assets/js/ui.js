/* VaxCluster UI: collapsible sidebar, staggered entrance animation, scroll reveal. No dependencies. */
(function () {
  var d = document, h = d.documentElement;
  h.classList.add('js');

  function stagger(list, max) {
    Array.prototype.forEach.call(list, function (el, i) { el.style.setProperty('--i', Math.min(i, max || 10)); });
  }

  function init() {
    // ---- staggered entrance (CSS does the animating, this only sets each element's order)
    stagger(d.querySelectorAll('.hero > div:first-child > *'));
    stagger(d.querySelectorAll('.side > .side-head, .side > a.n, .side > .who'));
    stagger(d.querySelectorAll('.main .card, .main .alert, .main .stepper'), 8);
    Array.prototype.forEach.call(d.querySelectorAll('tbody'), function (t) { stagger(t.children, 14); });

    // ---- collapsible sidebar (state remembered between pages)
    var b = d.getElementById('coll');
    if (b) {
      var sync = function () {
        var min = h.classList.contains('side-min');
        b.setAttribute('aria-expanded', String(!min));
        b.setAttribute('aria-label', min ? 'Agrandir le menu' : 'Réduire le menu');
      };
      sync();
      b.addEventListener('click', function () {
        h.classList.toggle('side-min');
        try { localStorage.setItem('vaxSide', h.classList.contains('side-min') ? '1' : '0'); } catch (e) {}
        sync();
      });
    }

    // ---- reveal sections as they scroll into view (public pages)
    var els = d.querySelectorAll('.sec .wrap > h2, .sec .wrap > p, .sec .grid > *, .sec .split > *, .foot .grid2 > *');
    Array.prototype.forEach.call(d.querySelectorAll('.sec .grid, .sec .split, .foot .grid2'), function (c) { stagger(c.children, 6); });
    if (!els.length) return;
    var show = function (el) { el.classList.add('in'); };
    if (!('IntersectionObserver' in window)) { Array.prototype.forEach.call(els, function (el) { el.classList.add('reveal', 'in'); }); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { show(e.target); io.unobserve(e.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    Array.prototype.forEach.call(els, function (el) { el.classList.add('reveal'); io.observe(el); });
    window.addEventListener('beforeprint', function () { Array.prototype.forEach.call(els, show); });
  }

  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', init); else init();
})();
