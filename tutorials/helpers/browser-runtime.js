// Injected into every document of the tutorial browser page via `page.addInitScript`.
// Plain, dependency-free browser JS (no imports/exports) — draws the on-screen
// narration card, element highlight ring, and animated cursor used by the
// tutorial video generator. None of this ships to the real application; it
// only exists inside the Playwright-controlled tutorial browser.
(function () {
  if (window.__tutorialRuntime) {
    return;
  }

  var STYLE_ID = '__tutorial_style__';
  var ROOT_ID = '__tutorial_root__';

  var CSS = [
    '#' + ROOT_ID + ' * { box-sizing: border-box; }',
    '.tt-card {',
    '  position: fixed; left: 50%; bottom: 44px; z-index: 999999;',
    '  transform: translate(-50%, 16px); opacity: 0; pointer-events: none;',
    '  transition: opacity 220ms ease, transform 220ms ease;',
    '  background: rgba(15, 23, 42, 0.94); color: #fff;',
    '  padding: 16px 24px; border-radius: 14px; max-width: 480px; min-width: 280px;',
    '  box-shadow: 0 16px 40px rgba(0,0,0,0.35); backdrop-filter: blur(8px);',
    '  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;',
    '}',
    '.tt-card.visible { opacity: 1; transform: translate(-50%, 0); }',
    '.tt-card-eyebrow {',
    '  font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;',
    '  color: #5eead4; margin-bottom: 6px;',
    '}',
    '.tt-card-title { font-size: 17px; font-weight: 700; margin-bottom: 5px; line-height: 1.3; }',
    '.tt-card-desc { font-size: 14px; line-height: 1.5; color: rgba(255,255,255,0.86); }',
    '.tt-highlight {',
    '  position: fixed; z-index: 999997; border: 3px solid #5eead4; border-radius: 10px;',
    '  box-shadow: 0 0 0 4px rgba(94,234,212,0.22), 0 0 26px rgba(94,234,212,0.5);',
    '  opacity: 0; pointer-events: none;',
    '  transition: opacity 220ms ease, left 260ms ease, top 260ms ease, width 260ms ease, height 260ms ease;',
    '}',
    '.tt-highlight.visible { opacity: 1; }',
    '.tt-cursor {',
    '  position: fixed; left: -100px; top: -100px; width: 26px; height: 26px;',
    '  z-index: 1000000; opacity: 0; pointer-events: none;',
    '  transition: left 480ms cubic-bezier(.4,0,.2,1), top 480ms cubic-bezier(.4,0,.2,1), opacity 200ms ease;',
    '}',
    '.tt-cursor.visible { opacity: 1; }',
    '.tt-cursor svg { display: block; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.45)); }',
    '.tt-click-pulse {',
    '  position: fixed; z-index: 999999; width: 40px; height: 40px; border-radius: 50%;',
    '  border: 3px solid #5eead4; background: rgba(94,234,212,0.15);',
    '  transform: translate(-50%, -50%) scale(0.3); pointer-events: none;',
    '  animation: tt-pulse 550ms ease-out forwards;',
    '}',
    '@keyframes tt-pulse {',
    '  0% { transform: translate(-50%, -50%) scale(0.3); opacity: 0.9; }',
    '  100% { transform: translate(-50%, -50%) scale(1.7); opacity: 0; }',
    '}',
    // The zoom effect scales <body> itself so real page content grows —
    // our own overlay lives on <html> (a sibling of <body>, see
    // ensureRoot), so it never gets caught up in this transform. Both
    // transform *and* transform-origin transition: when a caller keeps the
    // same scale but moves the origin (see zoomToPoint), this smoothly pans
    // the already-zoomed view instead of zooming out and back in.
    'body.tt-zoomable {',
    '  transition: transform 420ms cubic-bezier(.4,0,.2,1), transform-origin 420ms cubic-bezier(.4,0,.2,1);',
    '  will-change: transform;',
    '}',
  ].join('\n');

  var CURSOR_SVG =
    '<svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg">' +
    '<path d="M3 2 L3 21 L8.4 16.6 L12 24 L15.2 22.4 L11.6 15 L19 15 Z" ' +
    'fill="#ffffff" stroke="#0f172a" stroke-width="1.4" stroke-linejoin="round" />' +
    '</svg>';

  function ensureStyle() {
    if (document.getElementById(STYLE_ID)) return;
    var style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = CSS;
    document.head.appendChild(style);
  }

  function ensureRoot() {
    var root = document.getElementById(ROOT_ID);
    if (root) return root;

    root = document.createElement('div');
    root.id = ROOT_ID;
    root.innerHTML =
      '<div class="tt-card" id="tt-card">' +
      '  <div class="tt-card-eyebrow" id="tt-card-eyebrow"></div>' +
      '  <div class="tt-card-title" id="tt-card-title"></div>' +
      '  <div class="tt-card-desc" id="tt-card-desc"></div>' +
      '</div>' +
      '<div class="tt-highlight" id="tt-highlight"></div>' +
      '<div class="tt-cursor" id="tt-cursor">' +
      CURSOR_SVG +
      '</div>';
    // Appended to <html>, not <body> — the zoom effect (see zoomToPoint)
    // applies a CSS transform to <body>, which would otherwise drag our
    // fixed-position overlay along with it.
    document.documentElement.appendChild(root);
    document.body.classList.add('tt-zoomable');
    return root;
  }

  function init() {
    ensureStyle();
    ensureRoot();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }

  function showCard(eyebrow, title, description) {
    ensureStyle();
    ensureRoot();
    var card = document.getElementById('tt-card');

    var apply = function () {
      document.getElementById('tt-card-eyebrow').textContent = eyebrow || '';
      document.getElementById('tt-card-title').textContent = title || '';
      document.getElementById('tt-card-desc').textContent = description || '';
      card.classList.add('visible');
    };

    if (card.classList.contains('visible')) {
      card.classList.remove('visible');
      setTimeout(apply, 220);
    } else {
      apply();
    }
  }

  function hideCard() {
    ensureRoot();
    var card = document.getElementById('tt-card');
    card.classList.remove('visible');
  }

  function setHighlight(rect) {
    ensureRoot();
    var el = document.getElementById('tt-highlight');
    var pad = 6;
    el.style.left = rect.x - pad + 'px';
    el.style.top = rect.y - pad + 'px';
    el.style.width = rect.width + pad * 2 + 'px';
    el.style.height = rect.height + pad * 2 + 'px';
    el.classList.add('visible');
  }

  function clearHighlight() {
    ensureRoot();
    document.getElementById('tt-highlight').classList.remove('visible');
  }

  function moveCursor(x, y) {
    ensureRoot();
    var el = document.getElementById('tt-cursor');
    el.style.left = x + 'px';
    el.style.top = y + 'px';
    el.classList.add('visible');
  }

  function hideCursor() {
    ensureRoot();
    document.getElementById('tt-cursor').classList.remove('visible');
  }

  function clickPulse(x, y) {
    ensureRoot();
    var el = document.createElement('div');
    el.className = 'tt-click-pulse';
    el.style.left = x + 'px';
    el.style.top = y + 'px';
    document.documentElement.appendChild(el);
    setTimeout(function () {
      el.remove();
    }, 600);
  }

  // Scales <body> up around a fixed viewport point so real page content
  // (never our own overlay — see ensureRoot) visually grows, drawing the
  // viewer's focus to whatever is being typed into right now.
  function zoomToPoint(x, y, scale) {
    ensureRoot();
    document.body.style.transformOrigin = x + 'px ' + y + 'px';
    document.body.style.transform = 'scale(' + (scale || 1.6) + ')';
  }

  function resetZoom() {
    ensureRoot();
    document.body.style.transform = 'scale(1)';
  }

  window.__tutorialRuntime = {
    showCard: showCard,
    hideCard: hideCard,
    setHighlight: setHighlight,
    clearHighlight: clearHighlight,
    moveCursor: moveCursor,
    hideCursor: hideCursor,
    clickPulse: clickPulse,
    zoomToPoint: zoomToPoint,
    resetZoom: resetZoom,
  };
})();
