// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Drive a fresh, FULLSCREEN Zen (Firefox) via geckodriver through a chapter's
// scenes (from episodes/<chapter>.json), holding each for its narration length.
// lane 'app'  -> the running page at BASE/<chapter><app-path>
// lane 'code' -> the native GitHub blob, lines highlighted.
// gpu-screen-recorder captures externally; assembly syncs to the first change.
//
// PROFILE: a DEDICATED, PERSISTENT capture profile (default ~/.zen-capture) — set up
// once (complete Zen's first-run wizard, hide its sidebar), reused every run. Not your
// daily ~/.zen: no pollution, no lock-contention with your running Zen. We (re)seed its
// user.js on every launch so the look below is always applied, whatever else is in it.
//
// KIOSK (default on): launch chromeless — no toolbar, no Zen sidebar, fullscreen. This
// is what guarantees a clean capture; F11-style fullscreen() alone does NOT hide Zen's
// sidebar. KIOSK=0 falls back to window fullscreen() (then hide the sidebar in-profile).
//
// ZOOM: layout.css.devPixelsPerPx is the ABSOLUTE device-px-per-CSS-px ratio, so
// effective CSS width = captured_framebuffer_px / DPR. This 4K desk captures at 3840 px
// and the target is 1024 CSS wide (confirmed 150% view: 3840 /2.5 OS /1.5 zoom = 1024x576),
// so DPR = 3840/1024 = 3.75. Driver logs the resulting innerWidth to driver.log. Env:
//   DPR=3.75             device-px per CSS-px (framebuffer_px / desired_CSS_width)
//   KIOSK=0              use window fullscreen() instead of kiosk
//   CAPTURE_PROFILE=dir  override the dedicated profile path
//   USE_REAL_PROFILE=1   use your daily $PROFILE instead (not recommended)
//
//   BASE=http://127.0.0.1:8000 node real-zen.mjs <chapter>

import { Builder } from 'selenium-webdriver';
import firefox from 'selenium-webdriver/firefox.js';
import { readFileSync, mkdirSync, writeFileSync } from 'fs';
import { homedir } from 'os';

const CHAP = process.argv[2] || '01-Simple';
const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const REPO = process.env.REPO || 'markc/spe';
const PROFILE = process.env.PROFILE || `${homedir()}/.zen/212lt933.Default Profile`;
const CAPTURE_PROFILE = process.env.CAPTURE_PROFILE || `${homedir()}/.zen-capture`;
const ZEN_BIN = process.env.ZEN_BIN || '/opt/zen-browser-bin/zen-bin';
const DPR = process.env.DPR || '3.75';                        // 3840/1024 = 3.75 -> 1024 CSS wide
const KIOSK = process.env.KIOSK !== '0';                      // default on: chromeless capture
const USE_REAL_PROFILE = process.env.USE_REAL_PROFILE === '1';

const root = new URL('..', import.meta.url).pathname;               // 00-Tutorial/
const ep = JSON.parse(readFileSync(`${root}episodes/${CHAP}.json`, 'utf8'));
const durs = JSON.parse(readFileSync(`/tmp/ep/${CHAP}/durations.json`, 'utf8'));

// Code lane = the native GitHub blob (forced light via prefs above; sidebars auto-hide
// at this zoom). The #L anchor highlights + jumps to the lines; we then ease onto them.
const blob = (file, hl) => {
  const [a, b] = String(hl).split('-');
  return `https://github.com/${REPO}/blob/main/${file}${b ? `#L${a}-L${b}` : `#L${a}`}`;
};
// Small eased "settle" onto the highlighted lines: nudge up, glide back down.
const EASE_ONTO = `
  const target = window.pageYOffset;
  window.scrollTo(0, Math.max(0, target - 280));
  const from = window.pageYOffset, dur = 1400, t0 = performance.now();
  const ease = t => (t < 0.5 ? 2*t*t : 1 - Math.pow(-2*t+2,2)/2);
  (function step(now){ const p = Math.min(1,(now-t0)/dur); window.scrollTo(0, from + (target-from)*ease(p)); if (p<1) requestAnimationFrame(step); })(performance.now());
`;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// The dedicated capture profile. Seed user.js so our look is (re)applied on every
// launch, authoritative over anything geckodriver or the profile itself sets. user.js
// only holds THESE keys — it does not touch the wizard/sidebar settings you set once.
const profileDir = USE_REAL_PROFILE ? PROFILE : CAPTURE_PROFILE;
mkdirSync(profileDir, { recursive: true });
writeFileSync(`${profileDir}/user.js`, [
  `user_pref("layout.css.devPixelsPerPx", "${DPR}");`,                      // 1024 CSS wide
  `user_pref("layout.css.prefers-color-scheme.content-override", 1);`,      // 1 = light -> GitHub light
  `user_pref("ui.systemUsesDarkTheme", 0);`,
  `user_pref("browser.theme.content-theme", 1);`,
  `user_pref("browser.aboutConfig.showWarning", false);`,
  `user_pref("browser.startup.page", 0);`,
  `user_pref("browser.sessionstore.resume_from_crash", false);`,
  `user_pref("browser.shell.checkDefaultBrowser", false);`,
  '',
].join('\n'));

const opts = new firefox.Options();
opts.setBinary(ZEN_BIN);
opts.addArguments('-profile', profileDir);
if (KIOSK) opts.addArguments('-kiosk');   // chromeless: no toolbar, no Zen sidebar

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
  console.error(`look: kiosk=${KIOSK} devPixelsPerPx=${DPR} -> screen ${dims[0]}x${dims[1]}, viewport ${dims[2]}x${dims[3]} (target 1024x576), light forced`);

  for (let i = 0; i < ep.scenes.length; i++) {
    const s = ep.scenes[i];
    const start = Date.now();
    if (s.lane === 'code') {
      await driver.get(blob(s.file, s.hl));       // GitHub highlights + jumps to the lines
      await sleep(1400);                          // let the heavy page render/settle
      await driver.executeScript(EASE_ONTO);      // then glide onto the highlighted lines
    } else {
      await driver.get(`${BASE}/${CHAP}${s.app || '/'}`);
      await sleep(300);
    }
    const left = durs[i] * 1000 - (Date.now() - start);
    if (left > 0) await sleep(left);
  }
  await sleep(400);
} finally {
  await driver.quit();
}
console.log(`zen driver done: ${CHAP}, ${ep.scenes.length} scenes`);
