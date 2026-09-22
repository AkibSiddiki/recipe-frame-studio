// Recipe Frame Studio desktop helper utilities

window.RecipeStudio = {
    csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    },

    async postJson(url, data = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        return response.json();
    }
};

// Global desktop keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl+N or Cmd+N for New Project
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'n') {
        const createBtn = document.querySelector('a[href*="/project/create"]');
        if (createBtn) {
            e.preventDefault();
            window.location.href = createBtn.href;
        }
    }

    // Ctrl+, or Cmd+, for Settings
    if ((e.ctrlKey || e.metaKey) && e.key === ',') {
        const settingsBtn = document.querySelector('a[href*="/settings"]');
        if (settingsBtn) {
            e.preventDefault();
            window.location.href = settingsBtn.href;
        }
    }
});
