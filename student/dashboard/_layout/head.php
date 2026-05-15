<?php
/**
 * Shared <head> include for all student dashboard pages.
 * Set $pageTitle before including this file.
 */
$pageTitle = isset($pageTitle) ? $pageTitle : 'Studium';
?>
<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<link rel="shortcut icon" type="image/svg+xml" href="../../assets/LOGO.svg">

<!-- Google Fonts: Poppins -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Bootstrap 5 (kept for DataTables + plugins) -->
<link rel="stylesheet" href="../ty/css/bootstrap.min.css" />
<link rel="stylesheet" href="../ty/css/dataTables.bootstrap5.min.css" />

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Font Awesome (kit — async, non-blocking) -->
<script src="https://kit.fontawesome.com/8cebfeba05.js" crossorigin="anonymous" defer></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Studium Design System v2 -->
<link rel="stylesheet" href="css/studium-v2.css">

<!-- TailwindCSS (utility layer on top) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  corePlugins: { preflight: false },
  important: false,
  theme: {
    extend: {
      fontFamily: {
        sans: ['Poppins', 'sans-serif'],
      }
    }
  }
}
</script>
<style>
  *, *::before, *::after { font-family: 'Poppins', sans-serif !important; }
</style>
