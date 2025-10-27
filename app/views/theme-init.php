<?php /* Include this BEFORE Bootstrap CSS in <head> */ ?>
<script id="theme-init">
  (function () {
    try {
      var m = document.cookie.match(/(?:^|;\s*)theme=(light|dark)/);
      var cookieTheme = m ? m[1] : null;
      var storedTheme = localStorage.getItem('theme');
      var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      var theme = cookieTheme || storedTheme || (prefersDark ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', theme);
    } catch (_) {}
  })();
</script>
