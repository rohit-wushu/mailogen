/* Eventogen Mailer - front-end behaviour (jQuery + Bootstrap) */
(function ($) {
  'use strict';

  // ---- Toast notifications (replace native alert) ----
  function ensureStack() {
    var s = document.getElementById('toastStack');
    if (!s) { s = document.createElement('div'); s.id = 'toastStack'; s.className = 'toast-stack'; document.body.appendChild(s); }
    return s;
  }
  window.toast = function (message, type) {
    type = type || 'info';
    var icons = { success: 'check-circle-fill', danger: 'x-circle-fill', error: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
    var cls = type === 'error' ? 'danger' : type;
    var el = document.createElement('div');
    el.className = 'app-toast app-toast--' + cls;
    el.innerHTML = '<i class="bi bi-' + (icons[type] || icons.info) + '"></i><span></span><button aria-label="Close">&times;</button>';
    el.querySelector('span').textContent = message;
    var stack = ensureStack();
    stack.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('show'); });
    var kill = function () { el.classList.remove('show'); setTimeout(function () { el.remove(); }, 250); };
    el.querySelector('button').addEventListener('click', kill);
    setTimeout(kill, 5000);
  };
  // Render any server-side flash messages as toasts.
  if (window.__flashes && window.__flashes.length) {
    window.__flashes.forEach(function (f) { window.toast(f.msg, f.type); });
  }

  // ---- Styled confirm dialog (replaces window.confirm) ----
  window.confirmDialog = function (message, opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      var back = document.createElement('div');
      back.className = 'confirm-backdrop';
      back.innerHTML =
        '<div class="confirm-box">' +
          '<div class="confirm-msg"></div>' +
          '<div class="confirm-actions">' +
            '<button class="btn btn-light btn-sm" data-no>' + (opts.cancel || 'Cancel') + '</button>' +
            '<button class="btn btn-' + (opts.danger ? 'danger' : 'primary') + ' btn-sm" data-yes>' + (opts.ok || 'Confirm') + '</button>' +
          '</div>' +
        '</div>';
      back.querySelector('.confirm-msg').textContent = message;
      document.body.appendChild(back);
      requestAnimationFrame(function () { back.classList.add('show'); });
      var close = function (val) { back.classList.remove('show'); setTimeout(function () { back.remove(); }, 200); resolve(val); };
      back.querySelector('[data-yes]').addEventListener('click', function () { close(true); });
      back.querySelector('[data-no]').addEventListener('click', function () { close(false); });
      back.addEventListener('click', function (e) { if (e.target === back) close(false); });
    });
  };

  // ---- Sidebar toggle (mobile) ----
  var $sidebar = $('#sidebar');
  var $sidebarBackdrop = $('#sidebarBackdrop');
  function closeMobileSidebar() {
    $sidebar.removeClass('open');
    $sidebarBackdrop.removeClass('show');
  }
  $('#sidebarToggle').on('click', function () {
    $sidebar.toggleClass('open');
    $sidebarBackdrop.toggleClass('show', $sidebar.hasClass('open'));
  });
  $sidebarBackdrop.on('click', closeMobileSidebar);
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') closeMobileSidebar();
  });

  // ---- Sidebar nav groups (Audience / Reports inline accordion) ----
  $('.nav-group-toggle').on('click', function () {
    $(this).closest('.nav-group').toggleClass('open');
  });

  // ---- Theme toggle (top-bar button + sidebar switch stay in sync) ----
  function applyTheme(next) {
    document.documentElement.setAttribute('data-bs-theme', next);
    $('#themeToggle i').attr('class', next === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill');
    $('#themeSwitch').prop('checked', next === 'dark');
    $('#themeLabel').text(next === 'dark' ? 'Dark Mode' : 'Light Mode');
    $.ajax({
      url: $('#themeForm').attr('action'),
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      data: { theme: next, _csrf: $('#themeForm input[name=_csrf]').val() }
    });
  }
  $('#themeToggle').on('click', function () {
    applyTheme(document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
  });
  $('#themeSwitch').on('change', function () {
    applyTheme(this.checked ? 'dark' : 'light');
  });

  // ---- Command palette (Ctrl/Cmd + K) ----
  (function () {
    var cmdk = document.getElementById('cmdk');
    if (!cmdk) return;
    var input = document.getElementById('cmdkInput');
    var list = document.getElementById('cmdkList');
    var empty = document.getElementById('cmdkEmpty');
    var items = window.CMDK_ITEMS || [];
    var dataItems = [];    // live results from the server (contacts/campaigns/templates)
    var visible = [];      // currently rendered items
    var active = 0;
    var dataTimer = null;
    var isMac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);

    // Show ⌘ instead of Ctrl on Mac in the topbar hint.
    if (isMac) { document.querySelectorAll('.cmdk-mod').forEach(function (el) { el.textContent = '⌘'; }); }

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    function render(q) {
      q = (q || '').trim().toLowerCase();
      var statics = q ? items.filter(function (it) { return it.label.toLowerCase().indexOf(q) !== -1 || it.g.toLowerCase().indexOf(q) !== -1; }) : items;
      // Live data results first (most specific), then matching pages/actions.
      var matched = dataItems.concat(statics);
      visible = matched;
      active = 0;
      if (!matched.length) { list.innerHTML = ''; empty.classList.remove('d-none'); return; }
      empty.classList.add('d-none');
      var html = '', lastGroup = null, idx = 0;
      matched.forEach(function (it) {
        if (it.g !== lastGroup) { html += '<div class="cmdk-group">' + esc(it.g) + '</div>'; lastGroup = it.g; }
        html += '<a class="cmdk-item' + (idx === 0 ? ' active' : '') + '" data-i="' + idx + '" href="' + it.url + '">' +
          '<span class="ci-ico"><i class="bi bi-' + esc(it.icon) + '"></i></span>' +
          '<span class="ci-label">' + esc(it.label) + (it.sub ? ' <span class="ci-sub">' + esc(it.sub) + '</span>' : '') + '</span>' +
          '<span class="ci-enter">↵ Enter</span></a>';
        idx++;
      });
      list.innerHTML = html;
    }

    function fetchData(q) {
      fetch(window.APP_URL + '/search?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (input.value.trim() !== q) { return; }   // stale response — ignore
          dataItems = (d && d.results) || [];
          render(input.value);
        })
        .catch(function () {});
    }

    function setActive(i) {
      var els = list.querySelectorAll('.cmdk-item');
      if (!els.length) return;
      active = (i + els.length) % els.length;
      els.forEach(function (el, j) { el.classList.toggle('active', j === active); });
      els[active].scrollIntoView({ block: 'nearest' });
    }

    function open() {
      cmdk.classList.add('open');
      cmdk.setAttribute('aria-hidden', 'false');
      input.value = '';
      dataItems = [];
      render('');
      setTimeout(function () { input.focus(); }, 20);
    }
    function close() { cmdk.classList.remove('open'); cmdk.setAttribute('aria-hidden', 'true'); }
    function go() { if (visible[active]) { window.location.href = visible[active].url; } }

    window.openCmdk = open;
    var trigger = document.getElementById('searchTrigger');
    if (trigger) trigger.addEventListener('click', open);

    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); cmdk.classList.contains('open') ? close() : open(); }
      else if (cmdk.classList.contains('open')) {
        if (e.key === 'Escape') { close(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
        else if (e.key === 'Enter') { e.preventDefault(); go(); }
      }
    });
    input.addEventListener('input', function () {
      var q = this.value;
      render(q);                                     // instant: pages/actions
      clearTimeout(dataTimer);
      var qt = q.trim();
      if (qt.length >= 2) {
        dataTimer = setTimeout(function () { fetchData(qt); }, 180);   // debounced data search
      } else {
        dataItems = [];
        render(q);
      }
    });
    cmdk.addEventListener('click', function (e) { if (e.target === cmdk) close(); });
    list.addEventListener('mousemove', function (e) {
      var it = e.target.closest('.cmdk-item'); if (it) { setActive(+it.dataset.i); }
    });
  })();

  // ---- Copy merge-tag pills ----
  $(document).on('click', '.code-pill', function () {
    var text = $(this).text();
    if (navigator.clipboard) { navigator.clipboard.writeText(text); }
    var $el = $(this);
    var orig = $el.text();
    $el.text('copied!');
    setTimeout(function () { $el.text(orig); }, 900);
  });

  // ---- SMTP provider presets ----
  window.applyPreset = function (presets, provider, prefix) {
    prefix = prefix || '';
    var p = presets[provider];
    if (!p) return;
    if (p.host) $('#' + prefix + 'host').val(p.host);
    $('#' + prefix + 'port').val(p.port);
    $('#' + prefix + 'encryption').val(p.encryption);
  };

  // ---- AJAX SMTP test ----
  window.smtpTest = function (id, btn) {
    var $btn = $(btn);
    var to = prompt('Send a test email to:', $btn.data('email') || '');
    if (!to) return;
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Testing…');
    $.post(window.APP_URL + '/smtp/test', { id: id, to: to, _csrf: window.CSRF })
      .done(function (res) {
        if (res.ok) { window.toast('Test email sent successfully!', 'success'); }
        else { window.toast('Failed: ' + (res.error || 'unknown error'), 'error'); }
      })
      .fail(function () { window.toast('Request failed.', 'error'); })
      .always(function () { $btn.prop('disabled', false).html('<i class="bi bi-send"></i> Test'); setTimeout(function () { window.location.reload(); }, 1200); });
  };

  // ---- Tiny HTML block inserter for the template builder ----
  window.insertBlock = function (targetId, type) {
    var blocks = {
      heading: '<h2 style="font-family:Arial,sans-serif;color:#111">Your headline</h2>\n',
      text:    '<p style="font-family:Arial,sans-serif;color:#444;line-height:1.6">Write your message here. Use {{first_name}} to personalise.</p>\n',
      button:  '<table cellpadding="0" cellspacing="0"><tr><td style="background:#4f46e5;border-radius:6px"><a href="https://example.com" style="display:inline-block;padding:12px 24px;color:#fff;font-family:Arial,sans-serif;text-decoration:none">Click here</a></td></tr></table>\n',
      image:   '<img src="https://via.placeholder.com/600x200" alt="" style="max-width:100%;border-radius:8px">\n',
      divider: '<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">\n',
      columns: '<table width="100%"><tr><td style="width:50%;padding:8px;font-family:Arial">Column one</td><td style="width:50%;padding:8px;font-family:Arial">Column two</td></tr></table>\n',
      social:  '<p style="font-family:Arial"><a href="#">Facebook</a> · <a href="#">Twitter</a> · <a href="#">LinkedIn</a></p>\n',
      spacer:  '<div style="height:24px;line-height:24px;font-size:1px">&nbsp;</div>\n',
      html:    '<!-- Custom HTML — paste or write your own markup below -->\n<div style="font-family:Arial,sans-serif;color:#444;line-height:1.6">\n  Your custom HTML here\n</div>\n'
    };
    var ta = document.getElementById(targetId);
    if (!ta) return;
    var snippet = blocks[type];
    if (!snippet) return;
    insertAtCursor(ta, snippet);
    ta.dispatchEvent(new Event('input'));
  };

  // Insert text at the caret while preserving the textarea's native undo
  // stack (Ctrl/Cmd+Z). Direct `.value =` assignment would wipe undo history.
  function insertAtCursor(ta, text) {
    ta.focus();
    var start = ta.selectionStart, end = ta.selectionEnd;
    if (typeof start !== 'number') { start = end = ta.value.length; }
    var ok = false;
    try {
      ta.setSelectionRange(start, end);
      ok = document.execCommand('insertText', false, text);   // undoable
    } catch (e) { ok = false; }
    if (!ok) {
      if (typeof ta.setRangeText === 'function') {
        ta.setRangeText(text, start, end, 'end');             // undoable on modern browsers
      } else {
        ta.value = ta.value.slice(0, start) + text + ta.value.slice(end);
        ta.selectionStart = ta.selectionEnd = start + text.length;
      }
    }
  }
  window.insertAtCursor = insertAtCursor;

  // Fit an email iframe into its container: scale down if the mailer is wider
  // than the frame (so nothing is clipped on the sides) and grow the frame to
  // the content height (so there's no inner scrollbar). Re-fits after images.
  function fitFrame(frame) {
    if (!frame) return;
    var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
    if (!doc) return;
    function fit() {
      try {
        var b = doc.body, e = doc.documentElement;
        if (!b) return;
        if (e) { e.style.overflow = 'hidden'; }
        b.style.transform = '';            // reset to measure natural size
        b.style.transformOrigin = 'top left';
        b.style.width = '';
        b.style.margin = '0 auto';

        var frameW   = frame.clientWidth || 1;
        var contentW = Math.max(b.scrollWidth, e ? e.scrollWidth : 0);
        var scale = 1;
        if (contentW > frameW + 1) {
          scale = frameW / contentW;
          b.style.width = contentW + 'px';
          b.style.margin = '0';
          b.style.transform = 'scale(' + scale + ')';
        }
        var contentH = Math.max(b.scrollHeight, e ? e.scrollHeight : 0);
        frame.style.height = Math.ceil(contentH * scale + 16) + 'px';
      } catch (err) {}
    }
    fit();
    setTimeout(fit, 250);
    var imgs = doc.images || [];
    for (var i = 0; i < imgs.length; i++) {
      if (!imgs[i].complete) { imgs[i].addEventListener('load', fit); imgs[i].addEventListener('error', fit); }
    }
  }
  window.fitFrame = fitFrame;

  // ---- Live preview iframe (auto-grows to fit the full email) ----
  var _lastPreview = null;
  window.refreshPreview = function (sourceId, frameId) {
    _lastPreview = [sourceId, frameId];
    var src = document.getElementById(sourceId);
    var frame = document.getElementById(frameId);
    if (!src || !frame) return;
    var doc = frame.contentDocument || frame.contentWindow.document;
    doc.open(); doc.write(src.value || '<p style="color:#aaa;font-family:sans-serif">Preview…</p>'); doc.close();
    fitFrame(frame);
  };

  // Auto-fit any srcdoc preview iframes (e.g. the campaign detail page).
  $('iframe[data-autofit]').each(function () {
    var f = this;
    var run = function () { fitFrame(f); };
    if (f.contentDocument && f.contentDocument.readyState === 'complete') { run(); }
    f.addEventListener('load', run);
  });

  // Re-fit the visible previews when the window resizes (canvas width changes).
  var _resizeT;
  $(window).on('resize', function () {
    clearTimeout(_resizeT);
    _resizeT = setTimeout(function () {
      if (_lastPreview) {
        var frame = document.getElementById(_lastPreview[1]);
        if (frame && !frame.classList.contains('d-none')) {
          window.refreshPreview(_lastPreview[0], _lastPreview[1]);
        }
      }
      $('iframe[data-autofit]').each(function () { fitFrame(this); });
    }, 150);
  });

  // Auto-bind any [data-preview-source] textareas.
  $('[data-preview-source]').on('input', function () {
    window.refreshPreview(this.id, $(this).data('preview-source'));
  }).trigger('input');

  // ---- Confirm dangerous actions (styled dialog) ----
  $(document).on('submit', 'form[data-confirm]', function (e) {
    var form = this;
    if (form.dataset.confirmed === '1') { return; }   // already approved
    e.preventDefault();
    var danger = /delete|remove|cancel|reset|clear/i.test(form.dataset.confirm);
    window.confirmDialog(form.dataset.confirm, { danger: danger, ok: danger ? 'Yes, continue' : 'Confirm' })
      .then(function (ok) { if (ok) { form.dataset.confirmed = '1'; form.submit(); } });
  });

})(jQuery);
