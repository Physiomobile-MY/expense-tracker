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
        const subtotalAmount = document.querySelector('#subtotal');
        const totalAmount = document.querySelector('#total_amount');
        const tollTotal = document.querySelector('[data-toll-total]');
        const tollEntries = document.querySelectorAll('[data-toll-entry-amount]');
        const components = document.querySelectorAll('[data-travel-component]');

        if (!(mileageAmount instanceof HTMLInputElement)) {
            return;
        }

        const mileage = numberValue(distance) * numberValue(rate);
        const toll = Array.from(tollEntries).reduce((total, input) => total + numberValue(input), 0);
        mileageAmount.value = mileage > 0 ? mileage.toFixed(2) : '';

        if (tollTotal instanceof HTMLInputElement) {
            tollTotal.value = toll > 0 ? toll.toFixed(2) : '';
        }

        const componentTotal = Array.from(components).reduce((total, input) => total + numberValue(input), mileage);
        if (subtotalAmount instanceof HTMLInputElement && componentTotal > 0) {
            subtotalAmount.value = componentTotal.toFixed(2);
        }

        if (totalAmount instanceof HTMLInputElement && componentTotal > 0) {
            totalAmount.value = componentTotal.toFixed(2);
        }
    };

    const reindexTollRows = () => {
        document.querySelectorAll('[data-toll-row]').forEach((row, index) => {
            const label = row.querySelector('[data-toll-label]');
            const amount = row.querySelector('[data-toll-entry-amount]');

            label?.setAttribute('name', `toll_entries[${index}][label]`);
            amount?.setAttribute('name', `toll_entries[${index}][amount]`);
        });
    };

    document.querySelectorAll('[data-mileage-distance], [data-mileage-rate], [data-travel-component], [data-toll-entry-amount]').forEach((input) => {
        input.addEventListener('input', updateTravelTotals);
    });

    document.querySelector('[data-add-toll]')?.addEventListener('click', () => {
        const list = document.querySelector('[data-toll-list]');
        const firstRow = document.querySelector('[data-toll-row]');

        if (!(list instanceof HTMLElement) || !(firstRow instanceof HTMLElement)) {
            return;
        }

        const row = firstRow.cloneNode(true);
        row.querySelectorAll('input').forEach((input) => {
            input.value = '';
            input.addEventListener('input', updateTravelTotals);
        });
        list.appendChild(row);
        reindexTollRows();
        updateTravelTotals();
    });

    document.addEventListener('click', (event) => {
        const button = event.target instanceof Element ? event.target.closest('[data-remove-toll]') : null;

        if (!button) {
            return;
        }

        const rows = document.querySelectorAll('[data-toll-row]');
        const row = button.closest('[data-toll-row]');

        if (rows.length > 1) {
            row?.remove();
        } else {
            row?.querySelectorAll('input').forEach((input) => {
                input.value = '';
            });
        }

        reindexTollRows();
        updateTravelTotals();
    });

    updateTravelTotals();
});
