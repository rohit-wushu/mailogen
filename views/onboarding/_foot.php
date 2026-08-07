  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.ob-pill, .ob-choice').forEach(function (el) {
  var input = el.querySelector('input');
  if (!input) return;
  el.addEventListener('click', function () {
    var name = input.name;
    document.querySelectorAll('[name="' + name + '"]').forEach(function (i) {
      i.closest('.ob-pill, .ob-choice')?.classList.remove('checked');
    });
    input.checked = true;
    el.classList.add('checked');
  });
});

// Light/dark toggle. Every onboarding step always loads light by default —
// this only flips the current page's view; it doesn't carry over when you
// move to the next step. It still saves to the account so the dashboard
// theme matches once onboarding is done.
(function () {
  var btn = document.getElementById('obThemeToggle');
  if (!btn) return;
  var icon = btn.querySelector('i');
  function paint(t) { icon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill'; }
  btn.addEventListener('click', function () {
    var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    paint(next);
    fetch(<?= json_encode(url('settings/theme')) ?>, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: 'theme=' + next + '&_csrf=' + encodeURIComponent(<?= json_encode(csrf_token()) ?>)
    });
  });
})();
</script>
</body>
</html>
