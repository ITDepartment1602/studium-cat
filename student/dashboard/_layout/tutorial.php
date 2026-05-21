<!-- ═══════════════════════════════════════════════
     STUDIUM — First-Time Onboarding Tutorial
     Only rendered when login.tutorial IS NULL
═══════════════════════════════════════════════ -->

<?php
/* Pass package flag to JS */
$_tut_pkg2 = isset($isPackege2) && $isPackege2;
?>
<script>const TUT_PKG2 = <?= $_tut_pkg2 ? 'true' : 'false' ?>;</script>

<style>
/*
  ── How the overlay works ──
  #tut-backdrop  → transparent, pointer-events:all → blocks all clicks on page
                   (turns dark only for centered/no-target steps)
  #tut-spotlight → no background (transparent center = target fully visible)
                   huge box-shadow outward = dark veil on everything outside
                   sits ABOVE the backdrop so its box-shadow IS the darkness
*/

/* Click blocker — transparent except on centered steps */
#tut-backdrop {
  position: fixed;
  inset: 0;
  z-index: 99896;
  background: transparent;
  pointer-events: all;
}

/* Spotlight — transparent hole + outward dark veil + highlight ring */
#tut-spotlight {
  position: fixed;
  z-index: 99898;
  border-radius: 14px;
  /* no background → target content shows through clearly */
  box-shadow:
    0 0 0 9999px rgba(8,12,24,.80),   /* dark veil OUTSIDE the spotlight */
    0 0 0 3px rgba(255,255,255,.95),  /* white highlight ring */
    0 0 0 5px rgba(0,124,191,.65),    /* blue ring */
    0 0 40px rgba(0,124,191,.30);     /* soft glow */
  transition: top .38s cubic-bezier(.4,0,.2,1),
              left .38s cubic-bezier(.4,0,.2,1),
              width .38s cubic-bezier(.4,0,.2,1),
              height .38s cubic-bezier(.4,0,.2,1),
              opacity .28s ease;
  pointer-events: none;
  opacity: 0;
}

/* ── Speech bubble card ── */
#tut-card {
  position: fixed;
  z-index: 99902;
  width: 300px;
  max-width: calc(100vw - 24px);
  background: #ffffff;
  border-radius: 18px;
  padding: 18px 18px 14px;
  filter: drop-shadow(0 8px 32px rgba(0,0,0,.22)) drop-shadow(0 2px 8px rgba(0,0,0,.12));
  opacity: 0;
  transform: scale(.95) translateY(6px);
  transition: opacity .3s ease, transform .3s cubic-bezier(.34,1.3,.64,1);
  font-family: 'Inter', sans-serif;
  display: none;
  box-sizing: border-box;
}

/* ── Mobile ── */
@media (max-width: 600px) {
  /* Non-centered steps: pin to bottom of screen */
  #tut-card:not(.tut-centered) {
    position: fixed !important;
    bottom: 16px !important;
    left: 12px !important;
    right: 12px !important;
    top: auto !important;
    width: auto !important;
    max-width: none !important;
    max-height: calc(100vh - 72px) !important;
    border-radius: 16px;
    transform: none !important;
    overflow-y: auto !important;
    padding: 16px 16px 14px !important;
  }
  #tut-card:not(.tut-centered).tut-visible {
    transform: none !important;
    opacity: 1 !important;
  }
  #tut-card:not(.tut-centered)::before { display: none !important; }

  /* Centered steps (Welcome / Finish): proper viewport center */
  #tut-card.tut-centered {
    position: fixed !important;
    top: 50% !important;
    left: 16px !important;
    right: 16px !important;
    width: auto !important;
    max-width: none !important;
    max-height: 85vh !important;
    overflow-y: auto !important;
    transform: translateY(-48%) scale(.95) !important;
  }
  #tut-card.tut-centered.tut-visible {
    transform: translateY(-50%) scale(1) !important;
  }

  /* Steps where card should appear at top (e.g. sidebar footer step) */
  #tut-card.tut-mobile-top:not(.tut-centered) {
    bottom: auto !important;
    top: 16px !important;
    max-height: calc(100vh - 72px) !important;
  }

  .tut-emoji  { font-size: 2rem; margin-bottom: 10px; }
  .tut-title  { font-size: 0.9rem; font-weight: 700; }
  .tut-body   { font-size: 0.78rem; line-height: 1.6; }
  .tut-tip    { font-size: 0.72rem; }
  .tut-btn    { height: 32px; padding: 0 12px; font-size: 0.75rem; }
  .tut-progress-track { margin-bottom: 12px; }
  .tut-dots-row { margin-bottom: 10px; }
}

#tut-card.tut-visible {
  opacity: 1;
  transform: scale(1) translateY(0);
}

/* Speech bubble tails */
#tut-card::before {
  content: '';
  position: absolute;
  width: 0;
  height: 0;
  border: 11px solid transparent;
  display: none;
}
#tut-card.tut-tail-left::before {
  display: block;
  left: -21px;
  top: 26px;
  border-right: 11px solid #fff;
}
#tut-card.tut-tail-right::before {
  display: block;
  right: -21px;
  top: 26px;
  border-left: 11px solid #fff;
}
#tut-card.tut-tail-top::before {
  display: block;
  top: -21px;
  left: 30px;
  border-bottom: 11px solid #fff;
}
#tut-card.tut-tail-bottom::before {
  display: block;
  bottom: -21px;
  left: 30px;
  border-top: 11px solid #fff;
}

/* Centred modal (welcome / finish) */
#tut-card.tut-centered {
  width: 360px;
  max-width: calc(100vw - 32px);
  top: 50% !important;
  left: 50% !important;
  transform: translate(-50%, -48%) scale(.95);
  text-align: center;
}
#tut-card.tut-centered.tut-visible {
  transform: translate(-50%, -50%) scale(1);
}
#tut-card.tut-centered .tut-actions  { justify-content: center; }
#tut-card.tut-centered .tut-dots-row { justify-content: center; }

/* Header row */
.tut-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.tut-step-pill {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: .07em;
  text-transform: uppercase;
  color: #007CBF;
  background: rgba(0,124,191,.1);
  padding: 2px 9px;
  border-radius: 20px;
}
.tut-skip-link {
  font-size: 0.7rem;
  color: #94a3b8;
  cursor: pointer;
  border: none;
  background: none;
  padding: 0;
  transition: color .18s;
  font-family: inherit;
}
.tut-skip-link:hover { color: #64748b; }

/* Progress bar */
.tut-progress-track {
  height: 2.5px;
  background: #f1f5f9;
  border-radius: 10px;
  margin-bottom: 14px;
  overflow: hidden;
}
.tut-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #007CBF, #0D9488);
  border-radius: 10px;
  transition: width .4s ease;
}

/* Title */
.tut-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 7px;
  line-height: 1.3;
}

/* Body */
.tut-body {
  font-size: 0.8rem;
  color: #475569;
  line-height: 1.65;
  margin-bottom: 14px;
}
.tut-body strong { color: #1e293b; }

/* Tip box */
.tut-tip {
  font-size: 0.75rem;
  color: #0369a1;
  background: #f0f9ff;
  border-left: 3px solid #007CBF;
  border-radius: 0 7px 7px 0;
  padding: 7px 10px;
  margin-bottom: 12px;
  line-height: 1.55;
  display: none;
}
.tut-tip.visible { display: block; }
.tut-tip strong  { color: #0c4a6e; }

/* Dots + actions row */
.tut-dots-row {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 12px;
}
.tut-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #e2e8f0;
  transition: all .22s;
  flex-shrink: 0;
}
.tut-dot.active {
  background: #007CBF;
  width: 16px;
  border-radius: 3px;
}

/* Buttons */
.tut-actions {
  display: flex;
  align-items: center;
  gap: 7px;
}
.tut-spacer { flex: 1; }

.tut-btn {
  height: 34px;
  padding: 0 14px;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  transition: all .18s;
  font-family: inherit;
  letter-spacing: .01em;
  white-space: nowrap;
}
.tut-btn-primary {
  background: #007CBF;
  color: #fff;
}
.tut-btn-primary:hover { background: #006aa8; transform: translateY(-1px); }
.tut-btn-finish {
  background: linear-gradient(135deg, #007CBF, #0D9488);
  color: #fff;
  padding: 0 18px;
}
.tut-btn-finish:hover { opacity: .9; transform: translateY(-1px); }
.tut-btn-ghost {
  background: #f1f5f9;
  color: #64748b;
}
.tut-btn-ghost:hover { background: #e2e8f0; }
.tut-btn-ghost:disabled { opacity: .28; cursor: not-allowed; pointer-events: none; }

/* Centered welcome emoji */
.tut-emoji {
  font-size: 2.4rem;
  margin-bottom: 12px;
  display: block;
  animation: tut-bounce .6s ease forwards;
}
@keyframes tut-bounce {
  0%   { transform: scale(.5); opacity: 0; }
  70%  { transform: scale(1.15); }
  100% { transform: scale(1); opacity: 1; }
}

/* Confetti canvas */
#tut-confetti {
  position: fixed;
  inset: 0;
  z-index: 99910;
  pointer-events: none;
}

</style>

<!-- Tutorial DOM -->
<div id="tut-backdrop" style="display:none;"></div>
<div id="tut-spotlight"></div>
<canvas id="tut-confetti"></canvas>

<div id="tut-card">
  <div class="tut-header">
    <span class="tut-step-pill" id="tut-step-label">Step 1</span>
    <button class="tut-skip-link" id="tut-skip-btn">Skip tutorial</button>
  </div>
  <div class="tut-progress-track">
    <div class="tut-progress-fill" id="tut-progress" style="width:0%;"></div>
  </div>
  <div id="tut-emoji-wrap"></div>
  <div class="tut-title" id="tut-title"></div>
  <div class="tut-body"  id="tut-body"></div>
  <div class="tut-tip"   id="tut-tip"></div>
  <div class="tut-dots-row" id="tut-dots"></div>
  <div class="tut-actions">
    <button class="tut-btn tut-btn-ghost" id="tut-prev-btn">
      <i class="bi bi-arrow-left"></i> Back
    </button>
    <div class="tut-spacer"></div>
    <button class="tut-btn tut-btn-primary" id="tut-next-btn">
      Next <i class="bi bi-arrow-right"></i>
    </button>
    <button class="tut-btn tut-btn-finish" id="tut-finish-btn" style="display:none;">
      <i class="bi bi-check2-circle"></i> Let's Start!
    </button>
  </div>
</div>

<script>
(function () {

/* ══════════════════════════════════════════════
   STEP DEFINITIONS
   needsSidebar: true → auto-open sidebar on mobile before spotlighting
   pkg2Only: true     → only shown to Package 2 users
   expandId: string   → expand this collapsible section before spotlighting
══════════════════════════════════════════════ */
const ALL_STEPS = [
  {
    selector: null,
    position: 'center',
    emoji: '👋',
    title: 'Welcome to Studium!',
    body: `Hi there! Welcome to your personal nursing review platform. Let me give you a quick tour so you know exactly where everything is — it'll only take a minute!`,
  },
  {
    selector: '.s-sidebar-welcome',
    position: 'right',
    needsSidebar: true,
    emoji: null,
    title: "Hello, that's you!",
    body: `This is your profile card. It shows your name so you always know you're logged in to the right account.`,
    tip: `Make sure your name looks correct. If anything is wrong, you can update it in your Profile page.`,
  },
  {
    selector: '#sSidebar .s-nav',
    position: 'right',
    needsSidebar: true,
    emoji: null,
    title: 'Your Navigation Menu',
    body: `These are your main links. Use them to jump between <strong>Home</strong>, <strong>Exam History</strong>, <strong>My Notes</strong>, <strong>Profile</strong>, and more.`,
    tip: `Tip: Save your notes after every exam session using <strong>My Notes</strong> so you can review them later.`,
  },
  {
    selector: '.s-sidebar-footer',
    position: 'right',
    needsSidebar: true,
    mobileCardTop: true,
    emoji: null,
    title: 'Your Account Expiration',
    body: `This shows the exact date and time your access will end. Once it expires, you won't be able to use the question banks until you renew.`,
    tip: `Tip: Set a reminder on your phone a week before the expiry date so you have time to renew without interrupting your review.`,
  },
  {
    selector: '#tut-ngn-card',
    position: 'bottom',
    emoji: null,
    pkg2Only: true,
    title: 'NARC NGN QBanks',
    body: `These are <strong>Next Generation NCLEX</strong> questions — the newer, harder type of questions now used in the real NCLEX. Instead of just choosing one answer, you'll be asked to make clinical decisions the way a real nurse would.`,
    tip: `Tip: The real NCLEX now includes many NGN questions. Practice here regularly so they feel familiar on exam day.`,
  },
  {
    selector: '#tut-trad-card',
    position: 'bottom',
    emoji: null,
    title: 'Traditional QBanks',
    body: `This is your main study area. Browse through hundreds of multiple-choice nursing questions, choose your answer, and instantly read the full explanation. The more you practice here, the better your accuracy gets.`,
    tip: `Tip: Try to answer at least 50 questions a day. Consistency matters more than studying for long hours once in a while.`,
  },
  {
    selector: '#tut-cat-card',
    position: 'bottom',
    emoji: null,
    pkg2Only: true,
    title: 'CAT Exam Mode',
    body: `This works exactly like the real NCLEX. The exam automatically adjusts — if you answer correctly, the next question gets harder. If you miss, it gives you an easier one. It stops on its own when it's confident in your result.`,
    tip: `Tip: Treat every CAT exam like the real thing. No looking up answers during the exam — that defeats the purpose.`,
  },
  {
    selector: '#tut-stats-hdr',
    position: 'bottom',
    expandId: 'stats-section-body',
    emoji: null,
    title: 'Your Personal Scoreboard',
    body: `This section shows your overall performance — how many questions you've answered, how accurate your answers are, and a bar that shows how close you are to the <strong>75% passing mark</strong>. The goal is to push that bar past 75%.`,
    tip: `Tip: Check your accuracy here daily. If it's below 75%, focus your next session on your weakest subjects.`,
  },
  {
    selector: '#tut-topics-hdr',
    position: 'bottom',
    expandId: 'topics-section-body',
    emoji: null,
    title: 'Scores Per Nursing Topic',
    body: `This breaks down your scores by specific nursing subject — like <strong>Adult Health, Pharmacology, Maternal Health,</strong> and more. You can see exactly how you're doing in each area, not just overall.`,
    tip: `Tip: Don't just focus on what you're good at. Find your lowest-scoring subject here and spend extra time on it.`,
  },
  {
    selector: '#tut-readiness-hdr',
    position: 'bottom',
    expandId: 'readiness-section-body',
    emoji: null,
    title: 'NCLEX Readiness',
    body: `This is your readiness scorecard. It tells you — subject by subject — whether you're prepared for the NCLEX or not:<br><br>🟢 <strong>Green (Ready)</strong> — You're scoring 75% or higher. Keep it up!<br>🟡 <strong>Yellow (Almost)</strong> — You're close but not quite there yet. A little more practice goes a long way.<br>🔴 <strong>Red (Needs Work)</strong> — This subject needs more attention before your exam.`,
    tip: `Tip: Try to turn every subject green before you schedule your actual NCLEX. If even one is red, it could hurt your overall score.`,
  },
  {
    selector: null,
    position: 'center',
    emoji: '🎉',
    title: "You're all set!",
    body: `You now know how to use your Studium dashboard. Every question you answer brings you one step closer to your dream — whether you're aiming for <strong>USRN, AURN, CDRN,</strong> or any international nursing career.<br><br><strong>You've got this, future nurse.</strong>`,
  },
];

/* Filter steps based on package */
const STEPS = ALL_STEPS.filter(s => !s.pkg2Only || TUT_PKG2);
const TOTAL  = STEPS.length;

/* ── DOM refs ── */
const backdrop    = document.getElementById('tut-backdrop');
const spotlight   = document.getElementById('tut-spotlight');
const card        = document.getElementById('tut-card');
const stepLabel   = document.getElementById('tut-step-label');
const progressBar = document.getElementById('tut-progress');
const emojiWrap   = document.getElementById('tut-emoji-wrap');
const titleEl     = document.getElementById('tut-title');
const bodyEl      = document.getElementById('tut-body');
const dotsEl      = document.getElementById('tut-dots');
const prevBtn     = document.getElementById('tut-prev-btn');
const nextBtn     = document.getElementById('tut-next-btn');
const finishBtn   = document.getElementById('tut-finish-btn');
const skipBtn  = document.getElementById('tut-skip-btn');
const tipEl    = document.getElementById('tut-tip');

/* ── State ── */
let current = 0;

/* ── Build dots ── */
for (let i = 0; i < TOTAL; i++) {
  const d = document.createElement('span');
  d.className = 'tut-dot';
  dotsEl.appendChild(d);
}
function getDots() { return dotsEl.querySelectorAll('.tut-dot'); }

/* ── Scroll blocker ── */
function blockWheel(e)     { e.preventDefault(); e.stopPropagation(); }
function blockTouch(e)     { e.preventDefault(); e.stopPropagation(); }
function blockKeyScroll(e) {
  const scrollKeys = ['ArrowUp','ArrowDown','PageUp','PageDown','Home','End',' '];
  if (scrollKeys.includes(e.key)) e.preventDefault();
}
function lockScroll() {
  window.addEventListener('wheel',     blockWheel,     { passive: false });
  window.addEventListener('touchmove', blockTouch,     { passive: false });
  window.addEventListener('keydown',   blockKeyScroll, { passive: false });
}
function unlockScroll() {
  window.removeEventListener('wheel',     blockWheel);
  window.removeEventListener('touchmove', blockTouch);
  window.removeEventListener('keydown',   blockKeyScroll);
}

/* ── Mobile sidebar helpers ── */
function isMobile() { return window.innerWidth <= 900; }

function openSidebarForTutorial() {
  if (!isMobile()) return;
  const sb = document.getElementById('sSidebar');
  const ov = document.getElementById('sOverlay');
  if (sb) { sb.classList.add('open'); sb.style.zIndex = '99897'; }
  if (ov) ov.classList.add('active');
}

function closeSidebarForTutorial() {
  const sb = document.getElementById('sSidebar');
  const ov = document.getElementById('sOverlay');
  if (sb) { sb.classList.remove('open'); sb.style.zIndex = ''; }
  if (ov) ov.classList.remove('active');
}

/* ── Spotlight ──
   IMPORTANT: spotlight + card are position:fixed.
   getBoundingClientRect() returns viewport-relative coords.
   Do NOT add scrollY/scrollX — fixed elements are positioned
   relative to the viewport, not the document.
── */
function pad(rect, px) {
  return {
    top:    rect.top    - px,
    left:   rect.left   - px,
    width:  rect.width  + px * 2,
    height: rect.height + px * 2,
    bottom: rect.bottom + px,
    right:  rect.right  + px,
  };
}

function setSpotlight(rect) {
  const r = pad(rect, 7);
  /* viewport-relative — no scrollY offset for fixed elements */
  spotlight.style.top     = r.top    + 'px';
  spotlight.style.left    = r.left   + 'px';
  spotlight.style.width   = r.width  + 'px';
  spotlight.style.height  = r.height + 'px';
  spotlight.style.opacity = '1';
}

function clearSpotlight() {
  spotlight.style.opacity = '0';
}

/* ── Card placement with speech-bubble tail ──
   All positions are viewport-relative (position:fixed).
   Never adds scrollY/scrollX — fixed elements don't need it.
── */
function placeCard(step, targetRect) {
  card.classList.remove('tut-centered','tut-tail-left','tut-tail-right','tut-tail-top','tut-tail-bottom');
  card.style.top  = '';
  card.style.left = '';

  if (!targetRect || step.position === 'center') {
    card.classList.add('tut-centered');
    return;
  }

  const GAP = 16;
  const cW  = Math.min(300, window.innerWidth - 24);
  const cH  = card.offsetHeight || 260;
  const vW  = window.innerWidth;
  const vH  = window.innerHeight;
  const r   = pad(targetRect, 7);

  /* Clamp helpers — viewport coords only */
  const clampH = l => Math.max(8, Math.min(l, vW - cW - 8));
  const clampV = t => Math.max(8, Math.min(t, vH - cH - 8));

  /* Which sides physically fit */
  const fits = {
    right:  r.right  + GAP + cW <= vW,
    left:   r.left   - GAP - cW >= 0,
    bottom: r.bottom + GAP + cH <= vH,
    top:    r.top    - GAP - cH >= 0,
  };

  const preferOrder = {
    right:  ['right','left','bottom','top'],
    left:   ['left','right','bottom','top'],
    bottom: ['bottom','top','right','left'],
    top:    ['top','bottom','right','left'],
  }[step.position] || ['right','left','bottom','top'];

  let top, left, tail = '';

  for (let i = 0; i < preferOrder.length; i++) {
    const side = preferOrder[i];
    const isLast = i === preferOrder.length - 1;
    if (!fits[side] && !isLast) continue;

    if (side === 'right')  { left = r.right  + GAP;      top = clampV(r.top); tail = 'tut-tail-left';   }
    if (side === 'left')   { left = r.left   - GAP - cW; top = clampV(r.top); tail = 'tut-tail-right';  }
    if (side === 'bottom') { top  = r.bottom + GAP;       left = clampH(r.left); tail = 'tut-tail-top';  }
    if (side === 'top')    { top  = r.top    - GAP - cH;  left = clampH(r.left); tail = 'tut-tail-bottom'; }
    break;
  }

  /* Final clamp to keep card fully inside viewport */
  card.style.top  = clampV(top)  + 'px';
  card.style.left = clampH(left) + 'px';
  if (tail) card.classList.add(tail);
}

/* ── Render step ── */
function render(idx) {
  const step = STEPS[idx];
  card.classList.remove('tut-visible');

  setTimeout(() => {
    stepLabel.textContent    = `Step ${idx + 1} of ${TOTAL}`;
    progressBar.style.width  = (((idx + 1) / TOTAL) * 100) + '%';

    /* Emoji (only on centred steps) */
    if (step.emoji) {
      emojiWrap.innerHTML = `<span class="tut-emoji">${step.emoji}</span>`;
    } else {
      emojiWrap.innerHTML = '';
    }

    /* Content */
    titleEl.innerHTML = step.title;
    bodyEl.innerHTML  = step.body;

    /* Tip box */
    if (step.tip) {
      tipEl.innerHTML = step.tip;
      tipEl.classList.add('visible');
    } else {
      tipEl.innerHTML = '';
      tipEl.classList.remove('visible');
    }

    /* Mobile top/bottom card position */
    card.classList.toggle('tut-mobile-top', !!step.mobileCardTop);

    /* Dots */
    getDots().forEach((d, i) => d.classList.toggle('active', i === idx));

    /* Buttons */
    prevBtn.disabled            = idx === 0;
    const isLast                = idx === TOTAL - 1;
    nextBtn.style.display       = isLast ? 'none' : '';
    finishBtn.style.display     = isLast ? '' : 'none';

    /* Target element */
    if (step.selector) {
      const el = document.querySelector(step.selector);
      if (el) {
        /* Expand linked collapsible section if provided */
        if (step.expandId) {
          const sec = document.getElementById(step.expandId);
          if (sec) {
            sec.style.display   = 'block';
            sec.style.maxHeight = 'none';
            sec.style.overflow  = 'visible';
          }
        }

        backdrop.style.background = 'transparent';

        const doSpotlight = () => {
          el.scrollIntoView({ behavior: 'instant', block: 'center' });
          requestAnimationFrame(() => requestAnimationFrame(() => {
            const rect = el.getBoundingClientRect();
            setSpotlight(rect);
            placeCard(step, rect);
            card.classList.add('tut-visible');
          }));
        };

        if (step.needsSidebar) {
          /* Open sidebar first, wait for CSS slide-in transition (300ms) before measuring */
          openSidebarForTutorial();
          setTimeout(doSpotlight, 340);
        } else {
          closeSidebarForTutorial();
          doSpotlight();
        }
        return;
      }
    }

    /* No target — centred modal */
    if (!STEPS[idx].needsSidebar) closeSidebarForTutorial();
    clearSpotlight();
    backdrop.style.background = 'rgba(8,12,24,.82)';
    placeCard(step, null);
    card.classList.add('tut-visible');
  }, 200);
}

/* ── Show / hide ── */
function showTutorial() {
  backdrop.style.display = 'block';
  card.style.display     = 'block';
  lockScroll();
  render(0);
}

function hideTutorial() {
  card.classList.remove('tut-visible');
  clearSpotlight();
  backdrop.style.background = 'transparent';
  closeSidebarForTutorial();
  unlockScroll();
  setTimeout(() => {
    backdrop.style.display = 'none';
    card.style.display     = 'none';
  }, 320);
}

/* ── Confetti ── */
function launchConfetti() {
  const canvas  = document.getElementById('tut-confetti');
  canvas.width  = window.innerWidth;
  canvas.height = window.innerHeight;
  const ctx     = canvas.getContext('2d');
  const COLORS  = ['#007CBF','#0D9488','#6366f1','#f59e0b','#10b981','#ec4899'];
  const pieces  = Array.from({ length: 130 }, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * -canvas.height * .8,
    r: Math.random() * 6 + 3,
    d: Math.random() * 80 + 30,
    color: COLORS[Math.floor(Math.random() * COLORS.length)],
    tiltAngle: 0,
    tiltInc: Math.random() * .07 + .04,
  }));
  let angle = 0, raf;
  (function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    angle += .01;
    pieces.forEach(p => {
      p.tiltAngle += p.tiltInc;
      p.y += Math.cos(angle + p.d) + 2.2;
      p.x += Math.sin(angle) * .8;
      ctx.beginPath();
      ctx.lineWidth   = p.r / 2;
      ctx.strokeStyle = p.color;
      ctx.moveTo(p.x + Math.sin(p.tiltAngle) * 10 + p.r / 4, p.y);
      ctx.lineTo(p.x + Math.sin(p.tiltAngle) * 10,             p.y + Math.sin(p.tiltAngle) * 10 + p.r / 4);
      ctx.stroke();
    });
    if (pieces.some(p => p.y < canvas.height)) {
      raf = requestAnimationFrame(draw);
    } else {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
  })();
  setTimeout(() => { cancelAnimationFrame(raf); ctx.clearRect(0, 0, canvas.width, canvas.height); }, 3800);
}

/* ── DB: mark complete ── */
function markComplete() {
  fetch('tutorial_complete.php', { method: 'POST', credentials: 'same-origin' }).catch(() => {});
}

/* ── Button events ── */
nextBtn.addEventListener('click',   () => { if (current < TOTAL - 1) render(++current); });
prevBtn.addEventListener('click',   () => { if (current > 0) render(--current); });
skipBtn.addEventListener('click',   () => { hideTutorial(); markComplete(); });
finishBtn.addEventListener('click', () => { hideTutorial(); markComplete(); launchConfetti(); });

/* ── Keyboard (ESC=skip, Enter/→=next, ←=back) ── */
document.addEventListener('keydown', function (e) {
  if (backdrop.style.display === 'none') return;
  if (e.key === 'Escape')    { hideTutorial(); markComplete(); }
  if (e.key === 'Enter' || e.key === 'ArrowRight') {
    current === TOTAL - 1
      ? (hideTutorial(), markComplete(), launchConfetti())
      : render(++current);
  }
  if (e.key === 'ArrowLeft' && current > 0) render(--current);
});

/* ── Start ── */
showTutorial();

})();
</script>
