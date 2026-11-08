<!-- Last Modified Date: 10-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->

@extends('layouts.dashboard')
@section('title', $page_title)


@section('content')

    <div class="card border-0">
        <div class="card-header">

            <a class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse" data-target="#collapseOne"
                aria-expanded="true" aria-controls="collapseOne">
                {{ __('Show Filter') }}
            </a>
            @can('Export Containments')
                <a href="#" id="export" class="btn btn-info">{{ __("Export to CSV") }}</a>
                <a href="#" id="export-building-containment" class="btn btn-info">{{ __("Export Buildings-Containments to CSV") }}</a>

                <a href="#" id="export-shp" class="btn btn-info">{{ __("Export to Shape File") }}</a>
                <a href="#" id="export-kml" class="btn btn-info">{{ __("Export to KML") }}</a>
            @endcan
        </div><!-- /.card-header -->
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <form class="form-horizontal" id="filter-form">
                                        <div class="form-group row">
                                            <label for="containment_id" class="control-label col-md-2">{{ __("Containment ID") }}</label>

                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="containment_id"
                                                    placeholder="{{ __("Containment ID") }}"
                                                    oninput = "this.value = this.value.replace(/[^a-zA-Z0-9]/g, ''); " />
                                                <!-- Allow only alphabetic and numeric characters -->
                                            </div>

                                            <label for="volume_min" class="control-label col-md-2">{{ __("Containment Volume From (m³)") }}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="volume_min" name="volume_min"
                                                    placeholder="{{ __("Containment Volume From (m³)") }}"
                                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')" />
                                            </div>
                                            <label for="volume_max" class="control-label col-md-2">{{ __("Containment Volume To (m³)") }}</label>

                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="volume_max" name="volume_max"
                                                    placeholder="{{ __("Containment Volume To (m³)") }}"
                                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')" />
                                            </div>



                                        </div>
                                        <div class="form-group row">
                                            <label for="containment_location" class="control-label col-md-2">{{ __("Containment Location") }}
                                           </label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="containment_location">
                                                    <option value="">{{ __("Containment Location") }}</option>
                                                    @foreach ($containmentLocations as $key => $value)
                                                        <option value="{{ $value }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <label for="
                                            "
                                                class="control-label col-md-2">{{ __("Containment Type") }}
                                                </label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="type_id">
                                                    <option value="">{{ __("Containment Type") }}</option>
                                                    @foreach ($containmentTypes as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <label for="emptying_status" class="control-label col-md-2">{{ __("Emptying Status") }}
                                                </label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="emptying_status">
                                                    <option value="">{{ __("Emptying Status") }}</option>
                                                    <option value="TRUE">Yes</option>
                                                    <option value="FALSE">No</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="septic_compliance" class="control-label col-md-2">{{ __("Septic Tank Standard Compliance") }}
                                                 </label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="septic_compliance">
                                                    <option value="">{{ __("Septic Tank Standard Compliance") }}</option>
                                                    <option value="true">Yes</option>
                                                    <option value="false">No</option>
                                                </select>
                                            </div>
                                            <label for="date_from" class="control-label col-md-2">{{ __("Containment Construction Date From") }}
                                                </label>
                                            <div class="col-md-2">
                                                <input type="date" class="form-control date" id="date_from"
                                                    placeholder="{{ __("Containment Construction Date From") }}" onclick = 'this.showPicker();' />
                                            </div>
                                            <label for="date_to" class="control-label col-md-2">{{ __("Containment Construction Date To") }}
                                               </label>
                                            <div class="col-md-2">
                                                <input type="date" class="form-control date" id="date_to"
                                                    placeholder="{{ __("Containment Construction Date To") }}" onclick = 'this.showPicker();' />
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="house_number" class="control-label col-md-2">{{ __("House Number of Connected Building") }}
                                                </label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="house_number"
                                                    placeholder="{{ __("House Number of Connected Building") }}"
                                                    oninput = "this.value = this.value.replace(/[^a-zA-Z0-9-]/g, ''); " />
                                                <!-- Allow only alphabetic characters, numbers, and the hyphen (-) -->
                                            </div>

                                            <label for="bin" class="control-label col-md-2">{{ __("BIN of Connected  Building") }}
                                               </label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="bin"
                                                    placeholder="{{ __("BIN of Connected  Building") }}"
                                                    oninput = "this.value = this.value.replace(/[^a-zA-Z0-9]/g, ''); " />
                                                <!-- Allow only alphabetic and numeric characters -->
                                            </div>
                                        </div>
                                        <div class="card-footer text-right">
                                            <button type="submit" class="btn btn-info ">{{ __("Filter") }}</button>
                                            <button type="reset" id="reset-filter"
                                                class="btn btn-info reset">{{ __("Reset") }}</button>
                                        </div>
                                    </form>
                                </div>
                                <!--- accordion body!-->
                            </div>
                            <!--- collapseOne!-->
                        </div>
                        <!--- accordion item!-->
                    </div>
                    <!--- accordion !-->
                </div>
            </div>
            <!--- row !-->
        </div>
        <!--- card body !-->
        <div class="card-body">
            <div style="overflow: auto; width: 100%;">
                <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __("Containment ID") }}</th>
                            <th>{{ __("Containment Type") }}</th>
                            <th>{{ __("Containment Volume (m³)") }}</th>
                            <th>{{ __("Containment Location") }}</th>
                            <th>{{ __("Actions") }}</th>

                        </tr>
                    </thead>
                </table>
            </div>
        </div><!-- /.card-body -->
    </div> <!-- /.card -->

@stop

@push('scripts')
    <script>
        $.fn.dataTable.ext.errMode = 'throw';
        $(function() {

            var containment_id = '';
            var type_id = '';
            var containment_location = '';
            var emptying_status = '';
            var septic_compliance = '';
            var bin = '';
            var house_number = '';
            var volume_min = '';
            var volume_max = '';

            // var roadcd = '';

            var dataTable = $('#data-table').DataTable({
                bFilter: false,
                processing: true,
                processing: true,
                serverSide: true,
                scrollCollapse: true,
                stateSave: false,
                "stateDuration": 1800, // In seconds; keep state for half an hour

                ajax: {
                    url: '{!! url('fsm/containments/data') !!}',
                    data: function(d) {
                        d.containment_id = $('#containment_id').val();
                        d.type_id = $('#type_id').val();
                        d.containment_location = $('#containment_location').val();
                        d.emptying_status = $('#emptying_status').val();
                        d.septic_compliance = $('#septic_compliance').val();
                        d.bin = $('#bin').val();
                        d.house_number = $('#house_number').val();
                        d.const_date = $('#const_date').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                        d.volume_min = $('#volume_min').val();
                        d.volume_max = $('#volume_max').val();
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'type_id',
                        name: 'type_id'
                    },
                    {
                        data: 'size',
                        name: 'size'
                    },
                    {
                        data: 'location',
                        name: 'location'
                    },

                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ]
            }).on('draw', function() {
                $('.delete').on('click', function(e) {
                    var form = $(this).closest("form");
                    event.preventDefault();
                    Swal.fire({
                    title: "{{__('Are you sure?')}}",
                    text: {{ Illuminate\Support\Js::from(__('You won\'t be able to revert this!')) }},
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Yes, delete it!') }}",
                    cancelButtonText: '{{ __('Cancel') }}',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();

                        }
                    })
                });
            });

            var containment_id = '',
                type_id = '',
                volume_min = '',
                volume_max = '',
                containment_location = '',
                emptying_status = '';
            septic_compliance = '',
                bin = '',
                house_number = '',
                const_date = '',



                $('#filter-form').on('submit', function(e) {
                    var date_from = $('#date_from').val();
                    var date_to = $('#date_to').val();
                    if ((date_from !== '') && (date_to === '')) {

                        Swal.fire({
                            title:'{{ __('Date To is Required') }}',
                            text: '{{ __('Please Select Date To!') }}',
                            icon: 'warning',
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: "{{__('Close')}}"
                        })

                        return false;
                    }
                    if ((date_from === '') && (date_to !== '')) {

                        Swal.fire({
                            title:'{{ __('Date From is Required') }}',
                            text: '{{ __('Please Select Date From!') }}',
                            icon: 'warning',
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: "{{__('Close')}}"
                        })

                        return false;
                    }

                    if (date_from !== '' && date_to !== '' && date_to <= date_from) {
                        Swal.fire({
                            title:'{{ __('Invalid Date Range') }}',
                            text: '{{ __('Date To cannot be Before Date From!') }}',
                            icon: 'warning',
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '{{__('Close')}}'
                        });

                        return false;
                    }


                    const volumeMin = document.getElementById('volume_min').value.trim();
                    const volumeMax = document.getElementById('volume_max').value.trim();

                    if ((volumeMin === '') && (volumeMax !== '')) {
                        Swal.fire({
                            title:'{{ __('Minimum Volume is Required') }}',
                            text: '{{ __('Please enter the Minimum Volume!') }}',
                            icon: 'warning',
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: "{{__('Close')}}"

                        });

                        return false;
                    }

                    if ((volumeMax === '') && (volumeMin !== '')) {
                        Swal.fire({
                            title: '{{ __("Maximum Volume is Required") }}',
                            text: '{{ __("Please enter the Maximum Volume!") }}',
                            icon: 'warning',
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: "{{__('Close')}}"
                        });

                        return false;
                    }

                    if (volumeMin !== '' && volumeMax !== '' && parseFloat(volumeMax) <= parseFloat(
                            volumeMin)) {
                        Swal.fire({
                            title: '{{ __("Invalid Volume Range") }}',
                            text: '{{ __("Maximum Volume must be greater than Minimum Volume!") }}',

                            icon: 'warning',
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: "{{__('Close')}}"
                        });

                        return false;
                    }


                    if (volumeMin !== '' && parseFloat(volumeMin) < 0.1) {
                        Swal.fire({
                            title: '{{ __("Invalid Minimum Volume") }}',
                            text: '{{__("Minimum Volume must be at least 0.1 and cannot be 0!")}}',
                            icon: 'warning',
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: "{{__('Close')}}",
                        });

                        return false;
                    }

                    e.preventDefault();
                    dataTable.draw();
                    containment_id = $('#containment_id').val();
                    type_id = $('#type_id').val();
                    containment_location = $('#containment_location').val();
                    emptying_status = $('#emptying_status').val();
                    septic_compliance = $('#septic_compliance').val();
                    bin = $('#bin').val();
                    house_number = $('#house_number').val();
                    const_date = $('#const_date').val();
                    volume_min = $('#volume_min').val();
                    volume_max = $('#volume_max').val();
                });

            resetDataTable(dataTable);
            $("#export").on("click", function(e) {
                e.preventDefault();
                var containment_id = $('#containment_id').val();
                var type_id = $('#type_id').val();
                var volume_min = $('#volume_min').val();
                var volume_max = $('#volume_max').val();
                var containment_location = $('#containment_location').val();
                var emptying_status = $('#emptying_status').val();
                var septic_compliance = $('#septic_compliance').val();
                var bin = $('#bin').val();
                var house_number = $('#house_number').val();
                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();
                var const_date = $('#const_date').val();
                var searchData = $('input[type=search]').val();
                window.location.href = "{!! url('fsm/containments/export?searchData=') !!}" + searchData +
                    "&containment_id=" + containment_id +
                    "&type_id=" + type_id +
                    "&volume_min=" + volume_min +
                    "&volume_max=" + volume_max +
                    "&containment_location=" + containment_location +
                    "&emptying_status=" + emptying_status +
                    "&septic_compliance=" + septic_compliance +
                    "&bin=" + bin +
                    "&house_number=" + house_number +
                    "&date_from=" + date_from +
                    "&date_to=" + date_to +
                    "&const_date=" + const_date;

            });

            $("#export-building-containment").on("click", function(e) {
                e.preventDefault();
                var containment_id = $('#containment_id').val();
                var type_id = $('#type_id').val();
                var volume_min = $('#volume_min').val();
                var volume_max = $('#volume_max').val();
                var containment_location = $('#containment_location').val();
                var emptying_status = $('#emptying_status').val();
                var septic_compliance = $('#septic_compliance').val();
                var bin = $('#bin').val();
                var house_number = $('#house_number').val();
                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();
                var const_date = $('#const_date').val();
                var searchData = $('input[type=search]').val();
                window.location.href = "{!! url('fsm/containments/export-building-containment?searchData=') !!}" + searchData +
                    "&containment_id=" + containment_id +
                    "&type_id=" + type_id +
                    "&volume_min=" + volume_min +
                    "&volume_max=" + volume_max +
                    "&containment_location=" + containment_location +
                    "&emptying_status=" + emptying_status +
                    "&septic_compliance=" + septic_compliance +
                    "&bin=" + bin +
                    "&house_number=" + house_number +
                    "&date_from=" + date_from +
                    "&date_to=" + date_to +
                    "&const_date=" + const_date;
            });

            $("#export-shp").on("click", function(e) {
                e.preventDefault();
                var cql_param = getCQLParams();
                window.location.href =
                    "{{ Config::get('constants.GEOSERVER_URL') }}wfs?service=WFS&version=1.0.0&request=GetFeature&authkey={{ Config::get('constants.AUTH_KEY') }}&typeName={{ Config::get('constants.GEOSERVER_WORKSPACE') }}:containments_layer+&CQL_FILTER=" +
                    cql_param + " &outputFormat=SHAPE-ZIP&format_options=filename:Containments.zip";

            });

            $("#export-kml").on("click", function(e) {
                e.preventDefault();
                var cql_param = getCQLParams();
                window.location.href =
                    "{{ Config::get('constants.GEOSERVER_URL') }}wfs?service=WFS&version=1.0.0&request=GetFeature&authkey={{ Config::get('constants.AUTH_KEY') }}&typeName={{ Config::get('constants.GEOSERVER_WORKSPACE') }}:containments_layer+&CQL_FILTER=" +
                    cql_param + " &outputFormat=KML&format_options=filename:Containments.kml";

            });

            function getCQLParams() {
                containment_id = $('#containment_id').val();
                type_id = $('#type_id').val();
                volume_min = $('#volume_min').val();
                volume_max = $('#volume_max').val();
                containment_location = $('#containment_location').val();
                if (containment_location == '0') {
                    var location = 'Inside the building footprint';
                } else {
                    var location = 'Outside the building footprint';
                }
                emptying_status = $('#emptying_status').val();
                bin = $('#bin').val();
                house_number = $('#house_number').val();

                roadcd = $('#road_code').val();
                septic_compliance = $('#septic_compliance').val();
                const_date = $('#const_date').val();
                date_from = $('#date_from').val();
                date_to = $('#date_from').val();
                var cql_param = "deleted_at IS NULL";

                if (containment_id) {
                    cql_param += " AND \"id\" ILIKE '%" + containment_id + "%'";
                }
                if (type_id) {
                    cql_param += " AND type_id ='" + type_id + "'";
                }
                if (volume_min && volume_max) {
                    cql_param += " AND size BETWEEN '" + volume_min + "' AND '" + volume_max + "'";
                }

                if (containment_location) {
                    cql_param += " AND location ='" + containment_location + "'";
                }
                if (emptying_status) {
                    cql_param += " AND emptied_status ='" + emptying_status + "'";
                }
                if (septic_compliance) {
                    cql_param += " AND septic_criteria ='" + septic_compliance + "'";
                }
                if (bin) {
                    cql_param += " AND strConcat(bin,'') ILIKE '%" + bin + "%'";
                }
                if (house_number) {
                    cql_param += " AND house_number  ILIKE '%" + house_number + "%'";
                }
                if (const_date) {

                    // Split the date range string into start and end dates
                    const [startDate, endDate] = const_date.split(' - ');

                    // Trim any potential whitespace (though split handles it in this case)
                    const trimmedStartDate = startDate.trim();
                    const trimmedEndDate = endDate.trim();
                    cql_param += " AND construction_date BETWEEN '" + trimmedStartDate + "' AND '" +
                        trimmedEndDate + "'";
                }

                if (date_from && date_to) {
                    cql_param += " AND construction_date BETWEEN '" + date_from.trim() + "' AND '" + date_to
                        .trim() + "'";
                }


                return encodeURI(cql_param);
            }

            $('#const_date').daterangepicker({
                autoUpdateInput: false,
                showDropdowns: true,
                autoApply: true,
                maxDate: moment().format('MM/DD/YYYY'),
                drops: "auto"
            });
            $('#const_date').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format(
                    'MM/DD/YYYY'));

            });

            $('#const_date').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });


        });
    </script>
@endpush
