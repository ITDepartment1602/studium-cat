<?php
/**
 * Live Quiz — player page (students' phones). No login: players join with the
 * game PIN (pre-filled from the QR link ?pin=) and a display name.
 */
require_once __DIR__ . '/lib.php';
$pin = preg_replace('/\D/', '', $_GET['pin'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#005e91">
  <title>Join Live Quiz — Studium</title>
  <link rel="icon" href="../assets/LOGO.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/game.css?v=<?= LQ_VERSION ?>">
  <style>
    body { min-height: 100dvh; display: flex; flex-direction: column; }
    .bar { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 16px; padding-top: max(12px, env(safe-area-inset-top)); }
    .bar .lq-brand .mark { width: 36px; height: 36px; border-radius: 10px; }
    .bar .lq-brand .mark img { width: 26px; height: 26px; }
    .me { display: flex; align-items: center; gap: 8px; text-align: right; font-weight: 800; line-height: 1.15; }
    .me .av { font-size: .78rem; box-shadow: 0 0 0 2px #fff; }
    .me small { display: block; color: var(--gold-2); font-size: .76rem; }
    main { flex: 1; display: flex; flex-direction: column; width: min(760px, 100%); margin: 0 auto; padding: 8px 16px calc(24px + env(safe-area-inset-bottom)); }
    .view { flex: 1; display: flex; flex-direction: column; gap: 14px; animation: lq-pop .45s var(--ease); }
    .center { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 14px; }

    /* Join */
    .join-card { width: min(420px, 100%); padding: 28px 22px; text-align: center; }
    .join-card .logo { width: 76px; height: 76px; margin: 0 auto; border-radius: 22px; background: linear-gradient(135deg, #eef6fc, #e6f7f5); display: grid; place-items: center; animation: lq-pop .6s var(--spring); }
    .join-card .logo img { width: 52px; height: 52px; }
    .join-card h1 { margin: 14px 0 4px; font-size: 1.55rem; font-weight: 900; color: var(--ink); letter-spacing: -.02em; }
    .join-card p { margin: 0 0 18px; color: var(--muted); font-weight: 500; }
    .big-input { width: 100%; border: 2px solid var(--line); border-radius: 14px; padding: 15px; text-align: center; font-size: 1.6rem; font-weight: 900; letter-spacing: .12em; color: var(--ink); background: var(--soft); }
    .big-input.name { letter-spacing: 0; font-size: 1.3rem; }
    .big-input:focus { outline: none; border-color: var(--brand); background: #fff; box-shadow: 0 0 0 4px rgba(0,124,191,.15); }
    .err { color: #b91c1c; font-weight: 700; min-height: 1.4em; margin-top: 10px; }

    /* Waiting / center states */
    .pulse { width: 120px; height: 120px; border-radius: 50%; display: grid; place-items: center; font-size: 3rem; background: radial-gradient(circle, rgba(255,212,92,.35), rgba(255,212,92,.05) 70%); animation: lq-breathe 2s ease-in-out infinite; }
    @keyframes lq-breathe { 50% { transform: scale(1.08); box-shadow: 0 0 0 18px rgba(255,212,92,.06); } }
    .center h2 { margin: 0; font-size: clamp(1.5rem, 6vw, 2.1rem); font-weight: 900; letter-spacing: -.02em; }
    .center p { margin: 0; color: var(--on-blue-2); font-weight: 600; font-size: 1.02rem; }
    .name-tag { display: inline-flex; align-items: center; gap: 10px; padding: 6px 18px 6px 6px; border-radius: 999px; background: #fff; color: var(--ink); font-weight: 800; font-size: 1.15rem; }
    .name-tag .av { box-shadow: none; }

    /* Question */
    .q-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .q-top .grow { flex: 1; }
    .tbar { height: 10px; border-radius: 999px; background: rgba(255,255,255,.2); overflow: hidden; }
    .tbar span { display: block; height: 100%; width: 100%; background: linear-gradient(90deg, var(--gold), var(--gold-2)); transform-origin: left; }
    .tbar.low span { background: #ff6b6b; }
    .secs { font-weight: 900; font-variant-numeric: tabular-nums; min-width: 3ch; text-align: right; font-size: 1.15rem; }
    .q-card .q-text { font-size: clamp(1.08rem, 4.5vw, 1.45rem); }
    .q-card .q-scenario { font-size: .93rem; }
    .submit-wrap { position: sticky; bottom: 0; padding: 12px 0 calc(4px + env(safe-area-inset-bottom)); background: linear-gradient(180deg, transparent, rgba(6,62,99,.95) 40%); }
    .wait-bar { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 16px; background: #fff; color: var(--ink); font-weight: 800; box-shadow: 0 14px 30px -12px rgba(4,30,55,.6); animation: lq-pop .4s var(--spring); }
    .wait-bar .spin { width: 22px; height: 22px; border-radius: 50%; border: 3px solid #cbe4f3; border-top-color: var(--brand); animation: lq-spin .8s linear infinite; flex-shrink: 0; }
    .wait-bar small { display: block; color: var(--muted); font-weight: 600; font-size: .85rem; }
    @keyframes lq-spin { to { transform: rotate(360deg); } }

    /* Result */
    .result { border-radius: 22px; padding: 22px 18px; text-align: center; animation: lq-pop .55s var(--spring); box-shadow: 0 18px 36px -16px rgba(0,0,0,.45); }
    .result .ico { font-size: 3.4rem; line-height: 1; }
    .result h2 { margin: 6px 0 2px; font-size: 1.9rem; font-weight: 900; letter-spacing: -.02em; }
    .result .pts { font-size: 1.4rem; font-weight: 900; }
    .result.good { background: linear-gradient(160deg, #16c79a, #0d9488); }
    .result.part { background: linear-gradient(160deg, #fbbf24, #d97706); color: #3b2300; }
    .result.bad { background: linear-gradient(160deg, #f87171, #dc2626); }
    .result.none { background: linear-gradient(160deg, #64748b, #334155); }
    .stand { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; }
    .stand .pill { font-size: .92rem; padding: 8px 14px; }
    .rank-big { font-size: clamp(4rem, 22vw, 7rem); font-weight: 900; line-height: 1; color: #fff; text-shadow: 0 6px 0 rgba(4,30,55,.35); }
    .section-title { font-weight: 800; color: var(--on-blue-2); letter-spacing: .1em; text-transform: uppercase; font-size: .78rem; margin: 6px 0 -4px; }
    .lb-row { font-size: 1rem; padding: 6px 16px 6px 8px; }
  </style>
</head>
<body>
  <div class="lq-bg" aria-hidden="true"></div>
  <header class="bar">
    <span class="lq-brand"><span class="mark"><img src="../assets/LOGO.svg" alt=""></span><span><b>Studium</b><small>Live Quiz</small></span></span>
    <span class="me" id="me" hidden></span>
  </header>
  <main id="app"></main>
  <script>window.LQ_PIN = <?= json_encode($pin) ?>; window.LQ_V = <?= json_encode(LQ_VERSION) ?>;</script>
  <script src="assets/render.js?v=<?= LQ_VERSION ?>"></script>
  <script src="assets/player.js?v=<?= LQ_VERSION ?>"></script>
</body>
</html>
