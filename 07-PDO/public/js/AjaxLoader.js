document.addEventListener('DOMContentLoaded', () => {
    const contentContainer = document.getElementById('ajaxhere');
    
    // Initialize Bootstrap modals
    document.addEventListener('show.bs.modal', event => {
        const modal = event.target;
        if (modal.id === 'deleteModal') {
            const deleteLink = modal.querySelector('.delete-action');
            if (deleteLink) {
                deleteLink.addEventListener('click', async e => {
                    e.preventDefault();
                    const url = e.target.getAttribute('href');
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                    // First do the delete request
                    await loadContent(constructURL(url), contentContainer);
                    // Then explicitly load the list view
                    const listUrl = url.split('?')[0] + '?o=News&m=list';
                    await loadContent(constructURL(listUrl), contentContainer);
                    showToast('News item deleted successfully', 'success');
                }, { once: true }); // Remove listener after use
            }
        }
    });
    
    const loadContent = async (url, target, method = 'GET', formData = null) => {
        try {
            const options = {
                method: method,
                ...(formData && { body: formData })
            };
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const html = await response.text();
            
            // If response is empty, check for redirect method
            if (!html.trim()) {
                // Get the method from response headers set by server
                const redirectURL = new URL(response.url);
                const serverMethod = response.headers.get('X-Redirect-Method') || 'list';
                // Update the method parameter
                redirectURL.searchParams.set('m', serverMethod);
                // Remove any id parameter as we're redirecting to a list/index view
                redirectURL.searchParams.delete('id');
                await loadContent(redirectURL.toString(), target);
            } else {
                target.innerHTML = html;
            }
        } catch (error) {
            target.innerHTML = `<p class="text-danger">Load failed: ${error.message}</p>`;
        }
    };

    const constructURL = (baseURL, isForm = false) => {
        if (!baseURL) return '';
        // Always add x=main for AJAX requests, even for forms
        return baseURL.includes('?') ? `${baseURL}&x=main` : `${baseURL}?x=main`;
    };

    // Handle form submissions
    document.body.addEventListener('submit', event => {
        const form = event.target.closest('.news-form');
        if (form) {
            event.preventDefault();
            const formData = new FormData(form);
            loadContent(constructURL(form.action, true), contentContainer, 'POST', formData);
        }
    });

    // Handle ajax link clicks and confirmation dialog
    document.body.addEventListener('click', event => {
        const link = event.target.closest('.ajax-link');
        const confirmOkButton = event.target.closest('.modal button.btn-success');
        
        if (link && !link.closest('form')) {  // Ignore ajax-links inside forms
            event.preventDefault();
            loadContent(constructURL(link.getAttribute('href')), contentContainer);
        }
    });
});
