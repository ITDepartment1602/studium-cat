/* Live Quiz — generated background music + applause, shared by the host screen and the phones.
 *
 * Nothing is downloaded: a quiet lo-fi loop (soft electric piano, bass, light drums, a few
 * pentatonic notes) is synthesised with WebAudio and scheduled forever, so it can never have
 * a gap or a cut at the loop point. Browsers block audio until the first tap, so playback
 * starts on the first gesture. The mute choice is remembered, and everything pauses while the
 * tab is hidden.
 *
 *   LQMusic.isOn() / setOn(bool) / toggle() / onChange(fn)
 *   LQMusic.clap(strength)   applause burst (0.4 = light, 1 = big). Silent when muted.
 */
(function () {
  'use strict';
  const KEY = 'lq_sound';
  const MUSIC_VOL = 0.2;   // overall music loudness (kept low so it never competes with the game)
  const CLAP_VOL = 0.55;
  const BPM = 76;
  const STEP = 60 / BPM / 4;            // one 16th note
  const SWING = 0.045;                  // laid-back feel on the off 16ths
  const LOOKAHEAD = 0.45, TICK_MS = 60;

  let on = true;
  try { on = localStorage.getItem(KEY) !== '0'; } catch (e) { /* storage blocked */ }
  const listeners = [];

  let ctx = null, musicBus = null, sfxBus = null, noiseBuf = null, delayIn = null;
  let timer = 0, nextTime = 0, step = 0, started = false;

  /* Fmaj7 – Em7 – Dm7 – Cmaj9, one bar each (MIDI notes) */
  const CHORDS = [
    { root: 41, notes: [57, 60, 64, 67] },
    { root: 40, notes: [55, 59, 62, 67] },
    { root: 38, notes: [57, 60, 64, 65] },
    { root: 36, notes: [55, 59, 62, 64] },
  ];
  const PENTA = [72, 74, 76, 79, 81, 84];
  const hz = m => 440 * Math.pow(2, (m - 69) / 12);

  function init() {
    if (ctx) return ctx;
    const AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return null;
    ctx = new AC();
    const comp = ctx.createDynamicsCompressor();
    comp.threshold.value = -18; comp.ratio.value = 3;
    comp.connect(ctx.destination);

    musicBus = ctx.createGain(); musicBus.gain.value = MUSIC_VOL; musicBus.connect(comp);
    sfxBus = ctx.createGain(); sfxBus.gain.value = CLAP_VOL; sfxBus.connect(comp);

    // dreamy echo for the piano and melody
    delayIn = ctx.createGain(); delayIn.gain.value = 0.5;
    const delay = ctx.createDelay(1); delay.delayTime.value = STEP * 3;
    const fb = ctx.createGain(); fb.gain.value = 0.32;
    const damp = ctx.createBiquadFilter(); damp.type = 'lowpass'; damp.frequency.value = 1800;
    delayIn.connect(delay); delay.connect(damp); damp.connect(fb); fb.connect(delay);
    damp.connect(musicBus);

    // one second of white noise, reused for hats, snare and claps
    noiseBuf = ctx.createBuffer(1, ctx.sampleRate, ctx.sampleRate);
    const d = noiseBuf.getChannelData(0);
    for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
    return ctx;
  }

  function tone(freq, t, dur, vol, type, lp, echo) {
    const o = ctx.createOscillator(), g = ctx.createGain(), f = ctx.createBiquadFilter();
    o.type = type; o.frequency.value = freq;
    f.type = 'lowpass'; f.frequency.value = lp; f.Q.value = 0.4;
    g.gain.setValueAtTime(0.0001, t);
    g.gain.exponentialRampToValueAtTime(vol, t + 0.012);
    g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
    o.connect(f); f.connect(g); g.connect(musicBus);
    if (echo) g.connect(delayIn);
    o.start(t); o.stop(t + dur + 0.05);
  }

  function noise(t, dur, vol, type, freq, bus) {
    const s = ctx.createBufferSource(), f = ctx.createBiquadFilter(), g = ctx.createGain();
    s.buffer = noiseBuf;
    f.type = type; f.frequency.value = freq; f.Q.value = type === 'bandpass' ? 0.9 : 0.5;
    g.gain.setValueAtTime(0.0001, t);
    g.gain.exponentialRampToValueAtTime(vol, t + 0.002);
    g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
    s.connect(f); f.connect(g); g.connect(bus || musicBus);
    s.start(t, Math.random() * 0.6); s.stop(t + dur + 0.03);
  }

  function kick(t, vol) {
    const o = ctx.createOscillator(), g = ctx.createGain();
    o.type = 'sine';
    o.frequency.setValueAtTime(120, t);
    o.frequency.exponentialRampToValueAtTime(42, t + 0.14);
    g.gain.setValueAtTime(vol, t);
    g.gain.exponentialRampToValueAtTime(0.0001, t + 0.28);
    o.connect(g); g.connect(musicBus);
    o.start(t); o.stop(t + 0.3);
  }

  /* Schedule one 16th-note step (16 steps = 1 bar, 4 bars = the full chord loop) */
  function scheduleStep(n, t) {
    const bar = Math.floor(n / 16) % 4, s = n % 16, c = CHORDS[bar];
    const tt = t + ((s % 2) ? SWING : 0);

    // electric piano: chord on beat 1, a softer answer later in the bar
    if (s === 0 || s === 6 || s === 11) {
      const soft = s === 0 ? 1 : 0.55;
      c.notes.forEach((m, i) => {
        const when = tt + i * 0.018;
        tone(hz(m), when, 1.5, 0.05 * soft, 'sine', 2200, true);
        tone(hz(m) * 2.003, when, 0.7, 0.013 * soft, 'triangle', 2600, true);
      });
    }
    // warm bass
    if (s === 0 || s === 7 || s === 10) tone(hz(c.root), tt, s === 0 ? 0.7 : 0.36, 0.17, 'sine', 420, false);
    // drums: soft kick, brushed snare, light hats
    if (s === 0 || s === 10) kick(tt, 0.34);
    if (s === 4 || s === 12) noise(tt, 0.16, 0.05, 'bandpass', 1900);
    if (s % 2 === 0) noise(tt, 0.05, s % 4 === 0 ? 0.024 : 0.014, 'highpass', 7200);
    // sparse melody
    if ((s === 2 || s === 8 || s === 14) && Math.random() < 0.42) {
      const m = PENTA[Math.floor(Math.random() * PENTA.length)];
      tone(hz(m), tt, 0.9, 0.04, 'sine', 3000, true);
    }
  }

  function pump() {
    if (!ctx) return;
    if (nextTime < ctx.currentTime) nextTime = ctx.currentTime + 0.08; // after a pause
    while (nextTime < ctx.currentTime + LOOKAHEAD) {
      scheduleStep(step, nextTime);
      nextTime += STEP;
      step = (step + 1) % 64;
    }
  }

  function startMusic() {
    if (!on || document.hidden || !init()) return;
    if (ctx.state === 'suspended') ctx.resume().catch(() => {});
    if (timer) return;
    nextTime = ctx.currentTime + 0.1;
    pump();
    timer = setInterval(pump, TICK_MS);
    started = true;
  }
  function stopMusic() {
    if (timer) { clearInterval(timer); timer = 0; }
    if (ctx && ctx.state === 'running') ctx.suspend().catch(() => {});
  }

  /* Applause: lots of short band-passed noise bursts, thick at first, thinning out. */
  function clap(strength) {
    if (!on || !init()) return;
    if (ctx.state === 'suspended') ctx.resume().catch(() => {});
    const k = Math.max(0.25, Math.min(1.4, strength || 1));
    const count = Math.round(55 * k), span = 1.4 + 1.5 * k, t0 = ctx.currentTime + 0.03;
    for (let i = 0; i < count; i++) {
      const t = t0 + Math.pow(Math.random(), 1.6) * span;      // front-loaded
      const vol = (0.16 + Math.random() * 0.2) * (1 - 0.55 * (t - t0) / span);
      noise(t, 0.05 + Math.random() * 0.05, Math.max(0.03, vol), 'bandpass', 1100 + Math.random() * 1700, sfxBus);
    }
  }

  function setOn(v) {
    on = !!v;
    try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (e) { /* ignore */ }
    if (on) startMusic(); else stopMusic();
    listeners.forEach(fn => fn(on));
  }

  ['pointerdown', 'touchend', 'keydown', 'click'].forEach(ev =>
    document.addEventListener(ev, () => { if (on && (!timer || (ctx && ctx.state !== 'running'))) startMusic(); }, { passive: true }));
  document.addEventListener('visibilitychange', () => { if (document.hidden) stopMusic(); else if (on && started) startMusic(); });

  window.LQMusic = {
    isOn: () => on,
    setOn,
    toggle: () => setOn(!on),
    onChange: fn => listeners.push(fn),
    clap,
  };
})();
