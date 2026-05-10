document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-upload-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('[data-upload-button]');
            const buttonText = form.querySelector('[data-upload-button-text]');
            const buttonLoading = form.querySelector('[data-upload-button-loading]');
            const loadingPanel = form.querySelector('[data-upload-loading]');

            loadingPanel?.classList.remove('hidden');
            buttonText?.classList.add('hidden');
            buttonLoading?.classList.remove('hidden');
            buttonLoading?.classList.add('inline-flex');

            if (button instanceof HTMLButtonElement) {
                button.disabled = true;
                button.classList.add('cursor-not-allowed', 'opacity-80');
            }
        });
    });
});
