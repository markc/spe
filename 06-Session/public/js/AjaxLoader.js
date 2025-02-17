document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      const C = document.getElementById('ajaxhere'),
        lC = async (u, t) => {
          console.log('Loading:', u);
          try {
            const r = await fetch(u);
            if (!r.ok) throw new Error(`HTTP error! Status: ${r.status}`);
            t.innerHTML = `<p class="text-danger">Load failed: ${r.statusText}</p>`;
            const h = await r.text();
            t.innerHTML = h;
            console.log('Loaded:', h.substring(0, 100), '...');
          } catch (e) {
            console.error('Load failed:', e);
            t.innerHTML = `<p class="text-danger">Load failed: ${e.message}</p>`;
          }
        },
        cURL = b => {console.log('Constructed:', b.includes('?') ? `${b}&x=main` : `${b}?x=main` ); return b.includes('?') ? `${b}&x=main` : `${b}?x=main` };
  
      document.querySelectorAll('.ajax-link').forEach(a => {
        console.log(`Adding listener to ajax-link:`, a);
        a.addEventListener('click', e => {
          e.preventDefault();
          console.log('Clicked, URL:', a.getAttribute('href'));
          lC(cURL(a.getAttribute('href')), C);
        });
      });
      console.log('DOMContentLoaded, contentDiv:', C);
    }, 50);
});
