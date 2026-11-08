<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
{{-- Extend the main layout --}}
@extends('layouts.dashboard')
{{-- Add sections for the main layout --}}
@section('title',__('Emptying') )
{{-- Add sections for the index layout --}}
@section('filter-form')
    @include('layouts.filter-form', ['formFields' => $filterFormFields])
@endsection
@section('data-table')
    <div style="overflow: auto; width: 100%;">
        <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Application ID') }}</th>
                    <th>{{ __('Sludge Volume (m³)') }}</th>
                    <th>{{ __('Emptied Date') }}</th>
                    <th>{{ __('Total Cost') }}</th>
                    <th>{{ __('Service Provider Name') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
@endsection

{{-- Include the layout inside the main content section --}}
@section('content')
    @include('layouts.index')
@endsection

@push('scripts')
@if(session('popup_confirm'))
<script>
    Swal.fire({
        title: 'Are you sure?',
        text: "{{ session('popup_message') }}",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect to route that deletes the entire application and related data
            window.globalLoader.show(Swal.getConfirmButton());
            window.location.href = "{{ url('fsm/application') }}/{{ session('application_id') }}/force-delete";
        }
    });
</script>
@endif 


    <script>
        $(document).ready(function() {
            var dataTable = $('#data-table').DataTable({
                bFilter: false,
                processing: true,
                serverSide: true,
                stateSave: true,
                scrollCollapse: true,
                ajax: {
                    url: '{!! route('emptying.get-data') !!}',
                    data: function(d) {
                        d.application_id = $('#application_id').val();
                        d.emptied_date = $('#emptied_date').val();
                        d.containment_code = $('#containment_id').val() ? $('#containment_id').val() : "{{$containment_code}}";
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                    },
                },
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'application_id',
                        name: 'application_id'
                    },

                    {
                        data: 'volume_of_sludge',
                        name: 'volume_of_sludge'
                    },
                    {
                        data: 'emptied_date',
                        name: 'emptied_date',
                        render: function(data) {
                            return moment(data).format("dddd, MMMM Do YYYY");
                        }
                    },
                    {
                        data: 'total_cost',
                        name: 'total_cost'
                },
                    {
                        data: 'service_provider_id',
                        name: 'service_provider_id'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ],
                order: [
                    [0, 'desc']
                ]

            }).on('draw', function() {
               
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
       
        filterDataTable(dataTable);
        resetDataTable(dataTable);

    $('#filter-form').on('submit', function(e) {

        var date_from = $('#date_from').val();
        var date_to = $('#date_to').val();
        if (date_from !== '' && date_to !== '' && (date_to <= date_from)) {
                    Swal.fire({
                        title:  '{{ __('Invalid Date Range') }}',
                        text:  '{{ __("Date To cannot be Before Date From!") }}',
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Close') }}"
                    });

                    return false;
                }
        if ((date_from !== '') && (date_to === '')) {

            Swal.fire({
                title:'{{ __('Date To is Required') }}' ,
                text:'{{ __("Please Select Date To!") }}' ,
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
                        title:'{{ __('Date From is Required') }}' ,
                        text: '{{ __("Please Select Date From!") }}',
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Close') }}"
                    })

                    return false;
                }
                e.preventDefault();
                dataTable.draw();
                application_id = $('#application_id').val();
                date_from = $('#date_from').val();
                date_to = $('#date_to').val();
                containment_id = $('#containment_id').val();
                emptied_date = $('#emptied_date').val();

    });
           

    });
    </script>
@endpush
