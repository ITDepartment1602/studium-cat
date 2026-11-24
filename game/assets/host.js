/* Live Quiz — host screen controller (projector).
 * Polls api.php?a=host_state every second and redraws only when the phase or
 * question changes; live bits (player list, answered count, timer) update in place.
 */
(function () {
  'use strict';
  const CFG = window.LQ_HOST;
  const app = document.getElementById('app');
  LQ.autoFit(app);
  const esc = LQ.esc;
  const $ = (sel, root) => (root || document).querySelector(sel);

  let state = null;
  let viewKey = '';
  let clockOffset = 0;      // server ms − local ms
  let timerRaf = 0;
  let busy = false;
  let lastTick = -1;
  let knownPlayers = new Set();
  let timers = [];          // pending setTimeouts for the current screen

  const later = (fn, ms) => timers.push(setTimeout(fn, ms));
  const serverNow = () => Date.now() + clockOffset;

  /* ── API ── */
  async function api(action, body) {
    const headers = CFG.hk ? { 'X-LQ-Key': CFG.hk } : {};
    const opts = body === undefined ? { headers } : {
      method: 'POST',
      headers: Object.assign(headers, { 'Content-Type': 'application/json', 'X-CSRF-Token': CFG.csrf }),
      body: JSON.stringify(body),
    };
    const res = await fetch('api.php?a=' + action, Object.assign({ credentials: 'same-origin', cache: 'no-store' }, opts));
    const data = await res.json().catch(() => ({ ok: false, error: 'The server sent an invalid reply.' }));
    if (res.status === 401) {
      if (CFG.hk) location.reload(); // key expired: host.php shows the "reload your dashboard" notice
      else location.href = '../index.php';
    }
    return data;
  }

  async function act(action, body) {
    if (busy) return;
    busy = true;
    let r;
    try {
      r = await api(action, body || {});
      if (!r.ok) toast(r.error || (r.errors && r.errors[0]) || 'Something went wrong.', true);
    } finally { busy = false; }
    await poll();
    return r;
  }

  /* ── Sound (WebAudio, no files) ── */
  let soundOn = window.LQMusic ? LQMusic.isOn() : localStorage.getItem('lq_sound') !== '0';
  let actx = null;
  function beep(freq, dur, type, vol, when) {
    if (!soundOn) return;
    try {
      actx = actx || new (window.AudioContext || window.webkitAudioContext)();
      const t = actx.currentTime + (when || 0);
      const o = actx.createOscillator(), g = actx.createGain();
      o.type = type || 'sine'; o.frequency.value = freq;
      g.gain.setValueAtTime(vol || .12, t);
      g.gain.exponentialRampToValueAtTime(.0001, t + dur);
      o.connect(g); g.connect(actx.destination);
      o.start(t); o.stop(t + dur);
    } catch (e) { /* audio blocked until the first click */ }
  }
  const sfx = {
    join: () => { beep(660, .12, 'triangle', .1); beep(990, .14, 'triangle', .08, .08); },
    tick: () => beep(880, .06, 'square', .05),
    count: () => beep(523, .12, 'triangle', .12),
    go: () => { beep(523, .12, 'triangle'); beep(659, .12, 'triangle', .12, .12); beep(784, .25, 'triangle', .12, .24); },
    reveal: () => { beep(392, .15, 'sine', .14); beep(523, .15, 'sine', .14, .15); beep(659, .3, 'sine', .14, .3); },
    whoosh: () => { beep(300, .2, 'sawtooth', .04); beep(600, .25, 'sine', .06, .05); },
    drum: () => { for (let i = 0; i < 14; i++) beep(110 + (i % 2) * 20, .08, 'triangle', .07, i * .09); },
    ta: () => { beep(784, .18, 'triangle', .14); beep(1047, .35, 'triangle', .14, .16); },
    fanfare: () => [523, 659, 784, 1047, 784, 1047].forEach((f, i) => beep(f, .22, 'triangle', .13, i * .16)),
  };
  const soundBtn = $('#soundBtn');
  const paintSound = () => { soundBtn.innerHTML = LQ.icon(soundOn ? 'volume' : 'mute'); };
  soundBtn.title = 'Music & sound on/off';
  soundBtn.addEventListener('click', () => {
    soundOn = !soundOn;
    if (window.LQMusic) LQMusic.setOn(soundOn); else localStorage.setItem('lq_sound', soundOn ? '1' : '0');
    paintSound();
    if (soundOn) sfx.join();
  });
  paintSound();
  $('#fsBtn').innerHTML = LQ.icon('expand');

  $('#fsBtn').addEventListener('click', () => {
    if (document.fullscreenElement) document.exitFullscreen();
    else document.documentElement.requestFullscreen().catch(() => {});
  });
  $('#endBtn').addEventListener('click', () => {
    if (window.confirm('End the game? All players and scores will be cleared.')) act('end');
  });

  /* ── Auto-advance: answer + rationale → leaderboard → next question, hands-free ── */
  let autoOn = localStorage.getItem('lq_auto') !== '0';
  const autoBtn = $('#autoBtn');
  const paintAuto = () => { autoBtn.innerHTML = 'Auto ' + LQ.icon(autoOn ? 'play' : 'pause'); autoBtn.classList.toggle('off', !autoOn); };
  autoBtn.addEventListener('click', () => {
    autoOn = !autoOn;
    localStorage.setItem('lq_auto', autoOn ? '1' : '0');
    paintAuto();
    viewKey = '';            // redraw so the current screen starts/stops its countdown
    if (state) render();
  });
  paintAuto();

  // How long each screen stays up before moving on (ms)
  const REVEAL_MS = 7000;        // answer + rationale
  const LEADERBOARD_MS = 7000;

  /** Count down on `btn` from the phase's server start, then run `action`. Clicking skips ahead. */
  function autoAdvance(btn, phaseStart, ms, action, label) {
    btn.addEventListener('click', () => act(action));
    if (!autoOn) return;
    btn.classList.add('btn-auto');
    const due = phaseStart + ms;
    const step = () => {
      const left = due - serverNow();
      // Retry every 2s until the screen changes (the server ignores repeats once it has moved on)
      if (left <= 0) { btn.innerHTML = label; act(action); later(step, 2000); return; }
      btn.innerHTML = label + ' · ' + Math.ceil(left / 1000) + 's';
      btn.style.setProperty('--p', Math.min(100, (1 - left / ms) * 100) + '%');
      later(step, 250);
    };
    step();
  }

  function toast(msg, err) {
    const t = document.createElement('div');
    t.className = 'toast' + (err ? ' err' : '');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }

  /* ── Polling loop ── */
  async function poll() {
    let s;
    try { s = await api('host_state'); } catch (e) { return; }
    if (!s || !s.ok) return;
    // A newer version was deployed while this screen was open: reload (the game state lives on the server)
    if (s.v && CFG.v && s.v !== CFG.v) { location.reload(); return; }
    clockOffset = s.now - Date.now();
    state = s;
    render();
  }
  setInterval(() => { if (!busy) poll(); }, 1000);
  poll();

  function render() {
    const s = state;
    const inGame = s.phase !== 'setup';
    $('#playerChip').hidden = !inGame;
    $('#endBtn').hidden = !inGame;
    if (inGame) {
      $('#playerChip').textContent = s.players + (s.players === 1 ? ' player' : ' players');
    }

    const key = s.phase + ':' + (s.q_index ?? '');
    if (key !== viewKey) {
      viewKey = key;
      cancelAnimationFrame(timerRaf);
      timers.forEach(clearTimeout); timers = [];
      document.querySelectorAll('.ready').forEach(el => el.remove());
      ({ setup: drawSetup, lobby: drawLobby, question: drawQuestion, reveal: drawReveal, leaderboard: drawLeaderboard, podium: drawPodium }[s.phase] || drawSetup)(s);
    } else {
      update(s);
    }
  }

  function update(s) {
    if (s.phase === 'lobby') paintPlayers(s.player_list || []);
    if (s.phase === 'question') {
      const a = $('#answeredNum'); if (a) a.textContent = s.answered;
      const b = $('#answeredOf'); if (b) b.textContent = 'of ' + s.players + ' answered';
      const f = $('#answeredFill'); if (f) f.style.width = (s.players ? Math.round(s.answered / s.players * 100) : 0) + '%';
    }
    if (s.phase === 'setup') paintQuestionList(s.questions || []);
  }

  /* ═════════ Setup ═════════ */
  function drawSetup(s) {
    app.innerHTML = `
      <section class="screen">
        <div class="setup">
          <div class="card">
            <h2>Questions for this game</h2>
            <p class="sub" id="qCountLine"></p>
            <div class="q-list" id="qList"></div>
            <div style="margin-top:18px">
              <input class="title" id="gameTitle" maxlength="150" placeholder="Game title (e.g. Break-time Pharma Quiz)" value="Studium Live Quiz">
              <button class="btn btn-go btn-lg btn-block" id="createBtn">${LQ.icon('play')} Create game</button>
            </div>
          </div>
          <div class="card">
            <h2>Load new questions</h2>
            <p class="sub">Paste the SQL from ChatGPT. It replaces the current questions.</p>
            <textarea class="paste" id="pasteBox" spellcheck="false" placeholder="INSERT INTO live_quiz_questions (q_order, q_type, scenario, question, options, answer, rationale, time_limit) VALUES&#10;(1, 'mc', NULL, '...', '{&quot;choices&quot;:{...}}', '&quot;B&quot;', '...', 30);"></textarea>
            <div class="row" style="margin-top:12px">
              <button class="btn btn-blue" id="importBtn">${LQ.icon('check')} Check &amp; load questions</button>
              <button class="btn btn-light" id="promptBtn">${LQ.icon('clipboard')} ChatGPT prompt</button>
            </div>
            <div id="importMsg"></div>
          </div>
        </div>
      </section>`;
    paintQuestionList(s.questions || []);

    $('#createBtn').addEventListener('click', () => act('create', { title: $('#gameTitle').value }));
    $('#importBtn').addEventListener('click', async () => {
      const msg = $('#importMsg');
      msg.innerHTML = '';
      busy = true;
      try {
        const r = await api('import', { text: $('#pasteBox').value });
        if (r.ok) {
          msg.innerHTML = '<div class="ok-msg">' + LQ.icon('check') + ' Loaded ' + r.count + ' question' + (r.count === 1 ? '' : 's') + '. Ready to create the game.</div>';
          $('#pasteBox').value = '';
          paintQuestionList(r.questions || []);
        } else {
          const errs = r.errors || [r.error || 'Could not load the questions.'];
          msg.innerHTML = '<div class="errors"><b>Nothing was loaded. Fix these and paste again:</b><ul>' + errs.map(e => '<li>' + esc(e) + '</li>').join('') + '</ul></div>';
        }
      } finally { busy = false; }
    });
    $('#promptBtn').addEventListener('click', () => {
      $('#promptText').textContent = $('#promptTpl').innerHTML.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
      $('#promptModal').hidden = false;
    });
  }

  function paintQuestionList(qs) {
    const list = $('#qList'); if (!list) return;
    const sig = JSON.stringify(qs);
    if (list.dataset.sig === sig) return;
    list.dataset.sig = sig;
    $('#createBtn').disabled = qs.length === 0;
    if (!qs.length) {
      $('#qCountLine').textContent = 'No questions loaded yet.';
      list.innerHTML = '<div class="empty">Paste ChatGPT SQL on the right to load questions.</div>';
      return;
    }
    const secs = qs.reduce((a, q) => a + q.time_limit, 0);
    $('#qCountLine').textContent = qs.length + ' questions · about ' + Math.ceil((secs + qs.length * 25) / 60) + ' minutes';
    list.innerHTML = qs.map((q, i) => `
      <div class="q-item"><span class="n">${i + 1}</span>
        <span class="t"><small>${esc(q.type_label)}</small>${esc(LQ.cleanText(q.question))}</span>
        <span class="s">${LQ.icon('clock')} ${q.time_limit}s</span></div>`).join('');
  }

  $('#promptModal').addEventListener('click', e => { if (e.target.id === 'promptModal' || e.target.hasAttribute('data-close')) $('#promptModal').hidden = true; });
  $('#copyPrompt').addEventListener('click', async () => {
    try { await navigator.clipboard.writeText($('#promptText').textContent); toast('Prompt copied — paste it into ChatGPT.'); }
    catch (e) { toast('Select the text and copy it manually.', true); }
  });

  /* ═════════ Lobby ═════════ */
  function drawLobby(s) {
    const url = CFG.joinUrl + '?pin=' + s.pin;
    knownPlayers = new Set();
    app.innerHTML = `
      <section class="screen lobby">
        <div class="panel lobby-head">
          <div class="join-at"><b>Scan the QR code</b><span style="display:block;margin-top:6px">with your phone camera to join the game</span></div>
          <div class="qr" id="qr" aria-label="QR code to join"></div>
        </div>
        <div class="panel lobby-body">
          <div class="lobby-bar">
            <div class="count"><b id="pCount">0</b><span><span id="pWord">players</span> joined</span></div>
            <div class="row" style="align-items:center">
              <span class="pill gold">${esc(s.title)} · ${s.q_total} questions</span>
              <button class="btn btn-go btn-lg" id="startBtn" disabled>Start game ${LQ.icon('play')}</button>
            </div>
          </div>
          <div class="players" id="players"></div>
          <div class="waiting" id="waiting"><div><div class="dots"><i></i><i></i><i></i></div><p>Waiting for players…</p></div></div>
        </div>
      </section>`;
    try {
      const qr = qrcode(0, 'M');
      qr.addData(url);
      qr.make();
      $('#qr').innerHTML = qr.createSvgTag({ cellSize: 6, margin: 0, scalable: true });
    } catch (e) { $('#qr').textContent = url; }
    $('#startBtn').addEventListener('click', () => act('next'));
    // Re-fit the player circles whenever the PIN header changes height (it shrinks once players join)
    if (window.ResizeObserver) {
      let t = 0;
      new ResizeObserver(() => {
        clearTimeout(t);
        t = setTimeout(() => { const b = $('#players'); if (b) refitPlayers(b); }, 120);
      }).observe($('.lobby-head'));
    }
    paintPlayers(s.player_list || []);
  }

  /* Pick the biggest circle size at which every player fits the free space below the bar */
  function fitPlayers(box, n) {
    if (!n) { box.style.height = ''; return; }
    const W = box.clientWidth - 12;
    const H = Math.max(180, window.innerHeight - box.getBoundingClientRect().top - 50);
    const maxS = Math.min(230, W * 0.7), minS = 54;
    let s = maxS, g = 0;
    for (; s > minS; s -= 2) {
      g = Math.max(10, s * 0.16);
      const cols = Math.max(1, Math.floor((W + g) / (s + g)));
      const rows = Math.ceil(n / cols);
      if (rows * (s * 1.28 + 12) + (rows - 1) * g + 20 <= H) break;
    }
    box.style.setProperty('--s', Math.round(s) + 'px');
    box.style.setProperty('--g', Math.round(Math.max(10, s * 0.16)) + 'px');
    box.style.height = H + 'px';
  }
  function refitPlayers(box) {
    box.classList.add('sizing');
    fitPlayers(box, box.querySelectorAll('.pl:not(.out)').length);
    clearTimeout(box._sizing);
    box._sizing = setTimeout(() => box.classList.remove('sizing'), 500);
  }
  window.addEventListener('resize', () => { const b = $('#players'); if (b) refitPlayers(b); });

  function paintPlayers(list) {
    const box = $('#players'); if (!box) return;
    const ids = new Set(list.map(p => String(p.id)));
    $('#waiting').hidden = list.length > 0;
    const lobby = box.closest('.lobby');
    if (lobby && lobby.classList.contains('compact') !== list.length > 0) {
      lobby.classList.toggle('compact', list.length > 0);
    }
    // FLIP: remember where everyone was before the layout changes
    const first = new Map();
    box.querySelectorAll('.pl:not(.out)').forEach(el => first.set(el, el.getBoundingClientRect()));
    let changed = false, added = false;
    box.querySelectorAll('.pl:not(.out)').forEach(el => {
      if (ids.has(el.dataset.id)) return;
      knownPlayers.delete(el.dataset.id);
      el.classList.add('out');
      changed = true;
      setTimeout(() => { el.remove(); const b = $('#players'); if (b) refitPlayers(b); }, 340);
    });
    list.forEach(p => {
      const id = String(p.id);
      if (knownPlayers.has(id) && box.querySelector('.pl[data-id="' + id + '"]:not(.out)')) return;
      knownPlayers.add(id);
      const el = document.createElement('div');
      el.className = 'pl';
      el.dataset.id = id;
      el.innerHTML = '<div class="pl-in"><div class="pl-bob"><div class="pl-av">' + LQ.avatar(p.name, p.avatar) +
        '</div><div class="pl-name" title="' + esc(p.name) + '">' + esc(p.name) + '</div></div></div>' +
        '<button type="button" title="Remove ' + esc(p.name) + '" aria-label="Remove ' + esc(p.name) + '">' + LQ.icon('x') + '</button>';
      el.querySelector('button').addEventListener('click', () => act('kick', { id: p.id }));
      box.appendChild(el);
      added = changed = true;
    });
    if (changed) {
      fitPlayers(box, list.length);
      first.forEach((r, el) => {
        if (!el.isConnected || el.classList.contains('out')) return;
        const l = el.getBoundingClientRect();
        if (!l.width) return;
        const dx = r.left - l.left, dy = r.top - l.top, k = r.width / l.width;
        if (Math.abs(dx) < 1 && Math.abs(dy) < 1 && Math.abs(k - 1) < 0.01) return;
        el.animate([
          { transformOrigin: '0 0', transform: `translate(${dx}px, ${dy}px) scale(${k})` },
          { transformOrigin: '0 0', transform: 'none' },
        ], { duration: 520, easing: 'cubic-bezier(.22, 1.2, .36, 1)' });
      });
    }
    if (added) sfx.join();
    $('#pCount').textContent = list.length;
    $('#pWord').textContent = list.length === 1 ? 'player' : 'players';
    $('#startBtn').disabled = list.length === 0;
  }

  /* ═════════ Get ready (3-2-1) ═════════ */
  function showReady(q, startAt) {
    if (startAt - serverNow() <= 300) return;
    const ov = document.createElement('div');
    ov.className = 'ready';
    ov.innerHTML = `<div><div class="q-no">Question ${q.index + 1} of ${q.total}</div><div class="type"><span class="pill type">${esc(q.type_label)}</span></div><div class="num"></div></div>`;
    document.body.appendChild(ov);
    const num = ov.querySelector('.num');
    const step = () => {
      const ms = startAt - serverNow();
      if (ms <= 0) { ov.style.transition = 'opacity .3s'; ov.style.opacity = '0'; setTimeout(() => ov.remove(), 300); sfx.go(); return; }
      const n = String(Math.ceil(ms / 1000));
      if (num.textContent !== n) { num.textContent = n; num.style.animation = 'none'; void num.offsetWidth; num.style.animation = ''; sfx.count(); }
      later(step, 100);
    };
    sfx.whoosh();
    step();
  }

  /* ═════════ Question ═════════ */
  function qHeader(q, extra) {
    // Progress dots: done, current, upcoming
    const dots = Array.from({ length: q.total }, (_, i) => '<i class="' + (i < q.index ? 'done' : i === q.index ? 'now' : '') + '"></i>').join('');
    return `
      <div class="q-head">
        <div class="q-meta">
          <div class="q-meta-top"><span class="pill gold">Question ${q.index + 1} of ${q.total}</span><span class="pill type">${esc(q.type_label)}</span></div>
          <div class="q-dots" aria-hidden="true">${dots}</div>
        </div>
        <div class="grow"></div>
        ${extra || ''}
      </div>`;
  }

  function questionCard(q) {
    return `
      <div class="q-card">
        ${q.scenario ? '<div class="q-scenario">' + esc(q.scenario) + '</div>' : ''}
        <h1 class="q-text">${esc(LQ.cleanText(q.question))}</h1>
        <div id="qAnswers" style="margin-top:18px"></div>
      </div>`;
  }

  function drawQuestion(s) {
    const q = s.question;
    app.innerHTML = `
      <section class="screen mid">
        ${qHeader(q, `
          <div class="answered"><b id="answeredNum">${s.answered}</b><small id="answeredOf">of ${s.players} answered</small><span class="ans-bar"><span id="answeredFill" style="width:${s.players ? Math.round(s.answered / s.players * 100) : 0}%"></span></span></div>
          <div class="timer" id="timer" style="--size:104px"><svg viewBox="0 0 100 100"><circle class="track" cx="50" cy="50" r="44"/><circle class="bar" cx="50" cy="50" r="44"/></svg><span>${q.time_limit}</span></div>`)}
        ${questionCard(q)}
        <div class="controls"><button class="btn btn-ghost" id="skipBtn">Show answer now ${LQ.icon('next')}</button></div>
      </section>`;
    LQ.renderQuestion($('#qAnswers'), q, { mode: 'show' });
    $('#skipBtn').addEventListener('click', () => act('reveal'));
    lastTick = -1;
    showReady(q, s.phase_start);
    runTimer(s.phase_start, s.q_ends, q.time_limit);
  }

  function runTimer(startAt, endsAt, limit) {
    const el = $('#timer'); if (!el) return;
    const bar = el.querySelector('.bar'), num = el.querySelector('span');
    const C = 2 * Math.PI * 44;
    bar.style.strokeDasharray = C;
    const frame = () => {
      const now = serverNow();
      const left = Math.max(0, endsAt - Math.max(now, startAt));
      const secs = Math.ceil(left / 1000);
      num.textContent = secs;
      bar.style.strokeDashoffset = C * (1 - left / (limit * 1000));
      el.classList.toggle('low', secs <= 5);
      if (now >= startAt && secs <= 5 && secs > 0 && secs !== lastTick) { lastTick = secs; sfx.tick(); }
      if (left > 0) timerRaf = requestAnimationFrame(frame);
    };
    frame();
  }

  /* ═════════ Reveal ═════════ */
  function drawReveal(s) {
    sfx.reveal();
    const q = s.question, st = s.stats || {};
    const answered = st.full + st.partial + st.wrong;
    const total = Math.max(1, answered + st.no_answer);
    const pct = n => Math.round(n / total * 100);
    const isLast = q.index + 1 >= q.total;
    app.innerHTML = `
      <section class="screen mid">
        ${qHeader(q, '<span class="pill" style="background:#ecfdf5;color:#047857">' + LQ.icon('check') + ' Answer revealed</span>')}
        <div class="reveal-grid">
          ${questionCard(q)}
          <div class="reveal-side">
            <div class="panel">
              <h3>How the class did</h3>
              <div class="big-pct"><span id="pctNum">0</span>%<span style="font-size:1rem;color:var(--on-blue-2);font-weight:700"> fully correct</span></div>
              <div class="stat-bar"><span class="s-full" data-w="${pct(st.full)}"></span><span class="s-part" data-w="${pct(st.partial)}"></span><span class="s-wrong" data-w="${pct(st.wrong)}"></span></div>
              <div class="stat-legend">
                <span><i style="background:var(--good)"></i>${st.full} correct</span>
                <span><i style="background:var(--partial)"></i>${st.partial} partly</span>
                <span><i style="background:var(--bad)"></i>${st.wrong} wrong</span>
                <span><i style="background:rgba(255,255,255,.35)"></i>${st.no_answer} no answer</span>
              </div>
            </div>
            ${s.rationale ? '<div class="rationale"><b>Rationale</b>' + esc(s.rationale) + '</div>' : ''}
            <div class="controls">
              <button class="btn btn-go btn-lg" id="lbBtn">${isLast ? LQ.icon('trophy') + ' Reveal the winners' : 'Leaderboard ' + LQ.icon('play')}</button>
            </div>
          </div>
        </div>
      </section>`;
    const counts = ['mc', 'sata', 'highlight'].includes(q.type) ? (st.counts || {}) : null;
    LQ.renderQuestion($('#qAnswers'), q, { mode: 'show', answer: s.answer, counts });
    later(() => app.querySelectorAll('.stat-bar span').forEach(b => { b.style.width = b.dataset.w + '%'; }), 60);
    countUp($('#pctNum'), 0, pct(st.full), 900);
    autoAdvance($('#lbBtn'), s.phase_start, REVEAL_MS, 'leaderboard', isLast ? LQ.icon('trophy') + ' Reveal the winners' : 'Leaderboard ' + LQ.icon('play'));
  }

  function countUp(el, from, to, ms) {
    if (!el) return;
    const t0 = performance.now();
    const f = t => {
      const k = Math.max(0, Math.min(1, (t - t0) / ms));
      el.textContent = Math.round(from + (to - from) * (1 - Math.pow(1 - k, 3))).toLocaleString();
      if (k < 1) requestAnimationFrame(f);
    };
    requestAnimationFrame(f);
  }

  /* ═════════ Leaderboard: rows start in the old order, then slide into the new one ═════════ */
  function lbRow(r, enter, plain) {
    let move = '';
    if (r.prev_rank > 0 && r.prev_rank !== r.rank) {
      move = r.prev_rank > r.rank ? '<span class="up">' + LQ.icon('up') + ' ' + (r.prev_rank - r.rank) + '</span>' : '<span class="down">' + LQ.icon('down') + ' ' + (r.rank - r.prev_rank) + '</span>';
    }
    const meta = plain ? '' : [move, r.last_points ? '+' + r.last_points.toLocaleString() + ' pts' : 'no points this round', r.streak >= 2 ? LQ.icon('flame') + ' ' + r.streak + ' in a row' : ''].filter(Boolean).join(' · ');
    // While still in the old order (enter), show the old rank; it flips to the new one as the rows move
    const shownRank = enter && r.prev_rank ? r.prev_rank : r.rank;
    return `<div class="lb-row${shownRank === 1 ? ' first' : ''}${enter ? ' enter' : ''}" data-id="${r.id}" data-rank="${r.rank}">
      <span class="lb-rank">${shownRank}</span>${LQ.avatar(r.name, r.avatar)}
      <span class="lb-name">${esc(r.name)}${meta ? '<small>' + meta + '</small>' : ''}</span>
      <span class="lb-score" data-to="${r.score}" data-from="${r.score - r.last_points}">${(r.score - r.last_points).toLocaleString()}</span></div>`;
  }

  function drawLeaderboard(s) {
    const isLast = s.q_index + 1 >= s.q_total;
    if (isLast) { act('next'); return; } // no standings before the winners are revealed
    const rows = s.leaderboard || [];
    const before = rows.slice().sort((a, b) => (a.prev_rank || 999) - (b.prev_rank || 999));
    app.innerHTML = `
      <section class="screen mid">
        <h1 class="center-title"><small>After question ${s.q_index + 1} of ${s.q_total}</small>${isLast ? 'Final standings' : 'Leaderboard'}</h1>
        <div class="lb-wrap"><div class="lb" id="lb">${before.map(r => lbRow(r, true)).join('')}</div></div>
        <div class="controls" style="justify-content:center;margin-top:10px">
          <button class="btn btn-go btn-lg" id="nextBtn">${isLast ? LQ.icon('trophy') + ' Reveal the winners' : 'Next question ' + LQ.icon('play')}</button>
        </div>
      </section>`;
    autoAdvance($('#nextBtn'), s.phase_start, LEADERBOARD_MS, 'next', isLast ? LQ.icon('trophy') + ' Reveal the winners' : 'Next question ' + LQ.icon('play'));
    sfx.whoosh();

    // FLIP: slide every row from its old spot to its new one while scores count up
    later(() => {
      const lb = $('#lb'); if (!lb) return;
      const els = Array.from(lb.children);
      const first = new Map(els.map(el => [el.dataset.id, el.getBoundingClientRect().top]));
      rows.forEach(r => { const el = lb.querySelector('[data-id="' + r.id + '"]'); if (el) lb.appendChild(el); });
      els.forEach(el => {
        el.classList.remove('enter');
        const dy = first.get(el.dataset.id) - el.getBoundingClientRect().top;
        el.style.transition = 'none';
        el.style.transform = 'translateY(' + dy + 'px)';
      });
      void lb.offsetWidth;
      els.forEach(el => {
        el.style.transition = ''; el.style.transform = '';
        el.querySelector('.lb-rank').textContent = el.dataset.rank;
        el.classList.toggle('first', el.dataset.rank === '1');
      });
      lb.querySelectorAll('.lb-score').forEach(sc => countUp(sc, +sc.dataset.from, +sc.dataset.to, 1100));
      if (rows.some(r => r.prev_rank && r.prev_rank !== r.rank)) sfx.whoosh();
    }, 1100);
  }

  /* ═════════ Podium: 3rd → 2nd → 1st, one at a time ═════════ */
  function drawPodium(s) {
    const rows = s.leaderboard || [];
    const pod = (i, cls, label) => {
      const r = rows[i];
      if (!r) return `<div class="pod ${cls}"></div>`;
      return `<div class="pod ${cls}" id="pod${i}">
        <div class="who-wrap">${i === 0 ? '<div class="crown">' + LQ.icon('crown') + '</div>' : ''}${LQ.avatar(r.name, r.avatar)}<div class="who">${esc(r.name)}</div><div class="pts"><span data-to="${r.score}">0</span> pts</div></div>
        <div class="block">${label}</div></div>`;
    };
    app.innerHTML = `
      <section class="screen mid">
        <h1 class="center-title" id="podTitle"><small>${esc(s.title)}</small>And the winners are…</h1>
        <div class="drumroll pulse" id="drum">${LQ.icon('drum')}</div>
        <div class="podium">${pod(1, 'p2', '2')}${pod(0, 'p1', '1')}${pod(2, 'p3', '3')}</div>
        <div class="controls final-actions" id="finalActions" style="justify-content:center;margin-top:18px">
          <button class="btn btn-ghost btn-lg" id="againBtn">${LQ.icon('refresh')} Play again (new PIN)</button>
          <button class="btn btn-go btn-lg" id="finishBtn">Finish &amp; clear game</button>
        </div>
        <div class="rest" id="rest">${rows.slice(3).map(r => lbRow(Object.assign({}, r, { last_points: 0, prev_rank: 0 }), false, true)).join('')}</div>
      </section>`;
    $('#againBtn').addEventListener('click', () => act('create', { title: s.title }));
    $('#finishBtn').addEventListener('click', () => act('end'));

    const drum = $('#drum');
    const reveal = (i, label) => {
      drum.classList.remove('pulse');
      drum.innerHTML = label;
      const el = $('#pod' + i);
      if (!el) return;
      el.classList.add('show');
      const pts = el.querySelector('[data-to]');
      countUp(pts, 0, +pts.dataset.to, 1200);
    };
    const roll = text => { drum.innerHTML = LQ.icon('drum') + ' ' + text; drum.classList.add('pulse'); sfx.drum(); };
    // Same timeline the phones use before showing each player's own final rank
    roll('In 3rd place…');
    later(() => { reveal(2, LQ.medal(3) + ' 3rd place!'); sfx.ta(); }, 2000);
    later(() => roll('In 2nd place…'), 3800);
    later(() => { reveal(1, LQ.medal(2) + ' 2nd place!'); sfx.ta(); }, 5200);
    later(() => roll('And the champion is…'), 7000);
    later(() => {
      reveal(0, LQ.icon('trophy') + ' Champion!');
      sfx.fanfare(); confetti();
      const champ = rows[0];
      if (champ) {
        app.querySelector('.podium').classList.add('crowned');
        $('#podTitle').innerHTML = '<small>' + esc(s.title) + '</small><span class="congrats">Congratulations, ' + esc(champ.name) + '!</span>';
      }
    }, 8800);
    later(() => confetti(), 10600);
    later(() => { sfx.ta(); confetti(); }, 12600);
    later(() => { $('#rest').classList.add('show'); $('#finalActions').classList.add('show'); }, 10400);
  }

  function confetti() {
    const box = document.createElement('div');
    box.className = 'confetti';
    const colors = ['#ffd45c', '#f5b301', '#007cbf', '#0d9488', '#ffffff', '#d61f4e'];
    for (let i = 0; i < 160; i++) {
      const c = document.createElement('i');
      c.style.left = Math.random() * 100 + 'vw';
      c.style.background = colors[i % colors.length];
      c.style.animationDuration = 2.5 + Math.random() * 2.5 + 's';
      c.style.animationDelay = Math.random() * 1.2 + 's';
      c.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
      box.appendChild(c);
    }
    document.body.appendChild(box);
    setTimeout(() => box.remove(), 7500);
  }
})();
