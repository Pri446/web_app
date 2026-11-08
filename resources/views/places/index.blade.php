@extends('layouts.dashboard')
@section('title', $page_title)

@section('content')
<style>
    .chosen-container {
        width: 100% !important;
    }
    .chosen-container .chosen-search input {
        width: 100% !important;
        padding: 5px !important;
        display: block !important;
    }
    .chosen-container-single .chosen-search {
        display: block !important;
    }
    /* Ensure Chosen matches Bootstrap filter row height */
    .chosen-container-single .chosen-single {
        height: 38px !important;
        line-height: 32px !important;
        background: #fff !important;
        border: 1px solid #ced4da !important;
    }
</style>

<link href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css" rel="stylesheet">

<div class="card">
    <div class="card-header">
        <a href="{{ route('places.create') }}" data-testid="add-place-button" class="btn btn-info">Add Places</a>
        <a href="{{ action('PlacesController@export') }}" id="export" class="btn btn-info">Export to CSV</a>
        <button class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            Show Filter
        </button>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="accordion" id="accordionExample">
                    <div class="accordion-item">
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-parent="#accordionExample">
                            <div class="accordion-body">
                                <form class="form-horizontal" id="filter-form">
                                    <div class="form-group row">
                                        <label for="name" class="control-label col-md-2">{{ __('Places Name') }}</label>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('Places Name') }}"/>
                                        </div>

                                        <label for="filter_type" class="control-label col-md-2">{{ __('Type') }}</label>
                                        <div class="col-md-2">
                                            <select class="form-control chosen-select" id="filter_type" name="type">
                                                <option value="">{{ __('All Types') }}</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type }}">{{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <label for="ward" class="control-label col-md-2">{{ __('Ward') }}</label>
                                        <div class="col-md-2">
                                            <select class="form-control" id="ward" name="ward">
                                                <option value="">{{ __('All Wards') }}</option>
                                                @for ($i = 1; $i <= 16; $i++)
                                                    <option value="{{ $i }}" {{ request('ward') == $i ? 'selected' : '' }}>
                                                        {{ $i }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                         <label for="unique_reference_id" class="control-label col-md-2">{{ __('Unique Reference ID') }}</label>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control" id="unique_reference_id" name="unique_reference_id" placeholder="{{ __('Unique Reference ID') }}"/>
                                        </div>
                                    </div>
                                    <div class="card-footer text-right">
                                        <button type="submit" class="btn btn-info">{{ __('Filter') }}</button>
                                        <button type="button" id="reset-filter" class="btn btn-info reset">{{ __('Reset') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
       <table class="table table-bordered table-striped" id="data-table" style="width: 100%">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Places Name</th>
                    <th>Ward</th>
                    <th>Unique Reference ID</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>

<script>
    let dataTable;

    $(document).ready(function() {
        dataTable = $('#data-table').DataTable({
            bFilter: false,
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("places.getData") }}',
                data: function(d) {
                    d.type = $('#filter_type').val();
                    d.name = $('#name').val();
                    d.ward = $('#ward').val();
                    d.unique_reference_id = $('#unique_reference_id').val();
                }
            },
            columns: [
                { data: 'type', name: 'type' },
                { data: 'name', name: 'name' },
                { data: 'ward', name: 'ward' },
                { data: 'unique_reference_id', name: 'unique_reference_id' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        function initChosen() {
            $(".chosen-select").chosen({
                no_results_text: "No matching types found",
                width: "100%",
                search_contains: true,
                allow_single_deselect: true
            });
        }

        initChosen();

        $('#collapseOne').on('shown.bs.collapse', function () {
            $('.chosen-select').chosen('destroy');
            initChosen();
        });

        // 3. Filter form handlers
        $('#filter-form').on('submit', function(e) {
            e.preventDefault();
            dataTable.draw();
        });

        $('#reset-filter').on('click', function() {
            $('#filter-form')[0].reset();
            $('.chosen-select').val('').trigger('chosen:updated');
            dataTable.draw();
        });

        // 4. Export button handler
        $('#export').on('click', function(e) {
            e.preventDefault();
            
            var type = $('#filter_type').val();
            var name = $('#name').val();
            var ward = $('#ward').val();
            var unique_reference_id = $('#unique_reference_id').val();
            
            var url = "{{ route('places.export') }}";
            var params = [];
            
            if (type) params.push('type=' + encodeURIComponent(type));
            if (name) params.push('name=' + encodeURIComponent(name));
            if (ward) params.push('ward=' + encodeURIComponent(ward));
            if (unique_reference_id) params.push('unique_reference_id=' + encodeURIComponent(unique_reference_id));
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            window.location.href = url;
        });

        $(document).on('click', '.delete', function(e) {
            e.preventDefault();
            var form = $(this).closest("form");
            Swal.fire({
                title: '{{ __('Are you sure?') }}',
                text: {{ Illuminate\Support\Js::from(__('You won\'t be able to revert this!')) }},
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
            });
        });
    });
</script>
@endpush