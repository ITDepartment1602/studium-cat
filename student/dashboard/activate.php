<?php
session_start();
include '../../config.php';
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['pending_user_id'])) {
    header('location: ../');
    exit;
}

$fullname = $_SESSION['pending_fullname'];
$subMonth = $_SESSION['pending_subMonth'];
$userId   = $_SESSION['pending_user_id'];

// ── Activation grace period ──────────────────────────────────────────────
// FREE-trial students get ACTIVATION_GRACE_MONTHS from their access_date to
// activate manually. If they haven't by then, the account auto-activates and
// the trial runs from the deadline (deadline + subMonth), not from login.
// Only students whose access_date is on/after ACTIVATION_GRACE_START are
// covered — older students keep the original "activate whenever" behaviour.
// Edit ACTIVATION_GRACE_START to the production upload date before deploying.
const ACTIVATION_GRACE_START  = '2026-11-05';
const ACTIVATION_GRACE_MONTHS = 2;

$graceDeadline = null; // DateTime when the student is covered by the grace rule
try {
    $gStmt = mysqli_prepare($con, "SELECT groupname, access_date, subMonth, dateexpired FROM login WHERE id = ?");
    if ($gStmt) {
        mysqli_stmt_bind_param($gStmt, 's', $userId);
        mysqli_stmt_execute($gStmt);
        $gRow = mysqli_fetch_assoc(mysqli_stmt_get_result($gStmt));
        mysqli_stmt_close($gStmt);

        if ($gRow
            && $gRow['dateexpired'] === null
            && stripos((string)$gRow['groupname'], 'FREE') !== false
            && !empty($gRow['access_date'])
            && substr($gRow['access_date'], 0, 10) >= ACTIVATION_GRACE_START) {
            $graceDeadline = new DateTime($gRow['access_date'], new DateTimeZone('Asia/Manila'));
            $graceDeadline->modify('+' . ACTIVATION_GRACE_MONTHS . ' months');
            if (!empty($gRow['subMonth'])) $subMonth = $gRow['subMonth'];
        }
    }
} catch (Throwable $e) {
    // access_date column missing (SQL 013a not run) or query failed → legacy behaviour
    $graceDeadline = null;
}

// Grace period over → activate automatically, trial counted from the deadline
if ($graceDeadline !== null && new DateTime("now", new DateTimeZone('Asia/Manila')) >= $graceDeadline) {
    $autoExpiry = (clone $graceDeadline)->modify("+" . intval($subMonth) . " months");
    $autoExpiryStr = $autoExpiry->format('Y-m-d H:i:s');

    $aStmt = mysqli_prepare($con, "UPDATE login SET dateexpired = ?, subMonth = NULL WHERE id = ? AND dateexpired IS NULL");
    mysqli_stmt_bind_param($aStmt, 'ss', $autoExpiryStr, $userId);
    mysqli_stmt_execute($aStmt);
    mysqli_stmt_close($aStmt);

    unset($_SESSION['pending_user_id'], $_SESSION['pending_fullname'], $_SESSION['pending_subMonth']);

    if (new DateTime("now", new DateTimeZone('Asia/Manila')) > $autoExpiry) {
        // The trial already ran out while the account sat inactive
        $_SESSION['error'] = "Your account has expired as of <b>" . $autoExpiry->format('F j, Y') . "</b>. Please contact support.";
        header('location: ../../');
        exit;
    }

    $_SESSION['user_id'] = $userId;
    header("location: profile.php");
    exit;
}

$graceDeadlineLabel = $graceDeadline ? $graceDeadline->format('F j, Y') : null;

// Handle activation
if (isset($_POST['activate'])) {
    $dateNow = new DateTime("now", new DateTimeZone('Asia/Manila'));
    $dateNow->modify("+{$subMonth} months");
    $dateExpired = $dateNow->format('Y-m-d H:i:s');

    // Update both dateexpired and set subMonth to NULL
    mysqli_query($con, "
        UPDATE login
        SET dateexpired = '$dateExpired', subMonth = NULL
        WHERE id = '$userId'
    ");

    $_SESSION['user_id'] = $userId; // Set as active user
    unset($_SESSION['pending_user_id'], $_SESSION['pending_fullname'], $_SESSION['pending_subMonth']);

    header("location: profile.php");
    exit;
}


// If user clicks Not Now
if (isset($_POST['not_now'])) {
    header("location: ../../");
    exit;
}

// Preview of the plan window shown on the card (same math as activation above)
$monthLabel  = ($subMonth == 1) ? "Month" : "Months";
$previewFrom = new DateTime("now", new DateTimeZone('Asia/Manila'));
$previewTo   = (clone $previewFrom)->modify("+{$subMonth} months");
$startLabel  = $previewFrom->format('M j, Y');
$endLabel    = $previewTo->format('M j, Y');
$firstName   = explode(' ', trim($fullname))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Activate Subscription — Studium</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --s-primary: #007cbf;
      --s-primary-dark: #005e91;
      --s-accent: #0d9488;
      --s-accent2: #0f766e;
      --s-navy: #0a1628;
      --s-text: #0f172a;
      --s-text-2: #334155;
      --s-muted: #64748b;
      --s-border: #e2e8f0;
      --s-bg: #f5f7fa;
      --s-warn: #b45309;
      --s-warn-bg: #fffbeb;
      --ease-out: cubic-bezier(.22, 1, .36, 1);
      --ease-spring: cubic-bezier(.34, 1.4, .64, 1);
    }

    *, *::before, *::after { box-sizing: border-box; }

    html, body { height: 100%; margin: 0; }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      color: var(--s-text);
      background: var(--s-bg);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }

    /* ── Animated backdrop: soft brand-colored blobs + faint dot grid ── */
    .bg {
      position: fixed;
      inset: 0;
      z-index: 0;
      overflow: hidden;
      pointer-events: none;
      background:
        radial-gradient(circle at 1px 1px, rgba(15, 23, 42, .06) 1px, transparent 0) 0 0 / 22px 22px,
        linear-gradient(180deg, #f8fbfd 0%, #eef4f8 100%);
    }
    .blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(60px);
      opacity: .55;
      animation: drift 18s ease-in-out infinite alternate;
    }
    .blob-1 { width: 420px; height: 420px; background: rgba(0, 124, 191, .35); top: -120px; left: -100px; }
    .blob-2 { width: 380px; height: 380px; background: rgba(13, 148, 136, .32); bottom: -140px; right: -80px; animation-delay: -6s; }
    .blob-3 { width: 260px; height: 260px; background: rgba(99, 102, 241, .18); top: 55%; left: 60%; animation-delay: -12s; }

    @keyframes drift {
      0%   { transform: translate(0, 0) scale(1); }
      50%  { transform: translate(40px, 30px) scale(1.08); }
      100% { transform: translate(-30px, 50px) scale(.95); }
    }

    /* ── Card ── */
    .wrap {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 460px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 22px;
    }

    .brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 18px;
      font-size: 1.15rem;
      font-weight: 800;
      letter-spacing: -.01em;
      color: var(--s-navy);
    }
    .brand img { width: 34px; height: 34px; object-fit: contain; }

    .plan-date-value, .plan-name { overflow-wrap: anywhere; }

    .card {
      width: 100%;
      background: rgba(255, 255, 255, .92);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(226, 232, 240, .9);
      border-radius: 22px;
      box-shadow:
        0 1px 2px rgba(15, 23, 42, .04),
        0 24px 60px -12px rgba(15, 23, 42, .18);
      padding: 34px 30px 28px;
      text-align: center;
      opacity: 0;
      animation: riseIn .7s var(--ease-out) .15s forwards;
    }

    @keyframes riseIn {
      from { opacity: 0; transform: translateY(24px) scale(.98); }
      to   { opacity: 1; transform: none; }
    }
    @keyframes fadeDown {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: none; }
    }
    @keyframes popIn {
      from { opacity: 0; transform: scale(.6); }
      to   { opacity: 1; transform: scale(1); }
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: none; }
    }

    /* Staggered entrance for card contents */
    .stagger > * { opacity: 0; animation: fadeUp .55s var(--ease-out) forwards; }
    .stagger > *:nth-child(1) { animation-delay: .35s; }
    .stagger > *:nth-child(2) { animation-delay: .43s; }
    .stagger > *:nth-child(3) { animation-delay: .51s; }
    .stagger > *:nth-child(4) { animation-delay: .59s; }
    .stagger > *:nth-child(5) { animation-delay: .67s; }
    .stagger > *:nth-child(6) { animation-delay: .75s; }

    .eyebrow {
      display: inline-block;
      font-size: .7rem;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--s-accent2);
      background: rgba(13, 148, 136, .1);
      padding: 4px 10px;
      border-radius: 999px;
      margin-bottom: 10px;
    }

    h1 {
      font-size: 1.6rem;
      line-height: 1.25;
      font-weight: 800;
      letter-spacing: -.02em;
      margin: 0 0 8px;
      color: var(--s-navy);
    }

    .lead {
      font-size: .95rem;
      line-height: 1.6;
      color: var(--s-muted);
      margin: 0 auto 22px;
      max-width: 340px;
    }
    .lead b { color: var(--s-text); font-weight: 600; }

    /* Plan summary panel */
    .plan {
      text-align: left;
      border: 1px solid var(--s-border);
      border-radius: 16px;
      overflow: hidden;
      margin-bottom: 24px;
      background: #fff;
    }
    .plan-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 14px 16px;
      background: linear-gradient(135deg, rgba(0, 124, 191, .07), rgba(13, 148, 136, .07));
      border-bottom: 1px solid var(--s-border);
    }
    .plan-name { font-weight: 700; font-size: .95rem; color: var(--s-navy); }
    .plan-name small { display: block; font-weight: 500; font-size: .75rem; color: var(--s-muted); margin-top: 2px; }
    .plan-badge {
      flex-shrink: 0;
      font-size: .72rem;
      font-weight: 700;
      color: var(--s-primary-dark);
      background: rgba(0, 124, 191, .12);
      padding: 5px 10px;
      border-radius: 999px;
      white-space: nowrap;
    }
    .plan-dates {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 10px;
      padding: 14px 16px;
    }
    .plan-date-label { font-size: .68rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--s-muted); }
    .plan-date-value { font-size: .95rem; font-weight: 700; color: var(--s-text); margin-top: 2px; font-variant-numeric: tabular-nums; }
    .plan-dates > div:last-child { text-align: right; }
    .plan-arrow { color: #94a3b8; font-size: 1rem; }

    /* Buttons */
    .actions { display: flex; flex-direction: column; gap: 10px; }

    .btn {
      appearance: none;
      border: 0;
      font: inherit;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 14px 18px;
      border-radius: 12px;
      font-size: .95rem;
      font-weight: 700;
      transition: transform .18s var(--ease-out), box-shadow .18s var(--ease-out), background .18s, color .18s, border-color .18s;
      -webkit-tap-highlight-color: transparent;
    }
    .btn:focus-visible { outline: 3px solid rgba(0, 124, 191, .35); outline-offset: 2px; }
    .btn:active { transform: scale(.98); }
    .btn[disabled] { cursor: not-allowed; opacity: .75; }

    .btn-primary {
      position: relative;
      overflow: hidden;
      color: #fff;
      background: linear-gradient(135deg, var(--s-primary) 0%, var(--s-accent) 100%);
      box-shadow: 0 10px 24px -10px rgba(0, 124, 191, .7);
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 14px 30px -10px rgba(0, 124, 191, .75); }
    /* light sweep on hover */
    .btn-primary::after {
      content: '';
      position: absolute;
      top: 0; bottom: 0; left: -60%;
      width: 40%;
      background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .35), transparent);
      transform: skewX(-20deg);
      transition: left .6s var(--ease-out);
    }
    .btn-primary:hover::after { left: 120%; }

    .btn-ghost {
      color: var(--s-text-2);
      background: transparent;
      border: 1.5px solid var(--s-border);
    }
    .btn-ghost:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--s-text); }

    .btn-danger-soft {
      color: var(--s-text-2);
      background: #f1f5f9;
    }
    .btn-danger-soft:hover { background: #e2e8f0; }

    .footnote {
      margin-top: 16px;
      font-size: .75rem;
      color: var(--s-muted);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .spinner {
      width: 16px; height: 16px;
      border-radius: 50%;
      border: 2px solid rgba(255, 255, 255, .45);
      border-top-color: #fff;
      animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Confirmation dialog (2 steps) ── */
    .overlay {
      position: fixed;
      inset: 0;
      z-index: 50;
      display: grid;
      place-items: center;
      padding: 16px;
      background: rgba(10, 22, 40, .45);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      opacity: 0;
      visibility: hidden;
      transition: opacity .25s ease, visibility 0s linear .25s;
    }
    .overlay.open { opacity: 1; visibility: visible; transition: opacity .25s ease; }

    .dialog {
      position: relative;
      width: 100%;
      max-width: 400px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 30px 80px -20px rgba(10, 22, 40, .45);
      padding: 28px 26px 22px;
      text-align: center;
      overflow: hidden;
      transform: translateY(16px) scale(.96);
      transition: transform .35s var(--ease-spring);
    }
    .overlay.open .dialog { transform: none; }

    .steps-dots { display: flex; justify-content: center; gap: 6px; margin-bottom: 18px; }
    .steps-dots span {
      width: 22px; height: 4px;
      border-radius: 4px;
      background: var(--s-border);
      transition: background .3s, width .3s var(--ease-out);
    }
    .steps-dots span.on { background: var(--s-accent); width: 34px; }

    .step { display: none; }
    .step.active { display: block; animation: stepIn .35s var(--ease-out); }
    .step.back   { animation-name: stepInBack; }
    @keyframes stepIn     { from { opacity: 0; transform: translateX(24px); }  to { opacity: 1; transform: none; } }
    @keyframes stepInBack { from { opacity: 0; transform: translateX(-24px); } to { opacity: 1; transform: none; } }

    .d-icon {
      width: 60px; height: 60px;
      margin: 0 auto 14px;
      border-radius: 18px;
      display: grid;
      place-items: center;
      font-size: 1.6rem;
      animation: popIn .45s var(--ease-spring);
    }
    .d-icon.info { color: var(--s-primary); background: rgba(0, 124, 191, .1); }
    .d-icon.warn { color: var(--s-warn); background: var(--s-warn-bg); }

    .dialog h2 { font-size: 1.2rem; font-weight: 800; letter-spacing: -.01em; margin: 0 0 8px; color: var(--s-navy); }
    .dialog p  { font-size: .9rem; line-height: 1.6; color: var(--s-muted); margin: 0 0 18px; }
    .dialog p b { color: var(--s-text); }

    .callout {
      display: flex;
      gap: 10px;
      text-align: left;
      font-size: .82rem;
      line-height: 1.5;
      color: #92400e;
      background: var(--s-warn-bg);
      border: 1px solid #fde68a;
      border-radius: 12px;
      padding: 10px 12px;
      margin-bottom: 18px;
    }
    .callout i { color: var(--s-warn); margin-top: 1px; }

    .dialog .actions { flex-direction: row-reverse; }
    .dialog .actions .btn { padding: 12px 16px; font-size: .9rem; }

    @media (max-width: 480px) {
      .card { padding: 28px 20px 22px; border-radius: 20px; }
      h1 { font-size: 1.4rem; }
      .dialog .actions { flex-direction: column; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
    }
  </style>
</head>
<body>
  <div class="bg" aria-hidden="true">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
  </div>

  <main class="wrap">
    <section class="card" aria-labelledby="actTitle">
      <div class="brand">
        <img src="../../assets/LOGO.svg" alt="" aria-hidden="true">
        <span>Studium</span>
      </div>

      <div class="stagger">
        <div><span class="eyebrow">Ready to start</span></div>
        <h1 id="actTitle">Activate your subscription</h1>
        <p class="lead">Hi <b><?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?></b>! Your Studium plan is ready. Activate it now to unlock your review materials.</p>

        <div class="plan">
          <div class="plan-head">
            <div class="plan-name">
              <?= intval($subMonth) . ' ' . $monthLabel ?> Studium Plan
              <small><?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') ?></small>
            </div>
            <span class="plan-badge"><i class="bi bi-hourglass-split"></i> <?= intval($subMonth) . ' ' . strtolower($monthLabel) ?></span>
          </div>
          <div class="plan-dates">
            <div>
              <div class="plan-date-label">Starts</div>
              <div class="plan-date-value">Today · <?= $startLabel ?></div>
            </div>
            <i class="bi bi-arrow-right plan-arrow" aria-hidden="true"></i>
            <div>
              <div class="plan-date-label">Ends</div>
              <div class="plan-date-value"><?= $endLabel ?></div>
            </div>
          </div>
        </div>

        <?php if ($graceDeadlineLabel): ?>
        <div class="callout"><i class="bi bi-info-circle-fill"></i><span><b>Note:</b> You have <?= ACTIVATION_GRACE_MONTHS ?> months from your access date (until <b><?= $graceDeadlineLabel ?></b>) to activate. If you haven't activated by then, your account will activate automatically and your <?= intval($subMonth) . ' ' . strtolower($monthLabel) ?> trial will start counting.</span></div>
        <?php endif; ?>

        <form method="post" id="activationForm" class="actions">
          <button type="button" id="activateBtn" class="btn btn-primary">
            <i class="bi bi-lightning-charge-fill"></i> Activate now
          </button>
          <button type="submit" name="not_now" class="btn btn-ghost">Not now</button>
        </form>
      </div>

      <?php if (!$graceDeadlineLabel): ?>
      <div class="footnote"><i class="bi bi-shield-check"></i> Your plan only starts counting once you activate.</div>
      <?php endif; ?>
    </section>
  </main>

  <!-- Two-step confirmation -->
  <div class="overlay" id="confirmOverlay" aria-hidden="true">
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="stepTitle1">
      <div class="steps-dots" aria-hidden="true"><span class="on"></span><span></span></div>

      <div class="step active" data-step="1">
        <div class="d-icon info"><i class="bi bi-calendar2-check-fill"></i></div>
        <h2 id="stepTitle1">Start your plan today?</h2>
        <p>Your <b><?= intval($subMonth) . ' ' . strtolower($monthLabel) ?></b> will begin <b>today</b> and end on <b><?= $endLabel ?></b>.</p>
        <?php if ($graceDeadlineLabel): ?>
        <div class="callout"><i class="bi bi-calendar-event-fill"></i><span>You can take your time deciding, but only until <b><?= $graceDeadlineLabel ?></b> (<?= ACTIVATION_GRACE_MONTHS ?> months from your access date). If you haven't activated by then, your account will activate automatically and your <?= intval($subMonth) . ' ' . strtolower($monthLabel) ?> will start counting even if you didn't activate it yourself.</span></div>
        <?php endif; ?>
        <div class="actions">
          <button type="button" class="btn btn-primary" data-next>Continue <i class="bi bi-arrow-right"></i></button>
          <button type="button" class="btn btn-danger-soft" data-cancel>Cancel</button>
        </div>
      </div>

      <div class="step" data-step="2">
        <div class="d-icon warn"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <h2>Final confirmation</h2>
        <p>Please confirm you're ready to activate your subscription.</p>
        <div class="callout"><i class="bi bi-info-circle-fill"></i><span>This can't be undone. Your subscription days start counting as soon as you activate.<?php if ($graceDeadlineLabel): ?> If you'd rather wait, you can still do it any time before <b><?= $graceDeadlineLabel ?></b>.<?php endif; ?></span></div>
        <div class="actions">
          <button type="button" class="btn btn-primary" id="finalActivate"><i class="bi bi-check2-circle"></i> Yes, activate</button>
          <button type="button" class="btn btn-danger-soft" data-back><i class="bi bi-arrow-left"></i> Back</button>
        </div>
      </div>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form     = document.getElementById('activationForm');
  const overlay  = document.getElementById('confirmOverlay');
  const steps    = overlay.querySelectorAll('.step');
  const dots     = overlay.querySelectorAll('.steps-dots span');
  const openBtn  = document.getElementById('activateBtn');
  const finalBtn = document.getElementById('finalActivate');
  let submitting = false;

  function showStep(n, goingBack) {
    steps.forEach(s => {
      const on = s.dataset.step === String(n);
      s.classList.toggle('active', on);
      s.classList.toggle('back', on && !!goingBack);
    });
    dots.forEach((d, i) => d.classList.toggle('on', i < n));
    const focusEl = overlay.querySelector('.step.active .btn-primary');
    if (focusEl) focusEl.focus();
  }

  function openDialog() {
    showStep(1);
    overlay.classList.add('open');
    overlay.setAttribute('aria-hidden', 'false');
    setTimeout(() => overlay.querySelector('.step.active .btn-primary').focus(), 50);
  }

  function closeDialog() {
    if (submitting) return;
    overlay.classList.remove('open');
    overlay.setAttribute('aria-hidden', 'true');
    openBtn.focus();
  }

  openBtn.addEventListener('click', openDialog);
  overlay.querySelector('[data-next]').addEventListener('click', () => showStep(2));
  overlay.querySelector('[data-back]').addEventListener('click', () => showStep(1, true));
  overlay.querySelector('[data-cancel]').addEventListener('click', closeDialog);
  overlay.addEventListener('click', e => { if (e.target === overlay) closeDialog(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.classList.contains('open')) closeDialog(); });

  finalBtn.addEventListener('click', function () {
    if (submitting) return;
    submitting = true;
    finalBtn.disabled = true;
    finalBtn.innerHTML = '<span class="spinner"></span> Activating…';
    overlay.querySelector('[data-back]').disabled = true;

    // Hidden input so PHP sees $_POST['activate']
    const hidden = document.createElement('input');
    hidden.type  = 'hidden';
    hidden.name  = 'activate';
    hidden.value = '1';
    form.appendChild(hidden);
    form.submit();
  });
});
</script>
</body>
</html>
