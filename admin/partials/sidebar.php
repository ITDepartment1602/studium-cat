<?php
if (!isset($ap_base))     $ap_base     = '';
if (!isset($active_nav))  $active_nav  = '';
if (!isset($ap_title))    $ap_title    = 'Admin Panel';
if (!isset($ap_subtitle)) { date_default_timezone_set('Asia/Manila'); $ap_subtitle = date('l, F j, Y'); }

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_login'])) {
    header('Location: ' . $ap_base . '../index.php');
    exit;
}

$nav_items = [
    ['id'=>'dashboard',   'label'=>'Dashboard',        'icon'=>'bi bi-grid-fill',           'href'=> $ap_base . 'index.php'],
    ['id'=>'add_student', 'label'=>'Add Student',      'icon'=>'bi bi-person-plus-fill',    'href'=> $ap_base . 'import_csv.php'],
    ['id'=>'topics',      'label'=>'Manage Topics',    'icon'=>'bi bi-book-fill',           'href'=> $ap_base . 'manage topics/'],
    ['id'=>'questions',   'label'=>'Manage Questions', 'icon'=>'bi bi-patch-question-fill', 'href'=> $ap_base . 'manage question/'],
    ['id'=>'bundle',      'label'=>'Manage Bundle',    'icon'=>'bi bi-collection-fill',     'href'=> $ap_base . 'manage bundle/'],
    ['id'=>'group',       'label'=>'Manage Group',     'icon'=>'bi bi-people-fill',         'href'=> $ap_base . 'manage group/'],
    ['id'=>'result',      'label'=>'Manage Result',    'icon'=>'bi bi-bar-chart-fill',      'href'=> $ap_base . 'manage result/'],
    ['id'=>'feedback',    'label'=>'Feedback',         'icon'=>'bi bi-chat-heart-fill',     'href'=> $ap_base . 'manage feedback/'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($ap_title) ?> — Studium Admin</title>
  <link rel="icon" type="image/svg+xml" href="<?= $ap_base ?>../assets/LOGO.svg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <?php if (!empty($ap_extra_head)) echo $ap_extra_head; ?>
  <link rel="stylesheet" href="<?= $ap_base ?>admin-panel.css">
</head>
<body>

<div class="ap-sidebar-overlay" id="apOverlay"></div>

<div class="ap-layout">

  <!-- Sidebar -->
  <aside class="ap-sidebar" id="apSidebar">
    <div class="ap-sidebar-brand">
      <img src="<?= $ap_base ?>../assets/LOGO.svg" alt="Studium" class="ap-brand-logo" style="margin-right:9px;">
      <span class="ap-brand-panel-text">STUDIUM CAT</span>
    </div>

    <!-- Admin Profile Card -->
    <div class="ap-sidebar-profile">
      <div class="ap-sidebar-profile-row">
        <div class="ap-profile-avatar">A</div>
        <div class="ap-profile-info">
          <div class="ap-profile-label">Logged in as</div>
          <div class="ap-profile-name"><?= htmlspecialchars($_SESSION['admin_login'] ?? 'Administrator') ?></div>
        </div>
      </div>
      <span class="ap-profile-role"><i class="bi bi-shield-fill-check" style="font-size:0.58rem;color:rgba(13,148,136,0.7);"></i> Administrator</span>
    </div>

    <div class="ap-nav-wrap">
      <div class="ap-nav-section">Navigation</div>
      <ul class="ap-nav">
        <?php foreach ($nav_items as $item): ?>
        <li>
          <a href="<?= htmlspecialchars($item['href']) ?>"
             class="<?= $item['id'] === $active_nav ? 'active' : '' ?>">
            <i class="<?= $item['icon'] ?>"></i>
            <?= htmlspecialchars($item['label']) ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>

      <div class="ap-nav-section" style="margin-top:8px;">Account</div>
      <ul class="ap-nav ap-nav-logout">
        <li>
          <a href="#" onclick="document.getElementById('apLogoutModal').style.display='flex';return false;">
            <i class="bi bi-box-arrow-left"></i>
            Log Out
          </a>
        </li>
      </ul>
    </div>
  </aside>

  <!-- Logout Confirmation Modal -->
  <div id="apLogoutModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">
    <div style="background:#fff;border-radius:18px;width:90%;max-width:360px;overflow:hidden;box-shadow:0 24px 56px rgba(15,23,42,0.22);animation:ap-modal-in .22s ease;">
      <div style="padding:22px 24px 16px;display:flex;align-items:center;gap:14px;">
        <div style="width:46px;height:46px;border-radius:13px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="bi bi-box-arrow-left" style="color:#ef4444;font-size:1.2rem;"></i>
        </div>
        <div>
          <div style="font-size:1rem;font-weight:700;color:#0f172a;margin-bottom:3px;">Log Out?</div>
          <div style="font-size:0.78rem;color:#64748b;line-height:1.4;">You will be signed out of the admin panel.</div>
        </div>
      </div>
      <div style="padding:0 24px 20px;display:flex;gap:8px;justify-content:flex-end;">
        <button onclick="document.getElementById('apLogoutModal').style.display='none'"
          style="padding:9px 20px;border-radius:9px;border:1.5px solid #e2e8f0;background:#fff;font-family:Inter,sans-serif;font-size:0.82rem;font-weight:600;color:#64748b;cursor:pointer;transition:background .15s;">
          Cancel
        </button>
        <a href="<?= $ap_base ?>logout.php"
          style="padding:9px 20px;border-radius:9px;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);font-family:Inter,sans-serif;font-size:0.82rem;font-weight:600;color:#fff;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:opacity .15s;">
          <i class="bi bi-box-arrow-left"></i> Yes, Log Out
        </a>
      </div>
    </div>
  </div>

  <!-- Main -->
  <div class="ap-main">

    <header class="ap-topbar">
      <div class="ap-topbar-left">
        <button class="ap-topbar-mobile-btn" id="sidebarToggle" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <div class="ap-page-title"><?= htmlspecialchars($ap_title) ?></div>
          <div class="ap-page-subtitle"><?= htmlspecialchars($ap_subtitle) ?></div>
        </div>
      </div>
      <div class="ap-topbar-right">
        <?php if (!empty($ap_topbar_actions)) echo $ap_topbar_actions; ?>
        <div class="ap-avatar">A</div>
      </div>
    </header>

    <main class="ap-content">
