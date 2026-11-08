<!-- Last Modified Date: 07-05-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.dashboard')
@section('title', $page_title)
@push('style')
<style type="text/css">
.dataTables_filter {
    display: none;
}
</style>
@endpush
@section('content')
<div class="card">
    <div class="card-header">
    @can('Add Water Samples')
        <a href="{{ action('PublicHealth\WaterSamplesController@create') }}" class="btn btn-info">{{__('Add Water Samples')}}</a>
        @endcan
        @can('Export Water Samples to CSV')
        <a href="#" id="export" class="btn btn-info">{{__('Export to CSV')}}</a>
        @endcan
        @can('Export Water Samples to Shape')
        <a href="#" id="export-shp" class="btn btn-info">{{__('Export to Shape File')}}</a>
        @endcan
        @can('Export Water Samples to KML')
        <a href="#" id="export-kml" class="btn btn-info">{{__('Export to KML')}}</a>
        @endcan
        <a class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse"
            data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            {{__('Show Filter')}}
        </a>
    </div><!-- /.box-header -->
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
                                    <label for="sample_date" class="col-md-2 col-form-label ">{{__('Sample Date')}}</label>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" id="sample_date" onclick = 'this.showPicker();'/>
                                        </div>

                                        <label for="sample_location" class="col-md-2 col-form-label ">{{__('Sample Location')}}</label>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control" id="sample_location" placeholder = "{{__('Sample Location')}}" />
                                        </div>
                                        <label for="water_coliform_test_result" class="col-md-2 col-form-label ">{{__('Water Coliform Test Result')}}</label>
                                        <div class="col-md-2">
                                            <select class="form-control" id="water_coliform_test_result">
                                                <option value="">{{__('Water Coliform Test Result')}}</option>
                                                @foreach ($water_coliform_test_result as $key => $value)
                                                                <option value="{{ $key }}">{{ $value }}</option>
                                                            @endforeach
                                            </select>
                                        </div>
                                       
                                    </div>
                
                                    <div class="card-footer text-right">
                                        <button type="submit" class="btn btn-info ">{{__('Filter')}}</button>
                                        <button type="reset"   id="reset-filter" class="btn btn-info reset">{{__('Reset')}}</button>
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

    <div class="card-body"> <div style="overflow: auto; width: 100%;">
            <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>{{__('ID')}}</th>
                    <th>{{__('Sample Date')}}</th>
                    <th>{{__('Sample Location')}}</th>
                    <th>{{__('Water Coliform Test Result')}}</th>
                    <th>{{__('Actions')}}</th>
                </tr>
            </thead>
        </table>
</div>
    </div><!-- /.box-body -->
</div><!-- /.box -->
@stop

@push('scripts')
<script>
$(function() {
    var dataTable = $('#data-table').DataTable({
        bFilter: false,
        processing: true,
        serverSide: true,
        scrollCollapse: true,
        "bStateSave": true,
        "stateDuration": 1800, // In seconds; keep state for half an hour
        "fnStateSave": function(oSettings, oData) {
            localStorage.setItem('DataTables_' + window.location.pathname, JSON.stringify(oData));
        },
        "fnStateLoad": function(oSettings) {
            return JSON.parse(localStorage.getItem('DataTables_' + window.location.pathname));
        },
        ajax: {
            url: '{!! url("publichealth/water-samples/data") !!}',
            data: function(d) {
                d.sample_date = $('#sample_date').val();
                d.sample_location = $('#sample_location').val();
                d.water_coliform_test_result = $('#water_coliform_test_result').val();
            }
        },
        columns: [{
                data: 'id',
                name: 'id'
            },
            {
                data: 'sample_date',
                name: 'sample_date'
            },
            {
                data: 'sample_location',
                name: 'sample_location'
            },
            {
                data: 'water_coliform_test_result',
                name: 'water_coliform_test_result'
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



        resetDataTable(dataTable);

$('#filter-form').on('submit', function(e) {
    e.preventDefault();
    dataTable.draw();
});

  

    $("#export").on("click", function(e) {
        e.preventDefault();
        var searchData = $('input[type=search]').val();
        var sample_date = $('#sample_date').val();
        var sample_location = $('#sample_location').val();
        var water_coliform_test_result = $('#water_coliform_test_result').val();
        window.globalLoader.download(
            "{!! url('publichealth/water-samples/export?searchData=') !!}" + searchData +
                "&sample_date=" + sample_date + "&sample_location=" + sample_location + "&water_coliform_test_result=" + water_coliform_test_result,
            'water-samples.csv',
            this
        ).catch(function () {});
    })

    $("#export-shp").on("click", function(e) {
        e.preventDefault();
        var cql_param = getCQLParams();
        window.globalLoader.download(
            "{{ Config::get('constants.GEOSERVER_URL') }}wfs?service=WFS&version=1.0.0&request=GetFeature&authkey={{ Config::get('constants.AUTH_KEY') }}&typeName={{ Config::get('constants.GEOSERVER_WORKSPACE') }}:water_samples_layer+&CQL_FILTER=" + cql_param +
                " &outputFormat=SHAPE-ZIP&format_options=filename:Water Samples.zip",
            'Water Samples.zip',
            this
        ).catch(function () {});

    })

    $("#export-kml").on("click", function(e) {
        e.preventDefault();
        var cql_param = getCQLParams();

        window.globalLoader.download(
            "{{ Config::get('constants.GEOSERVER_URL') }}wfs?service=WFS&version=1.0.0&request=GetFeature&authkey={{ Config::get('constants.AUTH_KEY') }}&typeName={{ Config::get('constants.GEOSERVER_WORKSPACE') }}:water_samples_layer+&CQL_FILTER=" + cql_param +
                " &outputFormat=KML&format_options=filename:Water Samples.kml",
            'Water Samples.kml',
            this
        ).catch(function () {});
    });

    function getCQLParams() {
        var sample_date = $('#sample_date').val();
        var sample_location = $('#sample_location').val();
        var water_coliform_test_result = $('#water_coliform_test_result').val();

        var cql_param = "1=1 AND deleted_at IS NULL";
        if (sample_date) {
            cql_param += " AND sample_date ='" + sample_date + "'";
        }
        if (sample_location) {
            cql_param += " AND sample_location ='" + sample_location + "'";
        }
        if (water_coliform_test_result) {
            cql_param += " AND water_coliform_test_result ='" + water_coliform_test_result + "'";
        }

        return encodeURI(cql_param);
    }

    resetDataTable(dataTable);
    setTimeout(function() {
        localStorage.clear();
    }, 60 * 60 * 1000); ///for 1 hour
});
</script>

@endpush
