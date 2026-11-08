<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.dashboard')
@section('title', __('Application'))
@push('style')
    <style type="text/css">
        .dataTables_filter {
            display: none;
        }
        /* Added for modal table clarity */
        #tp-details-table th { background-color: #f8f9fa; }
    </style>
@endpush

@section('content')
    <div class="card">
        <div class="card-header">
            @if (!empty($createBtnLink) && !empty($createBtnTitle))
                <a href="{{ $createBtnLink }}" class="btn btn-info">{{ $createBtnTitle }}</a>
            @endif
            @if (!empty($exportBtnLink))
                <a href="{{ $exportBtnLink }}" class="btn btn-info" id="export" onclick="exportToCsv(event)" >{{ __('Export to CSV') }}</a>
            @endif

            <div class="float-right">
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#treatmentPlantModal"
                    data-loader-ignore id="btnShowTP">
                    {{ __('Treatment Plant Capacity') }}
                </button>
                <a class="btn btn-info" id="headingOne" type="button" data-toggle="collapse"
                    data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    {{ __('Show Filter') }}
                </a>
            </div>

            @if (!empty($reportBtnLink))
                <a class="btn btn-info" data-toggle="collapse" data-target="#collapseFilterPdf" aria-expanded="false"
                    aria-controls="collapseFilterPdf">{{ __('Generate Report') }}</a>
                <div class="card-body">
                    <div class="col-12">
                        <div id="collapseFilterPdf" class="accordion-collapse collapse" aria-labelledby="headingOne"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <div class="form-group row required">
                                    <label for="bin_text" class="control-label col-md-2">{{ __('Month') }}</label>
                                    <div class="col-md-2">
                                        <select class="form-control row" id="month_select" name="month" {{ !empty($application_months) ? '' : 'disabled' }}>
                                            @foreach($application_months as $unique)
                                                <option value="{{ $unique->date1 }}">
                                                    {{ date('F', mktime(0, 0, 0, $unique->date1, 10)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <label for="bin_text" class="control-label col-md-2">{{ __('Year') }}</label>
                                    <div class="col-md-2">
                                        <select class="form-control row" id="year_select" name="year" {{ !empty($application_years) ? '' : 'disabled' }}>
                                            @foreach($application_years as $unique)
                                                <option value="{{ $unique->date1 }}">{{ $unique->date1 }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <a class="btn btn-info pdf" id="pdf">{{ __('Export to PDF') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <form class="form-horizontal" id="filter-form">
                                        @foreach ($filterFormFields as $formFieldGroup)
                                            <div class="form-group row">
                                                @foreach ($formFieldGroup as $formField)
                                                    {!! Form::label($formField->labelFor, $formField->label, ['class' => $formField->labelClass]) !!}
                                                    <div class="col-md-2">
                                                        @if ($formField->inputType === 'text')
                                                            {!! Form::text($formField->inputId, $formField->inputValue, ['class' => $formField->inputClass, 'placeholder' => $formField->placeholder, 'autocomplete' => $formField->autoComplete]) !!}
                                                        @elseif ($formField->inputType === 'date')
                                                            {!! Form::date($formField->inputId, $formField->inputValue, ['class' => $formField->inputClass, 'onclick' => 'this.showPicker();']) !!}
                                                        @elseif ($formField->inputType === 'number')
                                                            {!! Form::number($formField->inputId, $formField->inputValue, ['class' => $formField->inputClass, 'placeholder' => $formField->placeholder]) !!}
                                                        @elseif ($formField->inputType === 'select')
                                                            {!! Form::select($formField->inputId, $formField->selectValues, $formField->selectedValue, ['class' => $formField->inputClass, 'placeholder' => $formField->placeholder]) !!}
                                                        @elseif ($formField->inputType === 'label')
                                                            {!! Form::label($formField->inputId, $formField->labelValue, ['class' => $formField->inputClass]) !!}
                                                        @elseif ($formField->inputType === 'multiple-select')
                                                            {!! Form::select($formField->inputId, $formField->selectValues, $formField->selectedValue, ['class' => $formField->inputClass, 'disabled' => $formField->disabled]) !!}
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                        <div class="card-footer text-right">
                                            <button type="submit" class="btn btn-info">{{ __('Filter') }}</button>
                                            <button id="reset-filter" type="button" class="btn btn-info">{{ __('Reset') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div style="overflow: auto; width: 100%;">
                <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('BIN') }}</th>
                            <th>{{ __('House Number') }}</th>
                            <th>{{ __('Containment ID') }}</th>
                            <th>{{ __('Street Code') }}</th>
                            <th>{{ __('Ward Number') }}</th>
                            <th>{{ __('Applicant Name') }}</th>
                            <th>{{ __('Applicant Contact') }}</th>
                            <th>{{ __('Service Provider Name') }}</th>
                            <th>{{ __('Application Date') }}</th>
                            <th>{{ __('Proposed Emptying Date') }}</th>
                            <th>{{ __('Emptying Status') }}</th>
                            <th>{{ __('Sludge Collection Status') }}</th>
                            <th>{{ __('Feedback Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- TREATMENT PLANT MODAL -->
    <div class="modal fade" id="treatmentPlantModal" tabindex="-1" role="dialog" aria-labelledby="tpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="tpModalLabel">{{ __('Treatment Plant Capacity') }}</h5>
                    <small id="currentDate"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="tp-details-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th>{{ __('Capacity') }}</th>
                            </tr>
                        </thead>
                        <tbody id="tp-body">
                            <tr>
                                <td colspan="4" class="text-center">{{ __('Click button to load data...') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>


@stop

@push('scripts')
    <script>
        $(document).ready(function () {
            
            $('#btnShowTP').on('click', function() {
            $.ajax({
                url: '{{ route("application.treatment-plant") }}',
                method: 'GET',
                beforeSend: function() {
                    $('#tp-body').html('<tr><td colspan="3" class="text-center">Loading...</td></tr>');
                },
                success: function(response) {
                    if(response.date_display) {
                        $('#currentDate').html(response.date_display);
                    } else if(response.date) {
                        const dateObj = new Date(response.date);
                        const options = { month: 'long', day: 'numeric' };
                        $('#currentDate').html(dateObj.toLocaleDateString(undefined, options));
                    }
                    
                    var html = '';
                    if(response.plants && response.plants.length > 0) {
                        $.each(response.plants, function(index, plant) {
                            html += '<tr>' +
                                    '<td>' + (plant.name ?? "N/A") + '</td>' +
                                    '<td>' + (plant.location ?? "N/A") + '</td>' +
                                    '<td>' + (plant.remaining_capacity ?? "0") + '</td>' + // The subtracted value
                                    '</tr>';
                        });
                    } else {
                        html = '<tr><td colspan="3" class="text-center">No active plants found.</td></tr>';
                    }
                    $('#tp-body').html(html);
                },
                error: function() {
                    $('#tp-body').html('<tr><td colspan="3" class="text-center text-danger">Error fetching data.</td></tr>');
                }
            });
        });

            // 2. Service Provider Dropdown AJAX
            var serviceProviderId = {{ Auth::user()->service_provider_id ?? 'null' }};
            var spUrl = serviceProviderId ? '{!! url("fsm/service-provider") !!}/' + serviceProviderId : '{!! url("fsm/service-provider") !!}/0';

            $.ajax({
                url: spUrl,
                method: 'GET',
                success: function (response) {
                    $('#service_provider_id').empty().append('<option value="">{{ __("Service Provider Name") }}</option>');
                    $.each(response, function (id, name) {
                        $('#service_provider_id').append('<option value="' + id + '">' + name + '</option>');
                    });
                }
            });

            // 3. DataTable Initialization
            var dataTable = $('#data-table').DataTable({
                bFilter: false,
                processing: true,
                serverSide: true,
                stateSave: true,
                ajax: {
                    url: '{!! route('application.get-data') !!}',
                    data: function(d) {
                        d.bin = $('#bin').val();
                        d.house_address = $('#house_address').val();
                        d.applicant_name = $('#applicant_name').val();
                        d.ward = $('#ward').val();
                        d.application_id = $('#application_id').val();
                        d.emptying_status = $('#emptying_status').val();
                        d.sludge_collection_status = $('#sludge_collection_status').val();
                        d.feedback_status = $('#feedback_status').val();
                        d.road_code = $('#road_code').val();
                        d.proposed_emptying_date = $('#proposed_emptying_date').val();
                        d.service_provider_id = $('#service_provider_id').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                    },
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'bin', name: 'bin' },
                    { data: 'house_address', name: 'house_address' },
                    { data: 'containment_id', name: 'containment_id' },
                    { data: 'road_code', name: 'road_code' },
                    { data: 'ward', name: 'ward' },
                    { data: 'applicant_name', name: 'applicant_name' },
                    { data: 'applicant_contact', name: 'applicant_contact' },
                    { data: 'service_provider_id', name: 'service_provider_id' },
                    { 
                        data: 'application_date', 
                        name: 'application_date',
                        render: function(data) { return data ? moment(data).format("dddd, MMMM Do YYYY") : ''; }
                    },
                    { data: 'proposed_emptying_date', name: 'proposed_emptying_date' },                
                    { data: 'emptying_status', name: 'emptying_status' },
                    { data: 'sludge_collection_status', name: 'sludge_collection_status' },
                    { data: 'feedback_status', name: 'feedback_status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[0, 'desc']]
            });

            // DataTable action rows are rendered dynamically, so use a
            // delegated handler that remains active after every redraw.
            $(document).on('click', '#data-table .delete', function(e) {
                e.preventDefault();
                deleteAction($(this).closest('form')[0]);
            });

            // 4. Form Actions (Filter & Reset)
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();

                if ((date_from !== '' && date_to === '') || (date_from === '' && date_to !== '')) {
                    Swal.fire({ icon: 'warning', title: 'Required', text: 'Both Date From and Date To are required for range filtering.' });
                    return false;
                }
                if (date_from !== '' && date_to !== '' && date_to < date_from) {
                    Swal.fire({ icon: 'warning', title: 'Invalid Range', text: 'Date To cannot be before Date From.' });
                    return false;
                }
                dataTable.draw();
            });

            $('#reset-filter').on('click', function() {
                $('#filter-form')[0].reset();
                $('#service_provider_id, #road_code').val(null).trigger('change');
                dataTable.draw();
            });

            // 5. Select2 for Road Code
            $('#road_code').prepend('<option selected=""></option>').select2({
                ajax: {
                    url: "{{ route('roadlines.get-road-names') }}",
                    data: function(params) { return { search: params.term, page: params.page || 1 }; },
                },
                placeholder:'{{ __('Street Name / Street Code') }}',
                allowClear: true,
                width: '100%'
            });

            // 6. PDF Export Logic
            $('#pdf').click(function() {
                var year_sel = localStorage.getItem('year_select') || $('#year_select').val();
                var month_sel = localStorage.getItem('month_select') || $('#month_select').val();
                if(!year_sel || !month_sel) return;
                window.globalLoader.download(
                    `application/pdf/${year_sel}/${month_sel}/monthly-report`,
                    'Monthly Report.pdf',
                    this
                ).catch(function () {});
            });

        });
    </script>
@endpush
