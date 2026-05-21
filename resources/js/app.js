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

    const numberValue = (input) => {
        const value = Number.parseFloat(input?.value || '');

        return Number.isFinite(value) ? value : 0;
    };

    const updateTravelTotals = () => {
        const distance = document.querySelector('[data-mileage-distance]');
        const rate = document.querySelector('[data-mileage-rate]');
        const mileageAmount = document.querySelector('[data-mileage-amount]');
        const totalAmount = document.querySelector('#total_amount');
        const components = document.querySelectorAll('[data-travel-component]');

        if (!(mileageAmount instanceof HTMLInputElement)) {
            return;
        }

        const mileage = numberValue(distance) * numberValue(rate);
        mileageAmount.value = mileage > 0 ? mileage.toFixed(2) : '';

        const componentTotal = Array.from(components).reduce((total, input) => total + numberValue(input), mileage);
        if (totalAmount instanceof HTMLInputElement && componentTotal > 0) {
            totalAmount.value = componentTotal.toFixed(2);
        }
    };

    document.querySelectorAll('[data-mileage-distance], [data-mileage-rate], [data-travel-component]').forEach((input) => {
        input.addEventListener('input', updateTravelTotals);
    });

    updateTravelTotals();
});
