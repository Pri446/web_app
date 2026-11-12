<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('cwis-rebuild-form');
    if (!form) return;
    var loader = document.getElementById('cwis-rebuild-loader');
    var button = form.querySelector('button[type="submit"]');
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (form.dataset.busy === 'true' || form.dataset.confirming === 'true') return;
        form.dataset.confirming = 'true';
        button.disabled = true;
        try {
            var confirmation = await window.Swal.fire({
                title: form.dataset.confirmTitle, text: form.dataset.confirm, icon: 'warning',
                showCancelButton: true, confirmButtonText: form.dataset.confirmButton,
                cancelButtonText: form.dataset.cancelButton, reverseButtons: true
            });
            if (!confirmation.isConfirmed) return;
            form.dataset.busy = 'true';
            form.setAttribute('aria-busy', 'true');
            loader.hidden = false;
            loader.focus();
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () { HTMLFormElement.prototype.submit.call(form); });
            });
        } catch (error) {
            if (window.toastr) window.toastr.error(form.dataset.confirmError);
        } finally {
            delete form.dataset.confirming;
            if (form.dataset.busy !== 'true') button.disabled = false;
        }
    });
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        loader.hidden = true;
        button.disabled = false;
        delete form.dataset.busy;
        delete form.dataset.confirming;
        form.removeAttribute('aria-busy');
    });
});
</script>
