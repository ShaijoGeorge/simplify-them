/**
 * SweetAlert2 – Delete Confirmation for EasyAdmin.
 *
 * Replaces EasyAdmin's bootstrap modal with a SweetAlert2 confirmation dialog.
 *
 * EasyAdmin's delete flow:
 *   1. Buttons with [data-action-name="delete"] are clicked.
 *   2. Bootstrap opens the #modal-delete modal (via data-bs-toggle).
 *   3. EasyAdmin's JS wires the modal confirm button to submit #delete-form.
 *
 * This script:
 *   - Strips data-bs-toggle/target attributes so Bootstrap never opens the modal.
 *   - Hides the #modal-delete element as a safety net.
 *   - Intercepts clicks in the capture phase and shows SweetAlert2 instead.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // ── 1. Disable Bootstrap modal triggering on delete buttons ──
        document.querySelectorAll('[data-action-name="delete"]').forEach(function (el) {
            el.removeAttribute('data-bs-toggle');
            el.removeAttribute('data-bs-target');
        });

        // ── 2. Hide the Bootstrap modal so it can never appear ──
        var bootstrapModal = document.querySelector('#modal-delete');
        if (bootstrapModal) {
            bootstrapModal.style.display = 'none';
            bootstrapModal.classList.remove('fade'); // prevent residual transitions
        }

        // ── 3. Intercept delete clicks in capture phase ──
        document.addEventListener('click', function (e) {
            var actionElement = e.target.closest('[data-action-name="delete"]');
            if (!actionElement) return;
            if (typeof SimplifySwal === 'undefined') return;

            // Prevent EasyAdmin's handler and any remaining Bootstrap behavior
            e.preventDefault();
            e.stopImmediatePropagation();

            var deleteFormAction = actionElement.getAttribute('formaction');

            SimplifySwal.confirm(
                'Delete this record?',
                'This action cannot be undone. The record will be permanently removed.',
                'Yes, delete it!',
                'Cancel'
            ).then(function (confirmed) {
                if (confirmed) {
                    // Show a brief "Deleted!" success animation before submitting
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'The record has been removed.',
                        showConfirmButton: false,
                        timer: 1200,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'swal-custom-popup'
                        },
                        willClose: function () {
                            var deleteForm = document.querySelector('#delete-form');
                            if (deleteForm) {
                                deleteForm.setAttribute('action', deleteFormAction);
                                deleteForm.submit();
                            }
                        }
                    });
                }
            });
        }, true); // capture phase — runs before EasyAdmin's bubble-phase listener

    });
})();
