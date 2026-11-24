/* Live Quiz — looping background music, shared by the host screen and the phones.
 * Quiet by default, remembers the mute choice, starts on the first tap/click (browsers
 * block autoplay), and pauses while the tab is hidden. */
(function () {
  'use strict';
  const KEY = 'lq_sound';
  const VOLUME = 0.14;
  const audio = new Audio('assets/music.mp3');
  audio.loop = true;
  audio.preload = 'auto';
  audio.volume = VOLUME;
  audio.setAttribute('playsinline', '');

  let on = true;
  try { on = localStorage.getItem(KEY) !== '0'; } catch (e) { /* storage blocked */ }
  const listeners = [];

  function play() {
    if (!on || document.hidden) return;
    const p = audio.play();
    if (p && p.catch) p.catch(() => { /* blocked until a gesture; retried below */ });
  }
  function setOn(v) {
    on = !!v;
    try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (e) { /* ignore */ }
    if (on) play(); else audio.pause();
    listeners.forEach(fn => fn(on));
  }

  ['pointerdown', 'touchend', 'keydown', 'click'].forEach(ev =>
    document.addEventListener(ev, () => { if (audio.paused) play(); }, { passive: true }));
  document.addEventListener('visibilitychange', () => { if (document.hidden) audio.pause(); else play(); });
  play();

  window.LQMusic = {
    isOn: () => on,
    setOn,
    toggle: () => setOn(!on),
    onChange: fn => listeners.push(fn),
  };
})();
