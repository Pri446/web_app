@extends('layouts.dashboard')

@section('title', $page_title)

@push('style')
    <style>
        .dataTables_filter {
            display: none;
        }

        #desludging-schedule-table_wrapper {
            width: 100%;
        }

        .schedule-table-container {
            width: 100%;
            overflow-x: auto;
        }

        .schedule-table-container table {
            min-width: 1400px;
        }

        .schedule-action {
            margin-right: 3px;
        }
    </style>
@endpush

@section('content')
    @php
        /*
         * Municipality-level users can see the service-provider column.
         * Provider users will eventually see only their own records.
         */
        $showServiceProvider =
            auth()->user()->hasRole('Super Admin') ||
            auth()->user()->hasRole('Municipality - Super Admin') ||
            auth()->user()->hasRole('Municipality - Help Desk');

    @endphp

    <div class="card">
        <div class="card-header">
            {{--
                This mirrors DNCC:
                Regenerate is hidden from both Super Admin roles.
            --}}
            @if (
                auth()->user()->can('Regenerate Schedule Desludging') &&
                !auth()->user()->hasRole('Super Admin') &&
                !auth()->user()->hasRole('Municipality - Super Admin')
            )
                <button
                    type="button"
                    id="regenerate-schedule"
                    class="btn btn-info"
                >
                    {{ __('Regenerate Desludging Schedule') }}
                </button>
            @endif

            @can('Export Schedule Desludging')
                <button
                    type="button"
                    id="export-schedule"
                    class="btn btn-info"
                >
                    {{ __('Export to CSV') }}
                </button>
            @endcan

            @can('Filter Schedule Desludging')
                <button
                    type="button"
                    class="btn btn-info float-right"
                    data-toggle="collapse"
                    data-target="#schedule-filter"
                    aria-expanded="false"
                    aria-controls="schedule-filter"
                >
                    {{ __('Show Filter') }}
                </button>
            @endcan
        </div>

        @can('Filter Schedule Desludging')
            <div
                id="schedule-filter"
                class="collapse"
            >
                <div class="card-body">
                    <form
                        id="schedule-filter-form"
                        class="form-horizontal"
                    >
                        <div class="form-group row">
                            <label
                                for="bin"
                                class="col-md-2 col-form-label"
                            >
                                {{ __('BIN') }}
                            </label>

                            <div class="col-md-2">
                                <input
                                    type="text"
                                    id="bin"
                                    class="form-control"
                                    placeholder="{{ __('BIN') }}"
                                >
                            </div>

                            <label
                                for="containment_id"
                                class="col-md-2 col-form-label"
                            >
                                {{ __('Containment ID') }}
                            </label>

                            <div class="col-md-2">
                                <input
                                    type="text"
                                    id="containment_id"
                                    class="form-control"
                                    placeholder="{{ __('Containment ID') }}"
                                >
                            </div>

                            <label
                                for="holding_num"
                                class="col-md-2 col-form-label"
                            >
                                {{ __('House Number') }}
                            </label>

                            <div class="col-md-2">
                                <input
                                    type="text"
                                    id="holding_num"
                                    class="form-control"
                                    placeholder="{{ __('House Number') }}"
                                >
                            </div>
                        </div>

                        <div class="form-group row">
                            <label
                                for="owner_name"
                                class="col-md-2 col-form-label"
                            >
                                {{ __('Owner Name') }}
                            </label>

                            <div class="col-md-2">
                                <input
                                    type="text"
                                    id="owner_name"
                                    class="form-control"
                                    placeholder="{{ __('Owner Name') }}"
                                >
                            </div>
                        </div>

                        <div class="card-footer text-right">
                            <button
                                type="submit"
                                class="btn btn-info"
                            >
                                {{ __('Filter') }}
                            </button>

                            <button
                                type="reset"
                                id="reset-schedule-filter"
                                class="btn btn-info"
                            >
                                {{ __('Reset') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        <div class="card-body">
            <div class="schedule-table-container">
                <table
                    id="desludging-schedule-table"
                    class="table table-bordered table-striped dtr-inline"
                    width="100%"
                >
                    <thead>
                        <tr>
                            @if ($showServiceProvider)
                                <th>
                                    {{ __('Service Provider') }}
                                </th>
                            @endif

                            <th>{{ __('ID') }}</th>
                            <th>{{ __('BIN') }}</th>
                            <th>{{ __('Containment ID') }}</th>
                            <th>{{ __('House Number') }}</th>
                            <th>{{ __('Next Emptying Date') }}</th>
                            <th>{{ __('House Locality') }}</th>
                            <th>{{ __('Road Number') }}</th>

                            <th>
                                {{ __('Owner Name') }}
                            </th>

                            <th>
                                {{ __('Owner Contact') }}
                            </th>

                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        {{-- Schedule rows will be loaded later. --}}
                    </tbody>
                </table>
            </div>
        </div>

        <form
            id="accept-schedule-form"
            method="POST"
            action="{{ route('desludging-schedule.accept') }}"
            class="d-none"
        >
            @csrf
            <input type="hidden" name="bin">
            <input type="hidden" name="containment_id">
            <input type="hidden" name="road_code">
            <input type="hidden" name="service_provider_id">
            <input type="hidden" name="next_emptying_date">
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function () {
            const showServiceProvider = @json($showServiceProvider);

            /*
             * Define DataTable columns based on the user's role.
             */
            const columns = [];

            if (showServiceProvider) {
                columns.push({
                    data: 'service_provider_name',
                    name: 'service_provider_name',
                    defaultContent: '-'
                });
            }

            columns.push(
                {
                    data: 'sequence',
                    name: 'sequence',
                    defaultContent: '-'
                },
                {
                    data: 'bin',
                    name: 'bin',
                    defaultContent: '-'
                },
                {
                    data: 'containment_id',
                    name: 'containment_id',
                    defaultContent: '-'
                },
                {
                    data: 'house_number',
                    name: 'house_number',
                    defaultContent: '-'
                },
                {
                    data: 'next_emptying_date',
                    name: 'next_emptying_date',
                    defaultContent: '-'
                },
                {
                    data: 'house_locality',
                    name: 'house_locality',
                    defaultContent: '-'
                },
                {
                    data: 'road_code',
                    name: 'road_code',
                    defaultContent: '-'
                },
                {
                    data: 'display_name',
                    name: 'display_name',
                    orderable: false,
                    searchable: false,
                    defaultContent: '-'
                },
                {
                    data: 'display_contact',
                    name: 'display_contact',
                    orderable: false,
                    searchable: false,
                    defaultContent: '-'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    defaultContent: ''
                }
            );

            const scheduleTable = $(
                '#desludging-schedule-table'
            ).DataTable({
                ajax: {
                    url: "{{ route('desludging-schedule.data') }}",
                    data: function (data) {
                        data.bin = $('#bin').val();
                        data.containment_id = $('#containment_id').val();
                        data.holding_num = $('#holding_num').val();
                        data.owner_name = $('#owner_name').val();
                    }
                },
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
                    emptyTable:
                        "{{ __('No schedule data is available.') }}"
                }
            });

            $(document).on('click', '.accept-schedule', function () {
                const form = $('#accept-schedule-form');

                form.find('[name="bin"]').val($(this).data('bin'));
                form.find('[name="containment_id"]')
                    .val($(this).data('containment-id'));
                form.find('[name="road_code"]')
                    .val($(this).data('road-code'));
                form.find('[name="service_provider_id"]')
                    .val($(this).data('service-provider-id'));
                form.find('[name="next_emptying_date"]')
                    .val($(this).data('next-emptying-date'));

                form.trigger('submit');
            });

            $(document).on('click', '.disagree-schedule', function () {
                const bin = $(this).data('bin');

                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('Do you want to remove this property from the desludging schedule?') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "{{ __('Yes') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: "{{ url('fsm/desludging-schedule') }}/" +
                            encodeURIComponent(bin) + '/disagree',
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            scheduleTable.ajax.reload(null, false);
                            Swal.fire(
                                "{{ __('Success') }}",
                                response.message,
                                'success'
                            );
                        },
                        error: function (xhr) {
                            const message = xhr.responseJSON &&
                                xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : "{{ __('The request could not be completed.') }}";

                            Swal.fire(
                                "{{ __('Error') }}",
                                message,
                                'error'
                            );
                        }
                    });
                });
            });

            // Redraw sends the current filters with the next AJAX request.
            $('#schedule-filter-form').on(
                'submit',
                function (event) {
                    event.preventDefault();
                    scheduleTable.draw();
                }
            );

            $('#reset-schedule-filter').on(
                'click',
                function () {
                    $('#bin').val('');
                    $('#containment_id').val('');
                    $('#holding_num').val('');
                    $('#owner_name').val('');

                    scheduleTable.draw();
                }
            );

            // Prevent duplicate requests from this browser tab.
            let isRegenerating = false;

            $('#regenerate-schedule').on(
                'click',
                function (event) {
                    event.preventDefault();

                    if (isRegenerating) {
                        return;
                    }

                    Swal.fire({
                        title: "{{ __('Are you sure?') }}",

                        text:
                            "{{ __('Do you want to generate priority-based emptying dates?') }}",

                        icon: 'warning',
                        showCancelButton: true,

                        confirmButtonText:
                            "{{ __('Yes, regenerate') }}",

                        cancelButtonText:
                            "{{ __('Cancel') }}"
                    }).then(function (result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        isRegenerating = true;

                        const button = $('#regenerate-schedule');
                        let elapsedSeconds = 0;
                        let elapsedTimer = null;

                        button
                            .prop('disabled', true)
                            .text("{{ __('Generating...') }}");


                        Swal.fire({
                            title: "{{ __('Generating desludging dates') }}",
                            html: "{{ __('Elapsed time:') }} " +
                                '<strong id="generation-elapsed">0</strong> ' +
                                "{{ __('seconds') }}",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: function () {
                                Swal.showLoading();
                                elapsedTimer = window.setInterval(
                                    function () {
                                        elapsedSeconds += 1;
                                        $('#generation-elapsed')
                                            .text(elapsedSeconds);
                                    }, 1000
                                );
                            }
                        });
                        $.ajax({
                            url:
                                "{{ route('desludging-schedule.regenerate') }}",

                            type: 'POST',
                            timeout: 120000,

                            data: {
                                _token: "{{ csrf_token() }}"
                            },

                            success: function (response) {
                                if (
                                    response.status !== 'success'
                                ) {
                                    showRegenerateError(
                                        response.message ||
                                        "{{ __('Schedule generation failed.') }}"
                                    );

                                    return;
                                }

                                const details = response.message + ' ' +
                                    (response.temporary_schedule_count || 0) +
                                    " {{ __('schedule rows generated;') }} " +
                                    (response.service_area_assigned_count || 0) +
                                    " {{ __('uncovered wards assigned to empty providers;') }} " +
                                    (response.provider_assigned_count || 0) +
                                    " {{ __('schedule rows matched to providers;') }} " +
                                    (response.provider_unassigned_count || 0) +
                                    " {{ __('schedule rows remain without a provider;') }} " +
                                    (response.priority_updated_count || 0) +
                                    " {{ __('priorities updated.') }}";

                                scheduleTable.ajax.reload(null, false);

                                Swal.fire({
                                    title:
                                        "{{ __('Success') }}",

                                    text: details,

                                    icon: 'success',

                                    confirmButtonText:
                                        "{{ __('OK') }}"
                                });
                            },

                            error: function (xhr, textStatus) {
                                let message =
                                    "{{ __('Failed to generate desludging dates.') }}";

                                if (textStatus === 'timeout') {
                                    message =
                                        "{{ __('Schedule generation timed out after 120 seconds. The server may still be processing; wait before trying again.') }}";
                                } else if (
                                    xhr.responseJSON &&
                                    xhr.responseJSON.message
                                ) {
                                    message = xhr.responseJSON.message;
                                }

                                showRegenerateError(message);
                            },

                            complete: function () {
                                if (elapsedTimer !== null) {
                                    window.clearInterval(elapsedTimer);
                                }

                                isRegenerating = false;

                                button
                                    .prop('disabled', false)
                                    .text(
                                        "{{ __('Regenerate Desludging Schedule') }}"
                                    );
                            }
                        });
                    });
                }
            );

            /*
             * Export remains disconnected until its backend is created.
             */
            $('#export-schedule').on(
                'click',
                function (event) {
                    event.preventDefault();

                    Swal.fire({
                        title: "{{ __('Not available yet') }}",

                        text:
                            "{{ __('Schedule export will be enabled after the export backend is connected.') }}",

                        icon: 'info',

                        confirmButtonText:
                            "{{ __('OK') }}"
                    });
                }
            );

            function showRegenerateError(message) {
                Swal.fire({
                    title: "{{ __('Error') }}",
                    text: message,
                    icon: 'error',
                    confirmButtonText: "{{ __('OK') }}"
                });
            }
        });
    </script>
@endpush
