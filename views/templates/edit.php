<?php /** @var ?array $template */ ?>
<link href="https://unpkg.com/grapesjs@0.21.12/dist/css/grapes.min.css" rel="stylesheet">

<div class="page-head">
  <h2><?= $template ? 'Edit template' : 'New template' ?></h2>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <div class="seg-tabs" id="modeTabs">
      <button type="button" class="active" id="modeVisual"><i class="bi bi-grid-1x2"></i> Visual Builder</button>
      <button type="button" id="modeHtml"><i class="bi bi-code-slash"></i> HTML Code</button>
    </div>
    <a href="<?= url('templates') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    <button type="submit" form="tplForm" class="btn btn-primary"><i class="bi bi-save"></i> Save template</button>
  </div>
</div>

<form method="post" action="<?= url('templates/store') ?>" id="tplForm">
  <?= csrf_field() ?>
  <?php if ($template): ?><input type="hidden" name="id" value="<?= (int) $template['id'] ?>"><?php endif; ?>
  <input type="hidden" name="body_html" id="body_html">

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-5"><label class="form-label">Template name *</label><input class="form-control" name="name" value="<?= e($template['name'] ?? '') ?>" required></div>
        <div class="col-md-5"><label class="form-label">Subject *</label><input class="form-control" name="subject" value="<?= e($template['subject'] ?? '') ?>" required></div>
        <div class="col-md-2">
          <label class="form-label">Merge tags</label>
          <div class="d-flex flex-wrap gap-1">
            <?php foreach (['{{first_name}}','{{company}}','{{email}}'] as $tag): ?><span class="code-pill"><?= e($tag) ?></span><?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<div class="row g-3" id="builderRow">
  <div class="col-lg-9">
    <div class="card"><div class="card-body p-0"><div id="gjs" style="min-height:660px"></div></div></div>
  </div>
  <div class="col-lg-3">
    <div class="card"><div class="card-body">
      <div class="seg-tabs w-100 mb-3">
        <button type="button" class="active w-50" id="tabBlocks"><i class="bi bi-grid-1x2"></i> Blocks</button>
        <button type="button" class="w-50" id="tabSettings"><i class="bi bi-sliders"></i> Settings</button>
      </div>
      <div id="paneBlocks"></div>
      <div id="paneSettings" class="d-none">
        <p class="text-muted small mb-2" id="settingsHint">Select a block in the email to edit its styles.</p>
        <div id="gjsSelectors"></div>
        <div id="gjsTraits"></div>
        <div id="gjsStyles"></div>
      </div>
    </div></div>
  </div>
</div>

<!-- HTML Code editor with live preview (toggled from the header) -->
<div class="row g-3 d-none" id="htmlMode">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-code-slash"></i> HTML source</span>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-light" id="htmlFormat" title="Insert merge tag">{{ }}</button>
          <button type="button" class="btn btn-sm btn-light" id="htmlPretty" title="Reset preview">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <textarea class="form-control font-monospace border-0" id="htmlSource" spellcheck="false"
                  style="min-height:620px;border-radius:0;resize:vertical;font-size:.82rem;line-height:1.5"
                  placeholder="Paste or write your email HTML here…"><?= e($template['body_html'] ?? '') ?></textarea>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-eye"></i> Live preview</span>
        <div class="btn-group btn-group-sm" role="group" id="previewWidth">
          <button type="button" class="btn btn-light active" data-w="100%" title="Desktop"><i class="bi bi-laptop"></i></button>
          <button type="button" class="btn btn-light" data-w="380px" title="Mobile"><i class="bi bi-phone"></i></button>
        </div>
      </div>
      <div class="card-body p-0" style="background:#f4f5fb;display:flex;justify-content:center;align-items:flex-start">
        <iframe id="htmlPreview" title="Email preview"
                style="width:100%;height:620px;border:0;background:#fff;transition:width .2s"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Fallback raw-HTML editor (used if the visual builder fails to load) -->
<div class="card mt-3 d-none" id="rawFallback">
  <div class="card-header">HTML source</div>
  <div class="card-body"><textarea class="form-control font-monospace" id="rawHtml" rows="16"><?= e($template['body_html'] ?? '') ?></textarea></div>
</div>

<!-- grapesjs pinned to a version compatible with the newsletter preset (1.0.2
     targets grapesjs ^0.21.x); the unpinned "latest" breaks init(). -->
<script src="https://unpkg.com/grapesjs@0.21.12/dist/grapes.min.js"></script>
<script src="https://unpkg.com/grapesjs-preset-newsletter@1.0.2/dist/index.js"></script>
<script>
(function () {
  var form = document.getElementById('tplForm');
  var bodyInput = document.getElementById('body_html');
  var initialHtml = <?= js($template['body_html'] ?? '<table style="width:100%;font-family:Arial,sans-serif"><tr><td style="padding:28px"><h1 style="color:#111;margin:0 0 10px">Your headline</h1><p style="color:#555;line-height:1.6">Click a block on the right to add it. Use {{first_name}} to personalise.</p></td></tr></table>') ?>;
  var editor = null;
  var suppressPaneSwitch = false;

  // ----------------------------------------------------------------
  //  Click-to-add block palette (custom, replaces GrapesJS drag blocks)
  // ----------------------------------------------------------------
  function buildBlockPalette() {
    var pane = document.getElementById('paneBlocks');
    var blocks = editor.BlockManager.getAll();
    pane.innerHTML = '';

    if (!blocks || !blocks.length) {
      pane.innerHTML = '<p class="text-muted small mb-0">No blocks available.</p>';
      return;
    }

    var grid = document.createElement('div');
    grid.className = 'blk-grid';

    blocks.forEach(function (block) {
      var label = block.get('label');
      if (typeof label !== 'string' || !label) { label = block.get('id'); }
      var caption = label.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

      var tile = document.createElement('button');
      tile.type = 'button';
      tile.className = 'blk-tile';
      tile.title = 'Click to add: ' + caption;
      tile.innerHTML =
        '<span class="blk-tile-icon">' + (block.get('media') || label) + '</span>' +
        '<span class="blk-tile-cap">' + caption + '</span>';
      tile.addEventListener('click', function () { addBlock(block); });
      grid.appendChild(tile);
    });

    pane.appendChild(grid);
  }

  // Append a block to the canvas on click. Inserts right after the selected
  // top-level section when there is one, otherwise at the end of the email.
  function addBlock(block) {
    if (!editor) { return; }
    var content = block.get('content');
    var wrapper = editor.getWrapper();
    var selected = editor.getSelected();
    var opts = {};

    if (selected && selected.parent && selected.parent() === wrapper) {
      opts.at = wrapper.components().indexOf(selected) + 1;
    }

    var added = wrapper.append(content, opts);
    var comp = Array.isArray(added) ? added[0] : added;
    if (!comp) { return; }

    // Select for visual feedback, but keep the Blocks tab open for rapid adds.
    suppressPaneSwitch = true;
    editor.select(comp);
    suppressPaneSwitch = false;

    var el = comp.getEl && comp.getEl();
    if (el && el.scrollIntoView) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
  }

  function useRawFallback() {
    document.getElementById('builderRow').classList.add('d-none');
    document.getElementById('rawFallback').classList.remove('d-none');
    document.getElementById('rawHtml').value = initialHtml;
  }

  if (typeof grapesjs !== 'undefined') {
    try {
      editor = grapesjs.init({
        container: '#gjs',
        height: '660px',
        fromElement: false,
        storageManager: false,
        plugins: ['grapesjs-preset-newsletter'],
        pluginsOpts: { 'grapesjs-preset-newsletter': { modalLabelImport: 'Paste your HTML', cellWidth: 100 } },
        // Render the managers into our own always-visible side panels.
        // `custom: true` suppresses GrapesJS's default draggable block list so
        // we can render our own click-to-add tile palette (see buildBlockPalette).
        blockManager:   { custom: true },
        selectorManager:{ appendTo: '#gjsSelectors' },
        traitManager:   { appendTo: '#gjsTraits' },
        styleManager:   {
          appendTo: '#gjsStyles',
          sectors: [
            { name: 'Typography', open: true,  properties: ['font-family','font-size','font-weight','color','line-height','text-align'] },
            { name: 'Background', open: false, properties: ['background-color'] },
            { name: 'Spacing',    open: false, properties: ['padding','margin'] },
            { name: 'Border',     open: false, properties: ['border-radius','border'] },
            { name: 'Dimension',  open: false, properties: ['width','height'] },
          ],
        },
      });
      editor.setComponents(initialHtml);

      // Build our custom click-to-add block palette (replaces drag-and-drop).
      buildBlockPalette();

      // Auto-switch to Settings when a block is selected, Blocks otherwise.
      // (Suppressed right after a click-to-add so the user stays in the Blocks
      //  tab and can keep adding without losing their place.)
      editor.on('component:selected', function () {
        if (suppressPaneSwitch) return;
        showPane('settings');
        document.getElementById('settingsHint').classList.add('d-none');
      });
      editor.on('component:deselected', function () { document.getElementById('settingsHint').classList.remove('d-none'); });
    } catch (err) {
      console.error('GrapesJS failed to initialise, using raw HTML editor:', err);
      editor = null;
      useRawFallback();
    }
  } else {
    useRawFallback();
  }

  function showPane(which) {
    var blocks = which === 'blocks';
    document.getElementById('tabBlocks').classList.toggle('active', blocks);
    document.getElementById('tabSettings').classList.toggle('active', !blocks);
    document.getElementById('paneBlocks').classList.toggle('d-none', !blocks);
    document.getElementById('paneSettings').classList.toggle('d-none', blocks);
  }
  document.getElementById('tabBlocks').addEventListener('click', function () { showPane('blocks'); });
  document.getElementById('tabSettings').addEventListener('click', function () { showPane('settings'); });

  // ----------------------------------------------------------------
  //  HTML Code mode + live preview
  // ----------------------------------------------------------------
  var htmlMode    = false;
  var builderRow  = document.getElementById('builderRow');
  var htmlPanel   = document.getElementById('htmlMode');
  var htmlSource  = document.getElementById('htmlSource');
  var htmlPreview = document.getElementById('htmlPreview');
  var modeVisual  = document.getElementById('modeVisual');
  var modeHtml    = document.getElementById('modeHtml');

  // Combine the editor's HTML for handing to the code view.
  function currentHtml() {
    if (editor) { return '<style>' + editor.getCss() + '</style>' + editor.getHtml(); }
    if (htmlSource.value.trim()) { return htmlSource.value; }
    return initialHtml;
  }

  var previewTimer;
  function renderPreview() {
    var doc = htmlPreview.contentDocument || htmlPreview.contentWindow.document;
    doc.open();
    doc.write(htmlSource.value || '<p style="font-family:sans-serif;color:#9aa0b5;padding:24px">Start typing HTML on the left…</p>');
    doc.close();
  }
  function schedulePreview() { clearTimeout(previewTimer); previewTimer = setTimeout(renderPreview, 250); }

  function enterHtmlMode() {
    if (!htmlSource.value.trim()) { htmlSource.value = currentHtml(); }
    htmlMode = true;
    builderRow.classList.add('d-none');
    document.getElementById('rawFallback').classList.add('d-none');
    htmlPanel.classList.remove('d-none');
    modeHtml.classList.add('active');
    modeVisual.classList.remove('active');
    renderPreview();
  }
  function enterVisualMode() {
    htmlMode = false;
    htmlPanel.classList.add('d-none');
    modeVisual.classList.add('active');
    modeHtml.classList.remove('active');
    if (editor) {
      builderRow.classList.remove('d-none');
      // Push any HTML edits back into the visual canvas.
      if (htmlSource.value.trim()) { editor.setComponents(htmlSource.value); }
    } else {
      // No visual builder available — fall back to the simple raw editor.
      document.getElementById('rawFallback').classList.remove('d-none');
      document.getElementById('rawHtml').value = htmlSource.value || initialHtml;
    }
  }
  modeHtml.addEventListener('click', enterHtmlMode);
  modeVisual.addEventListener('click', enterVisualMode);
  htmlSource.addEventListener('input', schedulePreview);
  document.getElementById('htmlPretty').addEventListener('click', renderPreview);
  document.getElementById('htmlFormat').addEventListener('click', function () {
    var t = htmlSource, s = t.selectionStart, tag = '{{first_name}}';
    t.value = t.value.slice(0, s) + tag + t.value.slice(t.selectionEnd);
    t.selectionStart = t.selectionEnd = s + tag.length;
    t.focus(); schedulePreview();
  });
  document.querySelectorAll('#previewWidth button').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('#previewWidth button').forEach(function (x) { x.classList.remove('active'); });
      b.classList.add('active');
      htmlPreview.style.width = b.dataset.w;
    });
  });

  // If the visual builder couldn't load, default straight into HTML mode so
  // the user still gets the code editor + preview (instead of the bare fallback).
  if (!editor) { enterHtmlMode(); }

  form.addEventListener('submit', function () {
    if (htmlMode) {
      bodyInput.value = htmlSource.value;
    } else if (editor) {
      bodyInput.value = '<style>' + editor.getCss() + '</style>' + editor.getHtml();
    } else {
      bodyInput.value = document.getElementById('rawHtml').value;
    }
  });
})();
</script>
<style>
  /* ---- Custom click-to-add block palette ---- */
  .blk-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .blk-tile {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 14px 8px 10px; border: 1px solid var(--bs-border-color);
    border-radius: 12px; background: var(--bs-body-bg); color: var(--bs-body-color);
    cursor: pointer; text-align: center; line-height: 1.2;
    transition: border-color .15s, box-shadow .15s, transform .15s, background .15s;
  }
  .blk-tile:hover {
    border-color: var(--brand);
    background: rgba(109, 94, 252, .05);
    box-shadow: 0 8px 20px -10px var(--ring);
    transform: translateY(-2px);
  }
  .blk-tile:active { transform: translateY(0); }
  .blk-tile:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }
  .blk-tile-icon {
    display: grid; place-items: center; width: 100%; height: 46px; color: #98a0bd;
    transition: color .15s;
  }
  .blk-tile:hover .blk-tile-icon { color: var(--brand); }
  .blk-tile-icon svg { width: auto; max-width: 60px; height: 100%; }
  .blk-tile-cap { font-size: .74rem; font-weight: 600; color: var(--bs-body-color); }
  #gjs .gjs-cv-canvas { background: #f4f5fb; }
  .gjs-one-bg { background: var(--bs-body-bg); }
  .gjs-two-color, .gjs-sm-label, .gjs-trt-trait { color: var(--bs-body-color); }
  .gjs-sm-sector-title, .gjs-clm-tags { background: transparent; }
</style>
