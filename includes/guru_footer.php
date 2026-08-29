    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  const sidebar = document.getElementById('adminSidebar');
  const toggle = document.getElementById('adminMenuToggle');
  const backdrop = document.getElementById('adminBackdrop');
  if (!sidebar || !toggle || !backdrop) return;
  function close() { sidebar.classList.remove('open'); backdrop.classList.remove('show'); }
  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('show');
  });
  backdrop.addEventListener('click', close);
})();
</script>
</body>
</html>
