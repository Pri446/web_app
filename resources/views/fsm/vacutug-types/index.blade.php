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
        @can('Add Desludging Vehicle')
        <a href="{{ action('Fsm\VacutugTypeController@create') }}" class="btn btn-info">{{ __('Add Desludging Vehicle') }}</a>
        @endcan
        @can('Export Desludging Vehicles')
        <a href="{{ action('Fsm\VacutugTypeController@export') }}" id="export" class="btn btn-info">{{ __('Export to CSV') }}</a>
        @endcan
        <a href="#" class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse"
            data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            {{ __('Show Filter') }}
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
                                        <label for="service_provider_id" class="col-md-2 col-form-label ">{{ __('Service Provider Name') }}</label>
                                        <div class="col-md-2">
                                            <select class="form-control" id="service_provider_id" name="service_provider_id">
                                                <option value="">{{ __('Service Provider Name') }}</option>
                                                @foreach ($service_provider_id as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label for="license_plate_number" class="col-md-2 col-form-label ">{{ __('Vehicle License Plate Number') }}</label>
                                        <div class="col-md-2">
                                            <select class="form-control" id="license_plate_number" name="license_plate_number">
                                                <option value="">{{ __('Vehicle License Plate Number') }}</option>
                                                @foreach ($license_plate_number as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label for="capacity" class="col-md-2 col-form-label">{{ __('Capacity  (m³)') }}</label>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control" id="capacity" placeholder="{{ __('Capacity (m³)') }}" oninput = "this.value = this.value.replace(/[^0-9.]/g, ''); " />
                                        </div>
                                        </div>
                                        <div class="form-group row">
                                        <label for="width" class="col-md-2 col-form-label ">{{ __('Width (m)') }}</label>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control" id="width"  placeholder="{{ __('Width (m)') }}" oninput = "this.value = this.value.replace(/[^0-9.]/g, '')"/>
                                        </div>
                                        <label for="width" class="col-md-2 col-form-label ">{{ __('Status') }}</label>
                                        <div class="col-md-2">
                                            <select class="form-control chosen-select" id="status" name="status">
                                                <option value="">{{ __('Status') }}</option>
                                                <option value="true">Operational</option>
                                                <option value="false">Not Operational</option>
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
            <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
            <thead>
                <tr>
                    
                <th>{{ __('Service Provider') }}</th>
                <th>{{ __('Vehicle License Plate Number') }}</th>
                <th>{{ __('Capacity (m³)') }}</th>
                <th>{{ __('Width (m)') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>

                </tr>
            </thead>
        </table>
   </div>
    </div><!-- /.card-body -->
</div><!-- /.card -->

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
            url: '{!! url("fsm/desludging-vehicles/data") !!}',
            data: function(d) {
                d.service_provider_id = $('#service_provider_id').val();
                d.license_plate_number = $('#license_plate_number').val();
                d.capacity = $('#capacity').val();
                d.width = $('#width').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            {
                data: 'service_provider_id',
                name: 'service_provider_id'
            },
            {
                data: 'license_plate_number',
                name: 'license_plate_number'
            },
            {
                data: 'capacity',
                name: 'capacity'
            },
            {
                data: 'width',
                name: 'width'
            },
            
            {
                data: 'status',
                name: 'status'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ],
        order: [
            [0, "desc"]
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
    var license_plate_number = '',
        service_provider_id = '',
        capacity = '',
        width = '',
        status = '';

    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        service_provider_id = $('#service_provider_id').val();
        license_plate_number = $('#license_plate_number').val();
        capacity = $('#capacity').val();
        width = $('#width').val();
        status = $('#status').val();
        dataTable.draw();
    });

    //$('#data-table_filter input[type=search]').attr('readonly', 'readonly');

    resetDataTable(dataTable);

    $("#export").on("click", function(e) {
        e.preventDefault();
        var searchData = $('input[type=search]').val();
        service_provider_id = $('#service_provider_id').val();
        license_plate_number = $('#license_plate_number').val();
        capacity = $('#capacity').val();
        width = $('#width').val();
        window.globalLoader.download(
            "{!! url('fsm/desludging-vehicles/export?searchData=') !!}" + searchData +
                "&service_provider_id=" + service_provider_id +
                "&license_plate_number=" + license_plate_number +
                "&capacity=" + capacity +
                "&width=" + width +
                "&status=" + status,
            'desludging-vehicles.csv',
            this
        ).catch(function () {});
    });

});
</script>
@endpush
