/* Live Quiz — question renderers shared by the host screen and the player phone.
 *
 * LQ.renderQuestion(container, q, opts) draws the answer area for q.type:
 *   opts.mode    'play' → interactive (phone), 'show' → read-only (projector)
 *   opts.answer  correct answer (reveal only) → marks right/wrong
 *   opts.mine    the player's own answer (reveal on the phone)
 *   opts.counts  {key: n} picks per option (host reveal, mc/sata/highlight)
 *   opts.onChange()  called whenever the player's answer changes
 * Returns { value(), complete() } for 'play' mode.
 */
(function () {
  'use strict';

  const TILE_COLORS = ['--t1', '--t2', '--t3', '--t4', '--t5', '--t6', '--t7', '--t8'];

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
  function h(html) {
    const t = document.createElement('template');
    t.innerHTML = html.trim();
    return t.content.firstElementChild;
  }
  const entries = o => Object.keys(o || {}).map(k => [k, o[k]]);
  const has = (arr, v) => Array.isArray(arr) && arr.indexOf(v) !== -1;

  function renderQuestion(root, q, opts) {
    opts = opts || {};
    root.innerHTML = '';
    const ctx = {
      play: opts.mode === 'play' && opts.answer === undefined,
      reveal: opts.answer !== undefined,
      answer: opts.answer,
      mine: opts.mine,
      counts: opts.counts || null,
      locked: opts.mine !== undefined && opts.mine !== null && opts.answer === undefined,
      changed: () => opts.onChange && opts.onChange(),
    };
    const fn = R[q.type] || R.mc;
    return fn(root, q, q.options || {}, ctx) || { value: () => null, complete: () => false };
  }

  const R = {};

  /* ── Multiple choice / SATA ── */
  function choices(root, q, o, ctx, multi) {
    const grid = h('<div class="tiles' + (ctx.reveal ? ' revealed' : '') + (ctx.locked ? ' locked' : '') + '"></div>');
    let picked = multi ? [] : null;
    if (ctx.mine != null) picked = ctx.mine;
    const correct = ctx.answer;

    entries(o.choices).forEach(([k, text], i) => {
      const isCorrect = ctx.reveal && (multi ? has(correct, k) : correct === k);
      const tag = ctx.play ? 'button' : 'div';
      const count = ctx.counts ? '<span class="count">' + (ctx.counts[k] || 0) + '</span>' : '';
      const el = h('<' + tag + ' class="tile" style="--c:var(' + TILE_COLORS[i % 8] + ')"' + (ctx.play ? ' type="button" aria-pressed="false"' : '') + '>' +
        '<span class="key">' + esc(k) + '</span><span class="txt">' + esc(text) + '</span>' + count +
        (isCorrect ? '<span class="mark" aria-label="correct">' + icon('check') + '</span>' : '') + '</' + tag + '>');
      if (ctx.reveal) el.classList.add(isCorrect ? 'correct' : 'wrong');
      if ((ctx.reveal || ctx.locked) && (multi ? has(picked, k) : picked === k)) el.classList.add('picked');
      if (ctx.play) {
        el.addEventListener('click', () => {
          if (multi) {
            picked = has(picked, k) ? picked.filter(x => x !== k) : picked.concat(k);
          } else {
            picked = k;
          }
          grid.querySelectorAll('.tile').forEach((t, j) => {
            const key = Object.keys(o.choices)[j];
            const on = multi ? has(picked, key) : picked === key;
            t.classList.toggle('picked', on);
            t.setAttribute('aria-pressed', on ? 'true' : 'false');
          });
          ctx.changed();
        });
      }
      grid.appendChild(el);
    });
    root.appendChild(grid);
    if (multi && ctx.play) root.prepend(h('<p class="hint">Select <b>all</b> that apply, then submit.</p>'));
    return { value: () => picked, complete: () => (multi ? picked.length > 0 : picked !== null) };
  }
  R.mc = (root, q, o, ctx) => choices(root, q, o, ctx, false);

  /* ── SATA: plain NCLEX-style checkbox list (no letters, no colours) ── */
  R.sata = (root, q, o, ctx) => {
    let picked = Array.isArray(ctx.mine) ? ctx.mine.slice() : [];
    const list = h('<div class="check-list' + (ctx.reveal ? ' revealed' : '') + '" role="group"></div>');
    if (ctx.play) root.appendChild(h('<p class="hint">Select <b>all</b> that apply, then submit.</p>'));

    entries(o.choices).forEach(([k, text]) => {
      const tag = ctx.play ? 'button' : 'div';
      const el = h('<' + tag + ' class="check"' + (ctx.play ? ' type="button" role="checkbox" aria-checked="false"' : '') + '>' +
        '<span class="box" aria-hidden="true"></span><span class="txt">' + esc(text) + '</span>' +
        (ctx.counts ? '<span class="count">' + (ctx.counts[k] || 0) + '</span>' : '') + '</' + tag + '>');
      const mine = has(picked, k);
      if (ctx.reveal) {
        const right = has(ctx.answer, k);
        if (right && (mine || ctx.mine == null)) el.classList.add('ok');
        else if (right) el.classList.add('miss');
        else if (mine) el.classList.add('bad');
      } else if (mine) {
        el.classList.add('on');
      }
      if (ctx.play) {
        el.addEventListener('click', () => {
          picked = has(picked, k) ? picked.filter(x => x !== k) : picked.concat(k);
          const on = has(picked, k);
          el.classList.toggle('on', on);
          el.setAttribute('aria-checked', on ? 'true' : 'false');
          ctx.changed();
        });
      }
      list.appendChild(el);
    });
    root.appendChild(list);
    return { value: () => picked, complete: () => picked.length > 0 };
  };

  /* ── Matrix multiple response (checkboxes) / multiple choice (one per row) ── */
  function matrix(root, q, o, ctx, multi) {
    const cols = o.columns || [];
    const rows = entries(o.rows);
    const val = {};
    rows.forEach(([rk]) => { val[rk] = multi ? [] : null; });
    if (ctx.mine) Object.assign(val, ctx.mine);

    if (ctx.play) root.appendChild(h('<p class="hint">' + (multi ? 'Tick <b>all</b> that apply in each row.' : 'Choose <b>one</b> option in each row.') + '</p>'));
    const wrap = h('<div class="matrix-wrap"><table class="matrix"><thead><tr><th></th>' +
      cols.map(c => '<th>' + esc(c) + '</th>').join('') + '</tr></thead><tbody></tbody></table></div>');
    const tbody = wrap.querySelector('tbody');

    rows.forEach(([rk, label]) => {
      const tr = h('<tr><th>' + esc(label) + '</th></tr>');
      cols.forEach(c => {
        const td = document.createElement('td');
        const isOn = multi ? has(val[rk], c) : val[rk] === c;
        const btn = h('<' + (ctx.play ? 'button type="button"' : 'span') + ' class="cell-btn' + (multi ? '' : ' round') + '" aria-label="' + esc(label + ': ' + c) + '"></' + (ctx.play ? 'button' : 'span') + '>');
        if (ctx.reveal) {
          const right = multi ? has(ctx.answer[rk], c) : ctx.answer[rk] === c;
          if (right && (isOn || ctx.mine == null)) { btn.classList.add('ok'); btn.innerHTML = icon('check'); }
          else if (right) { btn.classList.add('miss'); btn.innerHTML = icon('check'); }
          else if (isOn) { btn.classList.add('bad'); btn.innerHTML = icon('x'); }
        } else if (isOn) {
          btn.classList.add('on'); btn.innerHTML = icon('check');
        }
        if (ctx.play) {
          btn.addEventListener('click', () => {
            if (multi) {
              val[rk] = has(val[rk], c) ? val[rk].filter(x => x !== c) : val[rk].concat(c);
            } else {
              val[rk] = c;
              tr.querySelectorAll('.cell-btn').forEach(b => { b.classList.remove('on'); b.textContent = ''; });
            }
            const on = multi ? has(val[rk], c) : true;
            btn.classList.toggle('on', on);
            btn.innerHTML = on ? icon('check') : '';
            ctx.changed();
          });
        }
        td.appendChild(btn);
        tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });
    root.appendChild(wrap);
    return {
      value: () => val,
      complete: () => rows.every(([rk]) => (multi ? val[rk].length > 0 : val[rk] !== null)),
    };
  }
  R.mmr = (root, q, o, ctx) => matrix(root, q, o, ctx, true);
  R.mpr = (root, q, o, ctx) => matrix(root, q, o, ctx, false);

  /* ── Display helper: never show raw {{n}} placeholders in the question line ── */
  function cleanText(s) {
    return String(s == null ? '' : s).replace(/\{\{\s*[A-Za-z0-9_]+\s*\}\}/g, '_____');
  }

  /* ═════════ Shared drag engine (mouse + touch via Pointer Events) ═════════
   * enableDrag(el, { can(slotEl), drop(slotEl), start(), end() })
   *  - drag starts after an 8px move, so a tap stays a tap
   *  - targets are any element with [data-drop]; can() decides if it accepts this chip
   *  - a drag never fires the element's click (el._dragged flag)                       */
  function enableDrag(el, hooks) {
    el.addEventListener('pointerdown', e => {
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      const id = e.pointerId, sx = e.clientX, sy = e.clientY;
      let ghost = null, over = null, box = null;
      const move = ev => {
        if (ev.pointerId !== id) return;
        if (!ghost) {
          if (Math.hypot(ev.clientX - sx, ev.clientY - sy) < 8) return;
          box = el.getBoundingClientRect();
          ghost = el.cloneNode(true);
          ghost.classList.add('drag-ghost');
          ghost.style.width = box.width + 'px';
          document.body.appendChild(ghost);
          el.classList.add('dragging');
          hooks.start && hooks.start();
        }
        ev.preventDefault();
        ghost.style.transform = 'translate(' + (ev.clientX - box.width / 2) + 'px,' + (ev.clientY - box.height / 2) + 'px) rotate(-2deg)';
        ghost.style.visibility = 'hidden';
        const under = document.elementFromPoint(ev.clientX, ev.clientY);
        ghost.style.visibility = '';
        const slot = under && under.closest('[data-drop]');
        const ok = slot && hooks.can(slot) ? slot : null;
        if (ok !== over) { if (over) over.classList.remove('drop-hover'); if (ok) ok.classList.add('drop-hover'); over = ok; }
      };
      const done = ev => {
        if (ev.pointerId !== id) return;
        window.removeEventListener('pointermove', move);
        window.removeEventListener('pointerup', done);
        window.removeEventListener('pointercancel', done);
        if (!ghost) return;
        ghost.remove();
        el.classList.remove('dragging');
        el._dragged = true;
        setTimeout(() => { el._dragged = false; }, 50);
        if (over) { over.classList.remove('drop-hover'); hooks.drop(over); }
        hooks.end && hooks.end();
      };
      window.addEventListener('pointermove', move, { passive: false });
      window.addEventListener('pointerup', done);
      window.addEventListener('pointercancel', done);
    });
  }

  /* ── Highlight (Studium passage style: tap phrases to mark them yellow) ── */
  R.highlight = (root, q, o, ctx) => {
    let picked = Array.isArray(ctx.mine) ? ctx.mine.slice() : [];
    if (ctx.play) root.appendChild(h('<p class="hint">Tap the findings in the passage to highlight them. Tap again to remove.</p>'));
    const box = h('<div class="passage"></div>');
    const items = o.items || {};

    const token = (k) => {
      const text = items[k];
      if (text === undefined) return null;
      const el = h('<' + (ctx.play ? 'button type="button" aria-pressed="false"' : 'span') + ' class="hl-token">' + esc(text) +
        (ctx.counts ? '<sup class="hl-count">' + (ctx.counts[k] || 0) + '</sup>' : '') + '</' + (ctx.play ? 'button' : 'span') + '>');
      const mine = has(picked, k);
      if (ctx.reveal) {
        const right = has(ctx.answer, k);
        if (right && (mine || ctx.mine == null)) el.classList.add('ok');
        else if (right) el.classList.add('miss');
        else if (mine) el.classList.add('bad');
      } else if (mine) el.classList.add('on');
      if (ctx.play) {
        el.addEventListener('click', () => {
          picked = has(picked, k) ? picked.filter(x => x !== k) : picked.concat(k);
          const on = has(picked, k);
          el.classList.toggle('on', on);
          el.setAttribute('aria-pressed', on ? 'true' : 'false');
          ctx.changed();
        });
      }
      return el;
    };

    if (o.text) {
      // Real passage: {{h1}} markers become highlightable phrases inside the text
      splitCloze(o.text).forEach(p => {
        if (p.t !== undefined) box.appendChild(document.createTextNode(p.t));
        else { const t = token(p.b); if (t) box.appendChild(t); }
      });
    } else {
      // No passage given: show the findings as one flowing clinical note
      entries(items).forEach(([k], i) => {
        if (i) box.appendChild(h('<span class="hl-sep" aria-hidden="true">·</span>'));
        box.appendChild(token(k));
      });
    }
    root.appendChild(box);
    return { value: () => picked, complete: () => picked.length > 0 };
  };

  /* ── Cloze text helpers ── */
  function splitCloze(text) {
    const parts = [];
    const re = /\{\{\s*([A-Za-z0-9_]+)\s*\}\}/g;
    let last = 0, m;
    while ((m = re.exec(text))) {
      parts.push({ t: text.slice(last, m.index) });
      parts.push({ b: m[1] });
      last = re.lastIndex;
    }
    parts.push({ t: text.slice(last) });
    return parts;
  }

  /* Read-only blank: numbered placeholder, the player's locked pick, or the revealed answer */
  function staticBlank(b, ctx, cls) {
    const right = ctx.reveal ? ctx.answer[b] : null;
    const mine = ctx.mine ? ctx.mine[b] : null;
    if (ctx.reveal) {
      if (ctx.mine != null && mine !== right) return '<span class="' + cls + ' bad"><del>' + esc(mine || '—') + '</del>' + esc(right) + '</span>';
      return '<span class="' + cls + ' ok">' + icon('check') + esc(right) + '</span>';
    }
    if (ctx.locked) return '<span class="' + cls + ' filled">' + esc(mine || '—') + '</span>';
    return '<span class="' + cls + ' empty"><b>' + esc(b) + '</b></span>';
  }

  /* ── Drop-down (cloze, Studium inline-select style) ── */
  R.dropdown = (root, q, o, ctx) => {
    const val = {};
    const blanks = [];
    if (ctx.play) root.appendChild(h('<p class="hint">Choose the best option for each blank.</p>'));
    const para = h('<div class="cloze"></div>');
    splitCloze(o.text || '').forEach(p => {
      if (p.t !== undefined) { para.appendChild(document.createTextNode(p.t)); return; }
      const b = p.b, list = (o.blanks || {})[b] || [];
      blanks.push(b);
      if (ctx.play) {
        const sel = h('<select class="dd-select" aria-label="Blank ' + esc(b) + '"><option value="">Select…</option>' +
          list.map(v => '<option>' + esc(v) + '</option>').join('') + '</select>');
        sel.addEventListener('change', () => {
          if (sel.value) val[b] = sel.value; else delete val[b];
          sel.classList.toggle('filled', !!sel.value);
          ctx.changed();
        });
        para.appendChild(sel);
      } else {
        para.appendChild(h(staticBlank(b, ctx, 'dd-pill')));
      }
    });
    root.appendChild(para);
    return { value: () => val, complete: () => blanks.every(b => !!val[b]) };
  };

  /* ── Drag & drop (cloze; drag a word onto a blank, or tap a blank then a word) ── */
  R.dragndrop = (root, q, o, ctx) => {
    const val = {};
    const tokens = o.tokens || [];
    const blanks = [];
    const slots = {};
    let active = null;      // blank chosen by tap, waiting for a word
    let selected = null;    // word chosen by tap, waiting for a blank

    if (ctx.play) root.appendChild(h('<p class="hint">Drag a word into a blank — or tap a blank, then tap a word. Tap a filled blank to take the word back.</p>'));
    const para = h('<div class="cloze"></div>');
    splitCloze(o.text || '').forEach(p => {
      if (p.t !== undefined) { para.appendChild(document.createTextNode(p.t)); return; }
      const b = p.b;
      blanks.push(b);
      if (ctx.play) {
        const s = h('<button type="button" class="cloze-blank" data-drop="' + esc(b) + '" aria-label="Blank ' + esc(b) + '"><b>' + esc(b) + '</b></button>');
        s.addEventListener('click', () => {
          if (s._dragged) return;
          if (selected !== null) { place(selected, b); return; }
          if (val[b]) { delete val[b]; active = null; paint(); ctx.changed(); return; }
          active = active === b ? null : b;
          paint();
        });
        enableDrag(s, {
          can: t => t !== s && t.hasAttribute('data-drop'),
          drop: t => { const tok = val[b]; if (!tok) return; delete val[b]; if (t.dataset.drop === '__bank') { paint(); ctx.changed(); } else place(tok, t.dataset.drop); },
        });
        slots[b] = s;
        para.appendChild(s);
      } else {
        para.appendChild(h(staticBlank(b, ctx, 'cloze-blank')));
      }
    });
    root.appendChild(para);

    const bank = h('<div class="choices-bank"' + (ctx.play ? ' data-drop="__bank"' : '') + '></div>');
    const correctVals = ctx.reveal ? Object.values(ctx.answer || {}) : [];
    tokens.forEach(tok => {
      const el = h('<' + (ctx.play ? 'button type="button"' : 'span') + ' class="choice-item">' + esc(tok) + '</' + (ctx.play ? 'button' : 'span') + '>');
      el.dataset.tok = tok;
      if (ctx.reveal && !correctVals.includes(tok)) el.classList.add('used');
      if (ctx.locked && Object.values(ctx.mine || {}).includes(tok)) el.classList.add('used');
      if (ctx.play) {
        el.addEventListener('click', () => {
          if (el._dragged) return;
          if (active !== null) { place(tok, active); return; }
          selected = selected === tok ? null : tok;
          paint();
        });
        enableDrag(el, {
          can: t => t.hasAttribute('data-drop') && t.dataset.drop !== '__bank',
          drop: t => place(tok, t.dataset.drop),
          start: () => { selected = null; active = null; paint(); },
        });
      }
      bank.appendChild(el);
    });
    root.appendChild(bank);

    function place(tok, b) {
      Object.keys(val).forEach(k => { if (val[k] === tok) delete val[k]; });
      val[b] = tok;
      selected = null; active = null;
      paint();
      ctx.changed();
    }
    function paint() {
      blanks.forEach(b => {
        const s = slots[b]; if (!s) return;
        s.innerHTML = val[b] ? esc(val[b]) : '<b>' + esc(b) + '</b>';
        s.classList.toggle('filled', !!val[b]);
        s.classList.toggle('active', active === b);
      });
      bank.querySelectorAll('.choice-item').forEach(t => {
        t.classList.toggle('used', Object.values(val).includes(t.dataset.tok));
        t.classList.toggle('sel', selected === t.dataset.tok);
      });
      root.classList.toggle('picking', active !== null || selected !== null);
    }
    return { value: () => val, complete: () => blanks.every(b => !!val[b]) };
  };

  /* ── Bowtie (Studium diagram: 2 actions · condition · 2 parameters, with a choice bank) ── */
  R.bowtie = (root, q, o, ctx) => {
    const groups = {
      actions: { list: o.actions || {}, max: 2, label: 'Actions to take' },
      condition: { list: o.conditions || {}, max: 1, label: 'Condition most likely' },
      parameters: { list: o.parameters || {}, max: 2, label: 'Parameters to monitor' },
    };
    // Each box keeps its own position: slots.actions = [left-top, left-bottom], etc.
    const slots = { actions: [null, null], condition: [null], parameters: [null, null] };
    if (ctx.mine) {
      slots.condition[0] = ctx.mine.condition || null;
      (ctx.mine.actions || []).slice(0, 2).forEach((k, i) => { slots.actions[i] = k; });
      (ctx.mine.parameters || []).slice(0, 2).forEach((k, i) => { slots.parameters[i] = k; });
    }
    const pick = g => slots[g];
    const value = () => ({
      condition: slots.condition[0],
      actions: slots.actions.filter(Boolean),
      parameters: slots.parameters.filter(Boolean),
    });
    let active = null;    // { g, i } slot waiting for a choice
    let selected = null;  // { g, k } choice waiting for a slot

    if (ctx.play) root.appendChild(h('<p class="hint">Drag each choice into the diagram — or tap a box, then tap a choice.</p>'));

    const diagram = h('<div class="bt-diagram">' +
      '<svg class="bt-lines" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">' +
      '<path d="M31 25 L44 50 L31 75 M69 25 L56 50 L69 75"/></svg></div>');
    const slotEls = {};
    ['actions', 'condition', 'parameters'].forEach(g => {
      const col = h('<div class="bt-col bt-' + g + '"><div class="bt-label">' + groups[g].label + '</div></div>');
      slotEls[g] = [];
      for (let i = 0; i < groups[g].max; i++) {
        const s = h('<' + (ctx.play ? 'button type="button"' : 'div') + ' class="bt-zone' + (g === 'condition' ? ' center' : '') + '"' +
          (ctx.play ? ' data-drop="' + g + ':' + i + '"' : '') + '></' + (ctx.play ? 'button' : 'div') + '>');
        if (ctx.play) {
          s.addEventListener('click', () => {
            if (s._dragged) return;
            if (selected && selected.g === g) { place(g, i, selected.k); return; }
            if (pick(g)[i]) { removeAt(g, i); return; }
            active = active && active.g === g && active.i === i ? null : { g, i };
            selected = null;
            paint();
          });
          enableDrag(s, {
            can: t => t.hasAttribute('data-drop') && (t.dataset.drop === '__bank:' + g || t.dataset.drop.split(':')[0] === g) && t !== s,
            drop: t => { const k = pick(g)[i]; if (!k) return; if (t.dataset.drop.startsWith('__bank')) removeAt(g, i); else place(g, +t.dataset.drop.split(':')[1], k); },
          });
        }
        slotEls[g].push(s);
        col.appendChild(s);
      }
      diagram.appendChild(col);
    });
    root.appendChild(diagram);

    // Choice bank: one column per group (hidden on the reveal — the diagram shows the answer)
    let bank = null;
    if (!ctx.reveal) {
      bank = h('<div class="bt-bank"></div>');
      [['actions', 'Actions'], ['condition', 'Conditions'], ['parameters', 'Parameters']].forEach(([g, title]) => {
        const col = h('<div class="bt-bank-col"' + (ctx.play ? ' data-drop="__bank:' + g + '"' : '') + '><div class="bt-bank-title">' + title + '</div></div>');
        entries(groups[g].list).forEach(([k, text]) => {
          const el = h('<' + (ctx.play ? 'button type="button"' : 'div') + ' class="choice-item bt-choice" data-g="' + g + '" data-k="' + esc(k) + '">' + esc(text) + '</' + (ctx.play ? 'button' : 'div') + '>');
          if (ctx.play) {
            el.addEventListener('click', () => {
              if (el._dragged) return;
              if (active && active.g === g) { place(g, active.i, k); return; }
              selected = selected && selected.k === k ? null : { g, k };
              active = null;
              paint();
            });
            enableDrag(el, {
              can: t => t.hasAttribute('data-drop') && t.dataset.drop.split(':')[0] === g,
              drop: t => place(g, +t.dataset.drop.split(':')[1], k),
              start: () => { active = null; selected = null; paint(); },
            });
          }
          col.appendChild(el);
        });
        bank.appendChild(col);
      });
      root.appendChild(bank);
    }

    function place(g, i, k) {
      const arr = slots[g];
      const from = arr.indexOf(k);
      if (from !== -1) arr[from] = arr[i];   // already in another box of this group: swap them
      arr[i] = k;
      active = null; selected = null;
      paint();
      ctx.changed();
    }
    function removeAt(g, i) {
      slots[g][i] = null;
      active = null;
      paint();
      ctx.changed();
    }
    function paint() {
      ['actions', 'condition', 'parameters'].forEach(g => {
        const mine = pick(g).filter(Boolean);
        const right = ctx.reveal ? (g === 'condition' ? [ctx.answer.condition] : ctx.answer[g]) : null;
        const shown = ctx.reveal ? right : pick(g);
        slotEls[g].forEach((s, i) => {
          const k = shown[i];
          s.className = 'bt-zone' + (g === 'condition' ? ' center' : '');
          if (k !== undefined && k !== null) {
            const text = esc(groups[g].list[k]);
            if (ctx.reveal) {
              const ok = ctx.mine == null || has(mine, k);
              s.classList.add(ok ? 'ok' : 'bad');
              s.innerHTML = (ok ? icon('check') : icon('x')) + '<span>' + text + '</span>';
            } else {
              s.classList.add('filled');
              s.innerHTML = '<span>' + text + '</span>';
            }
          } else {
            s.innerHTML = '<span class="bt-empty">' + (ctx.play ? 'Drop or tap' : '') + '</span>';
          }
          if (active && active.g === g && active.i === i) s.classList.add('active');
        });
      });
      if (bank) {
        bank.querySelectorAll('.bt-choice').forEach(el => {
          const g = el.dataset.g, k = el.dataset.k;
          el.classList.toggle('used', has(pick(g), k));
          el.classList.toggle('sel', !!selected && selected.k === k && selected.g === g);
          el.classList.toggle('dim', !!active && active.g !== g);
        });
      }
    }
    paint();
    return {
      value,
      complete: () => { const v = value(); return !!v.condition && v.actions.length === 2 && v.parameters.length === 2; },
    };
  };

  /* ═════════ Nurse avatars (our own SVG illustrations, 3 female + 3 male) ═════════ */
  const NURSES = {
    1: { name: 'Joy',   bg: '#dbeafe', skin: '#f5d0b5', scrub: '#0d9488', hairBack: '<path d="M27 46c0-20 10-30 23-30s23 10 23 30v22c-6 4-12 5-12 5V50H39v23s-6-1-12-5z" fill="#6b3f1f"/>', hairFront: '<path d="M31 40c2-12 10-18 19-18s17 6 19 18c-6-5-12-7-19-7s-13 2-19 7z" fill="#6b3f1f"/>', cap: 'nurse', face: 'smile' },
    2: { name: 'Bea',   bg: '#ccfbf1', skin: '#e8b48f', scrub: '#007cbf', hairBack: '<circle cx="50" cy="20" r="9" fill="#1f2937"/>', hairFront: '<path d="M31 42c1-13 9-20 19-20s18 7 19 20c-4-6-9-9-12-9-3 3-11 4-26 9z" fill="#1f2937"/>', cap: 'none', face: 'wink' },
    3: { name: 'Mia',   bg: '#fce7f3', skin: '#c98b5e', scrub: '#be185d', hairBack: '<g fill="#7c2d12"><circle cx="31" cy="38" r="9"/><circle cx="69" cy="38" r="9"/><circle cx="34" cy="52" r="8"/><circle cx="66" cy="52" r="8"/><circle cx="40" cy="25" r="10"/><circle cx="60" cy="25" r="10"/><circle cx="50" cy="21" r="10"/></g>', hairFront: '<g fill="#7c2d12"><circle cx="40" cy="29" r="6"/><circle cx="50" cy="27" r="6"/><circle cx="60" cy="29" r="6"/></g>', cap: 'none', face: 'grin' },
    4: { name: 'Leo',   bg: '#e0f2fe', skin: '#f5d0b5', scrub: '#0369a1', hairBack: '', hairFront: '<path d="M31 42c0-14 8-21 19-21s19 7 19 21c-3-6-7-9-11-9-6 3-14 3-22 1-3 2-4 5-5 8z" fill="#3f2a1d"/>', cap: 'none', face: 'smile' },
    5: { name: 'Kai',   bg: '#dcfce7', skin: '#8d5a3b', scrub: '#0d9488', hairBack: '', hairFront: '', cap: 'scrub', face: 'cool' },
    6: { name: 'Nico',  bg: '#fef3c7', skin: '#e8b48f', scrub: '#4d7c0f', hairBack: '', hairFront: '<path d="M31 40c1-12 9-18 19-18s18 6 19 18l-4-3-3 3-4-4-4 4-4-4-4 4-4-4-3 3-4-3z" fill="#111827"/>', cap: 'none', face: 'wow', steth: true },
  };

  const FACES = {
    smile: '<circle cx="43" cy="45" r="2.3" fill="#1f2937"/><circle cx="57" cy="45" r="2.3" fill="#1f2937"/><path d="M43 53c4 4 10 4 14 0" fill="none" stroke="#7a2e1f" stroke-width="2.4" stroke-linecap="round"/>',
    wink: '<path d="M40.5 45.5q2.5-2.5 5 0" fill="none" stroke="#1f2937" stroke-width="2.2" stroke-linecap="round"/><circle cx="57" cy="45" r="2.3" fill="#1f2937"/><path d="M43 53c4 4 10 4 14 0" fill="none" stroke="#7a2e1f" stroke-width="2.4" stroke-linecap="round"/><circle cx="38" cy="51" r="3" fill="#f43f5e" opacity=".25"/><circle cx="62" cy="51" r="3" fill="#f43f5e" opacity=".25"/>',
    grin: '<path d="M40.5 46q2.5-3 5 0M54.5 46q2.5-3 5 0" fill="none" stroke="#1f2937" stroke-width="2.2" stroke-linecap="round"/><path d="M42 52h16c0 5-4 8-8 8s-8-3-8-8z" fill="#7a2e1f"/><path d="M44 52h12v2H44z" fill="#fff"/><circle cx="38" cy="51" r="3" fill="#f43f5e" opacity=".3"/><circle cx="62" cy="51" r="3" fill="#f43f5e" opacity=".3"/>',
    cool: '<rect x="37" y="41" width="11" height="7" rx="3" fill="#0f172a"/><rect x="52" y="41" width="11" height="7" rx="3" fill="#0f172a"/><path d="M48 44h4" stroke="#0f172a" stroke-width="2"/><path d="M44 54c4 3 9 2 12-1" fill="none" stroke="#3b1a0e" stroke-width="2.4" stroke-linecap="round"/>',
    wow: '<path d="M40 40l5-1.5M60 40l-5-1.5" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/><circle cx="43" cy="45.5" r="2.6" fill="#1f2937"/><circle cx="57" cy="45.5" r="2.6" fill="#1f2937"/><ellipse cx="50" cy="55" rx="3.5" ry="4.2" fill="#7a2e1f"/>',
  };

  function nurseSvg(id) {
    const n = NURSES[id] || NURSES[1];
    const cap = n.cap === 'nurse'
      ? '<path d="M35 26l2-9h26l2 9z" fill="#fff" stroke="#e2e8f0"/><path d="M48 18.5h4v2.5h2.5v4H52v2.5h-4V25h-2.5v-4H48z" fill="#d61f4e"/>'
      : n.cap === 'scrub'
        ? '<path d="M30 40c0-14 9-22 20-22s20 8 20 22c-6-3-13-4-20-4s-14 1-20 4z" fill="#14b8a6"/><g fill="#ffffff" opacity=".55"><circle cx="40" cy="27" r="1.6"/><circle cx="50" cy="24" r="1.6"/><circle cx="60" cy="27" r="1.6"/><circle cx="45" cy="32" r="1.6"/><circle cx="55" cy="32" r="1.6"/></g>'
        : '';
    const steth = n.steth ? '<path d="M38 72c-2 10 2 16 12 16s14-6 12-16" fill="none" stroke="#334155" stroke-width="2.4"/><circle cx="50" cy="88" r="3.2" fill="#94a3b8" stroke="#334155" stroke-width="1.6"/>' : '';
    return '<svg class="nurse" viewBox="0 0 100 100" aria-hidden="true">' +
      '<circle cx="50" cy="50" r="50" fill="' + n.bg + '"/>' +
      '<clipPath id="nc' + id + '"><circle cx="50" cy="50" r="50"/></clipPath><g clip-path="url(#nc' + id + ')">' +
      n.hairBack +
      '<path d="M16 104c2-22 16-32 34-32s32 10 34 32z" fill="' + n.scrub + '"/>' +
      '<path d="M42 72l8 10 8-10z" fill="' + n.skin + '"/>' +
      '<rect x="44" y="58" width="12" height="15" rx="5" fill="' + n.skin + '"/>' +
      '<circle cx="31.5" cy="46" r="4" fill="' + n.skin + '"/><circle cx="68.5" cy="46" r="4" fill="' + n.skin + '"/>' +
      '<circle cx="50" cy="44" r="19" fill="' + n.skin + '"/>' +
      n.hairFront + cap + (FACES[n.face] || FACES.smile) + steth +
      '<path d="M30 96h10M60 96h10" stroke="#ffffff" stroke-width="2" opacity=".35"/></g></svg>';
  }
  const NURSE_IDS = Object.keys(NURSES).map(Number);

  /* Avatar: the chosen nurse illustration, or initials with a stable colour per name */
  const AV_COLORS = ['#007cbf', '#0d9488', '#7c3aed', '#d61f4e', '#c2610c', '#0369a1', '#be185d', '#4d7c0f', '#0f766e', '#9333ea'];
  function avatar(name, nurseId) {
    if (NURSES[nurseId]) return '<span class="av av-nurse" aria-hidden="true">' + nurseSvg(nurseId) + '</span>';
    const n = String(name || '?').trim();
    let hsh = 0;
    for (let i = 0; i < n.length; i++) hsh = (hsh * 31 + n.charCodeAt(i)) >>> 0;
    const parts = n.split(/\s+/).filter(Boolean);
    const ini = ((parts[0] || '?')[0] + (parts.length > 1 ? parts[parts.length - 1][0] : (parts[0] || '')[1] || '')).toUpperCase();
    return '<span class="av" style="--av:' + AV_COLORS[hsh % AV_COLORS.length] + '" aria-hidden="true">' + esc(ini) + '</span>';
  }

  /* ── Studium Live Quiz icon set (hand-drawn SVG, no emoji). 24×24, stroke = currentColor ── */
  const ICONS = {
    check: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
    x: '<path d="M6.5 6.5l11 11M17.5 6.5l-11 11"/>',
    play: '<path d="M8 5.5v13l10.5-6.5z" fill="currentColor" stroke="none"/>',
    pause: '<path d="M8 5.5h3v13H8zM13 5.5h3v13h-3z" fill="currentColor" stroke="none"/>',
    next: '<path d="M5 5.5l9 6.5-9 6.5z" fill="currentColor" stroke="none"/><path d="M18 5.5v13"/>',
    arrowRight: '<path d="M4 12h15M13 6l6 6-6 6"/>',
    up: '<path d="M12 18V6M6.5 11.5L12 6l5.5 5.5"/>',
    down: '<path d="M12 6v12M6.5 12.5L12 18l5.5-5.5"/>',
    volume: '<path d="M4 9.5h3.5L12 6v12l-4.5-3.5H4z" fill="currentColor"/><path d="M15.5 9a4 4 0 010 6M18 6.5a7.5 7.5 0 010 11"/>',
    mute: '<path d="M4 9.5h3.5L12 6v12l-4.5-3.5H4z" fill="currentColor"/><path d="M16 9.5l5 5M21 9.5l-5 5"/>',
    expand: '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
    clock: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
    clipboard: '<rect x="5.5" y="4.5" width="13" height="16" rx="2.5"/><path d="M9 4.5V3h6v1.5M9 10h6M9 14h4"/>',
    refresh: '<path d="M19.5 12a7.5 7.5 0 11-2.2-5.3"/><path d="M19.5 4.5v4.5H15"/>',
    lock: '<rect x="5" y="10.5" width="14" height="10" rx="2.5"/><path d="M8.5 10.5V8a3.5 3.5 0 017 0v2.5"/>',
    flame: '<path d="M12 3.5c.5 3 4.5 5 4.5 9.5a4.5 4.5 0 01-9 0c0-2.4 1.5-3.5 1.8-5.3 1.2.9 1.9 2.2 1.9 2.2.6-2 .8-4.2.8-6.4z" fill="currentColor"/>',
    trophy: '<path d="M8 4h8v5.5a4 4 0 01-8 0z" fill="currentColor"/><path d="M8 6H5.5a2.5 2.5 0 002.8 3.8M16 6h2.5a2.5 2.5 0 01-2.8 3.8M12 13.5v3.5M8.5 20.5h7M9.5 17h5"/>',
    crown: '<path d="M4 18.5L3 8l5 4 4-7 4 7 5-4-1 10.5z" fill="currentColor"/><path d="M4 18.5h16"/>',
    drum: '<ellipse cx="12" cy="9" rx="7.5" ry="3"/><path d="M4.5 9v6.5c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3V9M8 11.8v6.4M16 11.8v6.4M7 2.5l3.5 5M17 2.5l-3.5 5"/>',
    star: '<path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.8L12 16.8l-5.2 2.8 1-5.8-4.3-4.1 5.9-.8z" fill="currentColor"/>',
    cap: '<path d="M2.5 9.5L12 5l9.5 4.5L12 14z" fill="currentColor"/><path d="M6.5 11.5v4.5c3 2.2 8 2.2 11 0v-4.5M21.5 9.5v5"/>',
    bulb: '<path d="M9 17.5h6M10 20.5h4M12 3.5a5.5 5.5 0 00-3.4 9.8c.6.5.9 1.3.9 2.2h5c0-.9.3-1.7.9-2.2A5.5 5.5 0 0012 3.5z"/>',
    partial: '<circle cx="12" cy="12" r="8.5"/><path d="M12 3.5a8.5 8.5 0 010 17z" fill="currentColor"/>',
    checkCircle: '<circle cx="12" cy="12" r="8.5"/><path d="M8 12.3l2.8 2.8L16.2 9.5"/>',
    xCircle: '<circle cx="12" cy="12" r="8.5"/><path d="M9 9l6 6M15 9l-6 6"/>',
    hourglass: '<path d="M7 3.5h10M7 20.5h10M8 3.5c0 4.5 8 5 8 8.5s-8 4-8 8.5M16 3.5c0 4.5-8 5-8 8.5s8 4 8 8.5"/>',
  };
  function icon(name, cls) {
    return '<svg class="ic' + (cls ? ' ' + cls : '') + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[name] || '') + '</svg>';
  }

  /* Medal: ribbon + disc with the place number (1 gold, 2 silver, 3 bronze) */
  function medal(place) {
    const c = { 1: ['#ffd45c', '#d99a00', '#6b4a00'], 2: ['#f1f5f9', '#94a3b8', '#334155'], 3: ['#f6c18f', '#b8652b', '#4a2208'] }[place] || ['#e2e8f0', '#94a3b8', '#334155'];
    return '<svg class="medal" viewBox="0 0 48 56" aria-hidden="true">' +
      '<path d="M14 2h8l6 16h-8z" fill="#007cbf"/><path d="M34 2h-8l-6 16h8z" fill="#0d9488"/>' +
      '<circle cx="24" cy="34" r="17" fill="' + c[1] + '"/><circle cx="24" cy="34" r="13.5" fill="' + c[0] + '"/>' +
      '<text x="24" y="40.5" text-anchor="middle" font-family="Inter, sans-serif" font-weight="900" font-size="17" fill="' + c[2] + '">' + place + '</text></svg>';
  }


  /* ═════════ Fit-to-screen: scale a view down so it never needs scrolling ═════════
   * Uses the individual `scale` property (so it cooperates with entrance animations) and
   * widens the box by 1/scale so text reflows exactly as on a bigger screen, then pulls the
   * bottom margin up so the page height equals what is actually shown. */
  const FIT_MIN = 0.42;
  function fit(el) {
    if (!el || !el.isConnected) return;
    const parent = el.parentElement;
    const reset = () => { el.style.scale = ''; el.style.width = ''; el.style.marginBottom = ''; el.style.flex = ''; el.style.transformOrigin = ''; };
    reset();
    el.style.flex = 'none';
    const top = el.getBoundingClientRect().top + window.scrollY;
    const pad = parent ? (parseFloat(getComputedStyle(parent).paddingBottom) || 0) : 0;
    const avail = Math.max(120, window.innerHeight - top - Math.min(pad, 18) - 4);
    let h = el.offsetHeight;
    if (h <= avail) { reset(); return; }
    let z = Math.max(FIT_MIN, avail / h);
    const apply = zz => {
      el.style.transformOrigin = 'top left';
      el.style.width = (100 / zz) + '%';
      el.style.scale = String(zz);
      return el.offsetHeight;
    };
    for (let i = 0; i < 6; i++) {
      h = apply(z);
      const target = Math.max(FIT_MIN, Math.min(1, avail / h));
      if (Math.abs(target - z) < 0.01) { z = Math.min(z, target); break; }
      z = target;
    }
    h = apply(z);
    while (h * z > avail + 1 && z > FIT_MIN) { z = Math.max(FIT_MIN, z - 0.02); h = apply(z); }
    el.style.marginBottom = (-(h * (1 - z))) + 'px';
  }

  /* Keep the first view inside `app` fitted: on every view change, resize, and late layout
   * (fonts, animations). Views flagged data-nofit (lobby, forms) keep their own layout. */
  function autoFit(app) {
    let t = 0, timers = [];
    const run = () => {
      const el = app.firstElementChild;
      if (!el || el.hasAttribute('data-nofit') || el.classList.contains('lobby') || el.classList.contains('setup')) { if (el) { el.style.scale = ''; el.style.width = ''; el.style.marginBottom = ''; el.style.flex = ''; } return; }
      fit(el);
    };
    const soon = () => {
      clearTimeout(t); timers.forEach(clearTimeout);
      t = setTimeout(run, 30);
      timers = [setTimeout(run, 350), setTimeout(run, 1100)];
    };
    new MutationObserver(soon).observe(app, { childList: true });
    window.addEventListener('resize', soon);
    window.addEventListener('orientationchange', soon);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(soon);
    if (window.ResizeObserver) {
      // Watch the current view itself (its layout height changes as content appears or reflows).
      let last = '', watching = null;
      const ro = new ResizeObserver(() => {
        const el = app.firstElementChild; if (!el) return;
        const k = el.scrollHeight + ':' + el.offsetWidth;
        if (k !== last) { last = k; clearTimeout(t); t = setTimeout(run, 120); }
      });
      new MutationObserver(() => {
        const el = app.firstElementChild;
        if (el === watching) return;
        if (watching) ro.unobserve(watching);
        watching = el; last = '';
        if (el) ro.observe(el);
      }).observe(app, { childList: true });
    }
    soon();
    return soon;
  }

  window.LQ = { renderQuestion, esc, avatar, icon, medal, nurseSvg, NURSE_IDS, cleanText, fit, autoFit };
})();
