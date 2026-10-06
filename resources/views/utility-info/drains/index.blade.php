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

  @can('Add Drain On Map')
  <a href="{{ action('MapsController@index') }}#add_drain_control" class="btn btn-info">{{ __('Add Drain') }}</a>
  @endcan  
  @can('Export Drains to CSV')
    <a href="{{ action('UtilityInfo\DrainController@export') }}" id="export" class="btn btn-info">{{ __('Export to CSV')}}</a>

    @endcan
    @can('Export Drains to Shape')
    <a href="#" id="export-shp" class="btn btn-info">{{ __('Export to Shape File')}}</a>
    @endcan
    @can('Export Drains to KML')
    <a href="#" id="export-kml" class="btn btn-info">{{ __('Export to KML')}}</a>
    @endcan
    <a href="#" class="btn btn-info float-right" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter">{{ __('Show Filter')}}</a>
  </div><!-- /.box-header -->
  <div class="card-body">
    <div class="row">
      <div class="col-12">
        <div class="accordion" id="accordionFilter">
          <div class="accordion-item">
            <div id="collapseFilter" class="collapse" aria-labelledby="filter" data-parent="#accordionFilter">
              <div class="accordion-body">
                <form class="form-horizontal" id="filter-form">
                  <div class="form-group row">
                    <label for="code_text" class="col-md-2 col-form-label ">{{ __('Code')}}</label>
                    <div class="col-md-2">
                      <input type="text" class="form-control" id="code_text" placeholder="{{__("Code")}}" oninput="validateAlphanumeric(this)"/>
                    </div>
                    <label for="cover_type" class="col-md-2 col-form-label ">{{ __('Cover Type')}}</label>
                    <div class="col-md-2">
                      <select class="form-control" id="cover_type">
                        <option value="">{{__('Cover Type')}}</option>
                        @foreach($cover_type as $key)
                        <option value="{{$key}}">{{$key}}</option>
                        @endforeach

                      </select>
                    </div>
                  </div>
                  <div class="card-footer text-right">
                    <button type="submit" class="btn btn-info ">{{ __('Filter')}}</button>
                    <button id="reset-filter" type="reset" class="btn btn-info ">{{ __('Reset')}}</button>
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
      <table id="data-table" class="table table-bordered table-striped dataTable dtr-inline" width="100%">
        <thead>
          <tr>
            <th>{{ __('Code')}}</th>
            <th>{{ __('Road Code')}}</th>
            <th>{{ __('Surface Type')}}</th>
            <th>{{ __('Cover Type')}}</th>
            <th>{{ __('Width (mm)')}}</th>
            <th>{{ __('Length (m)')}}</th>
            <th>{{ __('Treatment Plant')}}</th>
            <th>{{ __('Actions')}}</th>
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
      ajax: {
        url: '{!! url("utilityinfo/drains/data") !!}',
        data: function(d) {
          d.code = $('#code_text').val();
          d.cover_type = $('#cover_type').val();
        }
      },
      columns: [{
          data: 'code',
          name: 'code'
        },
        {
          data: 'road_code',
          name: 'road_code'
        },
        {
          data: 'surface_type',
          name: 'surface_type'
        },
        {
          data: 'cover_type',
          name: 'cover_type'
        },
        {
          data: 'size',
          name: 'size',
          render: function(data) {
            return parseFloat(data).toFixed(2);
          }
        },
        {
          data: 'length',
          name: 'length'
        },
        {
          data: 'treatment_plant_id',
          name: 'treatment_plant_id'
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
          confirmButtonText: "{{__('Yes, delete it!')}}",
          cancelButtonText: '{{ __('Cancel') }}',
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        })

      });
    });

    var code = '',
      cover_type = '';

    $('#filter-form').on('submit', function(e) {
      
      e.preventDefault();
      dataTable.draw();
      code = $('#code_text').val();
      cover_type = $('#cover_type').val();

    });
    filterDataTable(dataTable);
    resetDataTable(dataTable);
    //  $('#data-table_filter input[type=search]').attr('readonly', 'readonly');
    $("#export").on("click", function(e) {
      e.preventDefault();
      var searchData = $('input[type=search]').val();
      var code = $('#code_text').val();
      var cover_type = $('#cover_type').val();
      window.location.href = "{!! url('utilityinfo/drains/export?searchData=') !!}" + searchData + "&code=" + code + "&cover_type=" + cover_type;
    })

    $("#export-shp").on("click", function(e) {
      e.preventDefault();
      var cql_param = getCQLParams();
     
      window.location.href = "{{ route('gis.export', ['format' => 'shp']) }}?layer=drains_layer&filter=" + encodeURIComponent(cql_param);
    })

    $("#export-kml").on("click", function(e) {
      e.preventDefault();
      var cql_param = getCQLParams();
     
      window.location.href = "{{ route('gis.export', ['format' => 'kml']) }}?layer=drains_layer&filter=" + encodeURIComponent(cql_param);
     
    });

    function getCQLParams() {
      code = $('#code_text').val();
      type = $('#cover_type').val();

      var cql_param = "1=1 AND deleted_at IS NULL";
      if (code) {
        cql_param += " AND code ILIKE '%" + code + "%'";
      }
      if (type) {
        cql_param += " AND cover_type ='" + type + "'";
      }
      return encodeURI(cql_param);

    }
  });
</script>
@endpush
