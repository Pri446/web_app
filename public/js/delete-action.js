/*
 * Delete Action Handler & Confirmation Module
 * Base IMIS - Clean Standalone Delete Management
 */

(function (window, $) {
    'use strict';

    /**
     * Display a SweetAlert2 confirmation dialog before submitting a delete form.
     * @param {Element|jQuery|String|Event} formOrElement - The form, element, selector, or event trigger.
     * @param {String} [customMessage] - Optional custom warning message.
     */
    function deleteAction(formOrElement, customMessage) {
        var formEl = formOrElement;

        if (formOrElement && typeof formOrElement.preventDefault === 'function') {
            formOrElement.preventDefault();
        }

        if (typeof formOrElement === 'string') {
            formEl = document.querySelector(formOrElement) || $(formOrElement)[0];
        } else if (formOrElement && formOrElement.target) {
            formEl = $(formOrElement.target).closest('form')[0];
        } else if (formOrElement && formOrElement.jquery) {
            formEl = formOrElement[0];
        } else if (formOrElement && typeof formOrElement.closest === 'function') {
            formEl = formOrElement.closest('form');
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: customMessage || "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed || result.value) {
                    executeSubmit(formEl, formOrElement);
                }
            });
        } else {
            if (confirm(customMessage || "Are you sure you want to delete this item?")) {
                executeSubmit(formEl, formOrElement);
            }
        }
    }

    function executeSubmit(formEl, formOrElement) {
        if (formEl && typeof formEl.submit === 'function') {
            formEl.submit();
        } else if (formEl && $(formEl).is('form')) {
            $(formEl).submit();
        } else if (formOrElement && typeof formOrElement.submit === 'function') {
            formOrElement.submit();
        }
    }

    /**
     * Global event delegation for any button or link with class .delete or data-action="delete"
     */
    function bindGlobalDeleteHandler() {
        $(document).off('click.deleteAction', '.delete, [data-action="delete"]')
                   .on('click.deleteAction', '.delete, [data-action="delete"]', function (e) {
            e.preventDefault();
            var form = $(this).closest('form')[0] || this;
            deleteAction(form);
        });
    }

    // Expose globally
    window.deleteAction = deleteAction;
    window.confirmDelete = deleteAction;
    window.bindGlobalDeleteHandler = bindGlobalDeleteHandler;

    // Auto-bind on DOM ready
    $(document).ready(function () {
        bindGlobalDeleteHandler();
    });

})(window, typeof jQuery !== 'undefined' ? jQuery : undefined);
