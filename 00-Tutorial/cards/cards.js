// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Deterministic timeline for the SPE video cards. Every element gets ONE Web
// Animation spanning the whole card (keyframes at absolute seconds), paused at
// load; the renderer calls window.seek(ms) before each screenshot, so the frames
// are exact regardless of how long each screenshot takes.
//
//   ?beats=0,5.1,10.4,...,33.0   cumulative narration starts + final end (seconds)
//   ?lead=0.6&tail=1.0           silence before the first / after the last beat
//   ?dur=3.2                     fixed length when there is no narration (title card)

const Q = new URLSearchParams(location.search);
const num = (k, d) => (Q.has(k) && Number.isFinite(Number(Q.get(k))) ? Number(Q.get(k)) : d);
const LEAD = num('lead', 0.6), TAIL = num('tail', 1.0);
let CUM = (Q.get('beats') || '').split(',').map(Number).filter(Number.isFinite);

// Preview without narration (and no ?dur): evenly spaced 5 s beats.
if (CUM.length < 2 && !Q.has('dur')) { const n = document.querySelectorAll('.beat').length; CUM = n ? [...Array(n + 1)].map((_, i) => i * 5) : []; }

const BEATS = CUM.length > 1 ? CUM.slice(0, -1).map((s, i) => [LEAD + s, LEAD + CUM[i + 1]]) : [];
const TOTAL = CUM.length > 1 ? LEAD + CUM.at(-1) + TAIL : num('dur', 4);
window.TOTAL = TOTAL;
window.BEATS = BEATS;

const EASE = 'cubic-bezier(.22,.8,.3,1)';
const ANIMS = [];

// kf(el, [[t, {css}], ...]) — one animation, offsets = t / TOTAL.
function kf(el, stops, easing = EASE) {
    const f = stops.map(([t, p]) => ({ offset: Math.min(1, Math.max(0, t / TOTAL)), easing, ...p }));
    if (f[0].offset > 0) f.unshift({ ...f[0], offset: 0 });
    if (f.at(-1).offset < 1) f.push({ ...f.at(-1), offset: 1 });
    const a = el.animate(f, { duration: TOTAL * 1000, fill: 'both' });
    a.pause();
    a.persist?.();
    ANIMS.push(a);
    return a;
}

// reveal(el, t0, t1) — slide/fade in at t0, out at t1 (t1 == null: stays).
function reveal(el, t0, t1 = null, o = {}) {
    const { dur = 0.6, out = 0.4, from = 'translateY(70px)', to = 'translateY(-50px)', hold = 'none' } = o;
    const s = [[t0, { opacity: 0, transform: from }], [t0 + dur, { opacity: 1, transform: hold }]];
    if (t1 != null) s.push([t1, { opacity: 1, transform: hold }], [t1 + out, { opacity: 0, transform: to }]);
    return kf(el, s);
}

// Beat layers: .beat[i] visible for BEATS[i]; children stagger in.
function beats(stagger = 0.12) {
    document.querySelectorAll('.beat').forEach((el, i) => {
        const [s, e] = BEATS[i] || [i * 5, i * 5 + 5];
        const last = i === document.querySelectorAll('.beat').length - 1;
        reveal(el, s - 0.1, last ? null : e - 0.35, { dur: 0.55, out: 0.45, from: 'scale(.985)', to: 'scale(1.012)' });
        [...el.querySelectorAll('[data-in]')].forEach((c, j) => {
            const d = Number(c.dataset.in) || j * stagger;
            reveal(c, s + 0.1 + d, null, { dur: 0.7 });
        });
    });
}

// typeIn(el, t0, dur) — left-to-right wipe in as many steps as characters.
function typeIn(el, t0, dur = 1.2) {
    const n = Math.max(1, el.textContent.length);
    return kf(el, [[t0, { clipPath: 'inset(0 100% 0 0)' }], [t0 + dur, { clipPath: 'inset(0 -2% 0 0)' }]], `steps(${n}, end)`);
}

function background() {
    const w1 = document.querySelector('.bg .w1'), w2 = document.querySelector('.bg .w2');
    if (w1) kf(w1, [[0, { transform: 'translate(0,0)' }], [TOTAL, { transform: 'translate(360px,240px)' }]], 'linear');
    if (w2) kf(w2, [[0, { transform: 'translate(0,0)' }], [TOTAL, { transform: 'translate(-300px,-200px)' }]], 'linear');
}

window.seek = (ms) => ANIMS.forEach((a) => (a.currentTime = ms));
window.ready = () => { document.fonts.ready.then(() => { window.seek(0); document.body.dataset.ready = '1'; }); };
Object.assign(window, { Q, LEAD, TAIL, EASE, kf, reveal, beats, typeIn, background });
