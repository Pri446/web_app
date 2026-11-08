<!-- Last Modified Date: 09-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.dashboard')
@push('style')
<style type="text/css">
.dataTables_filter {
    display: none;
}
</style>
@endpush
@section('title', $page_title)
@section('content')
<div class="card">
    <div class="card-header">
    @can('Add KPI Target')
        <a href="{{ action('Fsm\KpiTargetController@create') }}" class="btn btn-info">{{__('Add KPI Target')}}</a>
      @endcan
      @can('Export KPI Target')
      <a href="#" id="export" class="btn btn-info">{{__('Export to CSV')}}</a>
 @endcan

        <a href="#" class="btn btn-info float-right" data-toggle="collapse" data-target="#collapseFilter"
            aria-expanded="false" aria-controls="collapseFilter">{{ __('Show Filter') }}</a>
    </div><!-- /.card-header -->
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="accordion" id="accordionFilter">
                    <div class="accordion-item">
                        <div id="collapseFilter" class="collapse" aria-labelledby="filter"
                            data-parent="#accordionFilter">
                            <div class="accordion-body">
                                <form class="form-horizontal" id="filter-form">
                                    <div class="form-group row">

                                        <label for="indicators" class="col-md-2 col-form-label ">{{__('Indicator')}}</label>
                                        <div class="col-md-2">
                                        <select class="form-control" id="indicator_id" name="indicator_id">
                                            <option value="">{{__('Indicator')}}</option>
                                            @foreach($indicators as $value)
                                            <option value="{{$value}}">{{$value}}</option>
                                            @endforeach
                                        </select>
                                        </div>
                                        <label for="year" class="col-md-2 col-form-label ">{{__('Year')}}</label>
                                        <div class="col-md-2">
                                        <select class="form-control" id="year" name="year">
                                            <option value="">{{__('Year')}}</option>
                                            @foreach($years as $value)
                                            <option value="{{$value}}">{{$value}}</option>
                                            @endforeach
                                        </select>
                                        </div>
                                        </div>

                                    <div class="card-footer text-right">
                                        <button type="submit" class="btn btn-info">{{ __('Filter') }}</button>
                                        <button type="reset" id="reset-filter" class="btn btn-info reset">{{ __('Reset') }}</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body"> <div style="overflow: auto; width: 100%;">
            <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>{{__('ID')}}</th>
                    <th>{{__('Indicator')}}</th>
                    <th>{{__('Year')}}</th>
                    <th>{{__('Target (%)')}}</th>
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
        ajax: {
            url: '{!! url("fsm/kpi-targets/data") !!}',
            data: function(d) {

                d.indicator_id = $('#indicator_id').val();
                d.year = $('#year').val();

            }
        },
        columns: [{
                data: 'id',
                name: 'id'
            },

            {
                data: 'indicator_id',
                name: 'indicator_id'
            },
            {
                data: 'year',
                name: 'year'
            },
            {
                data: 'target',
                name: 'target'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ],
        order: [ [0, 'desc'] ]
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
        var indicator_id = $('#indicator_id').val();
        var year = $('#year').val();
        window.globalLoader.download(
            "{!! url('fsm/kpi-targets/export?searchData=') !!}" + searchData +
                "&indicator_id=" + indicator_id + "&year=" + year,
            'kpi-targets.csv',
            this
        ).catch(function () {});
    })

});
</script>
@endpush
