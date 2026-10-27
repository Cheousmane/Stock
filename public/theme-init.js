(function () {
  var stored = 'system';

  try {
    var saved = localStorage.getItem('theme');
    if (saved === 'light' || saved === 'dark' || saved === 'system') {
      stored = saved;
    }
  } catch (error) {
    // Stockage indisponible : on applique le mode système.
  }

  var prefersDark = window.matchMedia
    ? window.matchMedia('(prefers-color-scheme: dark)').matches
    : false;
  var isDark = stored === 'dark' || (stored === 'system' && prefersDark);
  var root = document.documentElement;

  root.classList.toggle('dark', isDark);
  root.dataset.theme = stored;
  root.style.colorScheme = isDark ? 'dark' : 'light';

  var meta = document.querySelector('meta[name="theme-color"]');
  if (meta) {
    meta.setAttribute('content', isDark ? '#0a0a0a' : '#ffffff');
  }
})();
