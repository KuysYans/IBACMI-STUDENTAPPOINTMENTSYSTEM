<?php
/**
 * Page loading screen (maroon overlay + logo in white circle + spinning ring).
 * Include this RIGHT AFTER the opening <body> tag of every page.
 * Works on XAMPP (/iba-system/...) and Railway (site root) because the
 * logo URL is computed from the real folder location.
 */
$__root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$__doc  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$__base = ($__doc && strncasecmp($__root, $__doc, strlen($__doc)) === 0)
    ? substr($__root, strlen($__doc)) : '';
$__logo = htmlspecialchars($__base . '/assets/logo.png', ENT_QUOTES);
?>
<style>
  .ibl-loader{position:fixed;inset:0;z-index:2147483647;
    background:radial-gradient(circle at 50% 45%,#B5393D 0%,#9E2A2E 55%,#7A1E22 100%);
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:28px;
    opacity:1;visibility:visible;transition:opacity .5s ease,visibility .5s ease}
  .ibl-loader.ibl-hide{opacity:0;visibility:hidden;pointer-events:none}

  .ibl-wrap{position:relative;width:150px;height:150px;display:flex;align-items:center;justify-content:center}

  /* spinning arc */
  .ibl-spin{position:absolute;inset:0;border-radius:50%;
    border:3px solid rgba(255,255,255,.18);border-top-color:#fff;
    animation:ibl-spin 1.1s linear infinite}

  /* white circle holding the logo */
  .ibl-disc{width:112px;height:112px;border-radius:50%;background:#fff;
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 0 0 0 rgba(255,255,255,.35),0 8px 24px rgba(0,0,0,.25);
    animation:ibl-breathe 2s ease-in-out infinite;will-change:transform}
  .ibl-logo{width:78px;height:78px;object-fit:contain}

  @keyframes ibl-spin{to{transform:rotate(360deg)}}
  @keyframes ibl-breathe{
    0%,100%{transform:scale(.96);box-shadow:0 0 0 0 rgba(255,255,255,.30),0 8px 24px rgba(0,0,0,.25)}
    50%{transform:scale(1.04);box-shadow:0 0 0 14px rgba(255,255,255,0),0 8px 24px rgba(0,0,0,.25)}}

  @media (prefers-reduced-motion:reduce){.ibl-spin,.ibl-disc{animation:none}}
</style>
<noscript><style>.ibl-loader{display:none!important}</style></noscript>

<div class="ibl-loader" id="iblLoader" aria-hidden="true">
  <div class="ibl-wrap">
    <span class="ibl-spin"></span>
    <div class="ibl-disc">
      <img class="ibl-logo" src="<?= $__logo ?>" alt="IBA College of Mindanao">
    </div>
  </div>
</div>

<script>
(function () {
  var el = document.getElementById('iblLoader');
  var MIN_MS = 700, start = Date.now(), done = false;

  function hide() {
    if (done) return; done = true;
    var wait = Math.max(0, MIN_MS - (Date.now() - start));
    setTimeout(function () { el.classList.add('ibl-hide'); }, wait);
  }
  function show() { done = false; start = Date.now(); el.classList.remove('ibl-hide'); }

  if (document.readyState === 'complete') hide();
  else window.addEventListener('load', hide);
  setTimeout(hide, 8000);                       // safety: never get stuck

  // back/forward cache: page restored without reloading
  window.addEventListener('pageshow', function (e) { if (e.persisted) { done = false; hide(); } });

  // show again when going to another page
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0) return;
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;
    var h = a.getAttribute('href');
    if (!h || h.charAt(0) === '#' || /^(javascript:|mailto:|tel:)/i.test(h)) return;
    if (a.origin !== location.origin) return;
    if (a.pathname === location.pathname && a.search === location.search && a.hash) return;
    show();
  });
  document.addEventListener('submit', function (e) { if (!e.defaultPrevented) show(); });
})();
</script>