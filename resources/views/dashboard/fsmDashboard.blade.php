<!-- Last Modified Date: 10-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.dashboard')
@section('title', $page_title)
@push('scripts')
    <script src="{{ asset('js/fsm-dashboard-charts.js') }}?v={{ filemtime(public_path('js/fsm-dashboard-charts.js')) }}"></script>
@endpush
@section('content')
    @php($fsm_chart_cards = true)
    <div class="fsm-dashboard">

        <div class="row" style="padding: 15px 0 15px 0;font-size: 24px;">
            <div class="col-lg-8 col-xs-8">
                <form class="form-inline" id="filter-form" action="{{ action('Fsm\FsmDashboardController@index') }}" method="get">
                    <div class="form-group">
                        <label for="year_select">{{ __('Year') }}</label>
                        <select class="form-control" id="year_select" name="year">
                            <option value="">{{ __('All Years') }}</option>
                            @for ($year = $maxDate; $year >= $minDate; $year--)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endfor
                        </select>

                        <button type="submit" class="ml-1 btn btn-info">{{ __("Filter") }}</button>
                        <a href="{{ action('Fsm\FsmDashboardController@index') }}" class="ml-1 btn btn-info reset">{{ __("Reset") }}</a>
                      </div>
          </form>
        </div>
  </div>
  @can('FSM Dashboard CountBox')
        <div class="row">
            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._serviceProvidersCountBox')
            </div>
            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._desludgingVehicleCountBox')
            </div>
            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._treatmentPlantCountBox')
            </div>
            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._applicationCountBox')
            </div>
            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._emptyingServicesCountBox')
            </div>

            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._uniqueContainCodeEmptiedCountBox')
            </div>
            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._sludgeCollectionsEmptyingServicesBox')
            </div>
            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._sludgeCollectionsCountBox')
            </div>

            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._costPaidByOwnerWithReceiptBox')
            </div>

        </div>
        <!-- /.row -->
    @endcan


        <div class="row">
        @can('Proportion of Different Containment Types Chart')
            <div class="col-md-6">
                @include('dashboard.containments._containTypeChart')
            </div>
    @endcan
    @can('Ward-Wise Distribution of Containment Types Chart')
            <div class="col-md-6">
                @include('dashboard.containments._containmentTypesPerWardChart')
            </div>
            @endcan
        </div>



        <div class="row containment-usage-chart-row">
        @can('Ward-Wise Distribution of Containment Types in Residential Buildings Chart')
            <div class="col-md-6">
                @include('dashboard.containments._containmentTypesByBldgUsesResidentialsChart')
            </div>
        @endcan

        @can('Containment Types Categorized by Building Usage Chart')
            <div class="col-md-6">
                @include('dashboard.containments._containmentTypesByBldgusesChart')
            </div>
        @endcan
        </div>


    <div class="row">
    @can('Containment Types Categorized by Land Use Chart')
        <div class="col-md-6">
            @include('dashboard.containments._containmentTypesByLanduseChart')
        </div>
        @endcan
    </div>



    <div class="row">
    @can('Summary of Applications, Emptying Services, Sludge Disposal, and Feedback by Ward Chart')
        <div class="col-md-12">
            @include('dashboard.fsmCharts._emptyingServicePerWardsChart')
        </div>
        @endcan
    </div>




    <div class="row">
    @can('Distribution of Emptying Requests by Structure Types Chart')
        <div class="col-md-6">
              @include('dashboard.charts._emptyingByStructureTypes')
            </div>
    @endcan
        @can('Comparison of Emptying Requests from Low-Income and Other Communities Chart')
            <div class= "col-md-6">
                @include('dashboard.charts._numberOfEmptyingByMonthsChart')
            </div>
        @endcan
    </div>


    <div class="row">
        @can('Monthly Emptying Requests Processed by Service Providers Chart')
            <div class="col-md-6">
                @include('dashboard.charts._monthlyRequestByOperators')
            </div>
        @endcan

    </div>
    <div class="row">
        @can('Emptying Requests for the Next Four Weeks Chart')
            <div class="col-md-6">
                @include('dashboard.containments._proposedEmptyingDateNextFourWeeksChart')
            </div>
        @endcan
        @can('Ward-Wise Distribution of Emptying Requests for the Next Four Weeks Chart')
            <div class="col-md-6">
                @include('dashboard.containments._proposedEmptyingDateContainmentsByWardChart')
            </div>
        @endcan
    </div>
        <div class="row">
    @can('Customer Satisfaction with FSM Service Quality Chart')

            <div class="col-md-6">
                @include('dashboard.fsm-feedback-charts._fsmServiceQualityChart')
            </div>
    @endcan
        @can('Sanitation Worker Compliance with PPE Guidelines Chart')
            <div class="col-md-6">
                @include('dashboard.fsm-feedback-charts._ppeChart')
            </div>
        @endcan
        </div>


    <div class="row">
        @can('Sludge Collection Trends by Treatment Plants Over the Last 5 Years Chart')
            <div class="col-md-6">
                @include('dashboard.fsmCharts._sludgeCollectionByTreatmentPlant')
            </div>
        @endcan
        @can('Containment Type-Wise Emptying Services Over the Last 5 Years Chart')
            <div class="col-md-6">
                @include('dashboard.fsmCharts._emptyingServiceByTypeYearChart')
            </div>
        @endcan
    </div>
    </div>
@stop
@push('scripts')
    <script>
        $('[id="year_select"]').change(function(e) {
            // e.preventDefault();
            var year_select = $(this).val();
            localStorage.setItem('year_select', year_select);
        })
    </script>
    <script>
        $(document).ready(function() {
            year_sel = localStorage.getItem('year_select');
            if (year_sel) {
                $("#year_select").val(year_sel);
            }
            $('.reset').click(function(e) {
                localStorage.removeItem("year_select");
                $("#year_select").val();
            })
        })
    </script>
@endpush
