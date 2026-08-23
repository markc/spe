// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Drive a fresh, FULLSCREEN Zen (Firefox) via geckodriver through a chapter's
// scenes (from episodes/<chapter>.json), holding each for its narration length.
// lane 'app'  -> the running page at BASE/<chapter><app-path>
// lane 'code' -> the native GitHub blob, lines highlighted.
// gpu-screen-recorder captures externally; assembly syncs to the first change.
//
// Look is FORCED via prefs (below), not inherited from your live profile — the
// driver's profile snapshot was stale (came up 120% + dark). By default we use a
// clean, disposable profile so nothing touches your real ~/.zen, and GitHub renders
// logged-out + light (a cleaner tutorial view).
//
// ZOOM NOTE: layout.css.devPixelsPerPx sets the ABSOLUTE device-px-per-CSS-px ratio,
// so effective CSS width = captured_framebuffer_px / DPR. This 4K desk captures at
// 3840 px and the target is ~1280 CSS wide (the confirmed 150% fullscreen view), so
// DPR = 3840 / 1280 = 3.0. The driver logs the resulting innerWidth to driver.log —
// nudge DPR if it's off (higher DPR = narrower/bigger; lower = wider/smaller). Env:
//   DPR=3.0              device-px per CSS-px (framebuffer_px / desired_CSS_width)
//   USE_REAL_PROFILE=1   launch against $PROFILE instead of a fresh one
//
//   BASE=http://127.0.0.1:8000 node real-zen.mjs <chapter>

import { Builder } from 'selenium-webdriver';
import firefox from 'selenium-webdriver/firefox.js';
import { readFileSync } from 'fs';
import { homedir } from 'os';

const CHAP = process.argv[2] || '01-Simple';
const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const REPO = process.env.REPO || 'markc/spe';
const PROFILE = process.env.PROFILE || `${homedir()}/.zen/212lt933.Default Profile`;
const ZEN_BIN = process.env.ZEN_BIN || '/opt/zen-browser-bin/zen-bin';
const DPR = process.env.DPR || '3.0';                         // 3840/1280 = 3.0 -> 1280 CSS wide
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

const opts = new firefox.Options();
opts.setBinary(ZEN_BIN);
if (USE_REAL_PROFILE) opts.addArguments('-profile', PROFILE);  // else: clean, disposable profile
opts.setPreference('browser.aboutConfig.showWarning', false);
// Force the look deterministically (profile snapshots came up 120% + dark):
opts.setPreference('layout.css.devPixelsPerPx', DPR);                      // OS_SCALE * PAGE_ZOOM
opts.setPreference('layout.css.prefers-color-scheme.content-override', 1); // 1 = light -> GitHub light
opts.setPreference('ui.systemUsesDarkTheme', 0);
opts.setPreference('browser.theme.content-theme', 1);                      // light content theme
// A fresh profile has no session to restore and no first-run tab to steal focus:
opts.setPreference('browser.startup.page', 0);
opts.setPreference('browser.aboutwelcome.enabled', false);
opts.setPreference('browser.sessionstore.resume_from_crash', false);

const driver = await new Builder().forBrowser('firefox').setFirefoxOptions(opts).build();

try {
  await driver.manage().window().fullscreen();   // F11: no chrome, no sidebar
  await sleep(700);

  // Report the effective layout so you can sanity-check the width in driver.log.
  const w = await driver.executeScript('return window.innerWidth');
  console.error(`look: devPixelsPerPx=${DPR} -> ${w} CSS px wide (target 1280), light forced`);

  // blank pre-roll while the recorder settles; the jump to scene 1 is the first
  // scene-change the assembler locks onto.
  await driver.get('about:blank');
  await sleep(2500);

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
