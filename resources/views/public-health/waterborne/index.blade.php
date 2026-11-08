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
        @can('Add Hotspot Identification')
        <a href="{{ action('PublicHealth\YearlyWaterborneController@create') }}" class="btn btn-info">{{__('Add Waterborne Cases Information')}}</a>
        @endcan
        @can('Export Hotspot Identifications')
        <a href="#" id="export" class="btn btn-info">{{__('Export to CSV')}}</a>
        @endcan
        <a href class="btn btn-info float-right" data-toggle="collapse" data-target="#collapseFilter"
            aria-expanded="false" aria-controls="collapseFilter">{{__('Show Filter')}}</a>
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



                                        <label for="year" class="control-label col-md-2">{{__('Year')}}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="year">
                                                    <option value="">{{__('Year')}}</option>
                                                    @foreach($years  as $key=>$value)
                                                    <option value="{{$key}}">{{$value}}</option>
                                                    @endforeach
                                                </select>
                                            </div>


                                            <label for="infected_disease" class="control-label col-md-2">{{__('Infected Disease')}}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="infected_disease">
                                                    <option value="">{{__('Infected Disease')}}</option>
                                                    @foreach($enumValues  as $key=>$value)
                                                    <option value="{{$key}}">{{$value}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>


                                    <div class="card-footer text-right">
                                        <button type="submit" class="btn btn-info">{{__('Filter')}}</button>
                                        <button type="reset" class="btn btn-info reset">{{__('Reset')}}</button>
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
                    <th>{{__('Infected Disease')}}</th>
                    <th>{{__('Year')}}</th>
                    <th>{{__('No. of Cases')}}</th>
                    <th>{{__('No. of Fatalities')}}</th>
                    <th>{{__('Actions')}}</th>
                </tr>
            </thead>
        </table>
    </div><!-- /.card-body -->
</div><!-- /.card -->
@stop

@push('scripts')
<script>
$(function() {
    var dataTable = $('#data-table').DataTable({
        processing: true,
        bFilter: false,
        serverSide: true,
        scrollCollapse: true,
        order: [],
        ajax: {
            url: '{!! url("publichealth/waterborne/data") !!}',
            data: function(d) {
                d.infected_disease = $('#infected_disease').val();
                d.year = $('#year').val();
            },
        },
        columns: [{
                data: 'id',
                name: 'id'
            },
            {
                data: 'infected_disease',
                name: 'infected_disease'
            },
            {
                data: 'year',
                name: 'year',
            },

            {
                data: 'total_no_of_cases',
                name: 'total_no_of_cases'
            },
            {
                data: 'total_no_of_fatalities',
                name: 'total_no_of_fatalities'
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

    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        dataTable.draw();
    });
    $(".reset").on("click", function(e) {
        $('#infected_disease').val('');
        $('#infected_disease option:selected').removeAttr('selected');
        $('#year').val('');
        $('#year option:selected').removeAttr('selected');
        $('#data-table').dataTable().fnDraw();
    });

    $("#export").on("click", function(e) {
        e.preventDefault();
        var searchData = $('input[type=search]').val();
                var infected_disease = $('#infected_disease').val();
                var year = $('#year').val();
        window.globalLoader.download(
            "{!! url('publichealth/waterborne/export?searchData=') !!}" + searchData +
                "&year=" + $('#year option:selected ').val() +
                "&infected_disease=" + $('#infected_disease option:selected ').val(),
            'waterborne-cases.csv',
            this
        ).catch(function () {});

    })


});
</script>


@endpush
