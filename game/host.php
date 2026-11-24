<?php
/**
 * Live Quiz — host screen (projector). Admin/IT login required.
 * Flow: load questions (paste SQL) → create game → lobby (PIN + QR) →
 *       question → reveal → leaderboard → … → podium → end (data wiped).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/lib.php';

if (!lq_is_host()) {
    if (isset($_GET['hk'])) { // expired/invalid key from the NCLEX dashboard
        http_response_code(401);
        echo '<!DOCTYPE html><meta charset="utf-8"><body style="font-family:sans-serif;padding:40px;text-align:center;color:#334155">'
           . '<h2>Live Quiz session expired</h2><p>Reload the Live Quiz page in your dashboard.</p></body>';
        exit;
    }
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$hostKey = lq_host_key_ok() ? lq_host_key() : '';
// Only Studium itself and the NCLEX Amplified dashboard may frame the host screen.
header("Content-Security-Policy: frame-ancestors 'self' https://nclexamplifiedreviewcenter.com https://*.nclexamplifiedreviewcenter.com");
header('Referrer-Policy: same-origin');
if (empty($_SESSION['lq_csrf'])) $_SESSION['lq_csrf'] = bin2hex(random_bytes(16));

$scheme  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$joinUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Live Quiz Host — Studium</title>
  <link rel="icon" href="../assets/LOGO.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/game.css?v=<?= LQ_VERSION ?>">
  <style>
    body { min-height: 100vh; display: flex; flex-direction: column; }
    .topbar { display: flex; align-items: center; gap: 12px; padding: 14px clamp(14px, 3vw, 32px); }
    .topbar .grow { flex: 1; }
    .topbar .pin-chip { font-weight: 900; font-size: 1.05rem; letter-spacing: .06em; }
    .icon-btn { width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--glass-line); background: var(--glass); color: #fff; font-size: 1rem; display: grid; place-items: center; }
    .icon-btn:hover { background: var(--glass-2); }
    main { flex: 1; display: flex; flex-direction: column; padding: 0 clamp(14px, 3vw, 32px) clamp(14px, 3vw, 28px); min-height: 0; }
    .screen { flex: 1; display: flex; flex-direction: column; gap: 18px; animation: lq-pop .5s var(--ease); }
    .screen.mid { justify-content: center; }

    /* Setup (Studium dashboard cards) */
    .setup { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 20px; align-items: start; }
    .setup .card { padding: 24px; }
    .setup h2 { margin: 0 0 4px; font-size: 1.3rem; font-weight: 800; color: var(--ink); letter-spacing: -.01em; }
    .setup p.sub { margin: 0 0 16px; color: var(--muted); font-weight: 500; }
    .q-list { display: grid; gap: 8px; max-height: 50vh; overflow: auto; padding-right: 4px; }
    .q-item { display: grid; grid-template-columns: 34px 1fr auto; gap: 12px; align-items: center; padding: 10px 12px; border-radius: 12px; background: var(--soft); border: 1px solid var(--line); }
    .q-item .n { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: var(--brand); color: #fff; font-weight: 800; }
    .q-item .t { font-weight: 600; font-size: .93rem; line-height: 1.35; overflow-wrap: anywhere; color: var(--ink); }
    .q-item .t small { display: block; color: var(--teal-dark); font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; font-weight: 800; }
    .q-item .s { color: var(--muted); font-weight: 700; font-size: .85rem; white-space: nowrap; }
    .empty { text-align: center; padding: 40px 16px; color: var(--muted); font-weight: 600; border: 2px dashed var(--line); border-radius: 14px; }
    textarea.paste { width: 100%; min-height: 230px; resize: vertical; border-radius: 14px; border: 1.5px solid var(--line); background: #0f172a; color: #e2e8f0; padding: 14px; font: 500 .84rem/1.5 ui-monospace, SFMono-Regular, Consolas, monospace; }
    textarea.paste:focus, input.title:focus { outline: 3px solid rgba(0,124,191,.35); border-color: var(--brand); }
    input.title { width: 100%; border-radius: 12px; border: 1.5px solid var(--line); background: #fff; color: var(--ink); padding: 12px 14px; font-weight: 700; margin-bottom: 14px; }
    .row { display: flex; gap: 10px; flex-wrap: wrap; }
    .errors { margin-top: 12px; padding: 12px 14px; border-radius: 12px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-weight: 600; font-size: .9rem; max-height: 220px; overflow: auto; }
    .errors li { margin: 3px 0; }
    .ok-msg { margin-top: 12px; padding: 12px 14px; border-radius: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; font-weight: 700; }

    /* Lobby */
    .lobby-head { display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: center; padding: clamp(16px, 2.5vw, 30px); }
    .join-at { font-weight: 600; color: var(--on-blue-2); }
    .join-at b { display: block; color: #fff; font-size: clamp(1.6rem, 4vw, 3.6rem); font-weight: 900; letter-spacing: -.01em; }
    .pin-box { text-align: center; }
    .pin-box small { display: block; font-weight: 800; color: var(--on-blue-2); letter-spacing: .16em; text-transform: uppercase; font-size: .82rem; }
    .pin-big { font-size: clamp(3rem, 8vw, 7rem); font-weight: 900; letter-spacing: .04em; line-height: 1; color: #fff; text-shadow: 0 6px 0 rgba(4,30,55,.35); font-variant-numeric: tabular-nums; }
    .qr { justify-self: end; background: #fff; border-radius: 18px; padding: 10px; width: clamp(140px, 17vw, 240px); aspect-ratio: 1; box-shadow: 0 16px 40px rgba(4,30,55,.4); }
    .qr svg { width: 100%; height: 100%; display: block; }
    /* Once players join, the PIN header shrinks so the player circles get the room */
    .lobby .lobby-head, .lobby .pin-big, .lobby .qr, .lobby .join-at b { transition: padding .45s var(--ease), font-size .45s var(--ease), width .45s var(--ease); }
    .lobby.compact .lobby-head { padding: 12px clamp(16px, 2vw, 26px); }
    .lobby.compact .pin-big { font-size: clamp(2.2rem, 4.6vw, 4.2rem); }
    .lobby.compact .qr { width: clamp(84px, 8vw, 130px); padding: 7px; border-radius: 12px; }
    .lobby.compact .join-at b { font-size: clamp(1.05rem, 1.8vw, 1.7rem); }
    .players.sizing .pl { transition: width .45s var(--ease); }
    .players.sizing .pl-av { transition: width .45s var(--ease), height .45s var(--ease), box-shadow .45s var(--ease); }
    .players.sizing .pl-name { transition: font-size .45s var(--ease), margin .45s var(--ease), max-width .45s var(--ease); }
    .lobby-body { flex: 1; display: flex; flex-direction: column; padding: 20px; min-height: 240px; }
    .lobby-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
    .count { font-weight: 800; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; }
    .count b { font-size: 2rem; color: var(--gold-2); }
    /* Lobby players: big avatar circles + name; all shrink together to fit the screen */
    .players { --s: 180px; display: flex; flex-wrap: wrap; justify-content: center; align-content: center; gap: var(--g, 22px); overflow-y: auto; overflow-x: hidden; padding: 14px 6px 6px; }
    .players:empty { display: none; }
    .pl { position: relative; width: var(--s); flex: 0 0 auto; will-change: transform; }
    .pl-in { animation: lq-pl-in .8s cubic-bezier(.22, 1.3, .36, 1) both; }
    .pl-bob { display: flex; flex-direction: column; align-items: center; }
    .pl-av { position: relative; width: var(--s); height: var(--s); border-radius: 50%; background: #fff; box-shadow: 0 0 0 calc(var(--s) * .055) #fff, 0 calc(var(--s) * .08) calc(var(--s) * .14) -4px rgba(4, 30, 55, .5); }
    .pl-av .av { width: 100%; height: 100%; font-size: calc(var(--s) * .34); box-shadow: none; }
    .pl-av::after { content: ''; position: absolute; inset: -6%; border-radius: 50%; border: calc(var(--s) * .04) solid var(--gold-2); opacity: 0; animation: lq-pl-burst .9s .15s ease-out both; pointer-events: none; }
    .pl-name { max-width: calc(var(--s) + var(--g, 20px) * .8); margin-top: calc(var(--s) * .09 + 4px); color: #fff; font-weight: 900; font-size: max(12px, calc(var(--s) * .16)); line-height: 1.15; letter-spacing: -.01em; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-shadow: 0 3px 0 rgba(4, 30, 55, .35); animation: lq-pl-name .5s .3s var(--spring) both; }
    .pl button { position: absolute; top: 2%; right: 2%; z-index: 2; display: grid; place-items: center; border: 0; background: var(--bad); color: #fff; width: 28px; height: 28px; border-radius: 50%; opacity: 0; transform: scale(.6); transition: opacity .15s, transform .2s var(--spring); box-shadow: 0 2px 8px rgba(0,0,0,.35); cursor: pointer; }
    .pl:hover button, .pl button:focus-visible { opacity: 1; transform: none; }
    .pl.out .pl-in { animation: lq-pl-out .35s ease-in both; }
    @keyframes lq-pl-in {
      0%   { opacity: 0; transform: translateY(-60%) scale(.1) rotate(-25deg); }
      45%  { opacity: 1; transform: translateY(6%) scale(1.18, .86) rotate(8deg); }
      65%  { transform: translateY(-4%) scale(.92, 1.08) rotate(-4deg); }
      82%  { transform: translateY(0) scale(1.04, .97) rotate(2deg); }
      100% { opacity: 1; transform: none; }
    }
    @keyframes lq-pl-burst { 0% { opacity: .9; transform: scale(.85); } 100% { opacity: 0; transform: scale(1.35); } }
    @keyframes lq-pl-name { from { opacity: 0; transform: translateY(14px) scale(.7); } to { opacity: 1; transform: none; } }
    @keyframes lq-pl-out { to { opacity: 0; transform: scale(.2) rotate(15deg); } }
    @media (prefers-reduced-motion: reduce) { .pl-in, .pl-name, .pl-av::after { animation: none !important; } }
    .waiting { flex: 1; display: grid; place-items: center; text-align: center; color: var(--on-blue-2); font-weight: 700; font-size: 1.15rem; }
    .waiting .dots i { display: inline-block; width: 10px; height: 10px; margin: 0 4px; border-radius: 50%; background: var(--gold-2); animation: lq-beat .6s ease-in-out infinite alternate; }
    .waiting .dots i:nth-child(2) { animation-delay: .2s; } .waiting .dots i:nth-child(3) { animation-delay: .4s; }

    /* Question / reveal */
    .q-head { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .q-head .grow { flex: 1; }
    .q-meta { display: grid; gap: 8px; }
    .q-meta-top { display: flex; gap: 8px; flex-wrap: wrap; }
    .q-dots { display: flex; gap: 6px; flex-wrap: wrap; max-width: 60vw; }
    .q-dots i { width: 22px; height: 6px; border-radius: 999px; background: rgba(255,255,255,.22); }
    .q-dots i.done { background: var(--teal); }
    .q-dots i.now { background: var(--gold-2); width: 34px; }
    .ans-bar { display: block; width: 120px; height: 6px; margin: 6px auto 0; border-radius: 999px; background: rgba(255,255,255,.2); overflow: hidden; }
    .ans-bar span { display: block; height: 100%; background: var(--gold-2); transition: width .5s var(--ease); }
    .answered { text-align: center; font-weight: 800; }
    .answered b { display: block; font-size: 2.2rem; color: #fff; line-height: 1; }
    .answered small { color: var(--on-blue-2); }
    .reveal-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, .45fr); gap: 18px; align-items: start; }
    .reveal-side { display: grid; gap: 14px; }
    .reveal-side .panel { padding: 18px; }
    .reveal-side h3 { margin: 0 0 10px; font-size: .9rem; letter-spacing: .1em; text-transform: uppercase; color: var(--on-blue-2); }
    .big-pct { font-size: 3rem; font-weight: 900; line-height: 1; margin-bottom: 10px; }
    .controls { display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap; }

    /* Leaderboard / podium */
    .center-title { text-align: center; margin: 0 0 6px; font-size: clamp(1.7rem, 3.4vw, 3rem); font-weight: 900; letter-spacing: -.02em; }
    .center-title small { display: block; font-size: .38em; color: var(--gold-2); letter-spacing: .2em; text-transform: uppercase; margin-bottom: 6px; }
    .lb-wrap { width: min(900px, 100%); margin: 0 auto; }
    .rest { width: min(720px, 100%); margin: 26px auto 0; display: grid; gap: 8px; opacity: 0; transition: opacity .6s; }
    .rest.show { opacity: 1; }
    .rest .lb-row { font-size: 1rem; padding: 6px 18px 6px 10px; }
    .final-actions { opacity: 0; transition: opacity .6s; }
    .final-actions.show { opacity: 1; }

    .modal { position: fixed; inset: 0; z-index: 60; display: grid; place-items: center; padding: 16px; background: rgba(6,30,55,.6); backdrop-filter: blur(6px); }
    .modal .box { width: min(820px, 100%); max-height: 90vh; overflow: auto; background: #fff; color: var(--ink); border-radius: 20px; padding: 22px; box-shadow: 0 30px 80px rgba(0,0,0,.4); }
    .modal pre { white-space: pre-wrap; background: #0f172a; border-radius: 12px; padding: 14px; font-size: .82rem; line-height: 1.5; color: #dbeafe; max-height: 55vh; overflow: auto; }

    /* Auto-advance: the button fills up as the countdown runs; clicking skips ahead */
    .auto-toggle { border: 1px solid var(--glass-line); cursor: pointer; }
    .auto-toggle.off { background: rgba(0,0,0,.2); color: var(--on-blue-3); }
    .btn-auto { position: relative; overflow: hidden; isolation: isolate; }
    .btn-auto::before { content: ''; position: absolute; inset: 0; z-index: -1; width: var(--p, 0%); background: rgba(255,255,255,.45); transition: width .25s linear; }

    /* Inside the admin panel (admin/live_quiz.php): the admin sidebar already shows the brand */
    .embed .topbar { padding: 10px 14px; }
    .embed .topbar .lq-brand { display: none; }
    .embed main { padding: 0 14px 14px; }

    @media (min-width: 1800px) { html { font-size: 20px; } .timer { --size: 130px !important; } }
    @media (min-width: 2300px) { html { font-size: 25px; } .timer { --size: 160px !important; } }
    @media (max-width: 900px) {
      .setup, .reveal-grid { grid-template-columns: 1fr; }
      .lobby-head { grid-template-columns: 1fr; text-align: center; }
      .qr { justify-self: center; }
      .join-at b { overflow-wrap: anywhere; }
    }
    @media (max-width: 600px) {
      .topbar { flex-wrap: wrap; gap: 8px; }
      .topbar .grow { display: none; }
      .topbar .btn { padding: 8px 14px; font-size: .9rem; }
      .join-at b { font-size: 1.15rem; }
    }
  </style>
</head>
<body<?= !empty($_GET['embed']) ? ' class="embed"' : '' ?>>
  <div class="lq-bg" aria-hidden="true"><span class="orb a"></span><span class="orb b"></span></div>

  <header class="topbar">
    <a class="lq-brand" <?= $hostKey !== '' ? '' : 'href="../' . ((($_SESSION['admin_home'] ?? '') === 'it') ? 'it' : 'admin') . '/" title="Back to the admin panel"' ?>>
      <span class="mark"><img src="../assets/LOGO.svg" alt=""></span>
      <span><b>Studium</b><small>Live Quiz</small></span>
    </a>
    <div class="grow"></div>
    <span class="pill" id="playerChip" hidden>0 players</span>
    <button class="pill auto-toggle" id="autoBtn" title="Move on automatically after the answer and the leaderboard">Auto</button>
    <button class="icon-btn" id="soundBtn" title="Sound on/off" aria-label="Toggle sound"></button>
    <button class="icon-btn" id="fsBtn" title="Fullscreen" aria-label="Toggle fullscreen"></button>
    <button class="btn btn-danger" id="endBtn" hidden>End game</button>
  </header>

  <main id="app"><div class="waiting">Loading…</div></main>

  <!-- ChatGPT prompt -->
  <div class="modal" id="promptModal" hidden>
    <div class="box">
      <h2 style="margin:0 0 6px;font-weight:800">ChatGPT prompt</h2>
      <p style="margin:0 0 12px;color:var(--muted);font-weight:500">Copy this into ChatGPT, change the topic and number of questions, then paste its SQL answer back here.</p>
      <pre id="promptText"></pre>
      <div class="row" style="justify-content:flex-end;margin-top:14px">
        <button class="btn btn-light" data-close>Close</button>
        <button class="btn btn-go" id="copyPrompt">Copy prompt</button>
      </div>
    </div>
  </div>

  <template id="promptTpl">You are an NCLEX question writer for NCLEX Amplified Review Center. Write [10] NCLEX-style questions about [TOPIC] for a live classroom quiz game. Mix these NCSBN item types: mc, sata, mmr, mpr, highlight, dragndrop, dropdown, bowtie.

Return ONLY one MySQL statement, no explanation, in exactly this shape:

INSERT INTO live_quiz_questions (q_order, q_type, scenario, question, options, answer, rationale, time_limit) VALUES
(1, 'mc', NULL, 'Question text?', '{"choices":{"A":"...","B":"...","C":"...","D":"..."}}', '"B"', 'Why B is correct.', 30),
(2, 'sata', 'Short client scenario.', 'Which actions should the nurse take? Select all that apply.', '{"choices":{"A":"...","B":"...","C":"...","D":"...","E":"..."}}', '["A","C","E"]', 'Rationale.', 45);

Rules:
- Text values use single quotes. Write an apostrophe inside text as two single quotes ('').
- Never use double quotes (") inside question, choice or rationale text.
- options and answer are JSON written inside single quotes.
- time_limit is seconds: 20–30 for mc, 40–60 for sata/highlight/dropdown/dragndrop, 60–90 for mmr/mpr/bowtie.
- scenario is a short client case (vital signs, findings) or NULL.
- question is a short instruction or question. NEVER put {{1}} placeholders in question; they belong only inside options.text.

JSON format for each q_type:
- mc:        options {"choices":{"A":"..","B":"..","C":"..","D":".."}}   answer "B"
- sata:      options {"choices":{"A":"..",…,"E":".."}} (5–8 choices)      answer ["A","C","E"]
- mmr:       options {"columns":["Pneumonia","Asthma","COPD"],"rows":{"r1":"finding 1","r2":"finding 2","r3":"finding 3","r4":"finding 4"}}
             answer {"r1":["Pneumonia"],"r2":["Asthma","COPD"],"r3":["COPD"],"r4":["Pneumonia","Asthma"]}  (each row: one or more columns)
- mpr:       options {"columns":["Indicated","Not Indicated"],"rows":{"r1":"action 1","r2":"action 2","r3":"action 3","r4":"action 4"}}
             answer {"r1":"Indicated","r2":"Not Indicated","r3":"Indicated","r4":"Not Indicated"}  (each row: exactly one column)
- highlight: options {"text":"The client reports {{h1}} since this morning. Vital signs: {{h2}}, {{h3}}, {{h4}}. The client also has {{h5}}.","items":{"h1":"a productive cough","h2":"Temp 38.9 C","h3":"HR 118","h4":"BP 128/76","h5":"clear lung sounds on the left"}}
             answer ["h1","h2","h3"]  (the findings that require follow-up; every {{key}} in text must be an item)
- dropdown:  question "Complete the sentence."  options {"text":"The client is most likely experiencing {{1}} as evidenced by {{2}}.","blanks":{"1":["DKA","HHS","Hypoglycemia"],"2":["Kussmaul respirations","Bradycardia","Hypertension"]}}
             answer {"1":"DKA","2":"Kussmaul respirations"}
- dragndrop: question "Drag the actions into the correct order."  options {"text":"The nurse should first {{1}} and then {{2}}.","tokens":["elevate the head of the bed","apply oxygen","call the provider","document findings"]}
             answer {"1":"elevate the head of the bed","2":"apply oxygen"}  (each token used once; add 1–2 extra wrong tokens)
- bowtie:    options {"conditions":{"c1":"..","c2":"..","c3":".."},"actions":{"a1":"..","a2":"..","a3":"..","a4":".."},"parameters":{"p1":"..","p2":"..","p3":"..","p4":".."}}
             answer {"condition":"c2","actions":["a1","a3"],"parameters":["p2","p4"]}  (exactly 1 condition, 2 actions, 2 parameters)</template>

  <script>
    window.LQ_HOST = {
      csrf: <?= json_encode($_SESSION['lq_csrf']) ?>,
      hk: <?= json_encode($hostKey) ?>,
      v: <?= json_encode(LQ_VERSION) ?>,
      joinUrl: <?= json_encode($joinUrl) ?>
    };
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
  <script src="assets/render.js?v=<?= LQ_VERSION ?>"></script>
  <script src="assets/music.js?v=<?= LQ_VERSION ?>"></script>
  <script src="assets/host.js?v=<?= LQ_VERSION ?>"></script>
</body>
</html>
