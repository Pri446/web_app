@if(session('cwis_saved') || session('cwis_result_success'))
@push('scripts')
<script>
    $(function () {
        toastr.success(@json(session('cwis_saved') ?? session('cwis_result_success')), null, {
            closeButton: true,
            progressBar: true,
            timeOut: 5000,
            extendedTimeOut: 1500,
            escapeHtml: true
        });
    });
</script>
@endpush
@endif
