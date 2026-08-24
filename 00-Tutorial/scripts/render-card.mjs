// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Render a cards/*.html page to a 4K 30 fps MP4, frame by frame, in headless
// Chromium (Playwright). The page exposes window.TOTAL (seconds) and
// window.seek(ms); every frame is an exact seek + screenshot, so timing is
// deterministic regardless of machine speed. Video only — audio is muxed later.
//
//   node render-card.mjs cards/title.html out.mp4 'n=7&name=PDO&idea=…&dur=3.2'
//   PREVIEW=0,1,2.5 node render-card.mjs cards/intro.html /tmp/x 'beats=…'   # PNG stills instead
//   ENC=x264 …                                                                 # CPU encode instead of NVENC

import { chromium } from 'playwright';
import { spawn } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import { resolve } from 'node:path';

const [, , html, out, query = ''] = process.argv;
if (!html || !out) { console.error('usage: render-card.mjs <page.html> <out.mp4> [query]'); process.exit(1); }
const FPS = 30;

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 3840, height: 2160 }, deviceScaleFactor: 1 });
await page.goto(pathToFileURL(resolve(html)).href + (query ? `?${query}` : ''));
await page.waitForFunction(() => document.body.dataset.ready === '1', null, { timeout: 30000 });
const total = await page.evaluate(() => window.TOTAL);
const frames = Math.round(total * FPS);

if (process.env.PREVIEW) {
    for (const t of process.env.PREVIEW.split(',').map(Number)) {
        await page.evaluate((ms) => window.seek(ms), t * 1000);
        await page.screenshot({ type: 'png', path: `${out}-${t.toFixed(2)}.png` });
    }
    await browser.close();
    process.exit(0);
}

// VAAPI (Intel Arc) by default; ENC=x264 for a CPU encode.
const x264 = process.env.ENC === 'x264';
const hw = x264 ? [] : ['-vaapi_device', '/dev/dri/renderD128'];
const enc = x264
    ? ['-pix_fmt', 'yuv420p', '-c:v', 'libx264', '-preset', 'medium', '-crf', '16']
    : ['-vf', 'format=nv12,hwupload', '-c:v', 'h264_vaapi', '-qp', '16', '-profile:v', 'high'];
const ff = spawn('ffmpeg', ['-y', '-loglevel', 'error', ...hw, '-f', 'image2pipe', '-c:v', 'png', '-framerate', String(FPS), '-i', '-',
    ...enc, '-r', String(FPS), '-movflags', '+faststart', out], { stdio: ['pipe', 'inherit', 'inherit'] });
ff.stdin.on('error', () => {});   // ffmpeg died: the close handler reports the exit code

const t0 = Date.now();
for (let i = 0; i < frames; i++) {
    await page.evaluate((ms) => window.seek(ms), (i * 1000) / FPS);
    const png = await page.screenshot({ type: 'png' });
    if (!ff.stdin.write(png)) await new Promise((r) => ff.stdin.once('drain', r));
    if (i % 90 === 0) process.stderr.write(`\r${html}: ${i}/${frames} frames`);
}
ff.stdin.end();
const code = await new Promise((r) => ff.on('close', r));
await browser.close();
process.stderr.write(`\r${html}: ${frames} frames in ${((Date.now() - t0) / 1000).toFixed(0)}s → ${out}\n`);
process.exit(code ?? 0);
