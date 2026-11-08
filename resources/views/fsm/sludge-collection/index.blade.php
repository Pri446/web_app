<!-- Last Modified Date: 18-04-2024
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
        @can('Export Sludge Collections')
        <a href="{{ action('Fsm\SludgeCollectionController@export') }}" id="export" class="btn btn-info">{{ __('Export to CSV') }}</a>
        @endcan
        <a class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse"
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
                                        <label for="treatment_plant_id" class="control-label col-md-2">{{ __('Treatment Plant') }}</label>
                                        <div class="col-md-2">
                                            <select class="form-control" id="treatment_plant_id">
                                                <option value="">{{ __('Treatment Plant') }}</option>
                                                @foreach($treatmentPlants as $key=>$value)
                                                <option value="{{$value}}">{{$key}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label for="date_from" class="control-label col-md-2">{{ __('Date From') }}</label>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" id="date_from"  placeholder="{{ __('Date From') }}" onclick = 'this.showPicker()'/>
                                        </div>
                                        <label for="date_to" class="control-label col-md-2">{{ __('Date To') }}</label>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" id="date_to" placeholder="{{ __('Date To') }}"  onclick = 'this.showPicker()'/>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="application_id" class="control-label col-md-2">{{ __('Application ID') }}</label>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control" id="application_id"  placeholder="{{ __('Application ID') }}"
                                            oninput = "this.value = this.value.replace(/[^0-9]/g, ''); "/> <!-- Allow only numeric characters (0-9) -->
                                        </div>
                                        <label for="servprov" class="control-label col-md-2">{{ __('Service Provider Name') }}</label>
                                        <div class="col-md-2">
                                            <select class="form-control" id="servprov">
                                                <option value="">{{ __('Service Provider Name') }}</option>
                                                @foreach($servprov as $key=>$value)
                                                <option value="{{$key}}">{{$value}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="card-footer text-right">
                                        <button type="submit" class="btn btn-info">{{ __('Filter') }}</button>
                                        <button type="reset" class="btn btn-info reset">{{ __('Reset') }}</button>
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
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Application ID') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Treatment Plant Name') }}</th>
                    <th>{{ __('Service Provider Name') }}</th>
                    <th>{{ __('Desludging Vehicle Number Plate') }}</th>
                    <th>{{ __('Sludge Volume (m³)') }}</th>
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
        ajax: {
            url: '{!! url("fsm/sludge-collection-log/getData") !!}',
            data: function(d) {
                d.treatment_plant_id = $('#treatment_plant_id').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
                d.application_id = $('#application_id').val();
                d.servprov = $('#servprov').val();
            }
        },
        columns: [
            {
                data: 'id',
                name: 'id'
            },{
                data: 'application_id',
                name: 'application_id'
            },
            {
                data: 'date',
                name: 'date'
            },
            {
                data: 'treatment_plant_id',
                name: 'treatment_plant_id'
            },
            {
                data: 'service_provider_id',
                name: 'service_provider_id'
            },
            {
                data: 'desludging_vehicle_id',
                name: 'desludging_vehicle_id'
            },
            {
                data: 'volume_of_sludge',
                name: 'volume_of_sludge'
            },

            {
                data: 'action',
                name: 'action'
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
                title: '{{ __('Are you sure?') }}',
                        text: "{!! __('You won\'t be able to revert this!') !!}",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '{{ __('Yes, delete it!') }}',
                        cancelButtonText: '{{ __('Cancel') }}',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            })
        });
    });
    var treatment_plant_id = treatment_plant_id,
        date_from = date_from,
        date_to = date_to,
        servprov = servprov,
        application_id = application_id;

    $('#filter-form').on('submit', function(e) {
        var date_from = $('#date_from').val();
        var date_to = $('#date_to').val();
        if ((date_from !== '') && (date_to === '')) {

            Swal.fire({
                        title:'{{ __('Date To is Required') }}',
                        text: "{{ __('Please Select Date To!') }}",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Close') }}"
            })

            return false;
        }
        if ((date_from === '') && (date_to !== '')) {

                    Swal.fire({
                        title:'{{ __('Date From is Required') }}',
                        text: "{{ __('Please Select Date From!') }}",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Close') }}"
                    })

                    return false;
                }

                if (date_from !== '' && date_to !== '' && date_to <= date_from) {
                    Swal.fire({
                        title: "{{ __('Invalid Date Range') }}",
                        text:"{{ __('Date To cannot be Before Date From!') }}" ,
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Close') }}"
                    });

                    return false;
                }

        e.preventDefault();
        dataTable.draw();
        treatment_plant_id = $('#treatment_plant_id').val();
        date_from = $('#date_from').val();
        date_to = $('#date_to').val();
        application_id = $('#application_id').val();
        servprov = $('#servprov').val();
    });

    //$('#data-table_filter input[type=search]').attr('readonly', 'readonly');

    $(".reset").on("click", function(e) {

        $('#treatment_plant_id').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        $('#application_id').val('');
        $('#data-table').dataTable().fnDraw();
    });

    $("#export").on("click", function(e) {
        e.preventDefault();
        var searchData = $('input[type=search]').val();
        treatment_plant_id = $('#treatment_plant_id').val();
        date_from = $('#date_from').val();
        date_to = $('#date_to').val();
        application_id = $('#application_id').val();
        servprov = $('#servprov').val();
        window.globalLoader.download(
            "{!! url('fsm/sludge-collection/export?searchData=') !!}" + searchData +
                "&treatment_plant_id=" + treatment_plant_id +
                "&date_from=" + date_from +
                "&date_to=" + date_to +
                "&application_id=" + application_id +
                "&servprov=" + servprov,
            'sludge-collections.csv',
            this
        ).catch(function () {});
    })


 
});
</script>
<!-- toggle filter show hide -->
// <script>
// $(document).ready(function() {
//     $('[data-toggle="collapse"]').click(function() {
//         $(this).toggleClass("active");
//         if ($(this).hasClass("active")) {
//             $(this).text("Hide Filter");
//         } else {
//             $(this).text("Show Filter");
//         }
//     });
// });
// </script>
@endpush
