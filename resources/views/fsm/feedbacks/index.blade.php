<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.dashboard')
@section('title', $page_title)


@section('content')

<div class="card ">
    <div class="card-header">
        <a class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse"
            data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            {{__("Show Filter")}}
        </a>
        @can('Export Feedbacks')
        <a href="{{ action('Fsm\FeedbackController@export') }}" id="export" class="btn btn-info"> {{__("Export to CSV")}}</a>
        @endcan
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
                                        <label for="application_id" class="control-label col-md-2"> {{__("Application ID")}}</label>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control" id="application_id" placeholder="{{__("Application ID")}}"
                                            oninput = "this.value = this.value.replace(/[^0-9]/g, ''); "/> <!-- Allow only numeric characters (0-9) -->
                                        </div>
                                        <label for="ward_select" class="control-label col-md-2"> {{__("Ward Number")}}</label>
                                        <div class="col-md-2">
                                            <select class="form-control" id="ward_select">
                                                <option value=""> {{__("Ward Number")}}</option>
                                                @foreach($wards as $key=>$value)
                                                <option value="{{$key}}">{{$value}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
        
                                    <div class="form-group row">
                                        <label for="date_from" class="control-label col-md-2"> {{__("Date From")}}</label>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" id="date_from" placeholder="{{__('Date From')}}" onclick = 'this.showPicker()'/>
                                        </div>
                                        <label for="date_to" class="control-label col-md-2"> {{__("Date To")}}</label>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" id="date_to" placeholder="{{__('Date To')}}" onclick = 'this.showPicker()'/>
                                        </div>
                                    </div>
                                    <div class="card-footer text-right">
                                        <button type="submit" class="btn btn-info "> {{__("Filter")}}</button>
                                        <button id="reset-filter" type="reset" class="btn btn-info reset"> {{__("Reset")}}</button>
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
    <div class="card-body">
    <div style="overflow: auto; width: 100%;">
            <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>{{__("Application ID")}}</th>
                    <th>{{__("Ward Number")}}</th>
                    <th>{{__("Feedback Date")}}</th>
                    <th>{{__("Actions")}}</th>

                </tr>
            </thead>
        </table>
</div>
    </div><!-- /.card-body -->
</div> <!-- /.card -->

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
            url: '{!! url("fsm/feedback/getData") !!}',
            data: function(d) {
                d.application_id = $('#application_id').val();
                d.ward = $('#ward_select').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },
        columns: [{
                data: 'application_id',
                name: 'application_id'
            },
            {
                data: 'ward',
                name: 'ward'
            },
            {
                data: 'created_at',
                name: 'created_at'
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
        var date_from = $('#date_from').val();
        var date_to = $('#date_to').val();
        if ((date_from !== '') && (date_to === '')) {

            Swal.fire({
                title: "{{ __('Date To is Required') }}",
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
                        title:"{{ __('Date From is Required') }}",
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
    });
    filterDataTable(dataTable);
    resetDataTable(dataTable);
    $("#export").on("click", function(e) {
        e.preventDefault();
        application_id = $('#application_id').val();
        ward = $('#ward_select').val();
        date_from = $('#date_from').val();
        date_to = $('#date_to').val();
        var searchData = $('input[type=search]').val();
        window.globalLoader.download(
            "{!! url('fsm/feedback/export?searchData=') !!}" + searchData +
                "&application_id=" + application_id + "&ward=" + ward + "&date_from=" + date_from + "&date_to=" + date_to,
            'feedbacks.csv',
            this
        ).catch(function () {});
    })

});
</script>


@endpush
