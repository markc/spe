// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Pilot browser-lane capture for 01-Simple: drives the real page and records
// it to a video. Node ESM, uses the installed Playwright. Assumes the chapter
// is already being served at BASE (see run-01.sh).
//
// Usage: BASE=http://127.0.0.1:8021 OUT=/tmp/spe-01 node scripts/capture-01.mjs

import { chromium } from 'playwright';
import { mkdirSync } from 'fs';

const BASE = process.env.BASE || 'http://127.0.0.1:8021';
const OUT = process.env.OUT || '/tmp/spe-01';
mkdirSync(OUT, { recursive: true });

const pause = (ms) => new Promise((r) => setTimeout(r, ms));

// Lay the page out at a VWxVH css viewport and paint it at deviceScaleFactor DSF,
// so the video is captured at (VW*DSF)x(VH*DSF) device pixels — razor sharp.
// Default = 4K: a 1280-wide browser at 3x -> 3840x2160. Override via env.
const VW = Number(process.env.VW || 1280);
const VH = Number(process.env.VH || 720);
const DSF = Number(process.env.DSF || 3);
const browser = await chromium.launch();
const ctx = await browser.newContext({
  viewport: { width: VW, height: VH },
  deviceScaleFactor: DSF,
  recordVideo: { dir: OUT, size: { width: VW * DSF, height: VH * DSF } },
});
const page = await ctx.newPage();

// A visible cursor + click ping, since Playwright doesn't draw one.
await page.addInitScript(() => {
  const dot = document.createElement('div');
  dot.style.cssText =
    'position:fixed;z-index:99999;width:22px;height:22px;margin:-11px 0 0 -11px;border-radius:50%;' +
    'background:rgba(255,90,60,.55);border:2px solid #ff5a3c;pointer-events:none;transition:transform .08s;left:0;top:0';
  addEventListener('DOMContentLoaded', () => document.body.appendChild(dot));
  addEventListener('mousemove', (e) => { dot.style.left = e.clientX + 'px'; dot.style.top = e.clientY + 'px'; });
  addEventListener('mousedown', () => { dot.style.transform = 'scale(1.6)'; });
  addEventListener('mouseup', () => { dot.style.transform = 'scale(1)'; });
});

async function clickNav(label) {
  const link = page.getByRole('link', { name: label, exact: true });
  await link.hover();
  await pause(500);
  await link.click();
  await pause(2200);
}

// Scene 1 — the running page, navigate the three pages
await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
await pause(2500);
await clickNav('About');
await clickNav('Contact');
await clickNav('Home');

// Scene 5 — input is trimmed + lower-cased (spaced, upper-case ABOUT resolves to About)
await page.goto(`${BASE}/?o=%20ABOUT%20`, { waitUntil: 'networkidle' });
await pause(2600);

// Scene 6 — an unknown page is a real 404
await page.goto(`${BASE}/?o=nope`, { waitUntil: 'networkidle' });
await pause(2600);

// Scene 8 — a script-injection attempt is just an unknown page, cleanly 404'd
await page.goto(`${BASE}/?o=${encodeURIComponent('<script>alert(1)</script>')}`, { waitUntil: 'networkidle' });
await pause(2800);

// Scene 9 — back home
await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
await pause(1800);

await ctx.close();          // finalizes the video file
const src = await page.video().path();
await browser.close();
console.log(src);           // raw webm path, for the encoder step
