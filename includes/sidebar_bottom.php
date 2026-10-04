  </main>
</div>
<script>
(function () {
  if (window.__sbBurgerReady) return;   // dili na maduha ang listener
  window.__sbBurgerReady = true;

  var sidebar = document.querySelector('.sidebar');
  var burger  = document.getElementById('sbBurger');
  if (!sidebar || !burger) return;

  function setOpen(open) {
    sidebar.classList.toggle('open', open);
    burger.setAttribute('aria-expanded', String(open));
  }

  burger.addEventListener('click', function (e) {
    e.stopPropagation();
    setOpen(!sidebar.classList.contains('open'));
  });

  // close after tapping a menu link
  sidebar.querySelectorAll('.side-nav a').forEach(function (a) {
    a.addEventListener('click', function () { setOpen(false); });
  });

  // close when tapping outside the sidebar
  document.addEventListener('click', function (e) {
    if (!sidebar.contains(e.target)) setOpen(false);
  });

  // reset when resized back to desktop
  window.addEventListener('resize', function () {
    if (window.innerWidth > 920) setOpen(false);
  });
})();
</script>
</body>
</html>
