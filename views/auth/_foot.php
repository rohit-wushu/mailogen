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
</script>
</body>
</html>
