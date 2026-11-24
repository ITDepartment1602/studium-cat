/* Live Quiz — player (phone) controller.
 * Keeps a random token in localStorage (no login), polls api.php?a=state every
 * second and shows: join → waiting room → get ready → question → (own answer kept
 * on screen while waiting) → result → leaderboard → winners.
 */
(function () {
  'use strict';
  const app = document.getElementById('app');
  const me = document.getElementById('me');
  const esc = LQ.esc;
  const $ = s => app.querySelector(s);

  const store = {
    get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} },
    del: k => { try { localStorage.removeItem(k); } catch (e) {} },
  };

  let token = store.get('lq_token') || '';
  let pin = window.LQ_PIN || '';
  let viewKey = '';
  let clockOffset = 0;
  let ctrl = null;          // current question renderer (play mode)
  let submitting = false;
  let raf = 0;
  let timers = [];
  let wasJoined = !!token;
  const serverNow = () => Date.now() + clockOffset;
  const plural = (n, w) => n + ' ' + w + (n === 1 ? '' : 's');

  /* ── API ── */
  async function api(action, body) {
    const url = 'api.php?a=' + action + (body ? '' : '&token=' + encodeURIComponent(token) + '&_=' + Date.now());
    const res = await fetch(url, body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body), cache: 'no-store' } : { cache: 'no-store' });
    return res.json();
  }

  /* ── Polling ── */
  let polling = false;
  async function poll() {
    // Not joined yet: nothing to poll, and re-rendering would wipe what they're typing
    if (polling || submitting || !token) return;
    polling = true;
    try {
      const s = await api('state');
      if (s && s.ok && s.v && window.LQ_V && s.v !== window.LQ_V && !submitting) { location.reload(); return; }
      if (s && s.ok) { clockOffset = s.now - Date.now(); render(s); }
    } catch (e) { /* network blip — next tick retries */ }
    finally { polling = false; }
  }
  setInterval(poll, 1000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

  if (token) poll(); else showJoin();

  function show(key, html) {
    if (key === viewKey) return false;
    viewKey = key;
    cancelAnimationFrame(raf);
    timers.forEach(clearTimeout); timers = [];
    document.querySelectorAll('.ready').forEach(el => el.remove());
    app.innerHTML = '<section class="view">' + html + '</section>';
    window.scrollTo(0, 0);
    return true;
  }

  /* Running number: counts from what is shown now up to the new value */
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

  const sndBtn = document.getElementById('soundBtn');
  if (sndBtn && window.LQMusic) {
    const paintSnd = on => { sndBtn.innerHTML = LQ.icon(on ? 'volume' : 'mute'); sndBtn.setAttribute('aria-pressed', on ? 'false' : 'true'); };
    paintSnd(LQMusic.isOn());
    LQMusic.onChange(paintSnd);
    sndBtn.addEventListener('click', () => LQMusic.toggle());
  } else if (sndBtn) sndBtn.hidden = true;

  let shownScore = null, meKey = '';
  function setMe(s) {
    if (!s || !s.name) { me.hidden = true; meKey = ''; shownScore = null; return; }
    me.hidden = false;
    const key = s.name + '|' + s.avatar;
    if (key !== meKey) {
      meKey = key;
      me.innerHTML = '<span>' + esc(s.name) + '<small><b id="meScore">0</b> pts</small></span>' + LQ.avatar(s.name, s.avatar);
      shownScore = null;
    }
    const target = s.score || 0;
    const el = me.querySelector('#meScore');
    if (shownScore === null) { el.textContent = target.toLocaleString(); shownScore = target; }
    else if (target !== shownScore) { countUp(el, shownScore, target, 1200); shownScore = target; }
  }

  function render(s) {
    if (s.phase === 'join') {
      // Token no longer valid: removed by the host, or a new game was created
      store.del('lq_token'); token = '';
      setMe(null);
      showJoin(wasJoined ? 'You left the game. Join again with the PIN on the screen.' : '');
      wasJoined = false;
      return;
    }
    if (s.phase === 'none') {
      setMe(null);
      if (token) { store.del('lq_token'); token = ''; }
      if (show('ended', `
        <div class="center">
          <div class="pulse">${LQ.icon('cap')}</div>
          <h2>Thanks for playing!</h2>
          <p>Keep reviewing — see you on the next break.</p>
          <button class="btn btn-go btn-lg" id="again">Join another game</button>
        </div>`)) $('#again').addEventListener('click', () => showJoin());
      return;
    }
    wasJoined = true;
    setMe(s);
    ({ lobby: viewLobby, question: viewQuestion, reveal: viewReveal, leaderboard: viewStanding, podium: viewFinal }[s.phase] || viewLobby)(s);
  }

  /* ═════════ Join ═════════ */
  function joinCard(title, sub, body) {
    return `
      <section class="view"><div class="center">
        <div class="card join-card">
          <div class="logo"><img src="../assets/LOGO.svg" alt="Studium"></div>
          <h1>${title}</h1>
          <p>${sub}</p>
          ${body}
          <div class="err" id="err" role="alert"></div>
        </div>
      </div></section>`;
  }

  function showJoin(note) {
    viewKey = 'join';
    if (pin.length === 6) return showName(note);
    app.innerHTML = joinCard('Join the quiz', note ? esc(note) : 'Enter the game PIN shown on the screen.', `
      <input class="big-input" id="pin" inputmode="numeric" autocomplete="off" maxlength="7" placeholder="000 000" aria-label="Game PIN">
      <button class="btn btn-go btn-lg btn-block" id="go" style="margin-top:14px">Enter</button>`);
    const input = $('#pin');
    input.focus();
    input.addEventListener('input', () => { input.value = input.value.replace(/\D/g, '').slice(0, 6); });
    const go = () => {
      const v = input.value.replace(/\D/g, '');
      if (v.length !== 6) { $('#err').textContent = 'The PIN has 6 numbers.'; return; }
      pin = v; showName();
    };
    $('#go').addEventListener('click', go);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') go(); });
  }

  function showName(note) {
    viewKey = 'name';
    app.innerHTML = joinCard("What's your name?", note ? esc(note) : "This is how you'll appear on the leaderboard.", `
      <input class="big-input name" id="name" maxlength="20" autocomplete="nickname" placeholder="Your name" aria-label="Your name">
      <div class="pick-title">Pick your nurse</div>
      <div class="nurse-pick" role="radiogroup" aria-label="Choose your avatar">
        ${LQ.NURSE_IDS.map(id => '<button type="button" class="nurse-opt" role="radio" aria-checked="false" data-id="' + id + '" aria-label="Nurse ' + id + '">' + LQ.nurseSvg(id) + '</button>').join('')}
      </div>
      <button class="btn btn-go btn-lg btn-block" id="go" style="margin-top:14px">Join game ${LQ.icon('play')}</button>
      <button class="btn btn-light btn-block" id="back" style="margin-top:10px">Use a different PIN (${esc(pin.slice(0, 3) + ' ' + pin.slice(3))})</button>`);
    const input = $('#name');
    input.value = store.get('lq_name') || '';
    input.focus();
    let chosen = +(store.get('lq_avatar') || 0) || LQ.NURSE_IDS[Math.floor(Math.random() * LQ.NURSE_IDS.length)];
    const paintPick = () => app.querySelectorAll('.nurse-opt').forEach(b => {
      const on = +b.dataset.id === chosen;
      b.classList.toggle('on', on);
      b.setAttribute('aria-checked', on ? 'true' : 'false');
    });
    app.querySelectorAll('.nurse-opt').forEach(b => b.addEventListener('click', () => { chosen = +b.dataset.id; paintPick(); }));
    paintPick();
    const go = async () => {
      const name = input.value.trim();
      if (name.length < 2) { $('#err').textContent = 'Type at least 2 letters.'; return; }
      $('#go').disabled = true;
      try {
        const r = await api('join', { pin, name, avatar: chosen });
        if (!r.ok) { $('#err').textContent = r.error; $('#go').disabled = false; return; }
        token = r.token;
        store.set('lq_token', token);
        store.set('lq_name', r.name);
        store.set('lq_avatar', String(chosen));
        viewKey = '';
        poll();
      } catch (e) { $('#err').textContent = 'No connection. Try again.'; $('#go').disabled = false; }
    };
    $('#go').addEventListener('click', go);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') go(); });
    $('#back').addEventListener('click', () => { pin = ''; showJoin(); });
  }

  /* ═════════ Waiting room ═════════ */
  function viewLobby(s) {
    show('lobby', `
      <div class="center">
        <div class="pulse">${LQ.icon('checkCircle')}</div>
        <h2>You're in!</h2>
        <span class="name-tag">${LQ.avatar(s.name, s.avatar)}${esc(s.name)}</span>
        <p>Look for your name on the big screen.<br>The game starts soon…</p>
        <span class="pill gold">${esc(s.title)}</span>
      </div>`);
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
      if (ms <= 0) { ov.style.transition = 'opacity .3s'; ov.style.opacity = '0'; setTimeout(() => ov.remove(), 300); return; }
      const n = String(Math.ceil(ms / 1000));
      if (num.textContent !== n) { num.textContent = n; num.style.animation = 'none'; void num.offsetWidth; num.style.animation = ''; }
      timers.push(setTimeout(step, 100));
    };
    step();
  }

  /* ═════════ Question ═════════ */
  function questionShell(s, footer) {
    const q = s.question;
    return `
      <div class="q-top">
        <span class="pill gold">Q${q.index + 1}/${q.total}</span>
        <span class="pill type">${esc(q.type_label)}</span>
        <div class="grow"></div>
        <span class="secs" id="secs"></span>
      </div>
      <div class="tbar" id="tbar"><span></span></div>
      <div class="q-card">
        ${q.scenario ? '<div class="q-scenario">' + esc(q.scenario) + '</div>' : ''}
        <h1 class="q-text">${esc(LQ.cleanText(q.question))}</h1>
        <div id="ans" style="margin-top:16px"></div>
      </div>
      <div class="submit-wrap">${footer}</div>`;
  }

  function viewQuestion(s) {
    if (s.answered) return viewLocked(s);
    const q = s.question;
    if (!show('q' + s.q_index, questionShell(s, '<button class="btn btn-go btn-lg btn-block" id="submit" disabled>Submit answer</button>'))) return;
    const btn = $('#submit');
    ctrl = LQ.renderQuestion($('#ans'), q, { mode: 'play', onChange: () => { btn.disabled = !ctrl.complete(); } });
    btn.addEventListener('click', () => submit(s));
    showReady(q, s.phase_start);
    tick(s.phase_start, s.q_ends, q.time_limit);
  }

  function tick(startAt, endsAt, limit) {
    const bar = app.querySelector('#tbar span'), secs = $('#secs'), wrap = $('#tbar');
    const frame = () => {
      if (!bar || !document.body.contains(bar)) return;
      const now = serverNow();
      const left = Math.max(0, endsAt - Math.max(now, startAt));
      bar.style.transform = 'scaleX(' + (left / (limit * 1000)) + ')';
      const sLeft = Math.ceil(left / 1000);
      secs.textContent = sLeft + 's';
      wrap.classList.toggle('low', sLeft <= 5);
      if (left <= 0) {
        const b = $('#submit');
        if (b) { b.disabled = true; b.textContent = "Time's up!"; }
        return;
      }
      raf = requestAnimationFrame(frame);
    };
    frame();
  }

  async function submit(s) {
    if (submitting || !ctrl || !ctrl.complete()) return;
    submitting = true;
    const btn = $('#submit');
    btn.disabled = true;
    btn.textContent = 'Sending…';
    const value = JSON.parse(JSON.stringify(ctrl.value()));
    try {
      const r = await api('answer', { token, q: s.q_index, answer: value });
      if (!r.ok) { toast(r.error || 'Could not send your answer.'); btn.textContent = r.error || 'Closed'; }
      else viewLocked(Object.assign({}, s, { answered: true, mine: value, answered_count: null }));
    } catch (e) {
      toast('No connection — tap Submit again.');
      btn.disabled = false; btn.textContent = 'Submit answer';
    } finally { submitting = false; }
  }

  /* Locked: their answer stays on screen (read-only) while the class finishes */
  function viewLocked(s) {
    const q = s.question;
    if (show('locked' + s.q_index, questionShell(s, `
        <div class="wait-bar" role="status"><span class="spin"></span>
          <span>Answer locked in!<small id="waitCount">Waiting for the others…</small></span></div>`))) {
      const area = $('#ans');
      area.classList.add('locked-area');
      LQ.renderQuestion(area, q, { mode: 'show', mine: s.mine });
      tick(s.phase_start, s.q_ends, q.time_limit);
    }
    if (s.answered_count != null && s.players) {
      const w = $('#waitCount');
      if (w) w.textContent = s.answered_count + ' of ' + plural(s.players, 'player') + ' answered — waiting for the others…';
    }
  }

  /* ═════════ Result after each question ═════════ */
  function viewReveal(s) {
    const r = s.result || {};
    let cls = 'none', ico = LQ.icon('clock'), title = "Time's up!";
    if (r.answered) {
      if (r.fraction >= 1) { cls = 'good'; ico = LQ.icon('checkCircle'); title = 'Correct!'; }
      else if (r.fraction > 0) { cls = 'part'; ico = LQ.icon('partial'); title = 'Partly correct'; }
      else { cls = 'bad'; ico = LQ.icon('xCircle'); title = 'Not quite'; }
    }
    if (!show('r' + s.q_index, `
      <div class="result ${cls}">
        <div class="ico">${ico}</div>
        <h2>${title}</h2>
        <div class="pts">+<span id="gain">0</span> pts</div>
        ${r.answered && r.fraction > 0 && r.fraction < 1 ? '<div style="font-weight:700;margin-top:4px">' + Math.round(r.fraction * 100) + '% of this item right</div>' : ''}
      </div>
      <div class="stand">
        <span class="pill gold">${LQ.icon('star')} #${s.rank} of ${s.players}</span>
        <span class="pill">${s.score.toLocaleString()} pts</span>
        ${s.streak >= 2 ? '<span class="pill">' + LQ.icon('flame') + ' ' + s.streak + ' in a row</span>' : ''}
      </div>
      <div class="q-card">
        <h1 class="q-text" style="font-size:1.02rem">${esc(LQ.cleanText(s.question.question))}</h1>
        <div id="rev" style="margin-top:12px"></div>
        ${s.rationale ? '<div class="rationale" style="margin-top:14px"><b>Rationale</b>' + esc(s.rationale) + '</div>' : ''}
      </div>`)) return;
    LQ.renderQuestion($('#rev'), s.question, { mode: 'show', answer: s.answer, mine: s.mine });
    countUp($('#gain'), 0, r.points || 0, 1100);
    if (navigator.vibrate) navigator.vibrate(cls === 'good' ? [60, 40, 60] : 120);
  }

  /* ═════════ Leaderboard (matches the big screen) ═════════ */
  function rowsHtml(rows, myName) {
    return rows.map(r => `<div class="lb-row enter${r.rank === 1 ? ' first' : ''}${r.name === myName ? ' me' : ''}">
      <span class="lb-rank">${r.rank}</span>${LQ.avatar(r.name, r.avatar)}<span class="lb-name">${esc(r.name)}</span>
      <span class="lb-score">${r.score.toLocaleString()}</span></div>`).join('');
  }

  function viewStanding(s) {
    let move = '';
    if (s.prev_rank > 0 && s.prev_rank !== s.rank) move = s.prev_rank > s.rank ? LQ.icon('up') + ' Up ' + (s.prev_rank - s.rank) : LQ.icon('down') + ' Down ' + (s.rank - s.prev_rank);
    const last = s.q_index + 1 >= s.q_total;
    show('lb' + s.q_index, `
      <div class="center" style="flex:0;padding-top:8px">
        <p>You're currently</p>
        <div class="rank-big">#${s.rank}</div>
        <div class="stand">
          <span class="pill gold">${s.score.toLocaleString()} pts</span>
          ${move ? '<span class="pill">' + move + '</span>' : ''}
          <span class="pill">of ${plural(s.players, 'player')}</span>
        </div>
      </div>
      <div class="section-title">Top players</div>
      <div class="lb">${rowsHtml(s.top || [], s.name)}</div>
      <p style="text-align:center;color:var(--on-blue-2);font-weight:600;margin:4px 0 0">${last ? LQ.icon('trophy') + ' The winners are coming up…' : 'Get ready for the next question!'}</p>`);
  }

  /* ═════════ Winners: wait for the big-screen reveal, then show your own place ═════════ */
  const PODIUM_REVEAL_MS = 9200;   // the host reveals 3rd, 2nd, then 1st at ~8.8s

  function viewFinal(s) {
    if (serverNow() < s.phase_start + PODIUM_REVEAL_MS) {
      show('podium-wait', `
        <div class="center">
          <div class="pulse">${LQ.icon('drum')}</div>
          <h2>And the winners are…</h2>
          <p>Watch the big screen!</p>
        </div>`);
      return;
    }
    // Top 3 get a podium card (gold / silver / bronze). Nobody sees who else won:
    // the phone shows only your own place.
    const PODIUM = {
      1: { cls: '', icon: LQ.icon('trophy'), line: "You're the Live Quiz Champion" },
      2: { cls: ' silver', icon: LQ.medal(2), line: 'You placed 2nd overall' },
      3: { cls: ' bronze', icon: LQ.medal(3), line: 'You placed 3rd overall' },
    };
    const pod = PODIUM[s.rank];
    const body = pod ? `
      <div class="champ-card${pod.cls}">
        <div class="trophy">${pod.icon}</div>
        ${LQ.avatar(s.name, s.avatar)}
        <h2>Congratulations, ${esc(s.name)}!</h2>
        <p>${pod.line} · ${s.score.toLocaleString()} pts</p>
      </div>` : `
      <div class="center" style="flex:0;padding-top:10px">
        <div style="font-size:4rem;line-height:1">${LQ.icon('cap')}</div>
        <div class="rank-big">#${s.rank}</div>
        <h2>Great effort! Every question brings you closer to that RN license.</h2>
        <div class="stand"><span class="pill gold">${s.score.toLocaleString()} pts</span><span class="pill">${plural(s.players, 'player')}</span></div>
      </div>`;
    if (!show('final', body)) return;
    if (s.rank <= 3) { confetti(); if (s.rank === 1) { timers.push(setTimeout(confetti, 1800)); timers.push(setTimeout(confetti, 3600)); } }
  }

  function confetti() {
    const box = document.createElement('div');
    box.className = 'confetti';
    const colors = ['#ffd45c', '#f5b301', '#007cbf', '#0d9488', '#ffffff', '#d61f4e'];
    for (let i = 0; i < 90; i++) {
      const c = document.createElement('i');
      c.style.left = Math.random() * 100 + 'vw';
      c.style.background = colors[i % colors.length];
      c.style.animationDuration = 2.5 + Math.random() * 2 + 's';
      c.style.animationDelay = Math.random() + 's';
      box.appendChild(c);
    }
    document.body.appendChild(box);
    setTimeout(() => box.remove(), 6000);
  }

  function toast(msg) {
    const t = document.createElement('div');
    t.className = 'toast err';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }
})();
