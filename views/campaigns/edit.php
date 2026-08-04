<?php /** @var ?array $campaign @var array $lists @var array $groups @var array $accounts @var array $templates @var array $schedules */ ?>
<div class="page-head">
  <h2><?= $campaign ? 'Edit Campaign' : 'Create Campaign' ?></h2>
  <a href="<?= url('campaigns') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to campaigns</a>
</div>

<form method="post" action="<?= url('campaigns/store') ?>" id="wizardForm">
  <?= csrf_field() ?>
  <?php if ($campaign): ?><input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>"><?php endif; ?>

  <div class="wizard">
    <!-- Stepper -->
    <div class="wizard-steps">
      <?php
      $steps = [
        ['Campaign Setup', 'Set up campaign details', 'gear'],
        ['Recipients', 'Select your audience', 'people'],
        ['Template', 'Design your email', 'palette'],
        ['Schedule', 'Sending & tracking', 'clock'],
        ['Review & Confirm', 'Review and save', 'check2-circle'],
      ];
      foreach ($steps as $i => [$t, $s, $ic]): ?>
        <div class="wstep <?= $i === 0 ? 'active' : '' ?>" data-step="<?= $i ?>" onclick="goStep(<?= $i ?>)">
          <span class="wnum"><?= $i + 1 ?></span>
          <div><div class="wtitle"><?= $t ?></div><div class="wsub"><?= $s ?></div></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Panels -->
    <div>
      <!-- Step 1 -->
      <div class="card wstep-panel" data-panel="0">
        <div class="card-header">Campaign Setup</div>
        <div class="card-body">
          <div class="mb-3"><label class="form-label">Campaign name *</label><input class="form-control" name="name" id="f_name" value="<?= e($campaign['name'] ?? '') ?>" required></div>
          <div class="mb-0"><label class="form-label">Subject line *</label><input class="form-control" name="subject" id="cmp_subject" value="<?= e($campaign['subject'] ?? '') ?>" placeholder="Use {{first_name}} to personalise"></div>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="card wstep-panel d-none" data-panel="1">
        <div class="card-header">Recipients</div>
        <div class="card-body">
          <?php $__src = ($campaign['source_type'] ?? 'list') === 'sheet' ? 'sheet' : 'list'; ?>
          <input type="hidden" name="source_type" id="sourceType" value="<?= $__src ?>">

          <!-- Recipient source toggle -->
          <div class="src-toggle mb-3" role="tablist">
            <button type="button" class="src-opt <?= $__src === 'list' ? 'active' : '' ?>" data-src="list">
              <i class="bi bi-people-fill"></i>
              <span><span class="src-opt-title">Contact list</span><span class="src-opt-sub">Pick a saved list / segment</span></span>
            </button>
            <button type="button" class="src-opt <?= $__src === 'sheet' ? 'active' : '' ?>" data-src="sheet">
              <i class="bi bi-file-earmark-spreadsheet-fill"></i>
              <span><span class="src-opt-title">Google Sheet</span><span class="src-opt-sub">Send straight from a sheet</span></span>
            </button>
          </div>

          <!-- Source: contact list -->
          <div data-src-panel="list" class="<?= $__src === 'sheet' ? 'd-none' : '' ?>">
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label">Contact list</label>
              <select class="form-select" name="list_id">
                <option value="">All my contacts</option>
                <?php foreach ($lists as $l): ?><option value="<?= (int) $l['id'] ?>" <?= (int) ($campaign['list_id'] ?? 0) === (int) $l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?> (<?= (int) $l['contact_count'] ?>)</option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Sector <span class="text-muted small">(optional)</span></label>
              <select class="form-select" name="sector">
                <option value="">All sectors</option>
                <?php foreach (($sectors ?? []) as $sec): ?><option value="<?= e($sec['sector']) ?>" <?= ($campaign['sector'] ?? '') === $sec['sector'] ? 'selected' : '' ?>><?= e($sec['sector']) ?> (<?= (int) $sec['c'] ?>)</option><?php endforeach; ?>
              </select>
              <div class="form-text">Send only to this sector.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Location <span class="text-muted small">(optional)</span></label>
              <select class="form-select" name="location">
                <option value="">All locations</option>
                <?php foreach (($locations ?? []) as $loc): ?><option value="<?= e($loc['location']) ?>" <?= ($campaign['location'] ?? '') === $loc['location'] ? 'selected' : '' ?>><?= e($loc['location']) ?> (<?= (int) $loc['c'] ?>)</option><?php endforeach; ?>
              </select>
              <div class="form-text">Send only to this location.</div>
            </div>
          </div>
          </div>

          <!-- Source: Google Sheet -->
          <div data-src-panel="sheet" class="<?= $__src === 'sheet' ? '' : 'd-none' ?>">
            <div class="mb-3">
              <label class="form-label">Google Sheet link</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                <input type="url" class="form-control" name="sheet_url" id="sheetUrl"
                       value="<?= e($campaign['sheet_url'] ?? '') ?>"
                       placeholder="https://docs.google.com/spreadsheets/d/…">
                <button type="button" class="btn btn-light" id="sheetCheckBtn"><i class="bi bi-arrow-repeat"></i> Check</button>
              </div>
              <div class="form-text">
                In Google Sheets: <strong>Share → General access → “Anyone with the link → Viewer”</strong>, then paste the link here.
              </div>
              <div id="sheetStatus" class="small mt-2"></div>
            </div>
            <div class="alert alert-info py-2 px-3 small mb-0">
              <i class="bi bi-magic me-1"></i>
              Your column headers become merge tags. A column named <span class="code-pill">Email</span> is required.
              Use any header in the subject or body, e.g. <span class="code-pill">{{First Name}}</span>,
              <span class="code-pill">{{Company}}</span>. Rows are added to your Contacts (deduped by email) when you send.
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">SMTP rotation group</label>
              <select class="form-select" name="smtp_group_id" id="grpSel" onchange="if(this.value)smtpSel.value=''">
                <option value="">— use single account —</option>
                <?php foreach ($groups as $g): ?><option value="<?= (int) $g['id'] ?>" <?= (int) ($campaign['smtp_group_id'] ?? 0) === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?> (<?= e(str_replace('_', ' ', $g['rotation_mode'])) ?>)</option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">…or single SMTP account</label>
              <select class="form-select" name="smtp_id" id="smtpSel" onchange="if(this.value)grpSel.value=''">
                <option value="">— none —</option>
                <?php foreach ($accounts as $a): ?><option value="<?= (int) $a['id'] ?>" <?= (int) ($campaign['smtp_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['label']) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3: Template -->
      <div class="card wstep-panel d-none" data-panel="2">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Template</span>
          <div class="seg-tabs">
            <button type="button" class="active" id="tabDesign" onclick="tplTab('design')">Design</button>
            <button type="button" id="tabPreview" onclick="tplTab('preview')">Preview</button>
          </div>
        </div>
        <div class="card-body">
          <!-- How do you want to build this email? -->
          <div class="row g-2 mb-3" id="methodRow">
            <div class="col-md-6">
              <div class="design-method active" data-method="editor" onclick="setDesignMethod('editor')">
                <i class="bi bi-pencil-square"></i>
                <div><div class="fw-semibold">Text editor</div><div class="text-muted small">Write &amp; design with blocks</div></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="design-method" data-method="upload" onclick="setDesignMethod('upload')">
                <i class="bi bi-filetype-html"></i>
                <div><div class="fw-semibold">Import HTML file</div><div class="text-muted small">Upload your own .html mailer</div></div>
              </div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-lg-9">
              <!-- Upload zone (shown only for the "Import HTML file" method) -->
              <div id="uploadZone" class="d-none mb-2">
                <label class="upload-drop" for="htmlFile" id="uploadDrop">
                  <i class="bi bi-cloud-arrow-up"></i>
                  <div class="mt-1">Click to upload or <strong>drag your .html file</strong> here</div>
                  <div class="text-muted small mt-1" id="uploadFileName">No file selected</div>
                </label>
                <input type="file" id="htmlFile" accept=".html,.htm,text/html" class="d-none">
                <div class="form-text"><i class="bi bi-info-circle"></i> Your file's HTML loads into the editor below — switch to <strong>Preview</strong> to see it, or <strong>Design</strong> to tweak the code.</div>
              </div>

              <div class="mb-2" id="tplPickRow">
                <select class="form-select form-select-sm" id="tplSelect" onchange="applyTemplate()" style="max-width:260px">
                  <option value="">Start from blank</option>
                  <?php foreach ($templates as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="editor-toolbar" id="designView">
                <select class="form-select form-select-sm border-0" style="width:auto"><option>Inter</option><option>Arial</option><option>Georgia</option></select>
                <select class="form-select form-select-sm border-0" style="width:64px"><option>14</option><option>16</option><option>18</option></select>
                <span class="sep"></span>
                <button type="button" class="tb" onclick="wrapSel('<b>','</b>')"><i class="bi bi-type-bold"></i></button>
                <button type="button" class="tb" onclick="wrapSel('<i>','</i>')"><i class="bi bi-type-italic"></i></button>
                <button type="button" class="tb" onclick="wrapSel('<u>','</u>')"><i class="bi bi-type-underline"></i></button>
                <span class="sep"></span>
                <button type="button" class="tb" onclick="wrapSel('<div style=\'text-align:left\'>','</div>')"><i class="bi bi-text-left"></i></button>
                <button type="button" class="tb" onclick="wrapSel('<div style=\'text-align:center\'>','</div>')"><i class="bi bi-text-center"></i></button>
                <button type="button" class="tb" onclick="wrapSel('<div style=\'text-align:right\'>','</div>')"><i class="bi bi-text-right"></i></button>
                <span class="sep"></span>
                <button type="button" class="tb" onclick="wrapSel('<a href=\'https://\'>','</a>')"><i class="bi bi-link-45deg"></i></button>
                <button type="button" class="tb" onclick="insertBlock('cmp_body','image')"><i class="bi bi-image"></i></button>
                <button type="button" class="tb" onclick="insertBlock('cmp_body','divider')"><i class="bi bi-dash-lg"></i></button>
                <button type="button" class="tb" onclick="insertBlock('cmp_body','columns')"><i class="bi bi-layout-split"></i></button>
              </div>
              <textarea class="form-control font-monospace" name="body_html" id="cmp_body" rows="14" data-preview-source="cmp_preview"><?= e($campaign['body_html'] ?? '') ?></textarea>
              <iframe id="cmp_preview" class="d-none" style="width:100%;height:420px;border:1px solid var(--bs-border-color);border-radius:10px;background:#fff"></iframe>
              <div class="mt-2"><span class="text-muted small me-1">Merge tags:</span>
                <?php foreach (['{{first_name}}','{{last_name}}','{{email}}','{{company}}'] as $tag): ?><span class="code-pill"><?= e($tag) ?></span><?php endforeach; ?>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="seg-tabs w-100 mb-2"><button type="button" class="active w-50">Blocks</button><button type="button" class="w-50" onclick="return false">Settings</button></div>
              <div class="blocks-panel">
                <?php foreach ([['text','Text','text-paragraph'],['image','Image','image'],['button','Button','hand-index'],['divider','Divider','dash-lg'],['spacer','Spacer','distribute-vertical'],['social','Social','share'],['html','HTML','code-slash'],['columns','Columns','layout-split']] as $b): ?>
                  <div class="block-btn" onclick="insertBlock('cmp_body','<?= $b[0] ?>')"><i class="bi bi-<?= $b[2] ?>"></i><?= $b[1] ?></div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 4: Schedule / sending -->
      <div class="card wstep-panel d-none" data-panel="3">
        <div class="card-header">Schedule &amp; Tracking</div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Throttle (emails/hour)</label>
              <input type="number" class="form-control" name="throttle_per_hour" value="<?= (int) ($campaign['throttle_per_hour'] ?? 0) ?>" min="0">
              <div class="form-text">0 = send as fast as the queue allows. Useful for Gmail/Workspace daily limits.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tracking</label>
              <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="track_opens" id="track_opens" <?= !isset($campaign['track_opens']) || (int) $campaign['track_opens'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="track_opens">Track opens (pixel)</label></div>
              <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="track_clicks" id="track_clicks" <?= !isset($campaign['track_clicks']) || (int) $campaign['track_clicks'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="track_clicks">Track link clicks</label></div>
            </div>
          </div>
          <div class="border-top pt-3">
            <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="enable_followup" id="enable_followup" <?= !empty($campaign['enable_followup']) ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="enable_followup">Enable follow-up sequence</label></div>
            <p class="text-muted small">Automatically email non-responders after a delay.</p>
            <div id="followups"></div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addFollowup()"><i class="bi bi-plus-lg"></i> Add follow-up step</button>
          </div>
          <p class="text-muted small mt-3 mb-0"><i class="bi bi-info-circle"></i> You'll choose <strong>Send Now</strong> or a <strong>schedule date</strong> on the next screen after saving.</p>
        </div>
      </div>

      <!-- Step 5: Review -->
      <div class="card wstep-panel d-none" data-panel="4">
        <div class="card-header">Review &amp; Confirm</div>
        <div class="card-body">
          <div class="row g-3" id="reviewBox"></div>
          <div class="alert alert-info mt-3 mb-0"><i class="bi bi-check-circle"></i> Saving creates the campaign as a <strong>draft</strong>. Launch or schedule it from the campaign page.</div>
        </div>
      </div>

      <!-- Footer nav -->
      <div class="d-flex justify-content-between mt-3">
        <button type="button" class="btn btn-light" id="btnBack" onclick="goStep(currentStep-1)" disabled><i class="bi bi-arrow-left"></i> Back</button>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> Save Draft</button>
          <button type="button" class="btn btn-primary" id="btnNext" onclick="goStep(currentStep+1)">Next <i class="bi bi-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <template id="followupTpl">
    <div class="followup-step">
      <button type="button" class="btn btn-sm btn-link text-danger position-absolute top-0 end-0" onclick="this.closest('.followup-step').remove()"><i class="bi bi-x-lg"></i></button>
      <div class="row g-2">
        <div class="col-4"><label class="form-label small">Wait (days)</label><input type="number" class="form-control form-control-sm" name="fu_delay[]" value="3" min="1"></div>
        <div class="col-8"><label class="form-label small">Subject</label><input class="form-control form-control-sm" name="fu_subject[]" placeholder="Re: {{first_name}}…"></div>
        <div class="col-12"><label class="form-label small">Body (HTML)</label><textarea class="form-control form-control-sm font-monospace" name="fu_body[]" rows="3"></textarea></div>
        <div class="col-12 d-flex gap-3 small">
          <label class="form-check"><input type="checkbox" class="form-check-input" name="fu_stop_opened[__I__]"> stop if opened</label>
          <label class="form-check"><input type="checkbox" class="form-check-input" name="fu_stop_clicked[__I__]"> stop if clicked</label>
          <label class="form-check"><input type="checkbox" class="form-check-input" name="fu_stop_replied[__I__]" checked> stop if replied</label>
        </div>
      </div>
    </div>
  </template>
</form>

<script>
let currentStep = 0;
const totalSteps = 5;
function goStep(n){
  if (n < 0 || n >= totalSteps) return;
  // require a name before leaving step 1
  if (currentStep === 0 && n > 0 && !document.getElementById('f_name').value.trim()){
    document.getElementById('f_name').focus(); document.getElementById('f_name').classList.add('is-invalid'); return;
  }
  document.getElementById('f_name').classList.remove('is-invalid');
  currentStep = n;
  document.querySelectorAll('.wstep-panel').forEach(p => p.classList.toggle('d-none', +p.dataset.panel !== n));
  document.querySelectorAll('.wstep').forEach(s => {
    const i = +s.dataset.step;
    s.classList.toggle('active', i === n);
    s.classList.toggle('done', i < n);
  });
  document.getElementById('btnBack').disabled = n === 0;
  document.getElementById('btnNext').classList.toggle('d-none', n === totalSteps - 1);
  if (n === totalSteps - 1) buildReview();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
function tplTab(which){
  const design = which === 'design';
  document.getElementById('tabDesign').classList.toggle('active', design);
  document.getElementById('tabPreview').classList.toggle('active', !design);
  document.getElementById('designView').classList.toggle('d-none', !design);
  document.getElementById('cmp_body').classList.toggle('d-none', !design);
  const f = document.getElementById('cmp_preview');
  f.classList.toggle('d-none', design);
  if (!design) window.refreshPreview('cmp_body', 'cmp_preview');
}

// ---- Design method: text editor vs. import HTML file (Brevo-style) ----
function setDesignMethod(m){
  const upload = m === 'upload';
  document.querySelectorAll('.design-method').forEach(el => el.classList.toggle('active', el.dataset.method === m));
  document.getElementById('uploadZone').classList.toggle('d-none', !upload);
  document.getElementById('tplPickRow').classList.toggle('d-none', upload);
  // In upload mode default to the rendered Preview; in editor mode show Design.
  tplTab(upload ? 'preview' : 'design');
}
function loadHtmlFile(file){
  if (!file) return;
  const name = (file.name || '').toLowerCase();
  if (!/\.html?$/.test(name)) { (window.toast ? window.toast('Please choose a .html or .htm file.', 'warning') : alert('Please choose a .html or .htm file.')); return; }
  const reader = new FileReader();
  reader.onload = function(ev){
    const b = document.getElementById('cmp_body');
    b.value = ev.target.result;
    b.dispatchEvent(new Event('input'));
    document.getElementById('uploadFileName').innerHTML = '<span class="text-success">' + file.name + ' loaded ✓</span>';
    tplTab('preview');                       // show the imported mailer rendered
  };
  reader.readAsText(file);
}
(function(){
  const input = document.getElementById('htmlFile');
  const drop  = document.getElementById('uploadDrop');
  if (input) input.addEventListener('change', function(){ loadHtmlFile(this.files[0]); });
  if (drop){
    ['dragenter','dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('dragover'); }));
    ['dragleave','drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('dragover'); }));
    drop.addEventListener('drop', e => { if (e.dataTransfer.files.length) loadHtmlFile(e.dataTransfer.files[0]); });
  }
})();
function wrapSel(open, close){
  const ta = document.getElementById('cmp_body');
  ta.focus();
  const s = ta.selectionStart, e = ta.selectionEnd;
  // Wrap the current selection while keeping native undo (Ctrl/Cmd+Z) intact.
  window.insertAtCursor(ta, open + ta.value.slice(s, e) + close);
  ta.dispatchEvent(new Event('input'));
}
const TEMPLATES = <?= js(array_map(fn($t) => ['id'=>(int)$t['id'],'subject'=>$t['subject'],'body'=>$t['body_html']], $templates)) ?>;
// ---- Recipient source toggle (Contact list vs Google Sheet) ----
(function(){
  var typeInput = document.getElementById('sourceType');
  if (!typeInput) return;
  function setSource(src){
    typeInput.value = src;
    document.querySelectorAll('.src-opt').forEach(function(b){ b.classList.toggle('active', b.dataset.src === src); });
    document.querySelectorAll('[data-src-panel]').forEach(function(p){ p.classList.toggle('d-none', p.dataset.srcPanel !== src); });
  }
  document.querySelectorAll('.src-opt').forEach(function(b){
    b.addEventListener('click', function(){ setSource(b.dataset.src); });
  });

  // ---- "Check" the Google Sheet: preview count + columns ----
  var btn = document.getElementById('sheetCheckBtn');
  var status = document.getElementById('sheetStatus');
  if (btn) btn.addEventListener('click', function(){
    var url = (document.getElementById('sheetUrl').value || '').trim();
    if (!url){ status.innerHTML = '<span class="text-danger">Paste a sheet link first.</span>'; return; }
    status.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm"></span> Checking…</span>';
    var body = new URLSearchParams(); body.append('sheet_url', url);
    fetch('<?= url('campaigns/sheet-preview') ?>', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: body })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d.ok){ status.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + (d.error||'Could not read the sheet.') + '</span>'; return; }
        if (!d.hasEmail){ status.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> No “Email” column found. Add one and try again.</span>'; return; }
        var cols = (d.columns||[]).map(function(c){ return '<span class="code-pill">{{'+c+'}}</span>'; }).join(' ');
        status.innerHTML =
          '<span class="text-success"><i class="bi bi-check-circle"></i> Found <strong>' + d.count + '</strong> recipient(s).</span>' +
          '<div class="mt-1 text-muted">Available merge tags: ' + cols + '</div>';
      })
      .catch(function(){ status.innerHTML = '<span class="text-danger">Network error. Try again.</span>'; });
  });
})();

function applyTemplate(){
  const id = parseInt(document.getElementById('tplSelect').value || '0');
  const t = TEMPLATES.find(x => x.id === id);
  if (t){ document.getElementById('cmp_subject').value = t.subject; const b=document.getElementById('cmp_body'); b.value=t.body; b.dispatchEvent(new Event('input')); }
}
let fuIndex = 0;
function addFollowup(){
  const html = document.getElementById('followupTpl').innerHTML.replace(/__I__/g, fuIndex++);
  const wrap = document.createElement('div'); wrap.innerHTML = html;
  document.getElementById('followups').appendChild(wrap.firstElementChild);
}
function buildReview(){
  const g = id => document.getElementById(id);
  const listSel = document.querySelector('[name=list_id]');
  const secSel = document.querySelector('[name=sector]');
  const sector = secSel && secSel.value ? secSel.value : 'All sectors';
  const locSel = document.querySelector('[name=location]');
  const location = locSel && locSel.value ? locSel.value : 'All locations';
  const rows = [
    ['Campaign name', g('f_name').value || '—'],
    ['Subject', g('cmp_subject').value || '—'],
    ['Audience', listSel.options[listSel.selectedIndex].text],
    ['Sector', sector],
    ['Location', location],
    ['Email length', (g('cmp_body').value.length) + ' chars'],
    ['Follow-ups', g('enable_followup').checked ? document.querySelectorAll('.followup-step').length + ' step(s)' : 'Disabled'],
    ['Tracking', [g('track_opens').checked?'opens':null, g('track_clicks').checked?'clicks':null].filter(Boolean).join(' + ') || 'off'],
  ];
  document.getElementById('reviewBox').innerHTML = rows.map(([k,v]) =>
    `<div class="col-md-6"><div class="border rounded-3 p-3"><div class="text-muted small">${k}</div><div class="fw-semibold">${v}</div></div></div>`).join('');
}
// preload existing follow-ups
const EXISTING = <?= js(array_map(fn($s)=>['delay'=>(int)$s['delay_days'],'subject'=>$s['subject'],'body'=>$s['body_html'],'o'=>(int)$s['stop_if_opened'],'c'=>(int)$s['stop_if_clicked'],'r'=>(int)$s['stop_if_replied']], $schedules)) ?>;
EXISTING.forEach(s => { addFollowup(); const all=document.querySelectorAll('.followup-step'); const el=all[all.length-1];
  el.querySelector('[name="fu_delay[]"]').value=s.delay; el.querySelector('[name="fu_subject[]"]').value=s.subject; el.querySelector('[name="fu_body[]"]').value=s.body;
  const cb=el.querySelectorAll('input[type=checkbox]'); cb[0].checked=!!s.o; cb[1].checked=!!s.c; cb[2].checked=!!s.r;
});
</script>
