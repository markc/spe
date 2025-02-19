document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const C = document.getElementById('ajaxhere'),
        lC = async (u, t) => {
            try {
                const r = await fetch(u);
                if (!r.ok) throw new Error(`HTTP error! Status: ${r.status}`);
                const h = await r.text();
                t.innerHTML = h;
            } catch (e) {
                t.innerHTML = `<p class="text-danger">Load failed: ${e.message}</p>`;
            }
        },
        cURL = b => {return b.includes('?') ? `${b}&x=main` : `${b}?x=main` };
  
        document.querySelectorAll('.ajax-link').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                lC(cURL(a.getAttribute('href')), C);
            });
        });
    }, 50);
});
