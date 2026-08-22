// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Drive a fresh, fullscreen Zen (Firefox) via geckodriver, navigating the 01
// scenes hands-free, holding each for its narration length. gpu-screen-recorder
// captures the screen externally; assembly syncs audio to the first scene change.
//
//   BASE=http://127.0.0.1:8000 ZOOM=1.2 node real-zen.mjs
// Reads scene durations from /tmp/ep01/durations.json (written by the orchestrator).

import { Builder, By } from 'selenium-webdriver';
import firefox from 'selenium-webdriver/firefox.js';
import { readFileSync, mkdtempSync } from 'fs';
import { tmpdir } from 'os';

const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const APP = `${BASE}/01-Simple`;
const CODE = (hl) => `${BASE}/00-Tutorial/codeview.html?f=01-Simple/public/index.php&hl=${hl}`;
const ZOOM = process.env.ZOOM || '1.2';
const durs = JSON.parse(readFileSync('/tmp/ep01/durations.json', 'utf8')); // seconds[]

const scenes = [
  { lane: 'app', act: 'tour' },
  { lane: 'code', hl: '4' },
  { lane: 'code', hl: '7-11' },
  { lane: 'code', hl: '13-14' },
  { lane: 'code', hl: '18-22' },
  { lane: 'app', act: 'notfound' },
  { lane: 'code', hl: '30-55' },
  { lane: 'app', act: 'xss' },
  { lane: 'app', act: 'home' },
];

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const profile = mkdtempSync(`${tmpdir()}/zen-spe-`);

const opts = new firefox.Options();
opts.setBinary('/usr/bin/zen-browser');
opts.addArguments('-profile', profile);
opts.setPreference('browser.aboutConfig.showWarning', false);
opts.setPreference('devtools.console.stdout.content', true);

const driver = await new Builder().forBrowser('firefox').setFirefoxOptions(opts).build();

async function zoom() { await driver.executeScript('document.documentElement.style.zoom = arguments[0]', ZOOM); }
async function go(url) { await driver.get(url); await zoom(); }
async function clickLink(name) { await driver.findElement(By.linkText(name)).click(); await zoom(); }

try {
  await driver.manage().window().fullscreen();
  await sleep(500);

  // pre-roll on a blank page while the recorder settles; the jump to scene 1
  // (a real page) is the first scene-change the assembler locks onto.
  await go('about:blank');
  await sleep(2500);

  for (let i = 0; i < scenes.length; i++) {
    const s = scenes[i];
    const start = Date.now();
    if (s.lane === 'code') {
      await go(CODE(s.hl));
      await sleep(400);
    } else if (s.act === 'tour') {
      await go(`${APP}/`);
      await sleep(1500);
      for (const n of ['About', 'Contact', 'Home']) { await clickLink(n); await sleep(1200); }
    } else if (s.act === 'notfound') {
      await go(`${APP}/?o=nope`);
    } else if (s.act === 'xss') {
      await go(`${APP}/?o=${encodeURIComponent('<script>alert(1)</script>')}`);
    } else {
      await go(`${APP}/`);
    }
    const left = durs[i] * 1000 - (Date.now() - start);
    if (left > 0) await sleep(left);
  }
  await sleep(400);
} finally {
  await driver.quit();
}
console.log('zen driver done');
