/* =============================================================================
   Album uploader — built for a photographer's real workload: several hundred
   full-size JPEGs in one go.

   Each file is sent as its own request rather than one giant multipart POST,
   because nginx cuts an upload at roughly 128 MiB no matter what PHP allows.
   Three run at a time: enough to saturate a home upload link, few enough that
   the server is not asked to resize six 40 MP files at once.
   ========================================================================== */
(function () {
  'use strict';

  var A = window.SBSAdmin;
  if (!A) { return; }

  var zone = A.$('[data-dropzone]');
  if (!zone) { return; }

  var input      = A.$('[data-file-input]');
  var list       = A.$('[data-up-list]');
  var summary    = A.$('[data-up-summary]');
  var barFill    = A.$('[data-up-bar]');
  var countEl    = A.$('[data-up-count]');
  var endpoint   = zone.getAttribute('data-endpoint');
  var albumId    = zone.getAttribute('data-album');
  var folderSel  = A.$('[data-up-folder]');

  var CONCURRENCY = 3;
  var MAX_BYTES   = 100 * 1024 * 1024;   // one file; nginx caps the request at ~128 MiB
  var ACCEPT      = ['image/jpeg', 'image/png', 'image/webp'];

  var queue    = [];
  var active   = 0;
  var done     = 0;
  var failed   = 0;
  var total    = 0;
  var uploaded = [];

  /* ------------------------------------------------------------- picking -- */

  zone.addEventListener('click', function () { input.click(); });

  zone.addEventListener('dragover', function (ev) {
    ev.preventDefault();
    zone.classList.add('is-over');
  });
  zone.addEventListener('dragleave', function () { zone.classList.remove('is-over'); });
  zone.addEventListener('drop', function (ev) {
    ev.preventDefault();
    zone.classList.remove('is-over');
    addFiles(ev.dataTransfer.files);
  });

  input.addEventListener('change', function () {
    addFiles(input.files);
    input.value = '';           // so picking the same file again still fires
  });

  function addFiles(fileList) {
    var files = Array.prototype.slice.call(fileList);
    if (!files.length) { return; }

    var rejected = 0;
    files.forEach(function (file) {
      if (ACCEPT.indexOf(file.type) === -1) { rejected++; return; }
      if (file.size > MAX_BYTES) { rejected++; return; }
      queue.push({ file: file, row: makeRow(file) });
      total++;
    });

    if (rejected) {
      A.toast(rejected + ' ไฟล์ถูกข้าม (ต้องเป็น JPG, PNG หรือ WebP และไม่เกิน 100 MB ต่อไฟล์)', 'warn');
    }

    summary.style.display = total ? 'flex' : 'none';
    render();
    pump();
  }

  /* ------------------------------------------------------------ each row -- */

  function makeRow(file) {
    var row = document.createElement('div');
    row.className = 'up-row';
    row.innerHTML =
      '<img class="up-row__thumb" alt="">' +
      '<span class="up-row__name"></span>' +
      '<span class="text-xs text-faint"></span>' +
      '<span class="up-row__state is-wait">รอคิว</span>';

    row.children[1].textContent = file.name;
    row.children[2].textContent = fmtBytes(file.size);

    // A local preview so the studio can see what is going up without waiting.
    var url = URL.createObjectURL(file);
    row.children[0].src = url;
    row.children[0].addEventListener('load', function () { URL.revokeObjectURL(url); });

    list.appendChild(row);
    return row;
  }

  function setState(row, text, cls) {
    var el = row.children[3];
    el.textContent = text;
    el.className = 'up-row__state ' + (cls || '');
  }

  /* -------------------------------------------------------------- pumping -- */

  function pump() {
    while (active < CONCURRENCY && queue.length) {
      send(queue.shift());
    }
    if (!active && !queue.length && total) { finish(); }
  }

  function send(item, attempt) {
    attempt = attempt || 1;
    active++;
    setState(item.row, '0%', '');

    var fd = new FormData();
    fd.append('photo', item.file, item.file.name);
    fd.append('album_id', albumId);
    fd.append('folder_id', folderSel ? folderSel.value : '');
    fd.append('_token', window.SBS.csrf);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', endpoint, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('X-CSRF-Token', window.SBS.csrf);

    xhr.upload.addEventListener('progress', function (ev) {
      if (ev.lengthComputable) {
        setState(item.row, Math.round(ev.loaded / ev.total * 100) + '%', '');
      }
    });

    xhr.addEventListener('load', function () {
      active--;
      var res = null;
      try { res = JSON.parse(xhr.responseText); } catch (e) {}

      if (xhr.status === 200 && res && res.ok) {
        done++;
        setState(item.row, 'เสร็จ', 'is-done');
        uploaded.push(res.photo);
        addToGrid(res.photo);
      } else if (attempt < 3 && xhr.status !== 419) {
        // A couple of retries cover the odd dropped connection on venue wifi.
        // `active` was already decremented above; send() will raise it again.
        setState(item.row, 'ลองใหม่...', '');
        setTimeout(function () { send(item, attempt + 1); }, 900 * attempt);
        return;
      } else {
        failed++;
        setState(item.row, (res && res.error) ? 'ไม่สำเร็จ' : 'ไม่สำเร็จ', 'is-error');
        item.row.title = (res && res.error) || ('HTTP ' + xhr.status);
      }
      render();
      pump();
    });

    xhr.addEventListener('error', function () {
      active--;
      if (attempt < 3) {
        setState(item.row, 'ลองใหม่...', '');
        setTimeout(function () { send(item, attempt + 1); }, 900 * attempt);
        return;
      }
      failed++;
      setState(item.row, 'เชื่อมต่อไม่ได้', 'is-error');
      render();
      pump();
    });

    xhr.send(fd);
  }

  function render() {
    var settled = done + failed;
    barFill.style.width = total ? (settled / total * 100) + '%' : '0';
    countEl.textContent = settled + ' / ' + total + ' ไฟล์'
      + (failed ? ' · ไม่สำเร็จ ' + failed : '');
  }

  function finish() {
    if (failed) {
      A.toast('อัปโหลดเสร็จ ' + done + ' ไฟล์ · ไม่สำเร็จ ' + failed + ' ไฟล์', 'warn');
    } else {
      A.toast('อัปโหลด ' + done + ' รูปเรียบร้อยแล้ว');
    }
    // Reload once the queue is fully drained so counts and folders are exact.
    setTimeout(function () { window.location.reload(); }, 1400);
  }

  /* ------------------------------------------- live insert into the grid -- */

  function addToGrid(photo) {
    var grid = A.$('[data-photo-grid]');
    if (!grid || !photo) { return; }

    var el = document.createElement('div');
    el.className = 'ph';
    el.setAttribute('data-photo-id', photo.id);
    el.innerHTML =
      '<div class="ph__media">' +
        '<input class="ph__check" type="checkbox" data-photo-check aria-label="เลือกรูปนี้">' +
        '<img src="' + photo.thumb + '" alt="" loading="lazy">' +
      '</div>' +
      '<div class="ph__body">' +
        '<div class="ph__name">' + escapeHtml(photo.name) + '</div>' +
        '<div class="ph__meta"><span>' + photo.size + '</span><span>' + photo.date + '</span></div>' +
      '</div>';
    grid.insertBefore(el, grid.firstChild);
  }

  /* --------------------------------------------------------------- utils -- */

  function fmtBytes(n) {
    var u = ['B', 'KB', 'MB', 'GB'];
    var i = 0;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return (i < 2 ? Math.round(n) : n.toFixed(1)) + ' ' + u[i];
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  // Leaving mid-upload would silently lose the rest of the queue.
  window.addEventListener('beforeunload', function (ev) {
    if (active || queue.length) {
      ev.preventDefault();
      ev.returnValue = '';
    }
  });
})();
