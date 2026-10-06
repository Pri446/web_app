@extends('layouts.dashboard')
@section('title', $page_title)


@section('content')
    <div class="modal fade" id="containmentsModal" tabindex="-1" role="dialog" aria-labelledby="containmentsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="containmentsModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            @can('Add Building Structure')
                <a href="{{ action('BuildingInfo\BuildingController@create') }}"
                    class="btn btn-info">{{ __('Add Building') }}</a>
            @endcan
            @can('Export Building Structures')
                <a href="{{ action('BuildingInfo\BuildingController@export') }}" id="export"
                    class="btn btn-info">{{ __('Export to CSV') }}</a>
            @endcan
            @can('Export Building Structures')
                <a href="#" id="export-shp" class="btn btn-info">{{ __('Export to Shape File') }}</a>
            @endcan
            @can('Export Building Structures')
                <a href="#" id="export-kml" class="btn btn-info">{{ __('Export to KML') }}</a>
            @endcan

            <a href="#" class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse"
                data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                {{ __('Show Filter') }}
            </a>
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
                                            <label for="bin_text" class="control-label col-md-2">{{ __('BIN') }}
                                            </label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="bin_text"
                                                    placeholder="{{ __('BIN') }}"
                                                    oninput = "this.value = this.value.replace(/[^a-zA-Z0-9]/g, ''); " />
                                                <!-- Allow only alphabetic and numeric characters -->
                                            </div>
                                            <label for="structype_select" class="control-label col-md-2">
                                                {{ __('Structure Type') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="structype_select">
                                                    <option value="">{{ __('Structure Type') }}</option>
                                                    @foreach ($structure_type as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label for="ward_select"
                                                class="control-label col-md-2">{{ __('Ward Number') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="ward_select">
                                                    <option value="">{{ __('Ward Number') }}</option>
                                                    @foreach ($ward as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="functional_use_select"
                                                class="control-label col-md-2">{{ __('Functional Use') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="functional_use_select">
                                                    <option value="">{{ __('Functional Use') }}</option>
                                                    @foreach ($functional_use as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label for="use_category_select"
                                                class="control-label col-md-2">{{ __('Use Category') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="use_category_select">
                                                    <option value="">{{ __('Use Category') }}</option>
                                                </select>
                                            </div>
                                            <label for="owner_name"
                                                class="control-label col-md-2">{{ __('Owner Name') }}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="owner_name"
                                                    placeholder="{{ __('Owner Name') }}" />
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="toilet"
                                                class="control-label col-md-2">{{ __('Presence of Toilet') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="toilet">
                                                    <option value="">{{ __('Presence of Toilet') }}</option>
                                                    <option value="Yes">Yes</option>
                                                    <option value="No">No</option>
                                                </select>
                                            </div>

                                            <label for="sanitation_system_id"
                                                class="control-label col-md-2">{{ __('Sanitation Systems') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="sanitation_system_id">
                                                    <option value="">{{ __('Sanitation Systems') }}</option>
                                                    @foreach ($sanitation_systems as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label for="watersourc"
                                                class="control-label col-md-2">{{ __('Main Drinking Water Source') }}</label>

                                            <div class="col-md-2">
                                                <select class="form-control" id="watersourc">
                                                    <option value="">{{ __('Main Drinking Water Source') }}</option>
                                                    @foreach ($water_sources as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="well_prese"
                                                class="control-label col-md-2">{{ __('Well in Premises') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="well_prese">
                                                    <option value="">{{ __('Well in Premises') }}</option>
                                                    <option value="Yes">Yes</option>
                                                    <option value="No">No</option>
                                                </select>
                                            </div>
                                            <label for="floor_count"
                                                class="control-label col-md-2">{{ __('Number of Floors') }}
                                            </label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="floor_count"
                                                    placeholder="{{ __('Number of Floors') }}" />
                                            </div>
                                            <label for="house_number"
                                                class="control-label col-md-2">{{ __('House Number') }}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="house_number"
                                                    placeholder="{{ __('House Number') }}
                                                    oninput = "this.value=this.value.replace(/[^a-zA-Z0-9-]/g, ''
                                                    ); " />
                                                    <!-- Allow only alphabetic characters, numbers, and the hyphen (-) -->
                                                </div>
                                            </div>


                                            <div class="form-group row">
                                                <label for="date_from" class="control-label col-md-2"> {{ __('Construction Date From') }}</label>
                                                <div class="col-md-2">
                                                    <input type="date" class="form-control" id="date_from"
                                                        placeholder=" {{ __('Construction Date From') }}"
                                                        onclick = 'this.showPicker();' />
                                                </div>
                                                <label for="date_to" class="control-label col-md-2">{{ __('Construction Date To') }}</label>

                                                <div class="col-md-2">
                                                    <input type="date" class="form-control" id="date_to"
                                                        placeholder="{{ __('Construction Date To') }}" onclick = 'this.showPicker();' />
                                                </div>



                                                <label for="road_code" class="control-label col-md-2">{{ __('Road Code') }}</label>
                                                <div class="col-md-2">
                                                    <select class="form-control" id="road_code">
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="card-footer text-right">
                                                <button type="submit" class="btn btn-info">{{ __('Filter') }}</button>
                                                <button type="reset" id="reset-filter" class="btn btn-info">{{ __('Reset') }}</button>
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
                    <!---col!-->
                </div>
                <!--- row !-->
            </div>
            <!--- card body !-->
            <div class="card-body">

                <div style="overflow: auto; width: 100%;">

                    <table id="data-table" class="table table-bordered table-striped dataTable dtr-inline" width="100%">
                        <thead>
                            <tr>
                                <th>{{ __('BIN') }}</th>
                                <th>{{ __('House Number') }}</th>
                                <th>{{ __('Road Code') }}</th>
                                <th>{{ __('Ward Number') }}</th>
                                <th>{{ __('Structure Type') }}</th>
                                <th>{{ __('Number of Floors') }}</th>
                                <th>{{ __('Presence of Toilet') }}</th>
                                <th>{{ __('Sanitation System') }}</th>
                                <th>{{ __('Owner Name') }}</th>
                                <th>{{ __('Actions') }}</th>

                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div> <!-- /.card -->

@stop


@push('scripts')
    <script>
        $.fn.dataTable.ext.errMode = 'throw';
        $(function() {

            var bin = '';
            var house_number = '';
            var structype = '';
            var ward = '';
            var functional_use = '';
            var roadcd = '';
            var ownername = '';
            var ownername = '';
            var sanitation_system_id = '';
            var floor_count = '';

            var dataTable = $('#data-table').DataTable({

                bFilter: false,
                processing: true,
                serverSide: true,
                scrollCollapse: true,
                "bStateSave": true,
                "stateDuration": 1800, // In seconds; keep state for half an hour
                ajax: {
                    url: '{!! url('building-info/buildings/data') !!}',
                    data: function(d) {
                        d.bin = $('#bin_text').val();
                        d.house_number = $('#house_number').val();
                        d.structype = $('#structype_select').val();
                        d.ward = $('#ward_select').val();
                        d.functional_use = $('#functional_use_select').val();
                        d.roadcd = $('#road_code').val();
                        d.toilet = $('#toilet').val();
                        d.defecation = $('#defecation').val();
                        d.toiletconn = $('#toiletconn').val();
                        d.watersourc = $('#watersourc').val();
                        d.well_prese = $('#well_prese').val();
                        d.ownername = $('#owner_name').val();
                        d.sanitation_system_id = $('#sanitation_system_id').val();
                        d.floor_count = $('#floor_count').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                        d.use_category_select = $('#use_category_select').val();



                    }
                },
                columns: [{
                        data: 'bin',
                        name: 'bin'
                    },
                    {
                        data: 'house_number',
                        name: 'house_number'
                    },
                    {
                        data: 'road_code',
                        name: 'road_code'
                    },
                    {
                        data: 'ward',
                        name: 'ward'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'floor_count',
                        name: 'floor_count'
                    },
                    {
                        data: 'toilet_status',
                        name: 'toilet_status'
                    },
                    {
                        data: 'sanitation_system_id',
                        name: 'sanitation_system_id'
                    },
                    {
                        data: 'owner_name',
                        name: 'owner_name'
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
                    text: "{!! __('You won\'t be able to revert this!') !!}",
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



            $(".sidebar-toggle").on("click", function() {
                dataTable.columns.adjust().draw(false);
            });


            var bin = '',
                house_number = '',
                structype = '',
                ward = '',
                roadcd = '',
                ownername = '';
            var toilet = '';
            var defecation = '';
            var toiletconn = '';
            var watersourc = '';
            var well_prese = '';
            var sanitation_system_id = '';
            var floor_count = '';
            var date_from = '';
            var date_to = '';

            $('#filter-form').on('submit', function(e) {

                //commented this as there is no owner name validaiton in add / edit building form for now
                // var ownernameO = $('#owner_name').val();
                // ownernameO = ownernameO.trim().toLowerCase();
                // var validO = /^[a-z][a-z\s]*$/.test(ownernameO);
                // if (!validO && (ownernameO != '')) {
                //     Swal.fire({
                //         title: `Owner name should contain letters only!`,
                //         icon: "warning",
                //         button: "Close",
                //         className: "custom-swal",
                //     })
                //     return false;
                // }
                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();

                if ((date_from !== '') && (date_to === '')) {

                    Swal.fire({
                        title: "{{ __('Construction Date To is required') }}",
                        text: "{{ __('Please Select Construction Date From') }}",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Close') }}"
                    });

                    return false;
                }
                if ((date_from === '') && (date_to !== '')) {

                    Swal.fire({
                        title: "{{ __('Construction Date From is Required') }}",
                        text: "{{ __('Please Select Construction Date To') }}",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Close') }}"
                    })

                    return false;
                }
                e.preventDefault();
                dataTable.draw();
                bin = $('#bin_text').val();
                house_number = $('#house_number').val();
                structype = $('#structype_select').val();
                ward = $('#ward_select').val();
                functional_use = $('#functional_use_select').val();
                roadcd = $('#road_code').val();
                ownername = $('#owner_name').val();
                toilet = $('#toilet').val();
                watersourc = $('#watersourc').val();
                well_prese = $('#well_prese').val();
                sanitation_system_id = $('#sanitation_system_id').val();
                floor_count = $('#floor_count').val();
                date_from = $('#date_from').val();
                date_to = $('#date_to').val();
                use_category_select = $('#use_category_select').val();
                date_from = $('#date_from').val();
                date_to = $('#date_to').val();

                //save filter data in local storage
            });

            $('#road_code').prepend('<option selected=""></option>').select2({
                ajax: {
                    url: "{{ route('roadlines.get-road-names') }}",
                    data: function(params) {
                        return {
                            search: params.term,
                            // ward: $('#ward').val(),
                            page: params.page || 1
                        };
                    },
                },
                placeholder: "{{ __('Road Code') }}",
                allowClear: true,
                closeOnSelect: true,
                width: '100%'
            });
            resetDataTable(dataTable);



            $("#export").on("click", function(e) {
                e.preventDefault();

                var searchData = $('input[type=search]').val();
                var bin = $('#bin_text').val();
                var house_number = $('#house_number').val();
                var structype = $('#structype_select').val();
                var ward = $('#ward_select').val();
                var roadcd = $('#road_code').val();
                var ownername = $('#owner_name').val();
                var toilet = $('#toilet').val();
                var watersourc = $('#watersourc').val();
                var well_prese = $('#well_prese').val();
                var functional_use = $('#functional_use_select').val();
                var sanitation_system_id = $('#sanitation_system_id').val();
                var floor_count = $('#floor_count').val();
                var use_category_select = $('#use_category_select').val();
                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();
                window.location.href = "{!! url('building-info/buildings/export?searchData=') !!}" +
                    searchData +
                    "&bin=" + bin +
                    "&house_number=" + house_number +
                    "&structype=" + structype +
                    "&ward=" + ward +
                    "&roadcd=" + roadcd +
                    "&ownername=" + ownername +
                    "&toilet=" + toilet +
                    "&watersourc=" + watersourc +
                    "&well_prese=" + well_prese +
                    "&functional_use=" + functional_use +
                    "&sanitation_system_id=" + sanitation_system_id +
                    "&floor_count=" + floor_count +
                    "&use_category_select=" + use_category_select +
                    "&date_from=" + date_from +
                    "&date_to=" + date_to;
            });

            $("#export-shp").on("click", function(e) {
                e.preventDefault();
                var cql_param = getCQLParams();
                window.location.href =
                    "{{ route('gis.wfs') }}?service=WFS&version=1.0.0&request=GetFeature&typeName={{ Config::get('constants.GEOSERVER_WORKSPACE') }}:buildings_layer+&CQL_FILTER=" +
                    cql_param + " &outputFormat=SHAPE-ZIP&format_options=filename:Buildings.zip";
            });
            $("#export-kml").on("click", function(e) {
                e.preventDefault();
                var cql_param = getCQLParams();
                window.location.href =
                    "{{ route('gis.wfs') }}?service=WFS&version=1.0.0&request=GetFeature&typeName={{ Config::get('constants.GEOSERVER_WORKSPACE') }}:buildings_layer+&CQL_FILTER=" +
                    cql_param + " &outputFormat=KML&format_options=filename:Buildings.kml";

            });

            function getCQLParams() {
                bin = $('#bin_text').val();
                structype = $('#structype_select').val();
                ward = $('#ward_select').val();
                functional_use = $('#functional_use_select').val();
                use_category_select = $('#functional_use_select').val();
                roadcd = $('#road_code').val();
                ownername = $('#owner_name').val();
                toilet = $('#toilet').val();
                defecation = $('#defecation').val();
                toiletconn = $('#toiletconn').val();
                watersourc = $('#watersourc').val();
                well_prese = $('#well_prese').val();
                sanitation_system_id = $('#sanitation_system_id').val();
                floor_count = $('#floor_count').val();
                house_number = $('#house_number').val();
                date_from = $('#date_from').val();
                date_to = $('#date_to').val();

                var cql_param = "deleted_at IS NULL";
                if (bin) {
                    cql_param += " AND bin ILIKE '%" + bin + "%'";
                }
                if (structype) {
                    cql_param += " AND structure_type_id = '" + structype + "'";
                }
                if (ward) {
                    cql_param += " AND ward = '" + ward + "'";
                }
                if (functional_use) {
                    cql_param += " AND functional_use_id = '" + functional_use + "'";
                }
                if (roadcd) {
                    cql_param += " AND road_code = '" + roadcd + "'";
                }

                if (ownername) {
                    cql_param += " AND owner_name ILIKE '%" + ownername + "%'";
                }
                if (toilet) {
                    cql_param += " AND toilet_status = '" + toilet + "'";
                }

                if (toiletconn) {
                    cql_param += " AND sanitation_system_type_id = '" + toiletconn + "'";
                }
                if (watersourc) {
                    cql_param += " AND water_source_id = '" + watersourc + "'";
                }
                if (well_prese) {
                    cql_param += " AND well_presence_status = '" + well_prese + "'";
                }
                if (sanitation_system_id) {
                    cql_param += " AND sanitation_system_id = '" + sanitation_system_id + "'";
                }
                if (floor_count) {
                    cql_param += " AND strConcat(floor_count,'')  ILIKE '" + floor_count + "%'";
                }
                if (house_number) {
                    cql_param += " AND house_number ILIKE '%" + house_number + "%'";
                }
                if (use_category_select) {
                    cql_param += " AND use_category_id = '" + use_category_select + "'";
                }
                if (date_from && date_to) {
                    cql_param += " AND construction_year BETWEEN '" + date_from.trim() + "' AND '" + date_to
                        .trim() + "'";
                }
                return encodeURI(cql_param);
            }
        });

        $(document).on('click', '.containment', function() {
            var modalHeader = $('#containmentsModal .modal-header');
            var modalBody = $('#containmentsModal .modal-body');
            var binId = $(this).data('id');
            $.ajax({
                url: 'buildings/' + binId + '/listContainments',
                type: 'GET',
                success: function(data) {
                    modalHeader.find('.modal-title').text(data.title);
                    modalBody.html(data.popContentsHtml);

                },
            });
        });


        document.getElementById('toilet').addEventListener('change', function() {
            const toiletPresence = this.value;
            const sanitationSelect = document.getElementById('sanitation_system_id');

            // Clear existing options
            sanitationSelect.innerHTML = '<option value="">Sanitation Systems</option>';

            if (toiletPresence === "Yes") {
                // Add options for IDs 1 to 8 and 11
                @foreach ($sanitation_systems as $key => $value)
                    @if (($key >= 1 && $key <= 8) || $key == 11)
                        sanitationSelect.innerHTML +=
                            `<option value="{{ $key }}">{{ $value }}</option>`;
                    @endif
                @endforeach
            } else if (toiletPresence === "No") {
                // Add remaining options (IDs not in 1 to 8 and 11)
                @foreach ($sanitation_systems as $key => $value)
                    @if (($key < 1 || $key > 8) && $key != 11)
                        sanitationSelect.innerHTML +=
                            `<option value="{{ $key }}">{{ $value }}</option>`;
                    @endif
                @endforeach
            }
        });




        $('#functional_use_select').on('change', function() {
            var functionalUseId = $(this).val();

            // Generate the URL correctly with the parameter
            var url = '{!! route('functionaluse.getusecat', ':functionalUseId') !!}'.replace(':functionalUseId', functionalUseId);

            $('#use_category_select').empty().append('<option value="">Use Category</option>');

            if (functionalUseId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $.each(data, function(id, name) {
                            // Populate the dropdown with options
                            $('#use_category_select').append('<option value="' + id + '">' +
                                name + '</option>');
                        });
                    },
                    error: function() {
                        alert('Error fetching use categories.');
                    }
                });
            }
        });

        // Add change event for the second dropdown to show input dynamically
        $('#use_category_select').on('change', function() {
            var selectedOption = $(this).val();

            // Remove any previously added input field
            $('#dynamic-input').remove();

            // Check the condition and add input field if needed
            if (selectedOption) {
                $('<div id="dynamic-input" class="col-md-2">' +

                    '</div>').insertAfter('#use_category_select');
            }
        });
    </script>
@endpush
