// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Drive a chromeless KIOSK Firefox via geckodriver through a chapter's scenes (from
// episodes/<chapter>.json), holding each for its narration length.
// lane 'app'  -> the running page at BASE/<chapter><app-path>
// lane 'code' -> the native GitHub blob, lines highlighted.
// gpu-screen-recorder captures externally; assembly syncs to the first change.
//
// WHY FIREFOX, NOT ZEN: Firefox -kiosk is genuinely chromeless (no tabs/toolbar/
// sidebar) and has no blocking first-run wizard, so a disposable profile Just Works.
// Zen paints its OWN sidebar that -kiosk can't hide, and shows a wizard on new
// profiles. The rendered page + GitHub view are pixel-identical (same Gecko engine),
// and kiosk hides all chrome anyway — so nothing is lost. Point BIN elsewhere to override.
//
// KIOSK (default on): chromeless + fullscreen. KIOSK=0 falls back to window fullscreen().
//
// ZOOM: layout.css.devPixelsPerPx is the ABSOLUTE device-px-per-CSS-px ratio, so
// effective CSS width = captured_framebuffer_px / DPR. This 4K desk captures at 3840 px
// and the target is 1024 CSS wide (confirmed 150% view: 3840 /2.5 OS /1.5 zoom = 1024x576),
// so DPR = 3840/1024 = 3.75. Driver logs screen+viewport dims to driver.log. Env:
//   DPR=3.75             device-px per CSS-px (framebuffer_px / desired_CSS_width)
//   KIOSK=0              use window fullscreen() instead of kiosk
//   BIN=/path/to/firefox override the browser binary (needs application.ini beside it)
//
//   BASE=http://127.0.0.1:8000 node drive-firefox.mjs <chapter>

import { Builder } from 'selenium-webdriver';
import firefox from 'selenium-webdriver/firefox.js';
import { readFileSync, writeFileSync } from 'fs';

const CHAP = process.argv[2] || '01-Simple';
const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const REPO = process.env.REPO || 'markc/spe';
const BIN = process.env.BIN || process.env.ZEN_BIN || '/usr/lib/firefox/firefox';
const DPR = process.env.DPR || '3.75';                        // 3840/1024 = 3.75 -> 1024 CSS wide
const KIOSK = process.env.KIOSK !== '0';                      // default on: chromeless capture
const CODE_LANE = process.env.CODE_LANE || 'local';          // 'local' self-contained | 'github'

const root = new URL('..', import.meta.url).pathname;               // 00-Tutorial/
const ep = JSON.parse(readFileSync(`${root}episodes/${CHAP}.json`, 'utf8'));
const durs = JSON.parse(readFileSync(`/tmp/ep/${CHAP}/durations.json`, 'utf8'));

// Code lane = the native GitHub blob (forced light via prefs below). The #L anchor
// highlights + jumps to the lines; we hide GitHub's chrome then ease onto them.
const blob = (file, hl) => {
  const [a, b] = String(hl).split('-');
  return `https://github.com/${REPO}/blob/main/${file}${b ? `#L${a}-L${b}` : `#L${a}`}`;
};
// The logged-out blob view shows a LEFT file-tree pane and can open a RIGHT "Symbols"
// pane — both eat width and intrude. The capture profile is fresh (logged out, no saved
// prefs) every run, so we can't rely on GitHub remembering them collapsed: collapse both
// each time (click the toggles) and CSS-hide any residual pane. Selectors are defensive.
const HIDE_GH_CHROME = `
  try {
    [...document.querySelectorAll('button,[role="button"],a')].forEach(b => {
      const l = ((b.getAttribute('aria-label')||'') + ' ' + (b.title||'')).toLowerCase();
      if (l.includes('collapse file tree') || l.includes('hide file tree')) b.click();  // left tree
      if (l.includes('symbol') && (b.getAttribute('aria-expanded')==='true' || b.getAttribute('aria-pressed')==='true')) b.click();  // right symbols
    });
  } catch (e) {}
  const st = document.createElement('style'); st.id = 'cap-hide-gh';
  st.textContent = '.Layout-sidebar,[data-testid="file-tree"],[data-testid="repos-file-tree"],'
    + '[class*="FileTree"],[data-testid="symbols-pane"],[data-testid="symbol-pane"],'
    + '[class*="SymbolsPane"],[class*="symbols-pane"]{display:none!important}';
  document.head.appendChild(st);
`;
// Eased scroll ONTO the highlighted first line. Target the line element itself (GitHub
// resets scroll to top during its React render, so reading pageYOffset was giving 0 and
// easing "to the top"). Falls back to no-move if the element isn't found.
const scrollToLine = (first) => `
  (function(){
    var el = document.querySelector('#L${first}, #LC${first}, [data-line-number="${first}"]');
    var target = el ? (el.getBoundingClientRect().top + window.pageYOffset - window.innerHeight*0.35)
                    : window.pageYOffset;
    target = Math.max(0, target);
    var from = window.pageYOffset, dur = 1400, t0 = performance.now();
    var ease = function(t){ return t < 0.5 ? 2*t*t : 1 - Math.pow(-2*t+2,2)/2; };
    (function step(now){ var p = Math.min(1,(now-t0)/dur); window.scrollTo(0, from + (target-from)*ease(p)); if (p<1) requestAnimationFrame(step); })(performance.now());
  })();
`;

// Self-contained code page (CODE_LANE=local, the default). GitHub's React blob view
// fights scripted scrolling (repeated scroll-to-top during hydration) and shows left/
// right panes; this renders the real source ourselves as static HTML — GitHub-light
// styling, syntax highlighting, one eased scroll that lands and STAYS. No React, no
// virtualization, no panes, no network to github.com. Written per scene to /tmp and
// loaded via file://. highlight.js/theme come from the CDN (same as the old codeview).
const repoRoot = `${root}..`;
const langOf = (f) => f.endsWith('.php') ? 'php' : f.endsWith('.json') ? 'json'
  : f.endsWith('.sql') ? 'sql' : f.endsWith('.css') ? 'css'
  : f.endsWith('.js') ? 'javascript' : 'plaintext';
const codePage = (file, hl) => {
  const [a, b] = String(hl).split('-').map(Number);
  const lo = a, hi = b || a;
  const src = readFileSync(`${repoRoot}/${file}`, 'utf8').replace(/\n$/, '');
  const html = `<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>${file}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<style>
:root{color-scheme:light}*{margin:0;padding:0;box-sizing:border-box}
html,body{background:#fff}
body{font:16px/1.6 "JetBrains Mono","DejaVu Sans Mono",monospace;padding:28px 44px;color:#1f2328}
.bar{color:#656d76;font-size:14px;margin-bottom:18px;border-bottom:1px solid #d0d7de;padding-bottom:14px}
.code{display:table;border-collapse:collapse;width:100%}
.line{display:table-row}
.ln{display:table-cell;text-align:right;color:#8c959f;user-select:none;padding:0 24px 0 0;white-space:nowrap;width:1%}
.src{display:table-cell;white-space:pre}
.line.hl{background:#fff8c5}
.line.hl .ln{color:#59636e;box-shadow:inset 3px 0 #d4a72c}
.hljs{background:transparent}
</style></head><body>
<div class="bar">spe/${file}</div>
<div class="code" id="code"></div>
<script>
const SRC=${JSON.stringify(src).replace(/<\//g, '<\\/')}, LO=${lo}, HI=${hi}, LANG=${JSON.stringify(langOf(file))};
const code=document.getElementById('code');
SRC.split('\\n').forEach((line,i)=>{const n=i+1;const row=document.createElement('div');row.className='line'+(n>=LO&&n<=HI?' hl':'');const g=document.createElement('span');g.className='ln';g.textContent=n;const s=document.createElement('span');s.className='src';s.innerHTML=line?hljs.highlight(line,{language:LANG}).value:'&nbsp;';row.append(g,s);code.appendChild(row);});
const first=code.querySelector('.line.hl');
if(first){const target=Math.max(0,first.offsetTop-innerHeight*0.35);const start=pageYOffset,dist=target-start,dur=1400,t0=performance.now();const ease=t=>(t<0.5?2*t*t:1-Math.pow(-2*t+2,2)/2);requestAnimationFrame(function step(now){const p=Math.min(1,(now-t0)/dur);scrollTo(0,start+dist*ease(p));if(p<1)requestAnimationFrame(step);});}
</script></body></html>`;
  const out = `/tmp/ep/${CHAP}/code-${lo}-${hi}.html`;
  writeFileSync(out, html);
  return `file://${out}`;
};
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Force the app shell OPEN for the capture. base.js hides pinned sidebars via a
// responsive matchMedia(960) listener that can fire spuriously under the driven
// browser (devPixelsPerPx + load-time width races), so we don't rely on pinSidebar;
// this style pins both panels open and offsets main, immune to that listener. It's a
// no-op on chapters with no sidebars (01-Simple) — the selectors simply match nothing.
const FORCE_SHELL = `
  if (document.querySelector('.sidebar-left')) {
    var id = 'cap-force-shell';
    if (!document.getElementById(id)) {
      var st = document.createElement('style'); st.id = id;
      st.textContent = '.sidebar-left,.sidebar-right{transform:translateX(0)!important;position:fixed!important}'
        + 'main{margin-inline:var(--sidebar-width)!important}.overlay{display:none!important}';
      document.head.appendChild(st);
    }
  }
`;

// Disposable geckodriver-managed profile (no -profile), so setPreference applies
// cleanly. Firefox has no blocking wizard; we still silence the welcome/first-run.
const opts = new firefox.Options();
opts.setBinary(BIN);
if (KIOSK) opts.addArguments('-kiosk');   // chromeless: no tabs, no toolbar, no sidebar
opts.setPreference('layout.css.devPixelsPerPx', DPR);                      // -> 1024 CSS wide
opts.setPreference('layout.css.prefers-color-scheme.content-override', 1); // 1 = light -> GitHub light
opts.setPreference('ui.systemUsesDarkTheme', 0);
opts.setPreference('browser.theme.content-theme', 1);
opts.setPreference('browser.aboutwelcome.enabled', false);
opts.setPreference('browser.startup.page', 0);
opts.setPreference('browser.startup.homepage_override.mstone', 'ignore');
opts.setPreference('browser.sessionstore.resume_from_crash', false);
opts.setPreference('browser.shell.checkDefaultBrowser', false);
opts.setPreference('datareporting.policy.firstRunURL', '');

const driver = await new Builder().forBrowser('firefox').setFirefoxOptions(opts).build();

try {
  if (!KIOSK) await driver.manage().window().fullscreen();   // kiosk is already chromeless+fullscreen
  await sleep(700);

  // blank pre-roll while the recorder settles; the jump to scene 1 is the first
  // scene-change the assembler locks onto.
  await driver.get('about:blank');
  await sleep(2500);

  // Report the effective layout. screen.width is reliable regardless of page-load
  // state; innerWidth confirms the actual viewport (0 => window never got sized).
  const dims = await driver.executeScript(
    'return [window.screen.width, window.screen.height, window.innerWidth, window.innerHeight]');
  console.error(`look: bin=${BIN} kiosk=${KIOSK} devPixelsPerPx=${DPR} -> screen ${dims[0]}x${dims[1]}, viewport ${dims[2]}x${dims[3]} (target 1024x576), light forced`);

  for (let i = 0; i < ep.scenes.length; i++) {
    const s = ep.scenes[i];
    const start = Date.now();
    if (s.lane === 'code') {
      if (CODE_LANE === 'github') {
        await driver.get(blob(s.file, s.hl));         // native GitHub blob (opt-in)
        await sleep(1400);
        await driver.executeScript(HIDE_GH_CHROME);   // collapse left tree + right symbols pane
        await sleep(300);
        await driver.executeScript(scrollToLine(String(s.hl).split('-')[0]));
      } else {
        await driver.get(codePage(s.file, s.hl));     // self-contained page: eases on, stays put
        await sleep(300);
      }
    } else {
      await driver.get(`${BASE}/${CHAP}${s.app || '/'}`);
      await sleep(300);
      // One pass: force the shell open (CSS, immune to base.js responsive un-pinning),
      // run any per-scene interaction (scheme/theme/toast), and report diagnostics.
      const info = await driver.executeScript(`
        ${FORCE_SHELL}
        var out = { iw: window.innerWidth, base: !!window.Base,
                    sbLeft: !!document.querySelector('.sidebar-left'),
                    forced: !!document.getElementById('cap-force-shell') };
        ${s.js ? `try { ${s.js} out.ok = true; } catch (e) { out.ok = false; out.err = String(e); }` : ''}
        out.body = document.body.className;
        return out;
      `);
      console.error(`scene ${i} app -> iw=${info.iw} base=${info.base} sbLeft=${info.sbLeft} forced=${info.forced}${s.js ? ` js.ok=${info.ok}${info.err ? ' err=' + info.err : ''}` : ''} body="${info.body}"`);
      await sleep(500);
    }
    const left = durs[i] * 1000 - (Date.now() - start);
    if (left > 0) await sleep(left);
  }
  await sleep(400);
} finally {
  await driver.quit();
}
console.log(`firefox driver done: ${CHAP}, ${ep.scenes.length} scenes`);
