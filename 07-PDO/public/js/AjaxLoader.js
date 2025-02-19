document.addEventListener('DOMContentLoaded', () => {
    const contentContainer = document.getElementById('ajaxhere');
    
    const loadContent = async (url, target) => {
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const html = await response.text();
            target.innerHTML = html;
        } catch (error) {
            target.innerHTML = `<p class="text-danger">Load failed: ${error.message}</p>`;
        }
    };

    const constructURL = baseURL => baseURL.includes('?') ? `${baseURL}&x=main` : `${baseURL}?x=main`;

    // Set up event delegation on the document body for .ajax-link clicks
    document.body.addEventListener('click', event => {
        const link = event.target.closest('.ajax-link');
        if (link) {
            event.preventDefault();
            loadContent(constructURL(link.getAttribute('href')), contentContainer);
        }
    });
});
