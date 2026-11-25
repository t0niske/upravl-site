// Админка CRM: меню на телефоне, клик по строке таблицы, подтверждения, выбор дат периода.
(function () {
  'use strict';

  var menu = document.querySelector('[data-menu]');
  if (menu) {
    menu.addEventListener('click', function () {
      var top = menu.closest('.top');
      var open = top.classList.toggle('is-open');
      menu.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Вся строка таблицы — ссылка на заявку или заход.
  document.addEventListener('click', function (e) {
    var row = e.target.closest('tr[data-href]');
    if (row && !e.target.closest('a, button, input, select')) window.location = row.dataset.href;
  });

  document.addEventListener('submit', function (e) {
    var text = e.target.getAttribute('data-confirm');
    if (text && !window.confirm(text)) e.preventDefault();
  });

  // Выбор «Даты…» в периоде показывает поля дат.
  document.querySelectorAll('[data-period]').forEach(function (select) {
    var dates = select.form.querySelector('[data-dates]');
    select.addEventListener('change', function () { if (dates) dates.hidden = select.value !== 'custom'; });
  });
})();
