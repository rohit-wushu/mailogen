<div class="ev-wrap">
  <p class="ev-lead text-muted">Check deliverability, syntax, role &amp; disposable signals — single address or a whole file.</p>

  <!-- Result / conversation area -->
  <div id="evFeed" class="ev-feed">
    <div class="ev-placeholder" id="evPlaceholder">
      <i class="bi bi-patch-check"></i>
      <p>Type in any email address to have it quickly validated.</p>
    </div>
  </div>

  <!-- Composer -->
  <form id="evForm" class="ev-composer" autocomplete="off">
    <?= csrf_field() ?>
    <textarea id="evInput" rows="1" class="ev-textarea"
              placeholder="Type in any email address to have it quickly validated"></textarea>
    <div class="ev-composer-row">
      <label class="ev-attach" id="evAttachLabel">
        <i class="bi bi-paperclip"></i>
        <span id="evAttachText">Attach CSV, TXT or Excel File</span>
        <input type="file" id="evFile" accept=".csv,.txt,.xlsx" hidden>
      </label>
      <button type="submit" class="ev-validate" id="evSubmit">
        Validate <i class="bi bi-arrow-up"></i>
      </button>
    </div>
  </form>
</div>

<style>
  /* ---- True-black canvas (scoped to this page only) ------------------ */
  body:has(.ev-wrap) .content { background: #000; }
  body:has(.ev-wrap) .page-head h2,
  body:has(.ev-wrap) .page-head .text-muted { color: #f4f5f7; }

  /* Black palette + lime accent, self-contained so it looks identical in
     light or dark global theme. */
  .ev-wrap {
    --ev-surface: #111114;   /* cards & composer            */
    --ev-inset:   #0b0b0d;   /* attribute grid / table head */
    --ev-line:    rgba(255,255,255,.08);
    --ev-line-2:  rgba(255,255,255,.05);
    --ev-text:    #f4f5f7;
    --ev-muted:   #8b8f9e;
    --ev-lime:    #c8f169;
    --ev-ok: #34d399; --ev-bad: #f87171; --ev-warn: #fbbf24;
    max-width: 760px; margin: 0 auto; display: flex; flex-direction: column;
    min-height: calc(100vh - 150px); color: var(--ev-text);
  }
  .ev-lead { text-align: center; margin: 2px 0 18px; font-size: .95rem; color: var(--ev-muted) !important; }

  .ev-feed { flex: 1 1 auto; display: flex; flex-direction: column; justify-content: flex-end;
             gap: 16px; padding-bottom: 18px; }
  .ev-placeholder { text-align: center; color: var(--ev-muted); padding: 56px 16px; }
  .ev-placeholder i { display: inline-grid; place-items: center; width: 76px; height: 76px; margin: 0 auto 16px;
             font-size: 2rem; color: var(--ev-lime); border-radius: 50%;
             background: rgba(200,241,105,.08); border: 1px solid rgba(200,241,105,.18); }
  .ev-placeholder p { margin: 0; font-size: 1.02rem; }

  /* Result card */
  .ev-card { background: var(--ev-surface); border: 1px solid var(--ev-line); border-radius: 22px;
             padding: 26px 30px; box-shadow: 0 24px 50px -30px rgba(0,0,0,.9); }
  .ev-card-head { display: flex; align-items: center; gap: 18px; }
  .ev-badge { width: 60px; height: 60px; border-radius: 50%; display: grid; place-items: center; flex: 0 0 auto;
              color: #0b0b0d; font-size: 1.5rem; }
  .ev-badge.ok   { background: var(--ev-ok);   box-shadow: 0 10px 24px -8px rgba(52,211,153,.55); }
  .ev-badge.bad  { background: var(--ev-bad);   box-shadow: 0 10px 24px -8px rgba(248,113,113,.55); }
  .ev-badge.warn { background: var(--ev-warn); box-shadow: 0 10px 24px -8px rgba(251,191,36,.55); }
  .ev-verdict-email { font-size: 1.35rem; font-weight: 600; color: var(--ev-text); word-break: break-all; line-height: 1.2; }
  .ev-verdict-text { font-size: 1.1rem; color: var(--ev-muted); margin-top: 2px; }
  .ev-verdict-text b.ok   { color: var(--ev-ok); }
  .ev-verdict-text b.bad  { color: var(--ev-bad); }
  .ev-verdict-text b.warn { color: var(--ev-warn); }

  .ev-grid { margin-top: 22px; background: var(--ev-inset); border: 1px solid var(--ev-line-2); border-radius: 16px;
             padding: 22px 28px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px 40px; }
  .ev-attr { display: flex; align-items: center; gap: 10px; font-size: 1rem; }
  .ev-dot { width: 11px; height: 11px; border-radius: 50%; flex: 0 0 auto; background: #3a3a42; }
  .ev-dot.t { background: var(--ev-ok); }   /* true  */
  .ev-dot.f { background: var(--ev-bad); }   /* false */
  .ev-attr .k { font-weight: 700; color: var(--ev-text); }
  .ev-attr .v { color: var(--ev-muted); }
  .ev-suggest { margin-top: 14px; font-size: .92rem; color: var(--ev-muted); }
  .ev-suggest a { color: var(--ev-lime); cursor: pointer; font-weight: 600; }

  /* Composer — sticks to the bottom of the viewport while the feed scrolls. */
  .ev-composer { position: sticky; bottom: 16px; z-index: 5;
                 background: var(--ev-surface); border: 1px solid var(--ev-line); border-radius: 22px;
                 padding: 18px 20px; box-shadow: 0 10px 40px -12px rgba(0,0,0,.8); transition: border-color .15s, box-shadow .15s; }
  .ev-composer:focus-within { border-color: rgba(200,241,105,.45); box-shadow: 0 0 0 3px rgba(200,241,105,.12), 0 10px 40px -12px rgba(0,0,0,.8); }
  /* Soft fade so cards scroll out cleanly behind the sticky composer. */
  .ev-composer::before { content: ''; position: absolute; left: 0; right: 0; top: -28px; height: 28px;
                         background: linear-gradient(to top, #000, transparent); pointer-events: none; }
  .ev-textarea { width: 100%; border: 0; outline: 0; resize: none; background: transparent; font-size: 1.05rem;
                 color: var(--ev-text); line-height: 1.5; max-height: 180px; }
  .ev-textarea::placeholder { color: var(--ev-muted); }
  .ev-composer-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 14px; }
  .ev-attach { display: inline-flex; align-items: center; gap: 9px; border: 1px solid var(--ev-line);
               border-radius: 999px; padding: 10px 18px; color: var(--ev-muted); cursor: pointer; font-weight: 500;
               margin: 0; transition: .15s; max-width: 70%; }
  .ev-attach:hover { border-color: rgba(200,241,105,.5); color: var(--ev-lime); }
  .ev-attach.has-file { border-color: var(--ev-ok); color: var(--ev-ok); }
  .ev-attach span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .ev-validate { display: inline-flex; align-items: center; gap: 8px; border: 0; border-radius: 999px;
                 padding: 11px 26px; font-weight: 700; color: #11140a; cursor: pointer;
                 background: var(--ev-lime); transition: .15s; }
  .ev-validate:hover { background: #d4f77f; box-shadow: 0 8px 22px -8px rgba(200,241,105,.6); }
  .ev-validate:disabled { opacity: .55; cursor: not-allowed; box-shadow: none; }

  /* Bulk results */
  .ev-bulk-summary { display: flex; gap: 10px; flex-wrap: wrap; margin: 4px 0 14px; }
  .ev-pill { border-radius: 999px; padding: 5px 14px; font-weight: 600; font-size: .85rem; }
  .ev-pill.ok   { background: rgba(52,211,153,.16); color: var(--ev-ok); }
  .ev-pill.bad  { background: rgba(248,113,113,.16); color: var(--ev-bad); }
  .ev-pill.warn { background: rgba(251,191,36,.18);  color: var(--ev-warn); }
  .ev-pill.muted{ background: rgba(255,255,255,.06); color: var(--ev-muted); }
  .ev-table { width: 100%; font-size: .92rem; color: var(--ev-text); }
  .ev-table th { text-align: left; color: var(--ev-muted); font-weight: 600; padding: 8px 10px; border-bottom: 1px solid var(--ev-line); }
  .ev-table td { padding: 9px 10px; border-bottom: 1px solid var(--ev-line-2); vertical-align: middle; }
  .ev-table td.text-muted { color: var(--ev-muted) !important; }
</style>

<script>
(function () {
  var form     = document.getElementById('evForm');
  var input    = document.getElementById('evInput');
  var fileInp  = document.getElementById('evFile');
  var attach   = document.getElementById('evAttachLabel');
  var attachTx = document.getElementById('evAttachText');
  var submit   = document.getElementById('evSubmit');
  var feed     = document.getElementById('evFeed');
  var placeholder = document.getElementById('evPlaceholder');
  var URL_CHECK = '<?= url('email-verifier/check') ?>';
  var URL_BULK  = '<?= url('email-verifier/bulk') ?>';

  // Auto-grow the textarea.
  input.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 180) + 'px';
  });
  // Enter submits, Shift+Enter = newline.
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
  });

  fileInp.addEventListener('change', function () {
    if (fileInp.files.length) {
      attachTx.textContent = fileInp.files[0].name;
      attach.classList.add('has-file');
    } else {
      attachTx.textContent = 'Attach CSV, TXT or Excel File';
      attach.classList.remove('has-file');
    }
  });

  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  function clearPlaceholder() { if (placeholder) { placeholder.remove(); placeholder = null; } }
  // Scroll the page to the bottom so the newest card rests just above the
  // sticky composer (older cards are pushed up).
  function scrollDown() {
    requestAnimationFrame(function () {
      window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    });
  }

  function badgeClass(r) { return r.valid ? 'ok' : (r.status === 'risky' ? 'warn' : 'bad'); }
  function badgeIcon(r)  { return r.valid ? 'bi-check-all' : (r.status === 'risky' ? 'bi-exclamation-lg' : 'bi-x-lg'); }
  function verdictWord(r){ return r.valid ? 'Valid' : (r.status === 'risky' ? 'Risky' : 'Invalid'); }

  function attr(key, val) {
    var cls = val ? 't' : 'f';
    return '<div class="ev-attr"><span class="ev-dot ' + cls + '"></span>'
         + '<span class="k">' + key + ':</span> <span class="v">' + (val ? 'true' : 'false') + '</span></div>';
  }

  function renderCard(r) {
    var b = badgeClass(r);
    var sugg = r.did_you_mean
      ? '<div class="ev-suggest">Did you mean <a data-sugg="' + esc(r.local || '') + esc(r.did_you_mean) + '">'
        + esc((r.email.split('@')[0]) + '@' + r.did_you_mean) + '</a>?</div>'
      : '';
    var html =
      '<div class="ev-card">' +
        '<div class="ev-card-head">' +
          '<div class="ev-badge ' + b + '"><i class="bi ' + badgeIcon(r) + '"></i></div>' +
          '<div>' +
            '<div class="ev-verdict-email">' + esc(r.email) + '</div>' +
            '<div class="ev-verdict-text">is ' + (r.valid ? 'a' : 'an') +
              ' <b class="' + b + '">' + verdictWord(r) + ' email address</b></div>' +
          '</div>' +
        '</div>' +
        '<div class="ev-grid">' +
          attr('Catchall',   r.catchall) +
          attr('Disposable', r.disposable) +
          attr('Role',       r.role) +
          attr('Syntax Valid', r.syntax) +
          attr('Unknown',    r.unknown) +
          '<div class="ev-attr"><span class="ev-dot ' + (r.valid ? 't' : 'f') + '"></span>' +
            '<span class="k">Message:</span> <span class="v">' + esc(r.message) + '</span></div>' +
        '</div>' +
        sugg +
      '</div>';
    var wrap = document.createElement('div');
    wrap.innerHTML = html;
    var node = wrap.firstElementChild;
    feed.appendChild(node);
    // wire suggestion click
    var s = node.querySelector('[data-sugg]');
    if (s) s.addEventListener('click', function () { input.value = s.textContent; checkSingle(s.textContent); });
    scrollDown();
  }

  function renderBulk(data) {
    var s = data.summary || {};
    var rows = data.results.map(function (r) {
      var word = verdictWord(r), b = badgeClass(r);
      var pillCls = b === 'ok' ? 'ok' : (b === 'warn' ? 'warn' : 'bad');
      return '<tr><td>' + esc(r.email) + '</td>' +
        '<td><span class="ev-pill ' + pillCls + '">' + word + '</span></td>' +
        '<td>' + (r.role ? 'Role ' : '') + (r.disposable ? 'Disposable ' : '') +
                 (r.catchall ? 'Catch-all ' : '') + (r.free ? 'Free ' : '') +
                 (!r.syntax ? 'Bad-syntax ' : '') + '</td>' +
        '<td class="text-muted">' + esc(r.message) + '</td></tr>';
    }).join('');
    var html =
      '<div class="ev-card">' +
        '<div class="ev-verdict-email" style="font-size:1.1rem">Verified ' + data.count + ' address' + (data.count === 1 ? '' : 'es') +
          (data.truncated ? ' <span class="text-muted">(first ' + data.limit + ')</span>' : '') + '</div>' +
        '<div class="ev-bulk-summary">' +
          '<span class="ev-pill ok">' + (s.valid || 0) + ' valid</span>' +
          '<span class="ev-pill warn">' + (s.risky || 0) + ' risky</span>' +
          '<span class="ev-pill bad">' + (s.invalid || 0) + ' invalid</span>' +
        '</div>' +
        '<div style="max-height:420px;overflow:auto"><table class="ev-table">' +
          '<thead><tr><th>Email</th><th>Status</th><th>Flags</th><th>Message</th></tr></thead>' +
          '<tbody>' + rows + '</tbody></table></div>' +
      '</div>';
    var wrap = document.createElement('div');
    wrap.innerHTML = html;
    feed.appendChild(wrap.firstElementChild);
    scrollDown();
  }

  function busy(on) {
    submit.disabled = on;
    submit.innerHTML = on ? 'Validating <span class="spinner-border spinner-border-sm"></span>'
                          : 'Validate <i class="bi bi-arrow-up"></i>';
  }

  function checkSingle(email) {
    clearPlaceholder();
    busy(true);
    fetch(URL_CHECK, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ email: email, _csrf: window.CSRF })
    }).then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok) { renderCard(d.result); }
        else { window.toast(d.error || 'Validation failed.', 'error'); }
      })
      .catch(function () { window.toast('Network error — please try again.', 'error'); })
      .finally(function () { busy(false); });
  }

  function checkBulk(file) {
    clearPlaceholder();
    busy(true);
    var fd = new FormData();
    fd.append('file', file);
    fd.append('_csrf', window.CSRF);
    fetch(URL_BULK, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok) {
          renderBulk(d);
          fileInp.value = ''; attachTx.textContent = 'Attach CSV, TXT or Excel File'; attach.classList.remove('has-file');
        } else { window.toast(d.error || 'Bulk validation failed.', 'error'); }
      })
      .catch(function () { window.toast('Network error — please try again.', 'error'); })
      .finally(function () { busy(false); });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (submit.disabled) return;
    if (fileInp.files.length) { checkBulk(fileInp.files[0]); return; }
    var email = input.value.trim();
    if (!email) { input.focus(); return; }
    checkSingle(email);
    input.value = ''; input.style.height = 'auto';
  });
})();
</script>
