<?php /** @var array $lists */ ?>
<div class="page-head">
  <div>
    <h2 class="mb-1">Import Contacts</h2>
    <p class="text-muted mb-0">Bring contacts in from a CSV or Excel file in seconds.</p>
  </div>
  <a href="<?= url('contacts') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-cloud-arrow-up me-1"></i> Upload a CSV or Excel file</div>
      <form class="card-body" method="post" action="<?= url('contacts/import') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="text-center mb-4">
          <div class="stat-icon grad-violet mx-auto" style="width:64px;height:64px;font-size:1.8rem"><i class="bi bi-filetype-csv"></i></div>
        </div>
        <div class="mb-3">
          <label class="form-label">CSV or Excel file *</label>
          <input type="file" class="form-control" name="file" accept=".csv,.xlsx,text/csv" required>
          <div class="form-text">Max ~10MB. The first row must be the header.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Add to list</label>
          <select class="form-select" name="list_id"><option value="">— none —</option>
            <?php foreach ($lists as $l): ?><option value="<?= (int) $l['id'] ?>"><?= e($l['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Import contacts</button>
      </form>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-info-circle me-1"></i> Expected format</div>
      <div class="card-body">
        <p class="small text-muted">Recognised columns (any order, case-insensitive):</p>
        <ul class="small"><li><code>email</code> (required)</li><li><code>first_name</code> / first name</li><li><code>last_name</code></li><li><code>company</code></li><li><code>sector</code> / industry / category <span class="text-muted">— for segmentation</span></li><li><code>location</code> / city / state / country <span class="text-muted">— for segmentation</span></li><li><code>phone</code></li></ul>
        <pre class="bg-body-tertiary p-2 rounded small mb-0">email,first_name,last_name,company,sector,location
jane@acme.com,Jane,Doe,Acme Inc,Healthcare,Mumbai
bob@globex.com,Bob,Lee,Globex,Education,Delhi</pre>
        <p class="small text-muted mt-2 mb-0"><i class="bi bi-diagram-3"></i> Add a <strong>sector</strong> and/or <strong>location</strong> column to segment contacts (e.g. Healthcare · Mumbai) — then target a single sector or location when sending a campaign.</p>
        <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle"></i> Duplicates are merged automatically and invalid emails skipped. Both <strong>.csv</strong> and <strong>.xlsx</strong> are supported.</p>
      </div>
    </div>
  </div>
</div>
