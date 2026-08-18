/* =============================================================================
   Admin panel behaviour. Vanilla, no build step.
   Exposes window.SBSAdmin for page-specific scripts (the uploader uses it).
   ========================================================================== */
(function () {
  'use strict';

  var $  = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* --------------------------------------------------------------- toast -- */

  function toast(message, type) {
    var el = document.createElement('div');
    el.textContent = message;
    el.style.cssText =
      'position:fixed;right:22px;bottom:22px;z-index:900;max-width:340px;' +
      'padding:12px 18px;border-radius:10px;font-size:14px;font-weight:600;color:#fff;' +
      'box-shadow:0 10px 30px rgba(0,0,0,.24);opacity:0;transform:translateY(10px);' +
      'transition:all .25s ease;background:' +
      (type === 'error' ? '#C8443A' : type === 'warn' ? '#C98A18' : '#2E9E5B') + ';';
    document.body.appendChild(el);
    requestAnimationFrame(function () {
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    setTimeout(function () {
      el.style.opacity = '0';
      el.style.transform = 'translateY(10px)';
      setTimeout(function () { el.remove(); }, 300);
    }, type === 'error' ? 4800 : 2600);
  }

  /* ---------------------------------------------------------------- ajax -- */

  function post(url, data) {
    var body;
    if (data instanceof FormData) {
      body = data;
      body.append('_token', window.SBS.csrf);
    } else {
      body = new FormData();
      Object.keys(data || {}).forEach(function (k) {
        var v = data[k];
        if (Array.isArray(v)) {
          v.forEach(function (item) { body.append(k + '[]', item); });
        } else {
          body.append(k, v);
        }
      });
      body.append('_token', window.SBS.csrf);
    }

    return fetch(url, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': window.SBS.csrf }
    }).then(function (res) {
      return res.json().catch(function () {
        throw new Error('เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง (' + res.status + ')');
      });
    }).then(function (json) {
      if (!json.ok) { throw new Error(json.error || 'ทำรายการไม่สำเร็จ'); }
      return json;
    });
  }

  /* ------------------------------------------------------------- sidebar -- */

  var side     = $('[data-side]');
  var backdrop = $('[data-side-backdrop]');

  function closeSide() {
    if (side) { side.classList.remove('is-open'); }
    if (backdrop) { backdrop.classList.remove('is-open'); }
  }

  $$('[data-side-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!side) { return; }
      var open = side.classList.toggle('is-open');
      if (backdrop) { backdrop.classList.toggle('is-open', open); }
    });
  });
  if (backdrop) { backdrop.addEventListener('click', closeSide); }

  /* --------------------------------------------------------------- theme -- */

  $$('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var now = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', now);
      try { localStorage.setItem('sbs-theme', now); } catch (e) {}
    });
  });

  /* ----------------------------------------------------------- dropdowns -- */

  document.addEventListener('click', function (ev) {
    var toggle = ev.target.closest('[data-dropdown-toggle]');
    var open   = $$('.dropdown.is-open');

    if (toggle) {
      var dd = toggle.closest('.dropdown');
      open.forEach(function (d) { if (d !== dd) { d.classList.remove('is-open'); } });
      dd.classList.toggle('is-open');
      ev.stopPropagation();
      return;
    }
    if (!ev.target.closest('.dropdown__menu')) {
      open.forEach(function (d) { d.classList.remove('is-open'); });
    }
  });

  /* ---------------------------------------------------------------- tabs -- */

  $$('[data-tabs]').forEach(function (bar) {
    bar.addEventListener('click', function (ev) {
      var tab = ev.target.closest('.tab');
      if (!tab) { return; }

      $$('.tab', bar).forEach(function (t) { t.classList.remove('is-active'); });
      tab.classList.add('is-active');

      var name  = tab.getAttribute('data-tab');
      var scope = bar.closest('[data-tab-scope]') || document;
      $$('[data-tab-panel]', scope).forEach(function (panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-tab-panel') === name);
      });

      // Keep the chosen tab across a reload without adding a history entry.
      try {
        var u = new URL(window.location.href);
        u.searchParams.set('tab', name);
        history.replaceState(null, '', u);
      } catch (e) {}
    });
  });

  /* --------------------------------------------------------------- modal -- */

  function openModal(sel) {
    var m = typeof sel === 'string' ? $(sel) : sel;
    if (m) { m.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
    return m;
  }
  function closeModal(m) {
    (m ? [m] : $$('.modal.is-open')).forEach(function (el) { el.classList.remove('is-open'); });
    document.body.style.overflow = '';
  }

  document.addEventListener('click', function (ev) {
    var opener = ev.target.closest('[data-modal-open]');
    if (opener) {
      ev.preventDefault();
      openModal(opener.getAttribute('data-modal-open'));
      return;
    }
    if (ev.target.closest('[data-modal-close]')) {
      closeModal(ev.target.closest('.modal'));
      return;
    }
    if (ev.target.classList.contains('modal')) { closeModal(ev.target); }
  });

  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') { closeModal(); }
  });

  /* ------------------------------------------------------------- confirm -- */

  var confirmModal = $('[data-confirm-modal]');
  var confirmFn    = null;

  function confirmAction(text, onOk, title) {
    if (!confirmModal) {
      if (window.confirm(text)) { onOk(); }
      return;
    }
    $('[data-confirm-text]', confirmModal).textContent = text;
    $('[data-confirm-title]', confirmModal).textContent = title || 'ยืนยันการทำรายการ';
    confirmFn = onOk;
    openModal(confirmModal);
  }

  if (confirmModal) {
    $('[data-confirm-ok]', confirmModal).addEventListener('click', function () {
      closeModal(confirmModal);
      if (confirmFn) { confirmFn(); confirmFn = null; }
    });
  }

  // Any link or button carrying data-confirm asks first.
  document.addEventListener('click', function (ev) {
    var el = ev.target.closest('[data-confirm]');
    if (!el || el.dataset.confirmed === '1') { return; }
    ev.preventDefault();
    confirmAction(el.getAttribute('data-confirm'), function () {
      el.dataset.confirmed = '1';
      el.click();
    });
  });

  // Forms carrying data-confirm ask before submitting.
  $$('form[data-confirm-submit]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (form.dataset.confirmed === '1') { return; }
      ev.preventDefault();
      confirmAction(form.getAttribute('data-confirm-submit'), function () {
        form.dataset.confirmed = '1';
        form.submit();
      });
    });
  });

  /* ------------------------------------------------- slug auto-fill ------- */

  $$('[data-slug-source]').forEach(function (src) {
    var target = $('[data-slug-target]', src.form || document);
    if (!target) { return; }
    src.addEventListener('input', function () {
      if (target.dataset.touched === '1') { return; }
      target.value = src.value.trim().toLowerCase()
        .replace(/[^\p{L}\p{M}\p{N}]+/gu, '-')
        .replace(/^-+|-+$/g, '');
    });
    target.addEventListener('input', function () { target.dataset.touched = '1'; });
  });

  /* ------------------------------------------------- table row search ----- */

  $$('[data-filter-table]').forEach(function (input) {
    var table = $(input.getAttribute('data-filter-table'));
    if (!table) { return; }
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      $$('tbody tr', table).forEach(function (tr) {
        tr.style.display = !q || tr.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
      });
    });
  });

  /* -------------------------------------------------- copy to clipboard --- */

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-copy]');
    if (!btn) { return; }
    ev.preventDefault();
    var text = btn.getAttribute('data-copy');
    var done = function () { toast('คัดลอกแล้ว'); };
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(done, done);
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); } catch (e) {}
      ta.remove();
      done();
    }
  });

  /* --------------------------------------------------- image file preview -- */

  $$('input[type="file"][data-preview]').forEach(function (input) {
    var img = $(input.getAttribute('data-preview'));
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (file && img) { img.src = URL.createObjectURL(file); }
    });
  });

  /* ------------------------------------------------------- drag to sort --- */

  $$('[data-sortable]').forEach(function (list) {
    var dragged = null;

    list.addEventListener('dragstart', function (ev) {
      var row = ev.target.closest('[data-sort-id]');
      if (!row) { return; }
      dragged = row;
      row.style.opacity = '.45';
      ev.dataTransfer.effectAllowed = 'move';
    });

    list.addEventListener('dragend', function () {
      if (dragged) { dragged.style.opacity = ''; }
      dragged = null;
      saveOrder();
    });

    list.addEventListener('dragover', function (ev) {
      ev.preventDefault();
      var over = ev.target.closest('[data-sort-id]');
      if (!over || over === dragged || !dragged) { return; }
      var rect = over.getBoundingClientRect();
      var after = (ev.clientY - rect.top) > rect.height / 2;
      over.parentNode.insertBefore(dragged, after ? over.nextSibling : over);
    });

    function saveOrder() {
      var ids = $$('[data-sort-id]', list).map(function (el) { return el.getAttribute('data-sort-id'); });
      post(list.getAttribute('data-sortable'), { ids: ids })
        .then(function () { toast('บันทึกลำดับใหม่แล้ว'); })
        .catch(function (err) { toast(err.message, 'error'); });
    }
  });

  window.SBSAdmin = {
    $: $, $$: $$,
    toast: toast,
    post: post,
    confirm: confirmAction,
    openModal: openModal,
    closeModal: closeModal
  };
})();
