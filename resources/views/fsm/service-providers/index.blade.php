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
        @can('Add Service Provider')
        <a href="{{ action('Fsm\ServiceProviderController@create') }}" class="btn btn-info">{{ __("Add Service Provider")}}</a>
        @endcan
        @can('Export to CSV')
        <a href="{{ action('Fsm\ServiceProviderController@export') }}" id="export" class="btn btn-info">{{ __("Export to CSV")}}</a>
        @endcan
        <a href="#" class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse"
            data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            {{ __("Show Filter")}}
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
                                    <label for="company_name" class="col-md-2 col-form-label">{{ __("Company Name") }}</label>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" id="company_name" placeholder="{{ __('Company Name') }}" />
                                    </div>

                                    <label for="ward" class="col-md-2 col-form-label">{{ __("Ward Number") }}</label>
                                    <div class="col-md-2">
                                        <select class="form-control" id="ward">
                                            <option value="">{{ __("Ward Number") }}</option>
                                            @foreach($ward as $key)
                                                <option value="{{ $key }}">{{ $key }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <label for="company_location" class="col-md-2 col-form-label">{{ __("Address") }}</label>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" id="company_location" placeholder="{{ __('Address') }}" />
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="email" class="col-md-2 col-form-label">{{ __("Email") }}</label>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" id="email" placeholder="{{ __('Email') }}" />
                                    </div>

                                    <label for="contact_person" class="col-md-2 col-form-label">{{ __("Contact Person Name") }}</label>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" id="contact_person" placeholder="{{ __('Contact Person Name') }}" />
                                    </div>

                                    <label for="status" class="col-md-2 col-form-label">{{ __("Status") }}</label>
                                    <div class="col-md-2">
                                        <select class="form-control chosen-select" id="status" name="status">
                                            <option value="">{{ __("Status") }}</option>
                                            <option value="true">Operational</option>
                                            <option value="false">Not Operational</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="card-footer text-right">
                                    <button type="submit" class="btn btn-info">{{ __("Filter") }}</button>
                                    <button type="reset" id="reset-filter" class="btn btn-info">{{ __("Reset") }}</button>
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
        <table id="data-table" class="table table-bordered table-striped" width="100%">
            <thead>
                <tr>
                <th>{{ __("Company Name") }}</th>
                <th>{{ __("Email") }}</th>
                <th>{{ __("Ward Number") }}</th>
                <th>{{ __("Address") }}</th>
                <th>{{ __("Contact Person Name") }}</th>
                <th>{{ __("Customer Feedback") }}</th>
                <th>{{ __("Status") }}</th>
                <th>{{ __("Actions") }}</th>

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
        order: [],
        scrollCollapse: true,
        ajax: {
            url: '{!! url("fsm/service-providers/data") !!}',
            data: function(d) {

                d.company_name = $('#company_name').val();
                d.ward = $('#ward').val();
                d.company_location = $('#company_location').val();
                d.email = $('#email').val();
                d.contact_person = $('#contact_person').val();
                d.status = $('#status').val();

            }
        },
        columns: [{
                data: 'company_name',
                name: 'company_name'
            },
            {
                data: 'email',
                name: 'email'
            },

            {
                data: 'ward',
                name: 'ward'
            },
            {
                data: 'company_location',
                name: 'company_location'
            },
            {
                data: 'contact_person',
                name: 'contact_person'
            },
            {
                data: 'rating',
                name: 'rating',
                orderable: false
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

    resetDataTable(dataTable);

    var company_name = '',
        ward = '',
        company_location = '',
        email = '',
        contact_person = '',
        status = '';

    $('#filter-form').on('submit', function(e) {

        e.preventDefault();
        dataTable.draw();
        company_name = $('#company_name').val();
        email = $('#email').val();
        company_location = $('#company_location').val();
        ward = $('#ward').val();
        contact_person = $('#contact_person').val();
        status = $('#status').val();
    });

    //$('#data-table_filter input[type=search]').attr('readonly', 'readonly');


    $("#export").on("click", function(e) {
        e.preventDefault();
        var searchData = $('input[type=search]').val();
        var company_name = $('#company_name').val();
        var ward = $('#ward').val();
        var email = $('#email').val();
        var company_location = $('#company_location').val();
        var contact_person = $('#contact_person').val();

        var status = $('#status').val();
        window.globalLoader.download(
            "{!! url('fsm/service-providers/export?searchData=') !!}" + searchData +
                "&company_name=" + company_name +
                "&ward=" + ward +
                "&email=" + email +
                "&company_location=" + company_location +
                "&contact_person=" + contact_person +
                "&status=" + status,
            'service-providers.csv',
            this
        ).catch(function () {});
    })
});
</script>
@endpush
