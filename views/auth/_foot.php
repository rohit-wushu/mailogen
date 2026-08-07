<?php /** Shared footer for all auth pages. */ ?>
      <div class="auth-legal">
        <a href="<?= url('legal/terms') ?>">Terms</a>
        <a href="<?= url('legal/privacy') ?>">Privacy</a>
        <a href="<?= url('legal/acceptable-use') ?>">Acceptable Use</a>
      </div>
    </div>
  </main>
</div>
<script>
// Show / hide password toggles.
document.querySelectorAll('[data-toggle-pw]').forEach(function (b) {
  b.addEventListener('click', function () {
    var i = document.querySelector(b.dataset.togglePw);
    if (!i) return;
    i.type = i.type === 'password' ? 'text' : 'password';
    b.querySelector('i').className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  });
});

// Light/dark toggle (guest pages have no account yet, so persist to localStorage).
(function () {
  var btn = document.getElementById('authThemeToggle');
  if (!btn) return;
  var icon = btn.querySelector('i');
  function paint(t) { icon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill'; }
  paint(document.documentElement.getAttribute('data-bs-theme'));
  btn.addEventListener('click', function () {
    var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('mg-theme', next);
    paint(next);
  });
})();
</script>
</body>
</html>
