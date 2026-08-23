// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Drive a fresh, FULLSCREEN Zen (Firefox) via geckodriver through a chapter's
// scenes (from episodes/<chapter>.json), holding each for its narration length.
// lane 'app'  -> the running page at BASE/<chapter><app-path>
// lane 'code' -> the native GitHub blob, lines highlighted (light theme = your
//                GitHub appearance setting).
// gpu-screen-recorder captures externally; assembly syncs to the first change.
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

const root = new URL('..', import.meta.url).pathname;               // 00-Tutorial/
const ep = JSON.parse(readFileSync(`${root}episodes/${CHAP}.json`, 'utf8'));
const durs = JSON.parse(readFileSync(`/tmp/ep/${CHAP}/durations.json`, 'utf8'));

// Code lane = the native GitHub blob (light per your GitHub theme; sidebars auto-hide
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
opts.addArguments('-profile', PROFILE);
opts.setPreference('browser.aboutConfig.showWarning', false);

const driver = await new Builder().forBrowser('firefox').setFirefoxOptions(opts).build();

try {
  await driver.manage().window().fullscreen();   // F11: no chrome, no sidebar
  await sleep(700);

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
