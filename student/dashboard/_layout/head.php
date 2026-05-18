<?php
/**
 * Shared <head> include for all student dashboard pages.
 * Set $pageTitle before including this file.
 * Bootstrap-free — uses studium-v2.css design system.
 */
$pageTitle = isset($pageTitle) ? $pageTitle : 'Studium';
?>
<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<link rel="shortcut icon" type="image/svg+xml" href="../../assets/LOGO.svg">

<!-- Google Fonts: Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Bootstrap Icons (independent icon library — kept) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Font Awesome (async, non-blocking) -->
<script src="https://kit.fontawesome.com/8cebfeba05.js" crossorigin="anonymous" defer></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Studium Design System v3 (Bootstrap-free) -->
<link rel="stylesheet" href="css/studium-v2.css">

<!-- Custom Modal System (SModal) — replaces Bootstrap modal JS -->
<script>
const SModal = {
  open(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('is-open');
    document.body.classList.add('s-modal-open');
    el.setAttribute('aria-hidden', 'false');
  },
  close(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('is-open');
    document.body.classList.remove('s-modal-open');
    el.setAttribute('aria-hidden', 'true');
  },
  init() {
    document.addEventListener('click', function(e) {
      /* Click directly on backdrop overlay closes modal */
      if (e.target.classList.contains('s-modal-backdrop')) {
        e.target.classList.remove('is-open');
        document.body.classList.remove('s-modal-open');
        e.target.setAttribute('aria-hidden', 'true');
      }
      /* data-s-dismiss="modal" attribute on any child closes nearest backdrop */
      const dismisser = e.target.closest('[data-s-dismiss="modal"]');
      if (dismisser) {
        const backdrop = dismisser.closest('.s-modal-backdrop');
        if (backdrop) {
          backdrop.classList.remove('is-open');
          document.body.classList.remove('s-modal-open');
          backdrop.setAttribute('aria-hidden', 'true');
        }
      }
    });
    /* ESC key closes all open modals */
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.s-modal-backdrop.is-open').forEach(function(m) {
          m.classList.remove('is-open');
          m.setAttribute('aria-hidden', 'true');
        });
        document.body.classList.remove('s-modal-open');
      }
    });
  }
};
document.addEventListener('DOMContentLoaded', function() { SModal.init(); });
</script>

<!-- TailwindCSS (utility fill layer — preflight disabled to avoid conflicts) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  corePlugins: { preflight: false },
  important: false,
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      }
    }
  }
}
</script>
