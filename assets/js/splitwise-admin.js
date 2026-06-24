/**
 * Splitwise WP — Admin JavaScript
 * Handles: live split preview and checkbox styling on Add Expense admin page
 */
(function () {
    'use strict';

    // Configuration
    const CONFIG = {
        amountId: 'amount',
        previewId: 'sw-admin-split-preview',
        usersContainerClass: 'sw-users-grid',
        selectedClass: 'sw-user-item--selected',
        labelPrefix: 'sw-admin-label-'
    };

    let previewTimeout = null;

    /**
     * Debounced update of the live split preview
     */
    function swAdminUpdatePreview() {
        if (previewTimeout) {
            clearTimeout(previewTimeout);
        }

        previewTimeout = setTimeout(() => {
            const amountInput = document.getElementById(CONFIG.amountId);
            const preview = document.getElementById(CONFIG.previewId);

            if (!amountInput || !preview) {
                return;
            }

            const amount = parseFloat(amountInput.value) || 0;
            const checkedCount = document.querySelectorAll(
                `.${CONFIG.usersContainerClass} input[type="checkbox"]:checked`
            ).length;

            const totalPeople = checkedCount + 1; // +1 for the current payer

            if (amount > 0) {
                if (totalPeople > 1) {
                    const share = (amount / totalPeople).toFixed(2);
                    preview.innerHTML = `
                        Each of <strong>${totalPeople}</strong> people pays: 
                        <strong>Rs ${share}</strong>
                    `;
                } else {
                    preview.innerHTML = `
                        You are paying the full amount: 
                        <strong>Rs ${amount.toFixed(2)}</strong>
                    `;
                }
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }, 120); // Small debounce for smooth typing
    }

    /**
     * Toggle selected styling on user label
     *
     * @param {string|number} userId
     */
    function swAdminToggleSelected(userId) {
        const checkbox = document.querySelector(
            `.${CONFIG.usersContainerClass} input[type="checkbox"][value="${userId}"]`
        );
        const label = document.getElementById(CONFIG.labelPrefix + userId);

        if (!checkbox || !label) {
            return;
        }

        if (checkbox.checked) {
            label.classList.add(CONFIG.selectedClass);
        } else {
            label.classList.remove(CONFIG.selectedClass);
        }
    }

    /**
     * Initialize the admin page functionality
     */
    function swAdminInit() {
        // Amount input listener
        const amountInput = document.getElementById(CONFIG.amountId);
        if (amountInput) {
            amountInput.addEventListener('input', swAdminUpdatePreview);
        }

        // Checkbox listeners
        const checkboxes = document.querySelectorAll(
            `.${CONFIG.usersContainerClass} input[type="checkbox"]`
        );

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                swAdminToggleSelected(this.value);
                swAdminUpdatePreview();
            });

            // Restore selected styling after form validation error
            if (checkbox.checked) {
                swAdminToggleSelected(checkbox.value);
            }
        });

        // Initial preview
        swAdminUpdatePreview();
    }

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', swAdminInit);
    } else {
        swAdminInit();
    }

    // Expose functions globally (in case you still use inline handlers)
    window.swAdminUpdatePreview = swAdminUpdatePreview;
    window.swAdminToggleSelected = swAdminToggleSelected;

})();