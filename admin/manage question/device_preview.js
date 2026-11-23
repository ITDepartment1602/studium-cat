/**
 * device_preview.js — Shows a question the way a student gets it, on real device sizes.
 * Used by json_import.php, pending.php and form.php.
 *
 * The frame loads the real student page (see includes/question_preview.php) and is laid out
 * at the device's true pixel size, then scaled down with a transform, which leaves the page's
 * own media queries working on real widths. The frame sits inside a copy of the exam shell,
 * because in a real exam the question is narrower than the screen: on an 820px iPad it gets
 * 699px, which is what decides which of the page's breakpoints apply.
 */

/* Measured from student/dashboard/ngn/index.php in Chrome. Re-measure if that shell's layout
   changes: nav and tools come from .navbar and .tools-nav, pad from .iframe-wrapper. */
const DP_DEVICES = {
  mobile:  { label: 'Mobile',       icon: 'bi-phone',   width: 390,  height: 844,  nav: 50, tools: 0,  pad: 8,  radius: 14 },
  tablet:  { label: 'Tablet / iPad', icon: 'bi-tablet', width: 820,  height: 1180, nav: 56, tools: 73, pad: 24, radius: 16 },
  laptop:  { label: 'Laptop',       icon: 'bi-laptop',  width: 1366, height: 768,  nav: 56, tools: 73, pad: 24, radius: 16 },
  desktop: { label: 'Desktop',      icon: 'bi-display', width: 1920, height: 1080, nav: 56, tools: 73, pad: 24, radius: 16 },
};
const DP_SHELL_PROGRESS = 3;
const DP_SHELL_BAR      = 64;

const DevicePreview = (() => {
  const DEVICE_KEY = 'dp_device';
  let root, items = [], pos = 0, loadId = 0, actualSize = false;
  let device = DP_DEVICES[localStorage.getItem(DEVICE_KEY)] ? localStorage.getItem(DEVICE_KEY) : 'laptop';

  const $ = sel => root.querySelector(sel);

  function build() {
    root = document.createElement('div');
    root.className = 'dp-overlay';
    root.innerHTML =
      '<div class="dp-toolbar">' +
        '<span class="dp-title"><i class="bi bi-eye"></i>Student Preview</span>' +
        '<div class="dp-devices">' + Object.keys(DP_DEVICES).map(key =>
          '<button type="button" class="dp-device" data-device="' + key + '"><i class="bi ' + DP_DEVICES[key].icon + '"></i>' + DP_DEVICES[key].label + '</button>').join('') +
        '</div>' +
        '<span class="dp-info" id="dpInfo"></span>' +
        '<div class="dp-actions">' +
          '<button type="button" class="dp-btn" id="dpPrev"><i class="bi bi-chevron-left"></i></button>' +
          '<span class="dp-count" id="dpCount"></span>' +
          '<button type="button" class="dp-btn" id="dpNext"><i class="bi bi-chevron-right"></i></button>' +
          '<button type="button" class="dp-btn" id="dpScale"></button>' +
          '<button type="button" class="dp-btn" id="dpReload"><i class="bi bi-arrow-clockwise"></i> Start over</button>' +
          '<button type="button" class="dp-close" id="dpClose" aria-label="Close">&times;</button>' +
        '</div>' +
      '</div>' +
      '<div class="dp-note" id="dpNote" style="display:none;"></div>' +
      '<div class="dp-stage" id="dpStage">' +
        '<div class="dp-sizer" id="dpSizer"><div class="dp-screen" id="dpScreen">' +
          '<div class="dp-nav" id="dpNav">' +
            '<div class="dp-nav-left"><i class="bi bi-mortarboard-fill"></i><span id="dpBrand">NCLEX NGN Exam</span><span class="dp-nav-badge">Question</span></div>' +
            '<div class="dp-nav-timer"><i class="bi bi-clock"></i> 00:00:00</div>' +
          '</div>' +
          '<div class="dp-progress" style="height:' + DP_SHELL_PROGRESS + 'px;"></div>' +
          '<div class="dp-main">' +
            '<div class="dp-tools" id="dpTools"><i class="bi bi-calculator"></i><i class="bi bi-journal-text"></i><i class="bi bi-arrows-fullscreen"></i><i class="bi bi-chat-dots"></i><i class="bi bi-pause-circle"></i></div>' +
            '<div class="dp-framewrap" id="dpWrap"><iframe class="dp-frame" id="dpFrame" title="Student page"></iframe></div>' +
          '</div>' +
          '<div class="dp-bar" id="dpBar" style="height:' + DP_SHELL_BAR + 'px;">' +
            '<span class="dp-bar-btn"><i class="bi bi-chevron-left"></i> Previous</span>' +
            '<span class="dp-bar-btn next">Next <i class="bi bi-chevron-right"></i></span>' +
          '</div>' +
          '<div class="dp-fab" id="dpFab"><i class="bi bi-plus-lg"></i></div>' +
        '</div></div>' +
        '<div class="dp-error" id="dpError" style="display:none;"></div>' +
      '</div>';
    document.body.appendChild(root);

    root.querySelectorAll('.dp-device').forEach(btn => btn.addEventListener('click', () => setDevice(btn.dataset.device)));
    $('#dpPrev').addEventListener('click', () => go(pos - 1));
    $('#dpNext').addEventListener('click', () => go(pos + 1));
    $('#dpScale').addEventListener('click', () => { actualSize = !actualSize; layout(); });
    $('#dpReload').addEventListener('click', () => load());
    $('#dpClose').addEventListener('click', close);
    $('#dpFrame').addEventListener('load', matchScrollbars);
    window.addEventListener('resize', () => { if (root.classList.contains('open')) layout(); });
  }

  /** Sizes everything for the current device. */
  function layout() {
    const d = DP_DEVICES[device];
    root.querySelectorAll('.dp-device').forEach(b => b.classList.toggle('active', b.dataset.device === device));

    $('#dpNav').style.height  = d.nav + 'px';
    $('#dpTools').style.width = d.tools + 'px';
    $('#dpTools').style.display = d.tools ? '' : 'none';
    $('#dpWrap').style.padding  = d.pad + 'px';
    $('#dpFrame').style.borderRadius = d.radius + 'px';
    $('#dpBrand').style.display = d.width <= 640 ? 'none' : '';
    $('#dpFab').style.display   = d.width < 768 ? '' : 'none';
    $('#dpBar').classList.toggle('small', d.width < 768);

    const screen = $('#dpScreen');
    screen.style.width  = d.width + 'px';
    screen.style.height = d.height + 'px';

    const stage = $('#dpStage');
    const scale = actualSize ? 1 : Math.min(1, (stage.clientWidth - 48) / d.width, (stage.clientHeight - 48) / d.height);
    screen.style.transform = 'scale(' + scale + ')';
    $('#dpSizer').style.width  = (d.width * scale) + 'px';
    $('#dpSizer').style.height = (d.height * scale) + 'px';

    const frameW = d.width - d.tools - d.pad * 2;
    const frameH = d.height - d.nav - DP_SHELL_PROGRESS - d.pad * 2 - DP_SHELL_BAR;
    $('#dpInfo').textContent = d.width + ' × ' + d.height + ' screen · question area ' + frameW + ' × ' + frameH + ' · shown at ' + Math.round(scale * 100) + '%';
    $('#dpScale').innerHTML  = '<i class="bi bi-' + (actualSize ? 'aspect-ratio' : 'zoom-in') + '"></i> ' + (actualSize ? 'Fit to screen' : 'Actual size');
  }

  /** Reloads the question as well: some pages build parts of their layout from the width at load. */
  function setDevice(key) {
    device = key;
    localStorage.setItem(DEVICE_KEY, key);
    layout();
    load();
  }

  /** Phones and tablets have overlay scrollbars that take up no width, so the admin's desktop
   *  scrollbars must not narrow the page on those devices. */
  function matchScrollbars() {
    const doc = $('#dpFrame').contentDocument;
    if (!doc || !doc.head || DP_DEVICES[device].width > 1024) return;
    const style = doc.createElement('style');
    style.textContent = '*{scrollbar-width:none;}';
    doc.head.appendChild(style);
  }

  function showError(message) {
    $('#dpError').innerHTML = '<i class="bi bi-exclamation-octagon-fill"></i>' + escapeForPreview(message);
    $('#dpError').style.display = '';
    $('#dpSizer').style.display = 'none';
  }

  function load() {
    const item = items[pos];
    const id   = ++loadId;
    $('#dpError').style.display = 'none';
    $('#dpSizer').style.display = '';
    $('#dpCount').textContent   = (pos + 1) + ' of ' + items.length;
    $('#dpPrev').disabled = pos === 0;
    $('#dpNext').disabled = pos === items.length - 1;
    $('#dpNote').style.display = item.note ? '' : 'none';
    $('#dpNote').textContent   = item.note || '';

    Promise.resolve().then(() => item.load())
      .then(url => { if (id === loadId) $('#dpFrame').src = url + '&t=' + Date.now(); })
      .catch(err => { if (id === loadId) showError(err.message || String(err)); });
  }

  function go(index) {
    if (index < 0 || index >= items.length) return;
    pos = index;
    load();
  }

  function close() {
    root.classList.remove('open');
    $('#dpFrame').src = 'about:blank';
  }

  function escapeForPreview(s) {
    const div = document.createElement('div');
    div.textContent = String(s);
    return div.innerHTML;
  }

  /** Runs a preview request and resolves to the student page URL. */
  function request(url, options) {
    return fetch(url, options)
      .then(r => r.json().catch(() => { throw new Error('The server replied with something unexpected (HTTP ' + r.status + ').'); }))
      .then(data => {
        if (!data.success) throw new Error(data.error || 'Could not build the preview.');
        return data.url;
      });
  }

  /** items: [{ title, load: () => Promise<url>, note? }] */
  function open(list, start) {
    if (!root) build();
    items = list;
    pos   = Math.max(0, start || 0);
    root.classList.add('open');
    layout();
    load();
  }

  /** Same as open(), for raw import or queue questions. rows: [{ question }] */
  function openRaw(type, rows, start) {
    open(rows.map(row => ({
      load: () => request('preview_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: type, question: row.question })
      })
    })), start);
  }

  return { open, openRaw, request };
})();
