// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Build a complete 01-Simple episode, hands-free:
//  1. synth each scene's narration (Chirp 3 HD) and measure its length
//  2. drive the app + code lanes at a native 3840x2160 viewport, holding each
//     scene for exactly its narration length, capturing via CDP screencast
//  3. write frame + audio concat lists for the assembly step (see make-episode.sh)
//
// Requires the SPE root server on BASE (php -S 127.0.0.1:8000 index.php).

import { chromium } from 'playwright';
import { writeFileSync, mkdirSync, rmSync } from 'fs';
import { execFileSync } from 'child_process';
import { dirname } from 'path';

const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const APP = `${BASE}/01-Simple`;
const CODE = (hl) => `${BASE}/00-Tutorial/codeview.html?f=01-Simple/public/index.php&hl=${hl}`;
const TTS = `${dirname(new URL(import.meta.url).pathname)}/google-tts.sh`;
const DIR = '/tmp/ep01';
rmSync(DIR, { recursive: true, force: true });
mkdirSync(`${DIR}/aud`, { recursive: true });

// lane: 'app' | 'code'.  app scenes use a big zoom + centring for the tiny column.
const scenes = [
  { lane: 'app', act: 'tour', narr: "What you're looking at is a complete PHP web page, with working navigation across three pages and a real not-found response, and the whole thing is produced by a single PHP statement with no framework and no configuration behind it, so let's walk through exactly how that works." },
  { lane: 'code', hl: '4', narr: "The entire program is a single expression: echo, followed by a new anonymous class, so PHP builds the object and immediately prints it, and printing an object is what triggers its toString method, which means the object really has only one job — to work out which page was requested and then render itself as HTML." },
  { lane: 'code', hl: '7-11', narr: "All of the pages live in one typed constant that maps each name to its title and the markup that goes with it, and because it's a constant rather than a property it's fixed when the file is parsed and shared across every request instead of being rebuilt each time the page loads." },
  { lane: 'code', hl: '13-14', narr: "These two properties use asymmetric visibility, which arrived in PHP 8.4, so anyone can read them but only the class itself is allowed to set them, and that is exactly the guarantee you want for a value that's computed once from the request and should never be quietly changed anywhere else afterwards." },
  { lane: 'code', hl: '18-22', narr: "Here is the PHP 8.5 pipe operator handling the request, and you can read it straight down the page: take the query parameter, trim the whitespace, lower-case it, and fall back to the home page when nothing is left, which is the same logic you would otherwise write as awkward nested function calls, except that now it reads in the exact order the steps actually happen." },
  { lane: 'app', act: 'notfound', narr: "When the requested page isn't one that we recognise, the code returns a genuine 404 status rather than a 200 response that merely says not found, because that status line is what browsers, search crawlers and the test suite all rely on to decide whether the page truly exists." },
  { lane: 'code', hl: '30-55', narr: "Rendering the page is just another pipe, where the page names become links and the current one is marked active, and the whole result is dropped into a heredoc template, so the HTML is written plainly as HTML with no string concatenation and no separate templating engine that you would have to learn on the side." },
  { lane: 'app', act: 'xss', narr: "And this is the one security habit worth carrying with you from the very first chapter: the incoming value is only ever used to look a page up, and it is never printed back into the document, so even a deliberate script-injection attempt simply becomes an unknown page and quietly returns a clean 404." },
  { lane: 'app', act: 'home', narr: "That is the entire engine in just fifty-seven lines, and every chapter from here builds on it by adding exactly one new idea, beginning with the next one, where we give the very same page a proper look with shared styling, dark mode and an application shell." },
];

// 1) synth + measure
console.log('synth…');
const durs = scenes.map((s, i) => {
  const wav = `${DIR}/aud/${String(i).padStart(2, '0')}.wav`;
  execFileSync('bash', [TTS, s.narr, wav], { stdio: 'ignore' });
  const d = Number(execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'default=nw=1:nk=1', wav]).toString().trim());
  console.log(`  scene ${i} ${d.toFixed(1)}s`);
  return d;
});

// 2) drive + capture
const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 3840, height: 2160 }, deviceScaleFactor: 1 });
const p = await c.newPage();
await p.addInitScript(() => {
  const put = () => {
    const dot = document.createElement('div');
    dot.id = '__cur';
    dot.style.cssText = 'position:fixed;z-index:99999;width:54px;height:54px;margin:-27px 0 0 -27px;border-radius:50%;background:rgba(255,95,60,.45);border:5px solid #ff5f3c;pointer-events:none;transition:transform .08s;left:-100px;top:0';
    document.body.appendChild(dot);
    addEventListener('mousemove', (e) => { dot.style.left = e.clientX + 'px'; dot.style.top = e.clientY + 'px'; });
    addEventListener('mousedown', () => dot.style.transform = 'scale(1.5)');
    addEventListener('mouseup', () => dot.style.transform = 'scale(1)');
  };
  addEventListener('DOMContentLoaded', put);
});

const cdp = await c.newCDPSession(p);
const frames = [];
cdp.on('Page.screencastFrame', async (f) => {
  frames.push({ buf: Buffer.from(f.data, 'base64'), t: f.metadata.timestamp });
  try { await cdp.send('Page.screencastFrameAck', { sessionId: f.sessionId }); } catch {}
});

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const appZoom = (z = 6) => p.evaluate((z) => {
  document.documentElement.style.zoom = String(z);
  const s = document.createElement('style');
  s.textContent = 'html{min-height:100vh;display:grid;place-items:center}';
  document.head.appendChild(s);
}, z);

async function tour() { await p.goto(`${APP}/`, { waitUntil: 'networkidle' }); await appZoom(); await sleep(1500);
  for (const n of ['About', 'Contact', 'Home']) { await p.getByRole('link', { name: n, exact: true }).click(); await sleep(1300); } }
async function notfound() { await p.goto(`${APP}/?o=nope`, { waitUntil: 'networkidle' }); await appZoom(); }
async function xss() { await p.goto(`${APP}/?o=${encodeURIComponent('<script>alert(1)</script>')}`, { waitUntil: 'networkidle' }); await appZoom(); }
async function home() { await p.goto(`${APP}/`, { waitUntil: 'networkidle' }); await appZoom(); }

await p.goto(`${APP}/`, { waitUntil: 'networkidle' }); await appZoom();
await cdp.send('Page.startScreencast', { format: 'png', everyNthFrame: 1 });
const LEAD = 0.6;
await sleep(LEAD * 1000);

for (let i = 0; i < scenes.length; i++) {
  const s = scenes[i];
  const start = Date.now();
  if (s.lane === 'code') { await p.goto(CODE(s.hl), { waitUntil: 'networkidle' }); await p.waitForFunction(() => document.body.dataset.ready === '1', { timeout: 8000 }).catch(() => {}); }
  else if (s.act === 'tour') { await tour(); }
  else if (s.act === 'notfound') { await notfound(); }
  else if (s.act === 'xss') { await xss(); }
  else { await home(); }
  const left = durs[i] * 1000 - (Date.now() - start);
  if (left > 0) await sleep(left);
}
await sleep(400);
await cdp.send('Page.stopScreencast');
await b.close();

// 3) write concat lists
let vlist = '';
for (let i = 0; i < frames.length; i++) {
  const name = `f-${String(i).padStart(5, '0')}.png`;
  writeFileSync(`${DIR}/${name}`, frames[i].buf);
  const dur = i < frames.length - 1 ? Math.max(0.033, frames[i + 1].t - frames[i].t) : 0.5;
  vlist += `file '${name}'\nduration ${dur.toFixed(3)}\n`;
}
vlist += `file 'f-${String(frames.length - 1).padStart(5, '0')}.png'\n`;
writeFileSync(`${DIR}/frames.txt`, vlist);

let alist = `file 'lead.wav'\n`;
for (let i = 0; i < scenes.length; i++) alist += `file 'aud/${String(i).padStart(2, '0')}.wav'\n`;
writeFileSync(`${DIR}/audio.txt`, alist);
writeFileSync(`${DIR}/lead.sec`, String(LEAD));
console.log(`frames=${frames.length} scenes=${scenes.length} dir=${DIR}`);
