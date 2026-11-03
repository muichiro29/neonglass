/**
 * NeonGlass — client-side enhancements for Pelican Panel
 * Author: Muichiro Dev · v1.0.1
 *
 * Design rule: nothing in here may ever throw into the page. Every entry point
 * is wrapped in safe(), every DOM lookup tolerates missing nodes, and all
 * injected nodes live OUTSIDE Livewire components so morphing never fights us.
 */
(function () {
  'use strict';

  const root = document.documentElement;
  const safe = (fn) => (...args) => {
    try { return fn(...args); } catch (_) { /* never break the panel */ }
  };

  // ─── Settings ────────────────────────────────────────────────────────────
  const CFG = (() => {
    try {
      const m = document.querySelector('meta[name="ng-settings"]');
      return m ? JSON.parse(m.content) : {};
    } catch (_) {
      return {};
    }
  })();
  const T = CFG.i18n || {};
  const REDUCED =
    !!CFG.reduce_motion ||
    (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

  const store = {
    get(k) { try { return localStorage.getItem(k); } catch (_) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (_) {} },
  };
  const setText = (el, txt) => { if (el && el.textContent !== txt) el.textContent = txt; };

  // ─── Route markers ───────────────────────────────────────────────────────
  const markRoute = safe(function () {
    if (!document.body) return;
    const p = (location.pathname || '/').toLowerCase().replace(/\/+$/, '') || '/';

    const isAuth = /\/(login|register|password|reset|verify|two-factor)/.test(p);
    if (isAuth) document.body.setAttribute('data-ng-auth', '1');
    else document.body.removeAttribute('data-ng-auth');

    // The "app" panel is the server list and has no navigation.
    const isAppPanel = /\bfi-panel-app\b/.test(document.body.className || '');
    const isList =
      !isAuth &&
      (isAppPanel
        ? !/\/account/.test(p)
        : p === '/' || /\/servers$/.test(p) || /^\/(app|dashboard)$/.test(p));

    // The native (Filament page) dashboard renders its own hero and cards:
    // the classic table decorations below must stay out of its way.
    const isNative = !!document.querySelector('[data-ng-native]');

    if (isNative) root.setAttribute('data-ng-page', 'native-dashboard');
    else if (isList) root.setAttribute('data-ng-page', 'server-list');
    else root.removeAttribute('data-ng-page');
  });

  // ─── Layout preference (grid / list / compact) ───────────────────────────
  const LAYOUTS = ['grid', 'list', 'compact'];
  const applyLayout = safe(function (name) {
    if (!LAYOUTS.includes(name)) name = 'grid';
    root.setAttribute('data-ng-layout', name);
    document.querySelectorAll('#ng-hero .ng-seg button').forEach((b) => {
      b.setAttribute('aria-pressed', b.dataset.layout === name ? 'true' : 'false');
    });
  });
  applyLayout(LAYOUTS.includes(store.get('ng-layout')) ? store.get('ng-layout') : CFG.dashboard_layout);

  const PALETTES = {
    neonglass: {
      background: '#07081a',
      foreground: '#f4f6ff',
      cursor: '#22d3ee',
      cursorAccent: '#07081a',
      selectionBackground: 'rgba(232, 121, 249, 0.3)',
      selectionForeground: '#ffffff',
      black: '#161833',
      red: '#fb7185',
      green: '#34d399',
      yellow: '#fbbf24',
      blue: '#60a5fa',
      magenta: '#e879f9',
      cyan: '#22d3ee',
      white: '#e4e7ff',
      brightBlack: '#6b6f9a',
      brightRed: '#fda4af',
      brightGreen: '#6ee7b7',
      brightYellow: '#fde047',
      brightBlue: '#93c5fd',
      brightMagenta: '#f0abfc',
      brightCyan: '#67e8f9',
      brightWhite: '#ffffff',
    },
    midnight: {
      background: '#020617',
      foreground: '#f1f5f9',
      cursor: '#818cf8',
      cursorAccent: '#020617',
      selectionBackground: 'rgba(129, 140, 248, 0.35)',
      selectionForeground: '#020617',
      black: '#0f172a',
      red: '#ef4444',
      green: '#22c55e',
      yellow: '#eab308',
      blue: '#6366f1',
      magenta: '#a855f7',
      cyan: '#22d3ee',
      white: '#e2e8f0',
      brightBlack: '#64748b',
      brightRed: '#f87171',
      brightGreen: '#4ade80',
      brightYellow: '#facc15',
      brightBlue: '#818cf8',
      brightMagenta: '#c084fc',
      brightCyan: '#67e8f9',
      brightWhite: '#f8fafc',
    },
    aurora: {
      background: '#0c0a1a',
      foreground: '#e0e7ff',
      cursor: '#a78bfa',
      cursorAccent: '#0c0a1a',
      selectionBackground: 'rgba(167, 139, 250, 0.35)',
      selectionForeground: '#0c0a1a',
      black: '#1e1b4b',
      red: '#f472b6',
      green: '#34d399',
      yellow: '#fbbf24',
      blue: '#60a5fa',
      magenta: '#c084fc',
      cyan: '#22d3ee',
      white: '#e0e7ff',
      brightBlack: '#6366f1',
      brightRed: '#f9a8d4',
      brightGreen: '#6ee7b7',
      brightYellow: '#fcd34d',
      brightBlue: '#93c5fd',
      brightMagenta: '#d8b4fe',
      brightCyan: '#67e8f9',
      brightWhite: '#f5f3ff',
    },
    nord: {
      background: '#2e3440',
      foreground: '#eceff4',
      cursor: '#88c0d0',
      cursorAccent: '#2e3440',
      selectionBackground: 'rgba(136, 192, 208, 0.35)',
      selectionForeground: '#2e3440',
      black: '#3b4252',
      red: '#bf616a',
      green: '#a3be8c',
      yellow: '#ebcb8b',
      blue: '#81a1c1',
      magenta: '#b48ead',
      cyan: '#88c0d0',
      white: '#e5e9f0',
      brightBlack: '#4c566a',
      brightRed: '#bf616a',
      brightGreen: '#a3be8c',
      brightYellow: '#ebcb8b',
      brightBlue: '#81a1c1',
      brightMagenta: '#b48ead',
      brightCyan: '#8fbcbb',
      brightWhite: '#eceff4',
    },
    minecraft: {
      background: '#1a1a1a',
      foreground: '#e0e0e0',
      cursor: '#55ff55',
      cursorAccent: '#1a1a1a',
      selectionBackground: 'rgba(85, 255, 85, 0.3)',
      selectionForeground: '#1a1a1a',
      black: '#000000',
      red: '#aa0000',
      green: '#00aa00',
      yellow: '#aaaa00',
      blue: '#0000aa',
      magenta: '#aa00aa',
      cyan: '#00aaaa',
      white: '#aaaaaa',
      brightBlack: '#555555',
      brightRed: '#ff5555',
      brightGreen: '#55ff55',
      brightYellow: '#ffff55',
      brightBlue: '#5555ff',
      brightMagenta: '#ff55ff',
      brightCyan: '#55ffff',
      brightWhite: '#ffffff',
    },
    dracula: {
      background: '#282a36',
      foreground: '#f8f8f2',
      cursor: '#bd93f9',
      cursorAccent: '#282a36',
      selectionBackground: 'rgba(189, 147, 249, 0.35)',
      selectionForeground: '#282a36',
      black: '#21222c',
      red: '#ff5555',
      green: '#50fa7b',
      yellow: '#f1fa8c',
      blue: '#bd93f9',
      magenta: '#ff79c6',
      cyan: '#8be9fd',
      white: '#f8f8f2',
      brightBlack: '#6272a4',
      brightRed: '#ff6e6e',
      brightGreen: '#69ff94',
      brightYellow: '#ffffa5',
      brightBlue: '#d6acff',
      brightMagenta: '#ff92df',
      brightCyan: '#a4ffff',
      brightWhite: '#ffffff',
    },
    catppuccin: {
      background: '#1e1e2e',
      foreground: '#cdd6f4',
      cursor: '#cba6f7',
      cursorAccent: '#1e1e2e',
      selectionBackground: 'rgba(203, 166, 247, 0.35)',
      selectionForeground: '#1e1e2e',
      black: '#45475a',
      red: '#f38ba8',
      green: '#a6e3a1',
      yellow: '#f9e2af',
      blue: '#89b4fa',
      magenta: '#cba6f7',
      cyan: '#94e2d5',
      white: '#bac2de',
      brightBlack: '#585b70',
      brightRed: '#f38ba8',
      brightGreen: '#a6e3a1',
      brightYellow: '#f9e2af',
      brightBlue: '#89b4fa',
      brightMagenta: '#f5c2e7',
      brightCyan: '#94e2d5',
      brightWhite: '#a6adc8',
    },
    tokyo_night: {
      background: '#1a1b26',
      foreground: '#c0caf5',
      cursor: '#7aa2f7',
      cursorAccent: '#1a1b26',
      selectionBackground: 'rgba(122, 162, 247, 0.35)',
      selectionForeground: '#1a1b26',
      black: '#15161e',
      red: '#f7768e',
      green: '#9ece6a',
      yellow: '#e0af68',
      blue: '#7aa2f7',
      magenta: '#bb9af7',
      cyan: '#7dcfff',
      white: '#a9b1d6',
      brightBlack: '#414868',
      brightRed: '#f7768e',
      brightGreen: '#9ece6a',
      brightYellow: '#e0af68',
      brightBlue: '#7aa2f7',
      brightMagenta: '#bb9af7',
      brightCyan: '#7dcfff',
      brightWhite: '#c0caf5',
    },
    one_dark: {
      background: '#282c34',
      foreground: '#abb2bf',
      cursor: '#61afef',
      cursorAccent: '#282c34',
      selectionBackground: 'rgba(97, 175, 239, 0.35)',
      selectionForeground: '#282c34',
      black: '#1e2127',
      red: '#e06c75',
      green: '#98c379',
      yellow: '#e5c07b',
      blue: '#61afef',
      magenta: '#c678dd',
      cyan: '#56b6c2',
      white: '#abb2bf',
      brightBlack: '#5c6370',
      brightRed: '#e06c75',
      brightGreen: '#98c379',
      brightYellow: '#e5c07b',
      brightBlue: '#61afef',
      brightMagenta: '#c678dd',
      brightCyan: '#56b6c2',
      brightWhite: '#ffffff',
    },
  };

  const LIGHT_PALETTE = {
    background: '#fafafa',
    foreground: '#18181b',
    cursor: '#0891b2',
    cursorAccent: '#fafafa',
    selectionBackground: 'rgba(8, 145, 178, 0.25)',
    selectionForeground: '#18181b',
    black: '#18181b',
    red: '#e11d48',
    green: '#059669',
    yellow: '#d97706',
    blue: '#2563eb',
    magenta: '#7c3aed',
    cyan: '#0891b2',
    white: '#e4e4e7',
    brightBlack: '#71717a',
    brightRed: '#f43f5e',
    brightGreen: '#10b981',
    brightYellow: '#f59e0b',
    brightBlue: '#3b82f6',
    brightMagenta: '#8b5cf6',
    brightCyan: '#06b6d4',
    brightWhite: '#fafafa',
  };


  // ─── Terminal themes ─────────────────────────────────────────────────────
  function activeTheme() {
    const isLight = !root.classList.contains('dark');
    if (isLight && !CFG.console_always_dark) return LIGHT_PALETTE;
    return PALETTES[CFG.console_palette || 'neonglass'] || PALETTES.neonglass;
  }

  // Font / line height / cursor from the Studio terminal settings (empty = keep Pelican's default)
  function applyTerminalOptions(term) {
    const o = term && term.options;
    if (!o || typeof o !== 'object') return;
    let changed = false;
    const set = function (k, v) { if (o[k] !== v) { o[k] = v; changed = true; } };
    if (Number.isFinite(CFG.term_size)) set('fontSize', CFG.term_size);
    if (Number.isFinite(CFG.term_leading)) set('lineHeight', CFG.term_leading / 100);
    if (['bar', 'block', 'underline'].indexOf(CFG.term_cursor) !== -1) set('cursorStyle', CFG.term_cursor);
    if (CFG.term_blink === 'on') set('cursorBlink', true);
    else if (CFG.term_blink === 'off') set('cursorBlink', false);
    if (changed) { try { window.dispatchEvent(new Event('resize')); } catch (e) { /* ignore */ } }
  }

  const terminals = new Set();
  const applyTheme = safe(function (term) {
    if (!term) return;
    const theme = activeTheme();
    if (term.options && typeof term.options === 'object') term.options.theme = theme; // xterm >= 5
    else if (typeof term.setOption === 'function') term.setOption('theme', theme);
    else return;
    applyTerminalOptions(term);
    terminals.add(term);
  });

  const applyTerminals = safe(function () {
    const el = document.getElementById('terminal');
    if (!el) return;
    if (el._xterm) applyTheme(el._xterm);
    if (window.terminal) applyTheme(window.terminal);
  });

  new MutationObserver(() => terminals.forEach((t) => applyTheme(t))).observe(root, {
    attributes: true,
    attributeFilter: ['class'],
  });

  // ─── Tab title suffix ────────────────────────────────────────────────────
  const ensureSuffix = safe(function () {
    if (!CFG.tab_title_suffix) return;
    if (!document.title.endsWith('NeonGlass')) {
      document.title = document.title.replace(/\s*·\s*NeonGlass$/, '') + ' · NeonGlass';
    }
  });

  // ─── Dashboard hero ──────────────────────────────────────────────────────
  const ICONS = {
    grid: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
    list: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
    compact: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="5" height="5" rx="1"/><rect x="10" y="3" width="5" height="5" rx="1"/><rect x="17" y="3" width="4" height="5" rx="1"/><rect x="3" y="10" width="5" height="5" rx="1"/><rect x="10" y="10" width="5" height="5" rx="1"/><rect x="17" y="10" width="4" height="5" rx="1"/></svg>',
  };

  function greeting() {
    const h = new Date().getHours();
    if (h < 5) return T.js_greet_night || 'Late night';
    if (h < 12) return T.js_greet_morning || 'Good morning';
    if (h < 19) return T.js_greet_afternoon || 'Good afternoon';
    return T.js_greet_evening || 'Good evening';
  }

  function classify(card) {
    if (card.querySelector('.fi-badge.fi-color-success, .fi-badge[class*="success"]')) return 'ok';
    if (card.querySelector('.fi-badge.fi-color-danger, .fi-badge[class*="danger"]')) return 'bad';
    if (card.querySelector('.fi-badge.fi-color-warning, .fi-badge[class*="warning"]')) return 'busy';
    return null;
  }

  function chip(kind, label) {
    const c = document.createElement('span');
    c.className = 'ng-chip' + (kind ? ' ng-chip--' + kind : '');
    c.dataset.kind = kind || 'total';
    const dot = document.createElement('i');
    const n = document.createElement('b');
    const l = document.createElement('span');
    l.textContent = label;
    c.append(dot, n, l);
    return c;
  }

  function buildHero() {
    const hero = document.createElement('section');
    hero.id = 'ng-hero';
    hero.className = 'ng-hero';
    hero.setAttribute('aria-label', T.js_dashboard || 'Dashboard');

    const main = document.createElement('div');
    main.className = 'ng-hero__main';
    const eyebrow = document.createElement('div');
    eyebrow.className = 'ng-hero__eyebrow';
    eyebrow.dataset.role = 'eyebrow';
    const title = document.createElement('h2');
    title.className = 'ng-hero__title';
    title.dataset.role = 'title';
    const sub = document.createElement('p');
    sub.className = 'ng-hero__sub';
    sub.dataset.role = 'sub';
    main.append(eyebrow, title, sub);

    const side = document.createElement('div');
    side.className = 'ng-hero__side';
    const chips = document.createElement('div');
    chips.className = 'ng-chips';
    chips.append(
      chip('', T.js_servers || 'servers'),
      chip('ok', T.js_online || 'online'),
      chip('busy', T.js_busy || 'busy'),
      chip('bad', T.js_offline || 'offline')
    );

    const tools = document.createElement('div');
    tools.className = 'ng-tools';
    const kbd = document.createElement('span');
    kbd.className = 'ng-kbd';
    const k = document.createElement('kbd');
    k.textContent = '/';
    kbd.append(k, document.createTextNode(T.js_search_hint || 'Search'));

    const seg = document.createElement('div');
    seg.className = 'ng-seg';
    seg.setAttribute('role', 'group');
    seg.setAttribute('aria-label', T.js_layout || 'Layout');
    LAYOUTS.forEach((name) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.dataset.layout = name;
      b.title = T['js_' + name] || name;
      b.setAttribute('aria-label', b.title);
      b.setAttribute('aria-pressed', root.getAttribute('data-ng-layout') === name ? 'true' : 'false');
      b.innerHTML = ICONS[name]; // static markup, no user data
      b.addEventListener('click', () => {
        store.set('ng-layout', name);
        applyLayout(name);
      });
      seg.append(b);
    });
    tools.append(kbd, seg);
    side.append(chips, tools);
    hero.append(main, side);
    return hero;
  }

  function removeHero() {
    const h = document.getElementById('ng-hero');
    if (h) h.remove();
    if (root.hasAttribute('data-ng-hero')) root.removeAttribute('data-ng-hero');
  }

  const ensureHero = safe(function () {
    if (root.getAttribute('data-ng-page') !== 'server-list' || CFG.dashboard_hero === false) {
      return removeHero();
    }
    const table = document.querySelector('.fi-main .fi-ta');
    if (!table) return removeHero();

    // Insert next to the Livewire component root — never inside it.
    const anchor = table.closest('[wire\\:id]') || table.parentElement;
    if (!anchor || !anchor.parentElement) return;

    let hero = document.getElementById('ng-hero');
    if (!hero) {
      hero = buildHero();
      anchor.parentElement.insertBefore(hero, anchor);
    }
    if (root.getAttribute('data-ng-hero') !== '1') root.setAttribute('data-ng-hero', '1');

    const cards = Array.from(document.querySelectorAll('.fi-main .fi-ta-record'));
    const counts = { ok: 0, bad: 0, busy: 0 };
    cards.forEach((c) => { const k = classify(c); if (k) counts[k]++; });
    const known = counts.ok + counts.bad + counts.busy;

    setText(hero.querySelector('[data-role="eyebrow"]'), greeting());
    setText(
      hero.querySelector('[data-role="title"]'),
      CFG.user ? (T.js_hello || 'Hello, :name').replace(':name', CFG.user) : (T.js_welcome || 'Welcome back')
    );
    setText(
      hero.querySelector('[data-role="sub"]'),
      cards.length
        ? cards.length + ' ' + (cards.length === 1 ? (T.js_server_one || 'server') : (T.js_servers || 'servers'))
        : (T.js_empty || 'No servers yet')
    );

    const set = (kind, n, show) => {
      const c = hero.querySelector('.ng-chip[data-kind="' + kind + '"]');
      if (!c) return;
      setText(c.querySelector('b'), String(n));
      if (c.hidden === show) c.hidden = !show;
    };
    set('total', cards.length, cards.length > 0);
    set('ok', counts.ok, known > 0 && counts.ok > 0);
    set('busy', counts.busy, known > 0 && counts.busy > 0);
    set('bad', counts.bad, known > 0 && counts.bad > 0);
  });

  // "/" focuses the table search, like most dashboards do.
  document.addEventListener('keydown', safe(function (e) {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
    if (root.getAttribute('data-ng-page') !== 'server-list') return;
    const t = e.target;
    if (t && (/^(input|textarea|select)$/i.test(t.tagName) || t.isContentEditable)) return;
    const input = document.querySelector('.fi-ta-search-field input, .fi-ta-search input');
    if (input) { e.preventDefault(); input.focus(); }
  }));

  // Cursor spotlight on server cards (mouse only, skipped with reduced motion).
  if (!REDUCED) {
    let raf = 0;
    document.addEventListener('pointermove', safe(function (e) {
      if (e.pointerType && e.pointerType !== 'mouse') return;
      if (raf || root.getAttribute('data-ng-page') !== 'server-list') return;
      const card = e.target.closest && e.target.closest('.fi-ta-record');
      if (!card) return;
      raf = requestAnimationFrame(() => {
        raf = 0;
        const r = card.getBoundingClientRect();
        card.style.setProperty('--mx', e.clientX - r.left + 'px');
        card.style.setProperty('--my', e.clientY - r.top + 'px');
      });
    }), { passive: true });
  }

  // ─── Particles container (PHP renders it; this is only a fallback) ───────
  const ensureParticles = safe(function () {
    if (CFG.particles === 'off' || !document.body) return;
    const bg = document.getElementById('ng-bg');
    if (bg && !bg.querySelector('.ng-particles')) {
      const el = document.createElement('div');
      el.className = 'ng-particles';
      el.setAttribute('aria-hidden', 'true');
      bg.append(el);
    }
  });

  // ─── Dark / light toggle ─────────────────────────────────────────────────
  const createThemeToggle = safe(function () {
    if (!document.body || document.getElementById('ng-theme-toggle')) return;

    const btn = document.createElement('button');
    btn.id = 'ng-theme-toggle';
    btn.type = 'button';
    btn.setAttribute('aria-label', 'Toggle dark mode');
    btn.title = 'Toggle dark / light mode';
    btn.innerHTML =
      '<svg class="ng-icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path></svg>' +
      '<svg class="ng-icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';

    btn.addEventListener('click', () => {
      const dark = !root.classList.contains('dark');
      root.classList.toggle('dark', dark);
      root.style.colorScheme = dark ? 'dark' : 'light';
      store.set('theme', dark ? 'dark' : 'light');
      store.set('filament_theme', dark ? 'dark' : 'light');
      window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: dark ? 'dark' : 'light' } }));
      terminals.forEach((t) => applyTheme(t));
    });

    document.body.appendChild(btn);
  });

  // ─── One throttled tick instead of many unthrottled observers ────────────
  const tick = safe(function () {
    markRoute();
    applyTerminals();
    ensureHero();
    ensureParticles();
    ensureSuffix();
  });

  let queued = false;
  const schedule = () => {
    if (queued) return;
    queued = true;
    requestAnimationFrame(() => { queued = false; fixMissingTranslations(); tick(); });
  };


  // ─── Missing Pelican translation keys (raw keys shown in UI) ─────────────
  const KEY_FIXES = {
    'server/user.permissions.versions_title': 'Versions',
    'server/user.permissions.versions': 'Versions',
  };
  const fixMissingTranslations = safe(function (rootEl) {
    const scope = rootEl || document;
    const nodes = scope.querySelectorAll
      ? scope.querySelectorAll('.fi-tabs-item, .fi-fo-tabs-item, button, a, span, label, [role="tab"]')
      : [];
    nodes.forEach((el) => {
      if (!el || el.children.length > 2) return;
      const raw = (el.textContent || '').trim();
      if (KEY_FIXES[raw]) {
        // Prefer replacing only text nodes to keep icons
        let done = false;
        el.childNodes.forEach((n) => {
          if (n.nodeType === 3 && n.textContent && n.textContent.includes(raw)) {
            n.textContent = n.textContent.replace(raw, KEY_FIXES[raw]);
            done = true;
          }
        });
        if (!done && el.childElementCount === 0) el.textContent = KEY_FIXES[raw];
      }
    });
  });

  const boot = safe(function () {
    fixMissingTranslations();
    tick();
    new MutationObserver((muts) => {
      for (const m of muts) {
        const t = m.target;
        // Console output churns constantly; ignore it.
        if (t && t.nodeType === 1 && t.closest && t.closest('.xterm')) continue;
        schedule();
        break;
      }
    }).observe(document.documentElement, { childList: true, subtree: true });
  });

  if (document.body) boot();
  else document.addEventListener('DOMContentLoaded', boot);

  document.addEventListener('livewire:navigated', () => {
    markRoute();
    applyLayout(root.getAttribute('data-ng-layout'));
    setTimeout(() => { fixMissingTranslations(); tick(); }, 60);
  });
})();
