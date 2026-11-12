@extends('layouts.dashboard')

@section('title', $page_title)

@section('content')
    <div class="card">
        <div class="card-body">
            <div style="overflow:auto;width:100%;">
                <table
                    id="desludging-reintegration-table"
                    class="table table-bordered table-striped dtr-inline"
                    width="100%"
                >
                    <thead>
                        <tr>
                            @if ($showServiceProvider)
                                <th>{{ __('Service Provider') }}</th>
                            @endif
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('BIN') }}</th>
                            <th>{{ __('Containment ID') }}</th>
                            <th>{{ __('House Number') }}</th>
                            <th>{{ __('House Locality') }}</th>
                            <th>{{ __('Road Number') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <form
            id="reintegration-confirm-form"
            method="POST"
            action="{{ route('desludging-reintegration.confirm') }}"
            class="d-none"
        >
            @csrf
            <input type="hidden" name="containment_id">
            <input type="hidden" name="bin">
            <input type="hidden" name="road_code">
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const showServiceProvider = @json($showServiceProvider);
            const columns = [];

            if (showServiceProvider) {
                columns.push({
                    data: 'service_provider_name',
                    name: 'service_provider_name',
                    defaultContent: '-'
                });
            }

            columns.push(
                { data: 'sequence', name: 'sequence' },
                { data: 'bin', name: 'bin' },
                { data: 'containment_id', name: 'containment_id' },
                { data: 'house_number', name: 'house_number', defaultContent: '-' },
                { data: 'house_locality', name: 'house_locality', defaultContent: '-' },
                { data: 'road_code', name: 'road_code', defaultContent: '-' },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    defaultContent: ''
                }
            );

            $('#desludging-reintegration-table').DataTable({
                ajax: "{{ route('desludging-reintegration.data') }}",
                columns: columns,
                searching: false,
                processing: true,
                serverSide: true,
                scrollX: true,
                scrollCollapse: true,
                order: [[showServiceProvider ? 1 : 0, 'asc']],
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                language: {
                    emptyTable: "{{ __('No reintegration data is available.') }}"
                }
            });

            $(document).on('click', '.reintegrate-schedule', function () {
                const form = $('#reintegration-confirm-form');

                form.find('[name="bin"]').val($(this).data('bin'));
                form.find('[name="containment_id"]')
                    .val($(this).data('containment-id'));
                form.find('[name="road_code"]')
                    .val($(this).data('road-code'));

                form.trigger('submit');
            });
        });
    </script>
@endpush
