/* =============================================================================
   Public site behaviour. No framework, no build step — one file, small pieces
   that each look for their own markup and do nothing when it is not on the page.
   ========================================================================== */
(function () {
  'use strict';

  var $  = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  };

  /* ------------------------------------------------------------- theme --- */

  var THEME_KEY = 'sbs-theme';

  function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    try { localStorage.setItem(THEME_KEY, theme); } catch (e) {}
  }

  $$('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var now = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      setTheme(now);
    });
  });

  /* ------------------------------------------------------- mobile menu --- */

  var burger = $('[data-burger]');
  var mnav   = $('[data-mobile-nav]');
  if (burger && mnav) {
    burger.addEventListener('click', function () {
      var open = mnav.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* --------------------------------------------------------------- FAQ --- */

  $$('.faq-q').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq-item');
      var open = item.classList.contains('is-open');

      // One open at a time within the same grid keeps the two columns tidy.
      var group = item.closest('.faq-grid') || document;
      $$('.faq-item.is-open', group).forEach(function (el) { el.classList.remove('is-open'); });

      if (!open) { item.classList.add('is-open'); }
      btn.setAttribute('aria-expanded', !open ? 'true' : 'false');
    });
  });

  /* -------------------------------------------------------- album tabs --- */

  $$('[data-album-tabs]').forEach(function (tabs) {
    var cards = $$('[data-album-cat]');
    tabs.addEventListener('click', function (ev) {
      var btn = ev.target.closest('.album-tab');
      if (!btn) { return; }

      $$('.album-tab', tabs).forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');

      var want = btn.getAttribute('data-cat');
      cards.forEach(function (card) {
        var show = want === 'all' || card.getAttribute('data-album-cat') === want;
        card.style.display = show ? '' : 'none';
      });
    });
  });

  /* ------------------------------------------------------ review slider -- */

  (function () {
    var rail = $('[data-review-rail]');
    var dots = $('[data-review-dots]');
    if (!rail || !dots) { return; }

    var cards   = $$('.review-card', rail);
    var perPage = window.matchMedia('(max-width: 1024px)').matches ? 1 : 3;
    var pages   = Math.max(1, Math.ceil(cards.length / perPage));
    var current = 0;

    function render() {
      cards.forEach(function (card, i) {
        var page = Math.floor(i / perPage);
        card.style.display = page === current ? '' : 'none';
      });
      $$('.dot', dots).forEach(function (d, i) {
        d.classList.toggle('is-active', i === current);
      });
    }

    dots.innerHTML = '';
    for (var i = 0; i < pages; i++) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'dot';
      b.setAttribute('aria-label', 'รีวิวชุดที่ ' + (i + 1));
      (function (idx) {
        b.addEventListener('click', function () { current = idx; render(); });
      })(i);
      dots.appendChild(b);
    }
    render();

    // Re-page when the breakpoint changes rather than on every resize pixel.
    var mq = window.matchMedia('(max-width: 1024px)');
    (mq.addEventListener ? mq.addEventListener.bind(mq, 'change') : mq.addListener.bind(mq))(function () {
      perPage = mq.matches ? 1 : 3;
      pages   = Math.max(1, Math.ceil(cards.length / perPage));
      current = 0;
      dots.innerHTML = '';
      for (var j = 0; j < pages; j++) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'dot';
        (function (idx) {
          btn.addEventListener('click', function () { current = idx; render(); });
        })(j);
        dots.appendChild(btn);
      }
      render();
    });
  })();

  /* ---------------------------------------------------------- lightbox --- */

  (function () {
    var box = $('[data-lightbox]');
    if (!box) { return; }

    var img     = $('[data-lb-image]', box);
    var caption = $('[data-lb-caption]', box);
    var counter = $('[data-lb-count]', box);
    var items   = [];
    var index   = 0;

    function collect() {
      items = $$('[data-lb-src]');
    }

    function show(i) {
      if (!items.length) { return; }
      index = (i + items.length) % items.length;
      var el = items[index];
      img.src = el.getAttribute('data-lb-src');
      img.alt = el.getAttribute('data-lb-caption') || '';
      caption.textContent = el.getAttribute('data-lb-caption') || '';
      counter.textContent = (index + 1) + ' / ' + items.length;
    }

    function open(i) {
      box.hidden = false;
      box.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      show(i);
    }

    function close() {
      box.classList.remove('is-open');
      box.hidden = true;
      document.body.style.overflow = '';
      img.src = '';
    }

    document.addEventListener('click', function (ev) {
      var trigger = ev.target.closest('[data-lb-src]');
      if (trigger && !ev.target.closest('.photo-cell__actions')) {
        ev.preventDefault();
        collect();
        open(items.indexOf(trigger));
      }
    });

    $('[data-lb-close]', box).addEventListener('click', close);
    $('[data-lb-prev]', box).addEventListener('click', function () { show(index - 1); });
    $('[data-lb-next]', box).addEventListener('click', function () { show(index + 1); });
    box.addEventListener('click', function (ev) { if (ev.target === box) { close(); } });

    document.addEventListener('keydown', function (ev) {
      if (box.hidden) { return; }
      if (ev.key === 'Escape')     { close(); }
      if (ev.key === 'ArrowLeft')  { show(index - 1); }
      if (ev.key === 'ArrowRight') { show(index + 1); }
    });
  })();

  /* ------------------------------------------------------ copy to clip --- */

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-copy]');
    if (!btn) { return; }
    ev.preventDefault();

    var text = btn.getAttribute('data-copy');
    var done = function () {
      var old = btn.getAttribute('title') || '';
      btn.setAttribute('title', 'คัดลอกแล้ว');
      toast('คัดลอกลิงก์แล้ว');
      setTimeout(function () { btn.setAttribute('title', old); }, 1800);
    };

    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(done, done);
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(ta);
      done();
    }
  });

  /* -------------------------------------------------------------- toast -- */

  function toast(message, type) {
    var el = document.createElement('div');
    el.textContent = message;
    el.style.cssText =
      'position:fixed;left:50%;bottom:28px;transform:translateX(-50%) translateY(10px);' +
      'z-index:900;padding:11px 22px;border-radius:100px;font-size:14px;font-weight:600;' +
      'color:#fff;box-shadow:0 8px 26px rgba(0,0,0,.28);opacity:0;transition:all .25s ease;' +
      'background:' + (type === 'error' ? '#C8443A' : '#241F19') + ';';
    document.body.appendChild(el);
    requestAnimationFrame(function () {
      el.style.opacity = '1';
      el.style.transform = 'translateX(-50%) translateY(0)';
    });
    setTimeout(function () {
      el.style.opacity = '0';
      el.style.transform = 'translateX(-50%) translateY(10px)';
      setTimeout(function () { el.remove(); }, 300);
    }, 2200);
  }
  window.sbsToast = toast;

  /* ------------------------------------------------------- form guards --- */

  $$('form[data-guard]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('[type="submit"]');
      if (btn) {
        btn.setAttribute('aria-disabled', 'true');
        btn.dataset.label = btn.textContent;
        btn.textContent = 'กำลังส่ง...';
      }
    });
  });

  /* -------------------------------------------------- reveal on scroll --- */

  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'none';
          io.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -40px 0px', threshold: 0.05 });

    $$('[data-reveal]').forEach(function (el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(14px)';
      el.style.transition = 'opacity .5s ease, transform .5s ease';
      io.observe(el);
    });
  }
})();
