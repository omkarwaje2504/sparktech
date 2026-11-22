// SPARKTECH CMS — small admin helpers.
(function () {
  'use strict';
  // Confirm before destructive buttons.
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (el && !window.confirm(el.getAttribute('data-confirm'))) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);

  // Clickable table rows.
  document.addEventListener('click', function (e) {
    var row = e.target.closest('tr[data-href]');
    if (row && !e.target.closest('a, button, form')) window.location = row.getAttribute('data-href');
  });

  // Copy-to-clipboard buttons.
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b) return;
    var text = b.getAttribute('data-copy');
    var done = function () { var t = b.textContent; b.textContent = 'Copied ✓'; setTimeout(function () { b.textContent = t; }, 1500); };
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(done);
    else { var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); done(); }
  });

  // Analytics charts: one tooltip for the hovered / focused column (values first, series names after).
  var tip = null;
  var showTip = function (el) {
    var data;
    try { data = JSON.parse(el.getAttribute('data-an-tip')); } catch (err) { return; }
    if (!tip) {
      tip = document.createElement('div');
      tip.className = 'an-tip';
      tip.setAttribute('role', 'tooltip');
      document.body.appendChild(tip);
    }
    tip.textContent = '';
    var head = document.createElement('div');
    head.className = 'an-tip-head';
    head.textContent = data.t;
    tip.appendChild(head);
    (data.r || []).forEach(function (row) {
      var line = document.createElement('div');
      line.className = 'an-tip-row';
      var key = document.createElement('i');
      if (row[2]) key.style.background = row[2]; else key.className = 'is-none';
      var val = document.createElement('b');
      val.textContent = row[1];
      var name = document.createElement('span');
      name.textContent = row[0];
      line.appendChild(key); line.appendChild(val); line.appendChild(name);
      tip.appendChild(line);
    });
    var r = el.getBoundingClientRect();
    tip.style.display = 'block';
    var x = r.left + r.width / 2 - tip.offsetWidth / 2;
    tip.style.left = Math.max(8, Math.min(x, window.innerWidth - tip.offsetWidth - 8)) + 'px';
    tip.style.top = Math.max(8, r.top + window.scrollY - tip.offsetHeight - 8) + 'px';
  };
  var hideTip = function () { if (tip) tip.style.display = 'none'; };
  ['pointerover', 'focusin'].forEach(function (ev) {
    document.addEventListener(ev, function (e) {
      var el = e.target.closest && e.target.closest('[data-an-tip]');
      if (el) showTip(el);
    });
  });
  ['pointerout', 'focusout'].forEach(function (ev) {
    document.addEventListener(ev, function (e) {
      if (e.target.closest && e.target.closest('[data-an-tip]')) hideTip();
    });
  });

  // Drag & drop upload area: auto-submit when files are chosen.
  var dz = document.querySelector('[data-dropzone]');
  if (dz) {
    var input = dz.querySelector('input[type=file]');
    ['dragenter', 'dragover'].forEach(function (ev) { dz.addEventListener(ev, function () { dz.classList.add('is-drag'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { dz.addEventListener(ev, function () { dz.classList.remove('is-drag'); }); });
    input.addEventListener('change', function () {
      if (input.files.length) {
        dz.querySelector('label b').textContent = 'Uploading ' + input.files.length + ' file(s)…';
        dz.submit();
      }
    });
  }
})();
