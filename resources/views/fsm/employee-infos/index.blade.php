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
        @can('Add Employee Info')
            <a href="{{ action('Fsm\EmployeeInfoController@create') }}" class="btn btn-info">{{ __("Add Employee Information") }}</a>
        @endcan

        @can('Export Employee Infos')
            <a href="#" id="export" class="btn btn-info">{{ __("Export to CSV") }}</a>
        @endcan

        <a href="#" class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            {{ __("Show Filter") }}
        </a>

        </div><!-- /.card-header -->
        <div class="card-body">
    <div class="row">
      <div class="col-12">
        <div class="accordion" id="accordionExample">
          <div class="accordion-item">
            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
              <div class="accordion-body">
              <form class="form-horizontal" id="filter-form">
                <div class="form-group row">
                    <label for="id" class="col-md-2 col-form-label ">{{ __("Employee ID") }}</label>
                    <div class="col-md-2">
                        <input type="text" class="form-control" id="id" placeholder="{{ __('Employee ID') }}"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '');"/> <!-- Allow only numeric characters (0-9) -->
                    </div>
                    <label for="employee_name" class="col-md-2 col-form-label text-right">{{ __("Employee Name") }}</label>
                    <div class="col-md-2">
                        <input type="text" class="form-control" id="employee_name" placeholder="{{ __('Employee Name') }}"/>
                    </div>
                    <label for="employee_type" class="col-md-2 col-form-label text-right">{{ __("Designation") }}</label>
                    <div class="col-md-2">
                        <select class="form-control" id="employee_type">
                            <option value="">{{ __("Designation") }}</option>
                            <option value="Management">Management</option>
                            <option value="Driver">Driver</option>
                            <option value="Cleaner/Emptier">Cleaner/Emptier</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="service_provider_id" class="col-md-2 col-form-label ">{{ __("Service Provider Name") }}</label>
                    <div class="col-md-2">
                        <select class="form-control" id="service_provider_id" name="service_provider_id">
                            <option value="">{{ __("Service Provider Name") }}</option>
                            @foreach ($service_provider_id as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label for="status" class="col-md-2 col-form-label text-right">{{ __("Status") }}</label>
                    <div class="col-md-2">
                        <select class="form-control chosen-select" id="status" name="status">
                            <option value="">{{ __("Status") }}</option>
                            <option value="true">Active</option>
                            <option value="false">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-info ">{{ __("Filter") }}</button>
                    <button type="reset" class="btn btn-info reset ">{{ __("Reset") }}</button>
                </div>
</form>

              </div>  <!--- accordion body!-->
            </div>    <!--- collapseOne!-->
          </div>      <!--- accordion item!-->
        </div>        <!--- accordion !-->
      </div>            <!---col!-->
    </div>            <!--- row !-->
  </div>              <!--- card body !-->

  <div class="card-body">
    <div style="overflow: auto; width: 100%;">
    <table id="data-table" class="table table-bordered table-striped" width="100%">
            <thead>
                <tr>
                <th>{{ __("Employee ID") }}</th>
            <th>{{ __("Employee Name") }}</th>
            <th>{{ __("Address") }}</th>
            <th>{{ __("Designation") }}</th>
            <th>{{ __("Monthly Remuneration") }}</th>
            <th>{{ __("Service Provider Name") }}</th>
            <th>{{ __("Status") }}</th>
            <th>{{ __("Actions") }}</th>

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
          url: '{!! url("fsm/employee-infos/data") !!}',
            data: function(d) {
                d.id = $('#id').val();
                d.employee_name = $('#employee_name').val();
                d.employee_type = $('#employee_type').val();
                d.service_provider_id = $('#service_provider_id').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'address', name: 'address' },
            { data: 'employee_type', name: 'employee_type' },
            { data: 'wage', name: 'wage' },
            { data: 'service_provider_id', name: 'service_provider_id' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [ [0, 'desc'] ]
    }).on( 'draw', function () {

        $('.delete').on('click', function(e) {

         var form =  $(this).closest("form");
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
    } );
    var id = '',
    employee_name = '',
    employee_type = '',
    service_provider_id = '',
    status = '';
    $('#filter-form').on('submit', function(e){
        e.preventDefault();
        dataTable.draw();
    });
    $(".reset").on("click",function(e){
        $('#id').val('');
        $('#employee_name').val('');
        $('#employee_type').val('');
        $('#service_provider_id').val('');
        $('#status').val('');
        $('#data-table').dataTable().fnDraw();
    });
    $("#export").on("click",function(e){
        e.preventDefault();
        var id = $('#id').val();
        var employee_name = $('#employee_name').val();
        var employee_type = $('#employee_type').val();
        var service_provider_id = $('#service_provider_id').val();
        var status = $('#status').val();
        var searchData=$('input[type=search]').val();
        window.globalLoader.download(
            "{!! url('fsm/employee-infos/export?searchData=') !!}" + searchData +
                "&id=" + id +
                "&employee_name=" + employee_name +
                "&employee_type=" + employee_type +
                "&service_provider_id=" + service_provider_id +
                "&status=" + status,
            'employee-infos.csv',
            this
        ).catch(function () {});
    })

});



</script>
<!-- toggle filter show hide -->

@endpush
