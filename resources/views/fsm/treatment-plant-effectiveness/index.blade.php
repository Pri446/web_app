<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.dashboard')
@section('title', $page_title)
@push('style')
    <style type="text/css">
        .dataTables_filter {
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    @can('Add Treatment Plant Efficiency Standard')
                        <a href="{{ action('Fsm\TreatmentPlantEffectivenessController@create') }}" class="btn btn-info">Add
                            Treatment Plant Efficiency Standard</a>
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
                                                    <label for="year"
                                                        class="col-md-2 col-form-label text-right">Year</label>
                                                    <div class="col-md-2">
                                                        <select class="form-control" id="year" name="year">
                                                            <option value="">Year</option>
                                                            @foreach ($pickYearResults as $unique)
                                                                <option value="{{ $unique->year }}"> {{ $unique->year }}
                                                                </option>
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
                <div class="card-body">
                    <div style="overflow: auto; width: 100%;">
                        <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
                            <thead>
                                <tr>
                                    <th>Treatment Plant Name</th>
                                    <th>Year</th>
                                    <th>Percentage of Effectiveness of FS/WW treatment</th>
                                    <th>Operational Cost Recovered (%)</th>
                                    <th>Treated Fecal Sludge (%)</th>
                                    <th>Percentage of Water Contamination Compliance (%)(on fecal coliform)</th>
                                    <th>Action </th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div><!-- /.card-body -->
            </div><!-- /.card -->
        </div><!-- /.col -->
    </div><!-- /.row -->

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="message"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>


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
                    url: '{!! url('fsm/treatment-plant-effectiveness/data') !!}',
                    data: function(d) {
                        d.year = $('#year').val();
                    }
                },

                columns: [{
                        data: 'treatment_plant_id',
                        name: 'treatment_plant_id'
                    },
                    {
                        data: 'year',
                        name: 'year'
                    },
                    {
                        data: 'tp_effectiveness',
                        name: 'tp_effectiveness'
                    },
                    {
                        data: 'recovered_operational_cost',
                        name: 'recovered_operational_cost'
                    },
                    {
                        data: 'treated_fecal_sludge',
                        name: 'treated_fecal_sludge'
                    },
                    {
                        data: 'water_contamination_compliance',
                        name: 'water_contamination_compliance'
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
                    deleteAction(form || this);
                });
            });
            var year = '';

            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                dataTable.draw();
                year = $('#year').val();
            });
            // $("#export").on("click", function(e) {
            //     e.preventDefault();
            //     var searchData = $('input[type=search]').val();
            //     treatment_plant_id = $('#treatment_plant_id').val();
            //     year = $('#year').val();
            //     tp_effectiveness = $('#tp_effectiveness').val();
            //     recovered_operational_cost = $('#recovered_operational_cost').val();
            //     treated_fecal_sludge = $('#treated_fecal_sludge').val();
            //     water_contamination_compliance = $('#water_contamination_compliance').val();
            //     window.location.href = "{!! url('fsm/treatment-plant-effectiveness/export?searchData=') !!}" + searchData +
            //         "&treatment_plant_id=" + treatment_plant_id +
            //         "&year=" + year + "&tp_effectiveness=" +
            //         tp_effectiveness + "&recovered_operational_cost=" + recovered_operational_cost +
            //         "&treated_fecal_sludge=" + treated_fecal_sludge +
            //         "&water_contamination_compliance=" + water_contamination_compliance;
            // });
            $(".reset").on("click", function(e) {
                $('#year').val('');
                $('#data-table').dataTable().fnDraw();
            });

            // $('#data-table_filter input[type=search]').attr('readonly', 'readonly');
        });
    </script>
@endpush
