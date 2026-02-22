/**
 * SweetAlert2 – Reusable helpers for Simplify-Them admin pages.
 *
 * Loaded via <script> on every EasyAdmin page (through the flash_messages override).
 * Exposes window.SimplifySwal so inline Twig <script> blocks can call helpers easily.
 *
 * Usage:
 *   SimplifySwal.toast('success', 'Record saved!');
 *   SimplifySwal.success('Client created successfully.');
 *   SimplifySwal.error('Something went wrong.');
 *   SimplifySwal.confirm('Delete this record?', 'This cannot be undone.').then(ok => { ... });
 */
(function () {
    'use strict';

    /* ── guard: wait for Swal to be available ─────────────────────────── */
    if (typeof Swal === 'undefined') {
        console.warn('[SimplifySwal] Swal is not loaded yet.');
        return;
    }

    /* ── Toast mixin ──────────────────────────────────────────────────── */
    var Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        showCloseButton: true,
        didOpen: function (toast) {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    /* ── Icon → colour map (matches the indigo/purple design system) ── */
    var iconColors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#6366f1'
    };

    /* ── Public API ───────────────────────────────────────────────────── */
    window.SimplifySwal = {

        /** Generic toast (top-right, auto-dismiss). */
        toast: function (icon, message) {
            return Toast.fire({
                icon: icon,
                title: message,
                iconColor: iconColors[icon] || undefined
            });
        },

        /** Convenience wrappers */
        success: function (msg) { return this.toast('success', msg); },
        error: function (msg) { return this.toast('error', msg); },
        warning: function (msg) { return this.toast('warning', msg); },
        info: function (msg) { return this.toast('info', msg); },

        /** Centred alert (blocks interaction until dismissed). */
        alert: function (icon, title, text) {
            return Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonColor: '#6366f1',
                customClass: { popup: 'swal-simplify' }
            });
        },

        /** Confirmation dialog (resolve → true / false). */
        confirm: function (title, text, confirmLabel, cancelLabel) {
            return Swal.fire({
                title: title || 'Are you sure?',
                text: text || '',
                icon: 'warning',
                iconColor: '#f59e0b',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: confirmLabel || 'Yes, delete it!',
                cancelButtonText: cancelLabel || 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                customClass: { popup: 'swal-simplify' }
            }).then(function (result) {
                return result.isConfirmed;
            });
        }
    };
})();
