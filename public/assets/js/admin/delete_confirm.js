/**
 * SweetAlert2 – Delete Confirmation for EasyAdmin.
 *
 * Replaces EasyAdmin's Bootstrap modals/native confirms with SweetAlert2
 * confirmation dialogs for both single-record delete and bulk batch delete.
 *
 * EasyAdmin's delete flow:
 *   1. Buttons with [data-action-name="delete"] are clicked.
 *   2. Bootstrap opens the #modal-delete modal (via data-bs-toggle).
 *   3. EasyAdmin's JS wires the modal confirm button to submit #delete-form.
 *
 * EasyAdmin's batch delete flow:
 *   1. Users select rows and click the batch delete action.
 *   2. Bootstrap opens the #modal-batch-action modal.
 *   3. EasyAdmin builds and submits a POST form on confirmation.
 *
 * This script:
 *   - Strips data-bs-toggle/target attributes so Bootstrap never opens modals.
 *   - Hides EasyAdmin delete modals as a safety net.
 *   - Intercepts clicks in the capture phase and shows SweetAlert2 instead.
 */
(function () {
    'use strict';

    function initDeleteConfirm() {
        if (window.__simplifyDeleteConfirmInitialized) {
            return;
        }

        window.__simplifyDeleteConfirmInitialized = true;

        function disableBootstrapModalTrigger(selector) {
            document.querySelectorAll(selector).forEach(function (el) {
                el.removeAttribute('data-bs-toggle');
                el.removeAttribute('data-bs-target');
            });
        }

        function hideModal(selector) {
            var modal = document.querySelector(selector);
            if (!modal) {
                return;
            }

            modal.style.display = 'none';
            modal.classList.remove('fade');
        }

        function submitBatchDelete(actionElement) {
            var selectedItems = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');
            var batchFormFields = {
                batchActionName: actionElement.getAttribute('data-action-name'),
                entityFqcn: actionElement.getAttribute('data-entity-fqcn'),
                batchActionUrl: actionElement.getAttribute('data-action-url'),
                batchActionCsrfToken: actionElement.getAttribute('data-action-csrf-token')
            };

            selectedItems.forEach(function (item, index) {
                batchFormFields['batchActionEntityIds[' + index + ']'] = item.value;
            });

            actionElement.setAttribute('disabled', 'disabled');

            var batchForm = document.createElement('form');
            batchForm.setAttribute('method', 'POST');
            batchForm.setAttribute('action', actionElement.getAttribute('data-action-url'));

            Object.keys(batchFormFields).forEach(function (fieldName) {
                var input = document.createElement('input');
                input.setAttribute('type', 'hidden');
                input.setAttribute('name', fieldName);
                input.setAttribute('value', batchFormFields[fieldName]);
                batchForm.appendChild(input);
            });

            document.body.appendChild(batchForm);
            batchForm.submit();
        }

        // ── 1. Disable Bootstrap modal triggering on delete actions ──
        disableBootstrapModalTrigger('[data-action-name="delete"]');
        disableBootstrapModalTrigger('[data-action-batch]');

        // ── 2. Hide the Bootstrap modals so they can never appear ──
        hideModal('#modal-delete');
        hideModal('#modal-batch-action');

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

        // ── 4. Intercept batch delete clicks in capture phase ──
        document.addEventListener('click', function (e) {
            var actionElement = e.target.closest('[data-action-batch]');
            if (!actionElement) return;
            if (typeof SimplifySwal === 'undefined') return;

            e.preventDefault();
            e.stopImmediatePropagation();

            var selectedItemsCount = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked').length;
            var actionLabel = actionElement.textContent.trim() || actionElement.getAttribute('title') || 'selected records';
            var recordLabel = selectedItemsCount === 1 ? 'record' : 'records';
            var confirmLabel = selectedItemsCount === 1 ? 'Yes, proceed' : 'Yes, proceed';

            SimplifySwal.confirm(
                actionLabel + ' selected ' + recordLabel + '?',
                'This action cannot be undone. ' + selectedItemsCount + ' ' + recordLabel + ' will be affected.',
                confirmLabel,
                'Cancel'
            ).then(function (confirmed) {
                if (confirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: selectedItemsCount + ' ' + recordLabel + ' have been removed.',
                        showConfirmButton: false,
                        timer: 1200,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'swal-custom-popup'
                        },
                        willClose: function () {
                            submitBatchDelete(actionElement);
                        }
                    });
                }
            });
        }, true);

        disableBootstrapModalTrigger('[data-action-name="delete"]');
        disableBootstrapModalTrigger('[data-action-batch]');
        hideModal('#modal-delete');
        hideModal('#modal-batch-action');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDeleteConfirm);
    } else {
        initDeleteConfirm();
    }

    document.addEventListener('turbo:load', initDeleteConfirm);
})();
