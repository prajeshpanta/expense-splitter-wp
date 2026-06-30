/**
 * Splitwise WP — Frontend JavaScript
 * Handles: live split preview, checkbox selection styling
 */
(function () {
    'use strict';

    const FORM_ID = 'splitwise-expense-form';
    const AMOUNT_ID = 'sw-amount';
    const PREVIEW_ID = 'sw-split-preview';

    let previewTimeout = null;

    /**
     * Debounced version of preview update
     */
    function swUpdatePreview() {
        if (previewTimeout) {
            clearTimeout(previewTimeout);
        }

        previewTimeout = setTimeout(() => {
            const amountInput = document.getElementById(AMOUNT_ID);
            const preview = document.getElementById(PREVIEW_ID);

            if (!amountInput || !preview) return;

            const amount = parseFloat(amountInput.value) || 0;
            const checkedBoxes = document.querySelectorAll(
                `#${FORM_ID} input[type="checkbox"]:checked`
            ).length;

            // +1 for the current user (payer)
            const totalPeople = checkedBoxes + 1;

            if (amount > 0 && totalPeople > 1) {
                const share = (amount / totalPeople).toFixed(2);
                preview.innerHTML = `
                    Each of <strong>${totalPeople}</strong> people pays: 
                    <strong>Rs ${share}</strong>
                `;
                preview.style.display = 'block';
            } else if (amount > 0 && totalPeople === 1) {
                preview.innerHTML = 'You are paying the full amount: <strong>Rs ' + amount.toFixed(2) + '</strong>';
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }, 150); // 150ms debounce
    }

    /**
     * Toggle selected styling on user label.
     * FIX: was toggling 'splitwise-user-item--selected', a class that does not
     * exist in splitwise-frontend.css (only '.splitwise-split-item.checked' is
     * styled there), so selecting a person previously had no visual effect.
     */
    function swToggleSelected(userId) {
        const checkbox = document.querySelector(
            `#${FORM_ID} input[type="checkbox"][value="${userId}"]`
        );
        const label = document.getElementById(`sw-label-${userId}`);

        if (!checkbox || !label) return;

        if (checkbox.checked) {
            label.classList.add('checked');
        } else {
            label.classList.remove('checked');
        }
    }

    /**
     * Initialize everything on page load
     */
    function swInit() {
        const form = document.getElementById(FORM_ID);
        if (!form) return;

        const checkboxes = form.querySelectorAll('input[type="checkbox"]');

        // Attach event listeners
        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', function () {
                swToggleSelected(this.value);
                swUpdatePreview();
            });
        });

        // Amount input listener
        const amountInput = document.getElementById(AMOUNT_ID);
        if (amountInput) {
            amountInput.addEventListener('input', swUpdatePreview);
        }

        // Restore previous selections (after form error)
        const preChecked = form.querySelectorAll('input[type="checkbox"]:checked');
        preChecked.forEach((cb) => {
            swToggleSelected(cb.value);
        });

        // Initial preview
        swUpdatePreview();
    }

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', swInit);
    } else {
        swInit();
    }

    // Expose functions (for backward compatibility with inline handlers)
    window.swUpdatePreview = swUpdatePreview;
    window.swToggleSelected = swToggleSelected;

})();