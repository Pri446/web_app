
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
            @can('Add PT/CT Toilet')
                <a href="{{ action('Fsm\CtptController@create') }}" class="btn btn-info">{{__('Add Public / Community Toilets')}}</a>
            @endcan
            @can('Export PT/CT Toilets')
                <a href="#" id="export" class="btn btn-info">{{__('Export to CSV')}}</a>
            @endcan
            <a href="#" class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse"
                data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                {{__('Show Filter')}}
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
                                        <label for="name" class="col-md-2 col-form-label ">{{__('Toilet Name')}}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="name"
                                                    placeholder="{{__('Toilet Name')}}" />
                                            </div>
                                            <label for="bin" class="control-label col-md-2">{{__('BIN')}}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="bin"
                                                    placeholder="{{__('BIN')}}" 
                                                    oninput = "this.value = this.value.replace(/[^a-zA-Z0-9]/g, ''); "/> <!-- Allow only alphabetic and numeric characters -->
                                            </div>

                                            <label for="house_address" class="control-label col-md-2">{{__('House Number')}}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="house_address"
                                                    placeholder="{{__('House Number')}}" 
                                                    oninput = "this.value = this.value.replace(/[^a-zA-Z0-9-]/g, ''); "/> <!-- Allow only alphabetic characters, numbers, and the hyphen (-) -->
                                            </div>
                                           
                                           
                                        </div>
                                        <div class="form-group row">
                                       
                                        <label for="type" class="col-md-2 col-form-label ">{{__('Toilet Type')}}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="type" name="type">
                                                    <option value="">{{__('Toilet Type')}}</option>
                                                    <option value="Community Toilet">{{__('Community Toilet')}}</option>
                                                    <option value="Public Toilet">{{__('Public Toilet')}}</option>
                                                </select>
                                            </div>

                                            <label for="ward" class="col-md-2 col-form-label ">{{__('Ward Number')}}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="ward">
                                                    <option value="">{{__('Ward Number')}}</option>
                                                    @foreach ($ward as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label for="caretaker_name" class="col-md-2 col-form-label ">{{__('Caretaker Name')}}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="caretaker_name"
                                                    placeholder="{{__('Caretaker Name')}}" />
                                            </div>
                                           
                                        </div>

                                        <div class="form-group row">
                                        <label for="sanitary_supplies_disposal_facility" class="col-md-2 col-form-label ">{{__('Sanitary Supplies & Disposal Facility')}}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="sanitary_supplies_disposal_facility"
                                                    name="sanitary_supplies_disposal_facility">
                                                    <option value="">{{__('Sanitary Supplies & Disposal Facility')}}</option>
                                                    <option value=true>{{__('Yes')}}</option>
                                                    <option value=false>{{__('No')}}</option>
                                                </select>
                                            </div>
                                    <label for="width" class="col-md-2 col-form-label ">{{__('Status')}}</label>
                                    <div class="col-md-2">
                                    <select class="form-control chosen-select" id="status" name="status">
                                        <option value="">{{__('Status')}}</option>
                                        <option value="true">{{__('Operational')}}</option>
                                        <option value="false">{{__('Not Operational')}}</option>

                                    </select>
                                    </div>
                                    </div>
                                        <div class="card-footer text-right">
                                            <button type="submit" class="btn btn-info ">{{__('Filter')}}</button>
                                            <button type="reset" id="reset-filter" class="btn btn-info ">{{__('Reset')}}</button>
                                        </div>
                                    </form>
                                </div> <!--- accordion body!-->
                            </div> <!--- collapseOne!-->
                        </div> <!--- accordion item!-->
                    </div> <!--- accordion !-->
                </div> <!---col!-->
            </div> <!--- row !-->
        </div>
        <!--- card body !-->
        <div class="card-body">
            <div style="overflow: auto; width: 100%;">
                <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('ID')}}</th>
                            <th>{{ __('Toilet Name')}}</th>
                            <th>{{ __('BIN')}}</th>
                            <th>{{ __('House Number')}}</th>
                            <th>{{ __('Toilet Type')}}</th>
                            <th>{{ __('Ward Number')}}</th>
                            <th>{{ __('Caretaker Name')}}</th>
                            <th>{{ __('Sanitary Supplies & Disposal Facility')}}</th>
                            <th>{{ __('Status')}}</th>
                            <th>{{ __('Actions')}}</th>

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
                    url: '{!! url('fsm/ctpt/data') !!}',
                    data: function(d) {
                        d.toilet_id = $('#toilet_id').val();
                        d.bin = $('#bin').val();
                        d.house_address = $('#house_address').val();
                        d.name = $('#name').val();
                        d.ward = $('#ward').val();
                        d.type = $('#type').val();
                        d.status = $('#status').val();

                        d.caretaker_name = $('#caretaker_name').val();

                        d.sanitary_supplies_disposal_facility = $(
                            '#sanitary_supplies_disposal_facility').val();
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
                        data: 'bin',
                        name: 'bin'
                    },
                    {
                        data: 'house_address',
                        name: 'house_address'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'ward',
                        name: 'ward'
                    },


                    {
                        data: 'caretaker_name',
                        name: 'caretaker_name'
                    },

                    
                    {
                        data: 'sanitary_supplies_disposal_facility',
                        name: 'sanitary_supplies_disposal_facility'
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
            var toilet_id = '',
                bin = '',
                house_address = '',
                name = '',
                type = '',
                ward = '',
                caretaker_name = '',
                sanitary_supplies_disposal_facility = '';
                status = '';


                $('#filter-form').on('submit', function(e) {
                    e.preventDefault();
                dataTable.draw();

                    bin = $('#bin').val();
                    name = $('#name').val();
                    house_address = $('#house_address').val();
                    type = $('#type').val();
                    ward = $('#ward').val();
                    caretaker_name = $('#caretaker_name').val();
                     status = $('#status').val();
                    sanitary_supplies_disposal_facility = $('#sanitary_supplies_disposal_facility').val();

                });


            $('#export').on('click', function(e) {
                e.preventDefault();
                var searchData = $('input[type=search]').val();

           
                bin = $('#bin').val();
                    name = $('#name').val();
                    house_address = $('#house_address').val();
                    type = $('#type').val();
                    ward = $('#ward').val();
                    caretaker_name = $('#caretaker_name').val();
                    status = $('#status').val();
                    sanitary_supplies_disposal_facility = $('#sanitary_supplies_disposal_facility').val();
                window.globalLoader.download(
                    "{!! url('fsm/ctpt/export?searchData=') !!}" + searchData +
                        "&bin=" + bin +
                        "&house_address=" + house_address +
                        "&name=" + name +
                        "&type=" + type +
                        "&ward=" + ward +
                        "&caretaker_name=" + caretaker_name +
                        "&status=" + status +
                        "&sanitary_supplies_disposal_facility=" + sanitary_supplies_disposal_facility,
                    'ctpt.csv',
                    this
                ).catch(function () {});
            });



            // function getCQLParams() {
            //     bin = $('#bin').val();
            //     name = $('#name').val();
            //     ward = $('#ward').val();
            //     caretaker_name = $('#caretaker_name').val();
            //     toilet_type = $('#type').val();

            //     sanitary_supplies_disposal_facility = $('#sanitary_supplies_disposal_facility').val();
            //     var cql_param = "deleted_at IS NULL";
                
            //     if (name) {
            //         cql_param += " AND name ILIKE '%" + name + "%'";
            //     }
            //     if (ward) {
            //         cql_param += " AND ward ='" + ward + "'";
            //     }
            //     if (caretaker_name) {
            //         cql_param += " AND caretaker_name ILIKE '%" + caretaker_name + "%'";
            //     }
            //     if (toilet_type) {
            //         cql_param += " AND type ='" + toilet_type + "'";
            //     }
            //     if (male_or_female_facility) {
            //         cql_param += " AND male_or_female_facility ='" + male_or_female_facility + "'";
            //     }
            //     if (handicap_facility) {
            //         cql_param += " AND handicap_facility ='" + handicap_facility + "'";
            //     }
            //     if (children_facility) {
            //         cql_param += " AND children_facility ='" + children_facility + "'";
            //     }
            //     if (sanitary_supplies_disposal_facility) {
            //         cql_param += " AND sanitary_supplies_disposal_facility ='" + sanitary_supplies_disposal_facility + "'";
            //     }
            //     return encodeURI(cql_param);
            // }
        });
    </script>
@endpush
