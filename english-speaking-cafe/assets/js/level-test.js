/* =========================================================
   Test de niveau : une question à la fois, avance automatique
   ========================================================= */
(function () {
  'use strict';
  var form = document.querySelector('[data-level-test]');
  if (!form) return;
  form.classList.add('is-stepper');
  var qs = Array.prototype.slice.call(form.querySelectorAll('.test_question'));
  var intro = form.querySelector('[data-test-intro]');
  var bar = form.querySelector('[data-test-bar]');
  var count = form.querySelector('[data-test-count]');
  var prev = form.querySelector('[data-test-prev]');
  var submit = form.querySelector('[data-test-submit]');
  var cur = -1;

  function show(i) {
    cur = Math.max(0, Math.min(qs.length - 1, i));
    qs.forEach(function (q, k) { q.classList.toggle('is-active', k === cur); });
    count.textContent = (cur + 1) + ' / ' + qs.length;
    bar.style.transform = 'scaleX(' + ((cur + 1) / qs.length) + ')';
    prev.disabled = cur === 0;
    submit.hidden = cur !== qs.length - 1; // résultat à la dernière question
    var first = qs[cur].querySelector('input:checked') || qs[cur].querySelector('input');
    if (first) first.focus({ preventScroll: true });
  }

  form.querySelector('[data-test-start]').addEventListener('click', function () {
    form.classList.add('is-started');
    intro.hidden = true;
    show(0);
    var top = form.getBoundingClientRect().top + window.scrollY - 120;
    if (window.__lenis) window.__lenis.scrollTo(top); else window.scrollTo({ top: top, behavior: 'smooth' });
  });

  // Réponse choisie → question suivante
  form.addEventListener('change', function (e) {
    if (!e.target.matches('input[type=radio]')) return;
    if (cur < qs.length - 1) setTimeout(function () { show(cur + 1); }, 320);
  });
  prev.addEventListener('click', function () { show(cur - 1); });
  form.addEventListener('submit', function () { submit.disabled = true; submit.classList.add('is-disabled'); });
})();
