document.addEventListener('DOMContentLoaded', () => {
    const jsonButtons = document.querySelectorAll('[data-json-toggle]');
    jsonButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const preview = button.nextElementSibling;
            if (!preview) {
                return;
            }
            const isHidden = preview.hasAttribute('hidden');
            if (isHidden) {
                preview.removeAttribute('hidden');
                button.textContent = 'Nascondi';
            } else {
                preview.setAttribute('hidden', 'hidden');
                button.textContent = 'Vedi';
            }
        });
    });

    const confirmButtons = document.querySelectorAll('button[data-confirm]');
    confirmButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            const label = (button.textContent || '').trim();
            const actionLabel = label !== '' ? label : 'questa azione';
            if (!window.confirm(`Confermi di eseguire ${actionLabel}?`)) {
                event.preventDefault();
            }
        });
    });
});
