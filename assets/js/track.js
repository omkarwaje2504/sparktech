/*!
 * SPARKTECH first-party analytics (app/lib/analytics.php) — no cookies, no third parties.
 *
 * localStorage keeps:
 *   st_v  { id: random 128-bit visitor hash, first: first visit time, n: number of visits }
 *   st_s  { id: visit ID, last: last activity } — a new visit starts after 30 idle minutes
 * and sends to /collect: page views, clicks (links & buttons), and on leaving a
 * page the visible time, scroll depth and whether a person interacted.
 */
(function () {
  'use strict';

  var cfg = window.ST || {};
  if (!cfg.track || !window.JSON) return;
  if (cfg.dnt && (navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.globalPrivacyControl)) return;

  var endpoint = (cfg.base || '') + '/collect';
  var IDLE = 30 * 60 * 1000;
  var human = false;

  var store = (function () {
    try { localStorage.setItem('st_t', '1'); localStorage.removeItem('st_t'); return localStorage; } catch (e) { return null; }
  })();
  var read = function (k) { try { return JSON.parse(store.getItem(k) || 'null'); } catch (e) { return null; } };
  var write = function (k, v) { try { store.setItem(k, JSON.stringify(v)); } catch (e) {} };
  var hex = function (bytes) {
    var a = new Uint8Array(bytes), c = window.crypto || window.msCrypto, out = '';
    if (c && c.getRandomValues) c.getRandomValues(a);
    else for (var i = 0; i < bytes; i++) a[i] = Math.floor(Math.random() * 256);
    for (var j = 0; j < a.length; j++) out += ('0' + a[j].toString(16)).slice(-2);
    return out;
  };

  // ---- Visitor hash + visit --------------------------------------------------
  var now = Date.now();
  var visitor = store ? read('st_v') : null;
  if (!visitor || !/^[a-f0-9]{32}$/.test(visitor.id || '')) visitor = { id: store ? hex(16) : '', first: now, n: 0 };
  var visit = store ? read('st_s') : null;
  if (!visit || !/^[a-f0-9]{32}$/.test(visit.id || '') || now - (visit.last || 0) > IDLE) {
    visit = { id: store ? hex(16) : '', last: now };
    visitor.n = (visitor.n || 0) + 1;
  }
  // Read by the enquiry form so a lead is linked to this visit (assets/js/site.js).
  var sta = window.STA = { human: function () { return human; } };
  var touch = function () {
    visit.last = Date.now();
    sta.vid = visitor.id;
    sta.sid = visit.id;
    if (store) { write('st_v', visitor); write('st_s', visit); }
  };
  touch();

  // ---- Sending -----------------------------------------------------------------
  var send = function (data) {
    data.vid = visitor.id;
    data.sid = visit.id;
    var body = JSON.stringify(data);
    try {
      if (navigator.sendBeacon && navigator.sendBeacon(endpoint, new Blob([body], { type: 'text/plain' }))) return;
    } catch (e) {}
    if (window.fetch) {
      fetch(endpoint, { method: 'POST', body: body, keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'text/plain' } })
        .catch(function () {});
    }
  };

  // ---- Page view ---------------------------------------------------------------
  var pid, visibleMs, visibleSince, maxScroll, leftSent;
  var utm = function () {
    var out = {}, q = new URLSearchParams(location.search);
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'li_fat_id']
      .forEach(function (k) { if (q.get(k)) out[k] = q.get(k).slice(0, 120); });
    return out;
  };
  var pageView = function () {
    pid = hex(8);
    visibleMs = 0;
    visibleSince = document.visibilityState === 'visible' ? Date.now() : 0;
    maxScroll = 0;
    leftSent = 0;
    var tz = '';
    try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) {}
    var ref = document.referrer;
    try { if (ref && new URL(ref).host === location.host) ref = ''; } catch (e) { ref = ''; }
    send({
      t: 'view', pid: pid, p: location.pathname, ti: document.title.slice(0, 190), r: ref, u: utm(),
      scr: (screen.width || 0) + 'x' + (screen.height || 0), lg: navigator.language || '', tz: tz,
      tp: navigator.maxTouchPoints || 0, wd: navigator.webdriver ? 1 : 0, vn: visitor.n
    });
    measureScroll();
  };

  // ---- Engagement: visible time, scroll depth, human interaction ----------------
  var measureScroll = function () {
    var doc = document.documentElement;
    var h = Math.max(doc.scrollHeight, document.body ? document.body.scrollHeight : 0);
    var seen = h > 0 ? Math.round(((window.scrollY || doc.scrollTop) + window.innerHeight) / h * 100) : 100;
    if (seen > maxScroll) maxScroll = Math.min(100, seen);
  };
  var ticking = false;
  window.addEventListener('scroll', function () {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () { measureScroll(); ticking = false; });
  }, { passive: true });

  var markHuman = function () {
    if (human) return;
    human = true;
    ['pointerdown', 'keydown', 'touchstart', 'wheel', 'mousemove', 'scroll'].forEach(function (ev) {
      window.removeEventListener(ev, markHuman, true);
    });
  };
  ['pointerdown', 'keydown', 'touchstart', 'wheel', 'mousemove', 'scroll'].forEach(function (ev) {
    window.addEventListener(ev, markHuman, { capture: true, passive: true });
  });

  var visibleSeconds = function () {
    var ms = visibleMs + (visibleSince ? Date.now() - visibleSince : 0);
    return Math.round(ms / 1000);
  };
  var leave = function () {
    var secs = visibleSeconds();
    if (secs === leftSent && secs > 0) return;
    leftSent = secs;
    send({ t: 'leave', pid: pid, p: location.pathname, s: secs, sc: maxScroll, hu: human ? 1 : 0 });
  };
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
      if (visibleSince) { visibleMs += Date.now() - visibleSince; visibleSince = 0; }
      leave();
    } else {
      if (Date.now() - visit.last > IDLE) { // came back after a long break: new visit
        visit = { id: hex(16), last: Date.now() };
        visitor.n += 1;
        touch();
        pageView();
        return;
      }
      visibleSince = Date.now();
      touch();
    }
  });
  window.addEventListener('pagehide', leave);
  window.addEventListener('pageshow', function (e) { if (e.persisted) { touch(); pageView(); } });

  // ---- Clicks: links, buttons, menu toggles, form submits -------------------------
  document.addEventListener('click', function (e) {
    var el = e.target && e.target.closest && e.target.closest('a[href], button, [role="button"], input[type="submit"], summary, [data-track]');
    if (!el || el.closest('#cms-ui, .cms-panel, .cms-modal')) return;
    markHuman();
    touch();
    // Prefer an explicit label, then a heading inside the element (cards), then its own text as written (not CSS upper-case).
    var heading = el.querySelector('h1, h2, h3, h4, h5, h6');
    var label = el.getAttribute('data-track') || el.getAttribute('aria-label')
      || ((heading ? heading.textContent : el.textContent) || el.value || '').replace(/\s+/g, ' ').trim();
    if (label.length > 80) label = label.slice(0, 77) + '...';
    if (!label) {
      var img = el.querySelector('img[alt]');
      label = (img && img.alt) || el.getAttribute('title') || '';
    }
    var href = el.tagName === 'A' ? (el.getAttribute('href') || '') : '';
    if (href && href.charAt(0) === '/') href = location.origin + href;
    var kind = el.tagName === 'A' ? 'link' : (el.type === 'submit' ? 'submit' : 'button');
    if (el.hasAttribute('data-filter-cat')) kind = 'filter';
    if (el.id === 'mobile-toggle' || el.hasAttribute('data-dropdown-toggle')) kind = 'menu';
    send({ t: 'click', pid: pid, p: location.pathname, l: label.slice(0, 190), h: href.slice(0, 255), k: kind });
  }, true);

  pageView();
})();
