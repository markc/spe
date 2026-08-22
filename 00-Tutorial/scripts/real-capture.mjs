// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Drive a VISIBLE browser fullscreen on the real desktop (captured externally by
// gpu-screen-recorder). WYSIWYG. Short proof: navigate 02-Styled at a set zoom.
//   BASE=http://127.0.0.1:8000/02-Styled ZOOM=1.2 node real-capture.mjs

import { chromium } from 'playwright';

const BASE = process.env.BASE || 'http://127.0.0.1:8000/02-Styled';
const ZOOM = process.env.ZOOM || '1.2';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const b = await chromium.launch({
  headless: false,
  args: ['--start-fullscreen', '--force-device-scale-factor=1', '--hide-scrollbars', '--ozone-platform-hint=auto'],
});
const c = await b.newContext({ viewport: null });   // real window size
const p = await c.newPage();
await p.addInitScript((z) => {
  addEventListener('DOMContentLoaded', () => { document.documentElement.style.zoom = z; });
}, ZOOM);

await p.goto(`${BASE}/`, { waitUntil: 'networkidle' });
await sleep(3000);
for (const n of ['About', 'Contact', 'Home']) {
  await p.getByRole('link', { name: n, exact: true }).click();
  await sleep(3000);
}
await b.close();
console.log('driver done');
