    </main>
  </div><!-- /.ap-main -->
</div><!-- /.ap-layout -->

<?php if (!empty($ap_extra_js)) echo $ap_extra_js; ?>

<script>
(function () {
  const sidebar  = document.getElementById('apSidebar');
  const overlay  = document.getElementById('apOverlay');
  const toggle   = document.getElementById('sidebarToggle');
  function openSidebar()  { sidebar.classList.add('open');  overlay.classList.add('open'); }
  function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
  if (toggle)  toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
  if (overlay) overlay.addEventListener('click', closeSidebar);
})();
</script>
</body>
</html>
