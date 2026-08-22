// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Sharpness test: capture 02-Styled at a native 3840x2160 viewport (page zoomed
// so it lays out ~1280-wide but rasterises at true 4K) via CDP screencast.
// Writes PNG frames + an ffmpeg concat list with real per-frame durations.

import { chromium } from 'playwright';
import { writeFileSync, mkdirSync, rmSync } from 'fs';

const BASE = process.env.BASE || 'http://127.0.0.1:8022/02-Styled';
const ZOOM = Number(process.env.ZOOM || 3);
const DIR = '/tmp/spe-testvid';
rmSync(DIR, { recursive: true, force: true });
mkdirSync(DIR, { recursive: true });

const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 3840, height: 2160 }, deviceScaleFactor: 1 });
const p = await c.newPage();
await p.addInitScript((z) => {
  addEventListener('DOMContentLoaded', () => { document.documentElement.style.zoom = String(z); });
}, ZOOM);

const cdp = await c.newCDPSession(p);
const frames = [];
cdp.on('Page.screencastFrame', async (f) => {
  frames.push({ buf: Buffer.from(f.data, 'base64'), t: f.metadata.timestamp });
  try { await cdp.send('Page.screencastFrameAck', { sessionId: f.sessionId }); } catch {}
});

const pause = (ms) => new Promise((r) => setTimeout(r, ms));
await p.goto(`${BASE}/`, { waitUntil: 'networkidle' });
await cdp.send('Page.startScreencast', { format: 'png', everyNthFrame: 1 });
await pause(2000);
await p.getByRole('link', { name: 'About', exact: true }).click(); await pause(2000);
await p.getByRole('link', { name: 'Contact', exact: true }).click(); await pause(2000);
await p.getByRole('link', { name: 'Home', exact: true }).click(); await pause(2000);
await cdp.send('Page.stopScreencast');
await b.close();

// Write frames + concat list with real durations from screencast timestamps.
let list = '';
for (let i = 0; i < frames.length; i++) {
  const name = `f-${String(i).padStart(5, '0')}.png`;
  writeFileSync(`${DIR}/${name}`, frames[i].buf);
  const dur = i < frames.length - 1 ? Math.max(0.033, frames[i + 1].t - frames[i].t) : 0.5;
  list += `file '${name}'\nduration ${dur.toFixed(3)}\n`;
}
list += `file 'f-${String(frames.length - 1).padStart(5, '0')}.png'\n`; // last frame, concat quirk
writeFileSync(`${DIR}/list.txt`, list);
console.log(`frames=${frames.length} dir=${DIR}`);
