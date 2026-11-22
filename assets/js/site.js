/*!
 * SPARKTECH site interactions (vanilla JS replacement for the Framer Motion
 * animations and React state of the original Next.js site).
 */
(function () {
  'use strict';

  var editing = document.documentElement.classList.contains('cms-editing');
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var swapClasses = function (el, add, remove) {
    if (!el) return;
    remove.split(' ').forEach(function (c) { if (c) el.classList.remove(c); });
    add.split(' ').forEach(function (c) { if (c) el.classList.add(c); });
  };

  // ---------------------------------------------------------------------------
  // Scroll reveal (fadeUp / fadeLeft / fadeRight / scaleIn + staggered groups)
  // ---------------------------------------------------------------------------
  function finishReveal(el) {
    el.classList.remove('is-in');
    el.removeAttribute('data-reveal');
    el.style.removeProperty('--d');
  }

  function playReveal(el) {
    if (!el.hasAttribute('data-reveal')) return;
    el.addEventListener('animationend', function handler(e) {
      if (e.target !== el) return;
      el.removeEventListener('animationend', handler);
      finishReveal(el);
    });
    el.classList.add('is-in');
  }

  function initReveal() {
    var all = $$('[data-reveal]');
    if (editing || reduceMotion || !('IntersectionObserver' in window)) {
      all.forEach(finishReveal);
      return;
    }
    all.forEach(function (el) {
      if (el.dataset.delay) el.style.setProperty('--d', el.dataset.delay + 'ms');
    });

    var groups = $$('[data-reveal-group]');
    var grouped = new Set();
    groups.forEach(function (g) {
      var step = parseInt(g.dataset.stagger || '100', 10);
      var i = 0;
      $$('[data-reveal]', g).forEach(function (child) {
        if (child.parentElement.closest('[data-reveal-group]') !== g) return;
        grouped.add(child);
        if (!child.dataset.delay) child.style.setProperty('--d', (i * step) + 'ms');
        i++;
      });
    });

    var revealGroup = function (g) {
      $$('[data-reveal]', g).forEach(function (child) {
        if (child.parentElement.closest('[data-reveal-group]') === g) playReveal(child);
      });
    };

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        io.unobserve(en.target);
        if (en.target.hasAttribute('data-reveal-group')) revealGroup(en.target);
        else playReveal(en.target);
      });
    }, { threshold: 0, rootMargin: '0px 0px -12% 0px' });

    groups.forEach(function (g) {
      if (g.hasAttribute('data-reveal-now')) revealGroup(g);
      else io.observe(g);
    });
    all.forEach(function (el) {
      if (grouped.has(el)) return;
      if (el.hasAttribute('data-reveal-now')) playReveal(el);
      else io.observe(el);
    });
  }

  // ---------------------------------------------------------------------------
  // Header: scroll state, services dropdown, mobile menu
  // ---------------------------------------------------------------------------
  function initHeader() {
    var header = $('#site-header');
    if (!header) return;
    var logo = $('#site-logo');
    var scrolled = null;
    var onScroll = function () {
      var s = window.scrollY > 20;
      if (s === scrolled) return;
      scrolled = s;
      [header, logo].forEach(function (el) {
        if (!el) return;
        swapClasses(el, s ? el.dataset.scrolledClass : el.dataset.topClass, s ? el.dataset.topClass : el.dataset.scrolledClass);
      });
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    $$('[data-dropdown]').forEach(function (dd) {
      var btn = $('[data-dropdown-toggle]', dd);
      var menu = $('[data-dropdown-menu]', dd);
      var chevron = $('svg', btn);
      var timer;
      var open = function () {
        clearTimeout(timer);
        if (!menu.classList.contains('hidden')) return;
        menu.classList.remove('hidden');
        menu.classList.add('animate-scale-in');
        chevron && chevron.classList.add('rotate-180');
        btn.setAttribute('aria-expanded', 'true');
      };
      var close = function () {
        menu.classList.add('hidden');
        menu.classList.remove('animate-scale-in');
        chevron && chevron.classList.remove('rotate-180');
        btn.setAttribute('aria-expanded', 'false');
      };
      dd.addEventListener('mouseenter', open);
      dd.addEventListener('mouseleave', function () {
        if (editing) return; // stay open while editing; close by clicking elsewhere
        timer = setTimeout(close, 120);
      });
      btn.addEventListener('click', function (e) {
        if (editing && e.target.closest('.cms-t')) return;
        menu.classList.contains('hidden') ? open() : close();
      });
      dd.addEventListener('focusout', function (e) {
        if (!dd.contains(e.relatedTarget) && !editing) close();
      });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
      document.addEventListener('click', function (e) {
        if (dd.contains(e.target) || (e.target.closest && e.target.closest('#cms-ui, .cms-panel, .cms-modal'))) return;
        close();
      });
    });

    var toggle = $('#mobile-toggle');
    var menu = $('#mobile-menu');
    if (toggle && menu) {
      var bars = $$('[data-bar]', toggle);
      var isOpen = false;
      toggle.addEventListener('click', function () {
        isOpen = !isOpen;
        swapClasses(menu, isOpen ? 'max-h-[85vh] opacity-100 pb-6' : 'max-h-0 opacity-0', isOpen ? 'max-h-0 opacity-0' : 'max-h-[85vh] opacity-100 pb-6');
        swapClasses(bars[0], isOpen ? 'rotate-45 translate-y-2' : '', isOpen ? '' : 'rotate-45 translate-y-2');
        swapClasses(bars[1], isOpen ? 'opacity-0' : '', isOpen ? '' : 'opacity-0');
        swapClasses(bars[2], isOpen ? '-rotate-45 -translate-y-2.5' : '', isOpen ? '' : '-rotate-45 -translate-y-2.5');
        toggle.setAttribute('aria-expanded', String(isOpen));
        document.body.style.overflow = isOpen ? 'hidden' : '';
      });
      var sToggle = $('#mobile-services-toggle');
      var sPanel = $('#mobile-services');
      if (sToggle && sPanel) {
        var sOpen = false;
        sToggle.addEventListener('click', function () {
          sOpen = !sOpen;
          swapClasses(sPanel, sOpen ? 'max-h-[500px]' : 'max-h-0', sOpen ? 'max-h-0' : 'max-h-[500px]');
          var ch = $('svg', sToggle);
          ch && ch.classList.toggle('rotate-180', sOpen);
          sToggle.setAttribute('aria-expanded', String(sOpen));
        });
      }
    }
  }

  // ---------------------------------------------------------------------------
  // Count-up numbers
  // ---------------------------------------------------------------------------
  function initCounters() {
    var els = $$('[data-counter]');
    if (!els.length || editing || reduceMotion || !('IntersectionObserver' in window)) return;
    var run = function (el) {
      var target = parseFloat(el.dataset.counter) || 0;
      var suffix = el.dataset.suffix || '';
      var decimals = (String(el.dataset.counter).split('.')[1] || '').length;
      var start = performance.now();
      var tick = function (t) {
        var p = Math.min(1, (t - start) / 1500);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = (eased * target).toFixed(decimals) + suffix;
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        io.unobserve(en.target);
        run(en.target);
      });
    }, { threshold: 0.3 });
    els.forEach(function (el) {
      el.textContent = '0' + (el.dataset.suffix || '');
      io.observe(el);
    });
  }

  // ---------------------------------------------------------------------------
  // Home: technologies expanding accordion
  // ---------------------------------------------------------------------------
  function initTechAccordion() {
    $$('[data-tech-accordion]').forEach(function (wrap) {
      var panels = $$('[data-tech-panel]', wrap);
      var setActive = function (idx) {
        panels.forEach(function (p, i) {
          var on = i === idx;
          swapClasses(p, on ? 'flex-[6] lg:flex-[5]' : 'flex-[1]', on ? 'flex-[1]' : 'flex-[6] lg:flex-[5]');
          swapClasses($('[data-tech-img]', p), on ? 'scale-105' : 'scale-100', on ? 'scale-100' : 'scale-105');
          swapClasses($('[data-tech-bar]', p), on ? 'opacity-100' : 'opacity-0', on ? 'opacity-0' : 'opacity-100');
          swapClasses($('[data-tech-collapsed]', p), on ? 'opacity-0' : 'opacity-100', on ? 'opacity-100' : 'opacity-0');
          swapClasses($('[data-tech-expanded]', p), on ? 'opacity-100 delay-150' : 'pointer-events-none opacity-0', on ? 'pointer-events-none opacity-0' : 'opacity-100 delay-150');
        });
      };
      panels.forEach(function (p, i) {
        p.addEventListener('mouseenter', function () { setActive(i); });
        p.addEventListener('focusin', function () { setActive(i); });
      });
    });
  }

  // ---------------------------------------------------------------------------
  // Home: projects carousel (autoplay + drag + dots)
  // ---------------------------------------------------------------------------
  function initCarousels() {
    $$('[data-carousel]').forEach(function (car) {
      var track = $('[data-carousel-track]', car);
      var viewport = $('[data-carousel-viewport]', car);
      var dotsWrap = $('[data-carousel-dots]', car);
      var slides = $$('[data-carousel-slide]', car);
      if (!track || !slides.length) return;
      var index = 0, perView = 3, dragging = false, startX = 0, offset = 0, moved = false;
      var autoplay = parseInt(car.dataset.autoplay || '0', 10);
      var timer = null;

      var maxIndex = function () { return Math.max(0, slides.length - perView); };
      var render = function () {
        index = Math.min(index, maxIndex());
        var w = 100 / perView;
        track.style.transform = 'translateX(calc(-' + (index * w) + '% + ' + offset + 'px))';
        $$('button', dotsWrap).forEach(function (d, i) {
          swapClasses(d, i === index ? 'w-8 bg-sky-500' : 'w-2 bg-gray-300', i === index ? 'w-2 bg-gray-300' : 'w-8 bg-sky-500');
        });
      };
      var buildDots = function () {
        dotsWrap.innerHTML = '';
        for (var i = 0; i <= maxIndex(); i++) {
          (function (i) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'h-2 rounded-full transition-all duration-300 w-2 bg-gray-300';
            b.setAttribute('aria-label', 'Go to slide ' + (i + 1));
            b.addEventListener('click', function () { index = i; render(); restart(); });
            dotsWrap.appendChild(b);
          })(i);
        }
      };
      var measure = function () {
        var w = window.innerWidth;
        var pv = w < 640 ? 1 : w < 1024 ? 2 : 3;
        if (pv !== perView || !dotsWrap.children.length) { perView = pv; buildDots(); }
        render();
      };
      var prev = function () { index = index <= 0 ? maxIndex() : index - 1; render(); };
      var next = function () { index = index >= maxIndex() ? 0 : index + 1; render(); };
      var restart = function () {
        if (timer) clearInterval(timer);
        if (autoplay && !editing && !reduceMotion) timer = setInterval(function () { if (!dragging) next(); }, autoplay);
      };

      var prevBtn = $('[data-carousel-prev]', car), nextBtn = $('[data-carousel-next]', car);
      prevBtn && prevBtn.addEventListener('click', function () { prev(); restart(); });
      nextBtn && nextBtn.addEventListener('click', function () { next(); restart(); });

      if (!editing) {
        track.addEventListener('pointerdown', function (e) {
          if (e.button !== 0) return;
          dragging = true; moved = false; startX = e.clientX; offset = 0;
          track.classList.remove('transition-transform', 'cursor-grab');
          track.classList.add('cursor-grabbing');
          try { track.setPointerCapture(e.pointerId); } catch (err) {}
        });
        track.addEventListener('pointermove', function (e) {
          if (!dragging) return;
          offset = e.clientX - startX;
          if (Math.abs(offset) > 4) moved = true;
          render();
        });
        var end = function () {
          if (!dragging) return;
          dragging = false;
          var threshold = (viewport.clientWidth || 1) / perView / 3;
          track.classList.add('transition-transform', 'cursor-grab');
          track.classList.remove('cursor-grabbing');
          if (offset > threshold) prev(); else if (offset < -threshold) next();
          offset = 0;
          render();
          restart();
        };
        track.addEventListener('pointerup', end);
        track.addEventListener('pointercancel', end);
        track.addEventListener('pointerleave', end);
        track.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); } }, true);
      }

      window.addEventListener('resize', measure);
      measure();
      restart();
    });
  }

  // ---------------------------------------------------------------------------
  // About: curved journey timeline (same geometry as the React version)
  // ---------------------------------------------------------------------------
  var JOURNEY_Y = [0.86, 0.8, 0.7, 0.48, 0.24, 0.04];
  function initJourney() {
    $$('[data-journey]').forEach(function (box) {
      var svg = $('[data-journey-svg]', box);
      var path = $('[data-journey-path]', box);
      var grad = $('linearGradient', box);
      var points = $$('[data-journey-point]', box);
      var drawn = false;
      var layout = function () {
        var w = box.clientWidth, h = box.clientHeight, n = points.length;
        if (!w || !n) return;
        var padX = Math.min(Math.max(96 + 8, w * 0.09), w * 0.16);
        var padY = Math.max(12, h * 0.06);
        var uw = Math.max(0, w - padX * 2), uh = Math.max(0, h - padY * 2);
        var pts = points.map(function (_, i) {
          var frac = JOURNEY_Y[i] !== undefined ? JOURNEY_Y[i] : JOURNEY_Y[JOURNEY_Y.length - 1];
          return { x: n > 1 ? padX + (uw * i) / (n - 1) : w / 2, y: padY + uh * frac };
        });
        var d = 'M ' + pts[0].x + ' ' + pts[0].y;
        for (var i = 1; i < pts.length; i++) {
          var p = pts[i - 1], c = pts[i], cx = p.x + (c.x - p.x) * 0.5;
          d += ' C ' + cx + ' ' + p.y + ', ' + cx + ' ' + c.y + ', ' + c.x + ' ' + c.y;
        }
        svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
        grad && grad.setAttribute('x2', String(w));
        path.setAttribute('d', d);
        points.forEach(function (el, i) { el.style.left = pts[i].x + 'px'; el.style.top = pts[i].y + 'px'; });
        var len = path.getTotalLength();
        path.style.strokeDasharray = len;
        if (!drawn) path.style.strokeDashoffset = editing || reduceMotion ? 0 : len;
      };
      layout();
      if ('ResizeObserver' in window) new ResizeObserver(layout).observe(box);
      else window.addEventListener('resize', layout);
      if (editing || reduceMotion || !('IntersectionObserver' in window)) { drawn = true; path.style.strokeDashoffset = 0; return; }
      var io = new IntersectionObserver(function (entries) {
        if (!entries[0].isIntersecting) return;
        io.disconnect();
        drawn = true;
        path.style.transition = 'stroke-dashoffset 1.2s cubic-bezier(0.22,1,0.36,1)';
        path.style.strokeDashoffset = 0;
      }, { threshold: 0.4 });
      io.observe(box);
    });
  }

  // ---------------------------------------------------------------------------
  // Parallax backgrounds (about + capabilities)
  // ---------------------------------------------------------------------------
  function initParallax() {
    var sections = $$('[data-parallax]');
    if (!sections.length || reduceMotion) return;
    var ticking = false;
    var update = function () {
      ticking = false;
      var vh = window.innerHeight;
      sections.forEach(function (sec) {
        var layer = $('[data-parallax-layer]', sec);
        if (!layer) return;
        var r = sec.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) return;
        var progress = Math.min(1, Math.max(0, (vh - r.top) / (vh + r.height)));
        var y = -30 + progress * 70;
        layer.style.transform = 'translate3d(0,' + y.toFixed(2) + '%,0)';
      });
    };
    var onScroll = function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } };
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    update();
  }

  // ---------------------------------------------------------------------------
  // Gallery: filters + lightbox
  // ---------------------------------------------------------------------------
  function initGallery() {
    $$('[data-gallery]').forEach(function (g) {
      var cat = '', loc = '';
      var items = $$('[data-gallery-item]', g);
      var empty = $('[data-gallery-empty]', g);
      var buttons = $$('[data-filter-cat]', g);
      var select = $('[data-filter-loc]', g);
      var apply = function () {
        var shown = 0;
        items.forEach(function (it, i) {
          var ok = (!cat || it.dataset.cat === cat) && (!loc || it.dataset.loc === loc);
          it.classList.toggle('hidden', !ok);
          if (ok) {
            shown++;
            if (!editing && !reduceMotion) {
              it.classList.remove('st-pop');
              void it.offsetWidth;
              it.style.setProperty('--d', (shown - 1) * 60 + 'ms');
              it.classList.add('st-pop');
            }
          }
        });
        empty && empty.classList.toggle('hidden', shown > 0);
        buttons.forEach(function (b) {
          var on = b.dataset.filterCat === cat;
          b.setAttribute('aria-pressed', String(on));
          swapClasses(b, on ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/25' : 'bg-white text-gray-700 hover:bg-gray-100',
            on ? 'bg-white text-gray-700 hover:bg-gray-100' : 'bg-sky-500 text-white shadow-lg shadow-sky-500/25');
        });
      };
      buttons.forEach(function (b) { b.addEventListener('click', function () { cat = b.dataset.filterCat; apply(); }); });
      select && select.addEventListener('change', function () { loc = select.value; apply(); });

      var lb = $('[data-lightbox]', g);
      if (!lb) return;
      var panel = $('[data-lightbox-panel]', lb);
      var imgs = $$('[data-lb-img]', lb);
      var dots = $('[data-lb-dots]', lb);
      var lastFocus = null;
      // Slideshow of the clicked project's category: one photo per second, never
      // paused (not even on hover) until the pop-up is closed.
      var slides = [], cur = 0, front = 0, timer = 0;
      var show = function (i) {
        cur = (i + slides.length) % slides.length;
        var s = slides[cur];
        var next = imgs[1 - front];
        next.src = s.dataset.img; next.alt = s.dataset.title;
        next.classList.remove('opacity-0');
        imgs[front].classList.add('opacity-0');
        front = 1 - front;
        $$('span', dots).forEach(function (d, j) { swapClasses(d, j === cur ? 'w-6 bg-white' : 'w-2 bg-white/60', j === cur ? 'w-2 bg-white/60' : 'w-6 bg-white'); });
      };
      var close = function () {
        clearInterval(timer);
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        document.body.style.overflow = '';
        lastFocus && lastFocus.focus();
      };
      items.forEach(function (it) {
        it.addEventListener('click', function (e) {
          if (e.target.closest('#cms-ui')) return;
          lastFocus = it;
          // Photos only: a project shown as a video is skipped by the slideshow.
          slides = items.filter(function (x) { return x.dataset.cat === it.dataset.cat && x.dataset.img && !/\.(mp4|webm)([?#]|$)/i.test(x.dataset.img); });
          if (!slides.length) return;
          slides.forEach(function (s) { new Image().src = s.dataset.img; });
          dots.innerHTML = slides.length > 1 ? slides.map(function () { return '<span class="h-2 w-2 rounded-full bg-white/60 transition-all duration-300"></span>'; }).join('') : '';
          imgs.forEach(function (im) { im.classList.add('opacity-0'); });
          show(Math.max(0, slides.indexOf(it)));
          clearInterval(timer);
          if (slides.length > 1) timer = setInterval(function () { show(cur + 1); }, 1000);
          lb.classList.remove('hidden');
          lb.classList.add('flex');
          panel.classList.remove('st-zoom'); void panel.offsetWidth; panel.classList.add('st-zoom');
          document.body.style.overflow = 'hidden';
          $('[data-lightbox-close]', lb).focus();
        });
      });
      lb.addEventListener('click', function (e) { if (!panel.contains(e.target)) close(); });
      $('[data-lightbox-close]', lb).addEventListener('click', close);
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !lb.classList.contains('hidden')) close(); });
    });
  }

  // ---------------------------------------------------------------------------
  // Service pages: keep the active pill visible in the mobile switcher
  // ---------------------------------------------------------------------------
  function initActiveScroll() {
    $$('[data-active-scroll]').forEach(function (nav) {
      var pill = $('[data-active]', nav);
      if (pill) nav.scrollLeft = pill.offsetLeft - nav.clientWidth / 2 + pill.clientWidth / 2;
    });
  }

  // ---------------------------------------------------------------------------
  // Contact form: prefill, field checks (same rules as app/lib/validate.php —
  // the server re-checks everything), AJAX submit with errors under each field
  // ---------------------------------------------------------------------------
  var LEAD_RULES = (function () {
    var re = function (src, fallback) {
      try { return new RegExp(src, 'u'); } catch (e) { return new RegExp(fallback); }
    };
    return {
      name: re("^(?=.{2,80}$)\\p{L}[\\p{L}\\p{M}.'’\\- ]*[\\p{L}\\p{M}.]$",
        "^(?=.{2,80}$)[A-Za-zÀ-ɏ][A-Za-zÀ-ɏ.'’\\- ]*[A-Za-zÀ-ɏ.]$"),
      email: /^(?!.*\.\.)[A-Za-z0-9](?:[A-Za-z0-9._%+\-]{0,62}[A-Za-z0-9])?@(?:[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,24}$/,
      phone: /^\+?[0-9(][0-9 ().\-]{5,24}[0-9]$/,
      company: re("^[\\p{L}\\p{N}][\\p{L}\\p{N}\\p{M} &.,'’()/+\\-–]{1,119}$",
        "^[A-Za-z0-9À-ɏ][A-Za-z0-9À-ɏ &.,'’()/+\\-–]{1,119}$")
    };
  })();
  var MAIL_TYPOS = { 'gmial.com': 'gmail.com', 'gamil.com': 'gmail.com', 'gmai.com': 'gmail.com', 'gmail.co': 'gmail.com',
    'gmail.con': 'gmail.com', 'gmail.cm': 'gmail.com', 'gmaill.com': 'gmail.com', 'gnail.com': 'gmail.com',
    'hotmial.com': 'hotmail.com', 'hotmal.com': 'hotmail.com', 'hotmail.con': 'hotmail.com', 'yaho.com': 'yahoo.com',
    'yahooo.com': 'yahoo.com', 'yahoo.con': 'yahoo.com', 'yahoo.co': 'yahoo.com', 'outlok.com': 'outlook.com',
    'outlook.con': 'outlook.com', 'rediffmail.con': 'rediffmail.com', 'redifmail.com': 'rediffmail.com' };

  function phoneOk(v) {
    if (!LEAD_RULES.phone.test(v) || (v.match(/\+/g) || []).length > 1) return false;
    var d = v.replace(/\D/g, ''), n = d.length;
    if (n < 7 || n > 15) return false;
    var uniq = {}; d.split('').forEach(function (c) { uniq[c] = 1; });
    if (Object.keys(uniq).length < 3) return false;
    if (/01234567|12345678|98765432/.test(d)) return false;
    var intl = v.charAt(0) === '+';
    if (intl && d.indexOf('91') === 0) return n === 12 && d.charAt(2) !== '0';
    if (!intl && n === 10) return d.charAt(0) !== '0';
    if (!intl && n === 11 && d.charAt(0) === '0') return d.charAt(1) !== '0';
    if (!intl && n === 12 && d.indexOf('91') === 0) return d.charAt(2) !== '0';
    return intl;
  }

  function leadFieldError(el, msg) {
    var v = el.value.trim();
    switch (el.name) {
      case 'name':
        return LEAD_RULES.name.test(v) && !/(.)\1{3,}/.test(v) ? '' : msg.name;
      case 'email': {
        if (!LEAD_RULES.email.test(v)) return msg.email;
        var at = v.lastIndexOf('@'), domain = v.slice(at + 1).toLowerCase();
        return MAIL_TYPOS[domain] ? 'Did you mean ' + v.slice(0, at + 1) + MAIL_TYPOS[domain] + '?' : '';
      }
      case 'phone':
        return phoneOk(v) ? '' : msg.phone;
      case 'company':
        return v === '' || (LEAD_RULES.company.test(v) && /[A-Za-zÀ-￿]/.test(v)) ? '' : msg.company;
      case 'service':
        return el.options.length < 2 || v !== '' ? '' : msg.service;
      case 'message': {
        if (v.length < 20 || v.length > 5000 || v.split(/\s+/).length < 3) return msg.message;
        if (/<\s*\/?\s*[a-z!]|\[\/?(url|link|img)/i.test(v) || (v.match(/(https?:\/\/|www\.)\S+/gi) || []).length > 1) return msg.message_links;
        return '';
      }
    }
    return '';
  }

  function initContactForm() {
    var form = $('[data-contact-form]');
    if (!form) return;
    var success = $('[data-contact-success]');
    var errorBox = $('[data-contact-error]', form);
    var defaultError = errorBox ? errorBox.textContent : '';
    var msg = {};
    try { msg = JSON.parse(form.getAttribute('data-messages') || '{}'); } catch (e) {}
    var select = form.querySelector('select[name="service"]');
    var m = document.cookie.match(/SPARKTECH_service_interest=([^;]+)/);
    if (m && select && !select.value) {
      var wanted = decodeURIComponent(m[1]);
      for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value === wanted) { select.value = wanted; break; }
      }
    }
    var btn = form.querySelector('button[type="submit"]');
    var label = $('[data-submit-label]', btn);
    var idle = label ? label.innerHTML : '';
    var fields = $$('input[name="name"], input[name="email"], input[name="phone"], input[name="company"], select[name="service"], textarea[name="message"]', form);

    var show = function (el, text) {
      var box = document.getElementById(el.id + '-err');
      el.setAttribute('aria-invalid', text ? 'true' : 'false');
      el.classList.toggle('st-field-ok', !text && el.value.trim() !== '');
      if (box) { box.textContent = text || ''; box.classList.toggle('is-shown', !!text); }
    };
    var check = function (el) { var t = leadFieldError(el, msg); show(el, t); return !t; };

    // Tidy values as people leave a field; re-check live once a field has been flagged.
    fields.forEach(function (el) {
      el.addEventListener('blur', function () {
        if (el.name === 'name' || el.name === 'company') el.value = el.value.replace(/\s+/g, ' ').trim();
        if (el.name === 'email') el.value = el.value.trim().toLowerCase();
        if (el.value.trim() !== '' || el.getAttribute('aria-invalid') === 'true') check(el);
      });
      el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', function () {
        if (el.name === 'phone') {
          var clean = el.value.replace(/[^0-9+ ().\-]/g, '');
          if (clean !== el.value) el.value = clean;
        }
        if (el.getAttribute('aria-invalid') === 'true') check(el);
      });
    });

    var msgBox = form.querySelector('textarea[name="message"]');
    var counter = $('[data-count-for="' + (msgBox && msgBox.id) + '"]', form);
    var count = function () {
      if (!counter || !msgBox) return;
      var n = msgBox.value.trim().length;
      counter.textContent = n < 20 ? n + ' / 20 min' : n + ' / 5000';
      counter.style.color = n > 0 && n < 20 ? '#f59e0b' : '';
    };
    msgBox && msgBox.addEventListener('input', count);
    count();

    form.addEventListener('submit', function (e) {
      if (editing) { e.preventDefault(); return; }
      var bad = fields.filter(function (el) { return !check(el); });
      if (bad.length) {
        e.preventDefault();
        if (errorBox) { errorBox.textContent = msg.summary || 'Please correct the highlighted fields.'; errorBox.classList.remove('hidden'); }
        bad[0].focus();
        return;
      }
      var sta = window.STA;
      if (sta) {
        form.elements._vid.value = sta.vid || '';
        form.elements._sid.value = sta.sid || '';
        form.elements._h.value = sta.human && sta.human() ? '1' : '';
      }
      if (!window.fetch || !window.FormData) return; // normal POST fallback
      e.preventDefault();
      errorBox && errorBox.classList.add('hidden');
      btn.disabled = true;
      if (label) label.innerHTML = '<svg class="inline h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> ' + (btn.dataset.labelSending || 'Sending...');
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
        .then(function (res) {
          if (res && res.ok) {
            form.reset();
            fields.forEach(function (el) { el.removeAttribute('aria-invalid'); el.classList.remove('st-field-ok'); });
            count();
            form.classList.add('hidden');
            success && success.classList.remove('hidden');
            success && success.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
          }
          var first = null;
          Object.keys((res && res.errors) || {}).forEach(function (k) {
            var el = form.elements[k];
            if (el && el.id) { show(el, res.errors[k]); first = first || el; }
          });
          if (errorBox) {
            errorBox.textContent = (res && res.message) || defaultError;
            errorBox.classList.remove('hidden');
          }
          first && first.focus();
        })
        .catch(function () { if (errorBox) { errorBox.textContent = defaultError; errorBox.classList.remove('hidden'); } })
        .then(function () { btn.disabled = false; if (label) label.innerHTML = idle; });
    });

    var again = $('[data-contact-again]');
    again && again.addEventListener('click', function (e) {
      e.preventDefault();
      success.classList.add('hidden');
      form.classList.remove('hidden');
      if (history.replaceState) history.replaceState(null, '', location.pathname);
    });
  }

  // ---------------------------------------------------------------------------
  // Popups: any link to "#popup-{id}" (or [data-open-popup="id"]) opens one
  // ---------------------------------------------------------------------------
  function initPopups() {
    var lastFocus = null;
    var openPopup = function (id) {
      var el = document.getElementById('popup-' + id);
      if (!el) return false;
      lastFocus = document.activeElement;
      el.hidden = false;
      requestAnimationFrame(function () { el.classList.add('is-open'); });
      document.body.style.overflow = 'hidden';
      var x = el.querySelector('.st-popup-x');
      x && x.focus({ preventScroll: true });
      return true;
    };
    var closePopup = function (el) {
      el.classList.remove('is-open');
      setTimeout(function () { el.hidden = true; }, 200);
      document.body.style.overflow = '';
      if (location.hash === '#popup-' + el.getAttribute('data-popup') && history.replaceState) {
        history.replaceState(null, '', location.pathname + location.search);
      }
      lastFocus && lastFocus.focus && lastFocus.focus({ preventScroll: true });
    };
    document.addEventListener('click', function (e) {
      if (!e.target.closest) return;
      var trigger = e.target.closest('a[href*="#popup-"], [data-open-popup]');
      if (trigger && !e.target.closest('#cms-ui, .cms-panel, .cms-modal')) {
        var id = trigger.getAttribute('data-open-popup') || (trigger.getAttribute('href') || '').split('#popup-')[1];
        if (id && openPopup(id)) { e.preventDefault(); return; }
      }
      var closer = e.target.closest('[data-popup-close]');
      var pop = closer && closer.closest('.st-popup');
      if (pop) { e.preventDefault(); closePopup(pop); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape' || document.querySelector('.cms-panel.is-open, .cms-modal')) return;
      var open = document.querySelector('.st-popup.is-open');
      open && closePopup(open);
    });
    if (location.hash.indexOf('#popup-') === 0) openPopup(location.hash.slice(7));
  }

  // ---------------------------------------------------------------------------
  // Photo-gallery sections: full-screen viewer with previous / next
  // ---------------------------------------------------------------------------
  function initLightboxGroups() {
    var box = null, items = [], index = 0;
    var show = function () {
      var it = items[index];
      var img = box.querySelector('img'), vid = box.querySelector('video');
      img.hidden = it.video; vid.hidden = !it.video;
      if (it.video) { img.removeAttribute('src'); vid.src = it.src; vid.play().catch(function () {}); }
      else { vid.pause(); vid.removeAttribute('src'); img.src = it.src; img.alt = it.caption; }
      box.querySelector('.st-lb-caption').textContent = it.caption;
      box.querySelector('.st-lb-count').textContent = (index + 1) + ' / ' + items.length;
    };
    var build = function () {
      box = document.createElement('div');
      box.className = 'st-lb';
      box.setAttribute('role', 'dialog');
      box.setAttribute('aria-modal', 'true');
      box.setAttribute('aria-label', 'Photo viewer');
      box.innerHTML = '<button type="button" class="st-lb-x" aria-label="Close">&times;</button>' +
        '<button type="button" class="st-lb-prev" aria-label="Previous">&#8249;</button>' +
        '<figure><img alt=""><video controls loop muted playsinline hidden></video><figcaption><span class="st-lb-caption"></span> <span class="st-lb-count"></span></figcaption></figure>' +
        '<button type="button" class="st-lb-next" aria-label="Next">&#8250;</button>';
      document.body.appendChild(box);
      var close = function () { box.classList.remove('is-open'); box.querySelector('video').pause(); document.body.style.overflow = ''; };
      box.addEventListener('click', function (e) {
        if (e.target.closest('.st-lb-prev')) { index = (index - 1 + items.length) % items.length; show(); }
        else if (e.target.closest('.st-lb-next')) { index = (index + 1) % items.length; show(); }
        else if (e.target.closest('.st-lb-x') || !e.target.closest('figure')) close();
      });
      document.addEventListener('keydown', function (e) {
        if (!box.classList.contains('is-open')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') { index = (index - 1 + items.length) % items.length; show(); }
        if (e.key === 'ArrowRight') { index = (index + 1) % items.length; show(); }
      });
    };
    document.addEventListener('click', function (e) {
      var btn = e.target.closest && e.target.closest('[data-lightbox-open]');
      if (!btn || e.target.closest('#cms-ui')) return;
      var group = btn.closest('[data-lightbox-group]');
      if (!group) return;
      var opens = $$('[data-lightbox-open]', group);
      items = opens.map(function (b) {
        var img = b.querySelector('img, video');
        var fig = b.closest('figure');
        var cap = fig && fig.querySelector('[data-lightbox-caption]');
        var video = !!img && img.tagName === 'VIDEO';
        return { src: img ? img.currentSrc || img.src : '', video: video, caption: cap ? cap.textContent.trim() : (img ? img.alt || img.getAttribute('aria-label') || '' : '') };
      });
      index = opens.indexOf(btn);
      if (!box) build();
      show();
      box.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    });
  }

  // Links to other websites open in a new tab.
  function initExternalLinks() {
    $$('a[href^="http"]').forEach(function (a) {
      if (a.host && a.host !== location.host && !a.hasAttribute('target')) {
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
      }
    });
  }

  function init() {
    initPopups();
    initLightboxGroups();
    initExternalLinks();
    initReveal();
    initHeader();
    initCounters();
    initTechAccordion();
    initCarousels();
    initJourney();
    initParallax();
    initGallery();
    initActiveScroll();
    initContactForm();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
