<?php
/**
 * Live Quiz host inside the admin layout, so the admin sidebar and top bar stay visible.
 * The game screen itself (game/host.php?embed=1) runs in an iframe so its styles never
 * collide with the admin panel's Bootstrap. "Projector view" opens it full-window.
 */
require_once '../config.php';

$ap_title    = 'Live Quiz Game';
$ap_subtitle = 'Host a break-time NCLEX quiz — students join on their phones';
$active_nav  = 'live_quiz';
$ap_topbar_actions = '<a href="../game/host.php" target="_blank" rel="noopener" class="lq-projector-btn"><i class="bi bi-display"></i> Projector view</a>';
$ap_extra_head = '<style>
  .ap-content:has(.lq-frame-wrap) { padding: 12px; }
  .lq-frame-wrap { height: calc(100vh - 96px); min-height: 620px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px -12px rgba(15,23,42,.35); background: #005e91; }
  .lq-frame-wrap iframe { display: block; width: 100%; height: 100%; border: 0; }
  .lq-projector-btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 10px; background: #007cbf; color: #fff; font-size: .82rem; font-weight: 600; text-decoration: none; margin-right: 10px; }
  .lq-projector-btn:hover { background: #005e91; color: #fff; }
  @media (max-width: 768px) { .lq-frame-wrap { height: calc(100vh - 80px); min-height: 520px; border-radius: 12px; } .lq-projector-btn span { display: none; } }
</style>';

require_once 'partials/sidebar.php';
?>
<div class="lq-frame-wrap">
  <iframe src="../game/host.php?embed=1" title="Live Quiz host" allow="fullscreen; autoplay" allowfullscreen></iframe>
</div>
<?php require_once 'partials/footer.php'; ?>
