// Markdown parser - JS port of PHP Util::md() for identical rendering
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

function md(s) {
    const b = [], L = '\x02', R = '\x03';
    const esc = t => t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    // Protect code blocks
    s = s.replace(/```(\w*)\r?\n([\s\S]*?)\r?\n```/g, (_, lang, code) => {
        const cls = lang ? ` class="lang-${lang}"` : '';
        b.push(`${L}pre${R}${L}code${cls}${R}${esc(code.trimEnd())}${L}/code${R}${L}/pre${R}`);
        return `\x00${b.length - 1}\x00`;
    });
    s = s.replace(/`([^`\n]+)`/g, (_, code) => {
        b.push(`${L}code${R}${esc(code)}${L}/code${R}`);
        return `\x00${b.length - 1}\x00`;
    });

    // GFM Tables
    s = s.replace(/^(\|.+\|)\r?\n(\|[-:\| ]+\|)\r?\n((?:\|.+\|\r?\n?)+)/gm, (_, hdr, align, body) => {
        const hdrs = hdr.split('|').filter(c => c.trim()).map(c => c.trim());
        const aligns = align.split('|').filter(c => c.trim()).map(c => {
            c = c.trim();
            if (c.startsWith(':') && c.endsWith(':')) return 'center';
            if (c.endsWith(':')) return 'right';
            return 'left';
        });
        const th = hdrs.map((h, i) => `${L}th style="text-align:${aligns[i]}"${R}${h}${L}/th${R}`).join('');
        const rows = body.trim().split('\n').map(r => {
            const cells = r.split('|').filter(c => c.trim()).map(c => c.trim());
            return `${L}tr${R}${cells.map((c, i) => `${L}td style="text-align:${aligns[i] || 'left'}"${R}${c}${L}/td${R}`).join('')}${L}/tr${R}`;
        }).join('');
        return `${L}table${R}${L}thead${R}${L}tr${R}${th}${L}/tr${R}${L}/thead${R}${L}tbody${R}${rows}${L}/tbody${R}${L}/table${R}`;
    });

    // Block elements
    s = s.replace(/^(#{1,6})\s+(.+)$/gm, (_, h, t) => `${L}h${h.length}${R}${t.trim()}${L}/h${h.length}${R}`);
    s = s.replace(/^[-*_]{3,}\s*$/gm, `${L}hr${R}`);
    s = s.replace(/^>\s*(.+)$/gm, `${L}blockquote${R}$1${L}/blockquote${R}`);

    // Lists
    s = s.replace(/^[-*+]\s+(.+)$/gm, `${L}ul${R}${L}li${R}$1${L}/li${R}${L}/ul${R}`);
    s = s.replace(/^\d+\.\s+(.+)$/gm, `${L}ol${R}${L}li${R}$1${L}/li${R}${L}/ol${R}`);
    s = s.replace(new RegExp(`${L}/ul${R}\\s*${L}ul${R}`, 'g'), '');
    s = s.replace(new RegExp(`${L}/ol${R}\\s*${L}ol${R}`, 'g'), '');
    s = s.replace(new RegExp(`${L}/blockquote${R}\\s*${L}blockquote${R}`, 'g'), '\n');

    // Inline elements
    s = s.replace(/!\[([^\]]*)\]\(([^)\s]+)\)/g, `${L}img src="$2" alt="$1"${R}`);
    s = s.replace(/\[([^\]]+)\]\(([^)]+)\)/g, `${L}a href="$2"${R}$1${L}/a${R}`);
    s = s.replace(/(\*\*|__)(.+?)\1/g, `${L}strong${R}$2${L}/strong${R}`);
    s = s.replace(/\*([^*\n]+)\*/g, `${L}em${R}$1${L}/em${R}`);
    s = s.replace(/(?<![^\s])_([^_\n]+)_(?![^\s])/g, `${L}em${R}$1${L}/em${R}`);
    s = s.replace(/~~(.+?)~~/g, `${L}del${R}$1${L}/del${R}`);

    // Join consecutive badge lines (linked images) into single line
    s = s.replace(new RegExp(`(${L}a[^${R}]*${R}${L}img[^${R}]*${R}${L}/a${R})\n(?=${L}a)`, 'g'), '$1 ');

    // Finalize - escape remaining content, restore protected blocks
    s = esc(s);
    s = s.replace(/\x00(\d+)\x00/g, (_, i) => b[parseInt(i)]);
    s = s.replace(/\x02/g, '<').replace(/\x03/g, '>');

    // Paragraphs
    return s.split(/\n{2,}/).map(p => {
        p = p.trim();
        if (!p) return '';
        if (/^<(?:h[1-6]|ul|ol|blockquote|hr|pre|table)/.test(p)) return p;
        return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
    }).join('\n').trim();
}

// Document viewer
async function loadDoc(path) {
    const content = document.getElementById('content');
    try {
        const res = await fetch(path);
        if (!res.ok) throw new Error(`Failed to load ${path}`);
        content.innerHTML = md(await res.text());
        document.querySelectorAll('[data-path]').forEach(a => a.classList.toggle('active', a.dataset.path === path));
        history.pushState({path}, '', `#${path}`);
    } catch (e) {
        content.innerHTML = `<p>Error: ${e.message}</p>`;
    }
}

// Auto-init doc viewer if data-path links exist
(function() {
    const init = () => {
        const links = document.querySelectorAll('[data-path]');
        if (!links.length) return;
        links.forEach(a => a.addEventListener('click', e => { e.preventDefault(); loadDoc(a.dataset.path); }));
        window.addEventListener('hashchange', () => location.hash && loadDoc(location.hash.slice(1)));
        loadDoc(location.hash ? location.hash.slice(1) : 'README.md');
    };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
})();
