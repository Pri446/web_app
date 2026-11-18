
<!-- Last Modified Date: 19-04-2024
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
            @can('Add Language')
            <a href="{{ action('Language\LanguageController@create') }}" class="btn btn-info">{{ __('Add Language') }}</a>
            @endcan
            @can('Export CSV Template')
                <a href="{{ action('Language\LanguageController@export_csv_format') }}" class="btn btn-info">{{ __('Download CSV Template for Import') }}</a>
            @endcan
        </div><!-- /.card-header -->

        <div class="card-body">
            <div style="overflow: auto; width: 100%;">
                <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('Language') }}</th>
                            <th>{{ __('Language Code') }}</th>
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
                    processing: true,
                    bFilter: false,
                    serverSide: true,
                    scrollCollapse: true,
                    ajax: {
                        url: '{!! url('language/data') !!}',
                        data: function(d) {

                        },
                    },

                    columns: [

                        {
                            data: 'name',
                            name: 'name'
                        },

                        {
                            data: 'code',
                            name: 'code'
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
            });

    </script>
@endpush
