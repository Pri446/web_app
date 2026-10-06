<!-- Last Modified Date: 11-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)  (© ISPL, 2024) -->
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

        @can('Add WaterSupply On Map')
        <a href="{{ action('MapsController@index') }}#add_watersupply_control" class="btn btn-info">{{ __('Add Water Supply')}}</a>
        @endcan    
        @can('Export WaterSupply Network to CSV')
            <a href="" id="export" class="btn btn-info">{{ __('Export to CSV') }}</a>

            @endcan
            @can('Export WaterSupply Network to Shape')
                    <a href="#" id="export-shp" class="btn btn-info">{{ __('Export to Shape File') }}</a>
            @endcan
            @can('Export WaterSupply Network to KML')
                    <a href="#" id="export-kml" class="btn btn-info">{{ __('Export to KML') }}</a>
            @endcan
            <a class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">{{ __('Show Filter') }}</a>

        </div>
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
                                            <label for="code" class="col-md-2 col-form-label ">{{ __('Code') }}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="code"  placeholder="{{ __('Code') }}" />
                                            </div>
                                            <label for="project_name" class="col-md-2 col-form-label ">{{ __('Project Name') }}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="project_name" placeholder="{{ __('Project Name') }}" />
                                            </div>
                                            <label for="length" class="col-md-2 col-form-label ">{{ __('Length (m)') }}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="lengths" placeholder="{{ __('Length (m)') }}"/>
                                            </div>
                                        </div>
                                        <div class="card-footer text-right">
                                            <button type="submit" class="btn btn-info ">{{ __('Filter') }}</button>
                                            <button id="reset-filter" type="reset" class="btn btn-info">{{ __('Reset') }}</button>
                                        </div>
                                    </form>
                                </div>  <!--- accordion body!-->
                            </div>    <!--- collapseOne!-->
                        </div>      <!--- accordion item!-->
                    </div>        <!--- accordion !-->
                </div>
            </div>            <!--- row !-->
        </div>              <!--- card body !-->

        <div class="card-body">
            <div style="overflow: auto; width: 100%;">
                <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Road Code') }}</th>
                            <th>{{ __('Project Name') }}</th>
                            <th>{{ __('Type') }} </th>
                            <th>{{ __('Material Type') }} </th>
                            <th>{{ __('Diameter (mm)') }}</th>
                            <th>{{ __('Length (m)') }}</th>
                            <th>{{ __('Actions') }}</th>
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
    pageLength: 10,
    "stateDuration": 1800, // In seconds; keep state for half an hour
    ajax: {
        url: '{!!url("utilityinfo/watersupplys/data")!!}',
        data: function(d) {
            d.code = $('#code').val();
            d.project_name = $('#project_name').val();
            d.lengths = $('#lengths').val();
        }
    },
    columns: [
        { data: 'code', name: 'code' },
        { data: 'road_code', name: 'road_code' },
        { data: 'project_name', name: 'project_name' },
        { data: 'type', name: 'type' },
        { data: 'material_type', name: 'material_type' },
        { data: 'diameter', name: 'diameter' },
        { data: 'length', name: 'length' },
        { data: 'action', name: 'action', orderable: false, searchable: false}
    ],
    order: [
        [0, 'desc']
    ],
    }).on('draw', function() {
        $('.delete').on('click', function(e) {
            var form = $(this).closest("form");
            event.preventDefault();
            Swal.fire({
                title: "{{ __('Are you sure?') }}",
                text: "{!! __('You won\'t be able to revert this!') !!}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: "{{__('Yes, delete it!')}}",
                cancelButtonText: '{{ __('Cancel') }}',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            })

        });
    });

    var code = '', project_name = '', length = '';
    $('#filter-form').on('submit', function(e){
     
        e.preventDefault();
        dataTable.draw();
        code = $('#code').val();
        length = $('#lengths').val();
        project_name = $('#project_name').val();

    });

   
    //  $('#data-table_filter input[type=search]').attr('readonly', 'readonly');
    filterDataTable(dataTable);
    resetDataTable(dataTable);
    $("#export").on("click", function(e) {
        e.preventDefault();
        var searchData = $('input[type=search]').val();
        var code = $('#code').val();
        var length = $('#lengths').val();
        var project_name = $('#project_name').val();
        window.location.href = "{!! url('utilityinfo/watersupplys/export?searchData=') !!}" + searchData +"&code=" + code + "&length=" + length +  "&project_name=" + project_name;
    })
    $("#export-shp").on("click", function (e) {
      e.preventDefault();
        var cql_param = getCQLParams();

        window.location.href = "{{ route('gis.export', ['format' => 'shp']) }}?layer=watersupply_network_layer&filter=" + encodeURIComponent(cql_param);

    })
    $("#export-kml").on("click", function(e) {
        e.preventDefault();
        var cql_param = getCQLParams();

        window.location.href = "{{ route('gis.export', ['format' => 'kml']) }}?layer=watersupply_network_layer&filter=" + encodeURIComponent(cql_param);
    });


    function getCQLParams(){
        code = $('#code').val();
        length = $('#length').val();
        project_name = $('#project_name').val();
        var cql_param = "deleted_at IS NULL";
        if (code) {
            cql_param += " AND code ILIKE '%" + code + "%'";
        }

        if (length) {
            cql_param += " AND length ='" + length + "'";
        }

        if (project_name) {
            cql_param += " AND project_name ='" + project_name + "'";
        }
        return encodeURI(cql_param);
    }

   
});

</script>
@endpush
