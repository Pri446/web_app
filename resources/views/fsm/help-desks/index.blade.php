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
        @can('Add Help Desk')
        <a href="{{ action('Fsm\HelpDeskController@create') }}" class="btn btn-info">{{ __('Add Help Desk') }}</a>
        @endcan
        @can('Export Help Desk')
        <a href="#" id="export" class="btn btn-info">{{ __('Export to CSV') }}</a>
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
                                        <label for="help_desk_id" class="col-md-2 col-form-label ">{{ __('ID') }}</label>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control" id="help_desk_id"
                                                placeholder={{ __("ID")}} 
                                                oninput = "this.value = this.value.replace(/[^0-9]/g, ''); "/> <!-- Allow only numeric characters (0-9) -->
                                        </div>
                                        <label for="name" class="col-md-2 col-form-label ">{{ __('Name') }}</label>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control" id="name" placeholder="{{ __('Name') }}" />
                                        </div>
                                         {{-- @hasrole('Super Admin|Municipality - Super Admin|Municipality - IT Admin|Municipality - Executive|Municipality - Sanitation Department') --}}
                                        <label for="servprov" class="col-md-2 col-form-label ">{{ __('Service Provider Name') }}</label>
                                        <div class="col-md-2">
                                            <select class="form-control" id="servprov">
                                                <option value="">{{ __('Service Provider Name') }}</option>
                                                @foreach($service_providers as $key=>$value)
                                                <option value={{$key}}>{{$value}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        {{-- @endhasrole --}}
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
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Contact Number') }}</th>
                    <th>{{ __('Service Provider Name') }}</th>
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
            url: '{!! url("fsm/help-desks/data") !!}',
            data: function(d) {
                d.help_desk_id = $('#help_desk_id').val();
                d.name = $('#name').val();
                d.contact_number = $('#contact_number').val();
                d.servprov = $('#servprov').val();
            }
        },
        columns: [{
                data: 'id',
                name: 'id'
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'contact_number',
                name: 'contact_number'
            },
            {
                data: 'service_provider',
                name: 'service_provider'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ], order: [
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
        var help_desk_id = $('#help_desk_id').val();
        var name = $('#name').val();
        var servprov = $('#servprov').val() ?? '';
        var searchData = $('input[type=search]').val();
        window.globalLoader.download(
            "{!! url('fsm/help-desks/export?searchData=') !!}" + searchData +
                "&help_desk_id=" + help_desk_id +
                "&name=" + name +
                "&servprov=" + servprov,
            'help-desks.csv',
            this
        ).catch(function () {});
    });

});
</script>
@endpush
