/* =============================================================================
   Chat widget client — talks to tobwai.com's chat API.
   Loaded only when inc/chat-widget.php has rendered, which only happens when
   the admin has switched chat on and supplied a site key.
   ========================================================================== */
(function () {
  'use strict';

  var root = document.querySelector('[data-tw-chat]');
  if (!root) { return; }

  var endpoint = root.getAttribute('data-endpoint');
  var siteKey  = root.getAttribute('data-key');

  var panel = root.querySelector('[data-tw-panel]');
  var log   = root.querySelector('[data-tw-log]');
  var form  = root.querySelector('[data-tw-form]');
  var input = root.querySelector('[data-tw-input]');
  var send  = root.querySelector('[data-tw-send]');

  /**
   * One id per browser, kept for the life of the conversation so tobwai can
   * thread replies. Format matches what its API validates: 32 hex characters,
   * no dashes. Anything else is rejected server-side as "not from our widget".
   */
  var VID_KEY = 'tw-chat-vid';
  var vid;
  try {
    vid = localStorage.getItem(VID_KEY);
  } catch (e) { vid = null; }

  if (!vid || !/^[a-f0-9]{32}$/.test(vid)) {
    vid = (window.crypto && crypto.randomUUID)
      ? crypto.randomUUID().replace(/-/g, '')
      : Array.from({ length: 32 }, function () {
          return '0123456789abcdef'[Math.floor(Math.random() * 16)];
        }).join('');
    try { localStorage.setItem(VID_KEY, vid); } catch (e) {}
  }

  /* ------------------------------------------------------------- opening -- */

  function open() {
    panel.hidden = false;
    root.classList.add('is-open');
    setTimeout(function () { input.focus(); }, 60);
    scrollDown();
  }
  function close() {
    panel.hidden = true;
    root.classList.remove('is-open');
  }

  root.querySelector('[data-tw-open]').addEventListener('click', open);
  root.querySelector('[data-tw-close]').addEventListener('click', close);
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && !panel.hidden) { close(); }
  });

  /* ------------------------------------------------------------ messages -- */

  function bubble(text, kind) {
    var el = document.createElement('div');
    el.className = 'tw-msg tw-msg--' + kind;
    el.textContent = text;
    log.appendChild(el);
    scrollDown();
    return el;
  }

  function scrollDown() {
    log.scrollTop = log.scrollHeight;
  }

  /**
   * Turn whatever went wrong into something a parent can act on. A studio's
   * customer does not benefit from "HTTP 500" — they benefit from knowing the
   * chat is down and that LINE still works.
   */
  function explain(error) {
    switch (error) {
      case 'rate_limited':
        return 'ส่งข้อความถี่เกินไป รบกวนรอสักครู่แล้วลองใหม่ค่ะ';
      case 'empty_text':
        return 'พิมพ์ข้อความก่อนกดส่งนะคะ';
      case 'bad_key':
      case 'no_site':
        return 'ระบบแชทยังเชื่อมต่อไม่สมบูรณ์ รบกวนทักทาง LINE แทนก่อนนะคะ';
      case 'network':
        return 'เชื่อมต่อไม่ได้ในตอนนี้ รบกวนทักทาง LINE แทนก่อนนะคะ';
      default:
        return 'ขออภัยค่ะ ระบบแชทมีปัญหาชั่วคราว รบกวนทักทาง LINE แทนก่อนนะคะ';
    }
  }

  var busy = false;

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    if (busy) { return; }

    var text = input.value.trim();
    if (!text) { return; }

    bubble(text, 'out');
    input.value = '';
    busy = true;
    send.disabled = true;

    var typing = bubble('กำลังพิมพ์...', 'in tw-msg--typing');

    fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      // No cookies: this is a cross-site call and the conversation is keyed by
      // vid, not by a session on tobwai's domain.
      credentials: 'omit',
      body: JSON.stringify({ action: 'send', vid: vid, text: text, key: siteKey })
    })
      .then(function (res) {
        return res.json().catch(function () { throw new Error('bad_json'); });
      })
      .then(function (json) {
        typing.remove();
        if (json && json.ok && json.reply) {
          bubble(json.reply, 'in');
        } else {
          bubble(explain(json && json.error), 'err');
        }
      })
      .catch(function () {
        typing.remove();
        bubble(explain('network'), 'err');
      })
      .then(function () {
        busy = false;
        send.disabled = false;
        input.focus();
      });
  });
})();
