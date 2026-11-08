<!-- Dashboard content loaded asynchronously by dashboard.indexAdmin. -->

@can('Building CountBox')
    <h1 style="padding-bottom: 15px;font-size: 24px;">{{__("Buildings") }}</h1>
    <div class="row">
        <div class="col-lg-3 col-md-12 col-xs-12  d-flex">
            @include('dashboard.countBox._buildCountBox')
        </div> <!-- main col div -->
        <div class="col-lg-9 col-md-12 col-xs-12  extra-padding">
            <div class="row">
                <div class="col-lg-4 d-flex">
                    @include('dashboard.countBox._residentialBuildCountBox')
                </div> <!--sub col div -->
                <div class="col-lg-4  d-flex">
                    @include('dashboard.countBox._commercialBuildCountBox')
                </div> <!--sub col div -->
                <div class="col-lg-4  d-flex">
                    @include('dashboard.countBox._industrialBuildCountBox')
                </div> <!--sub col div -->
            </div> <!-- sub row -->
            <div class="row">
                <div class="col-lg-4  d-flex">
                    @include('dashboard.countBox._mixedBuildCountBox')
                </div> <!--sub col div -->
                <div class="col-lg-4 d-flex ">
                    @include('dashboard.countBox._institutionCountBox')
                </div>
                {{-- <div class="col-lg-4  d-flex">
                    @include('dashboard.countBox._healthBuildCountBox')
                </div> <!--sub col div --> --}}
                <div class="col-lg-4 d-flex ">
                    @include('dashboard.countBox._educationBuildCountBox')
                </div>

            </div> <!--sub row -->


            <div class="row">

                <div class="col-lg-4 d-flex ">
                    @include('dashboard.countBox._othersBuildCountBox')
                </div>

            </div>

        </div> <!-- col div -->
    </div> <!-- row div -->
@endcan

@can('Sanitation CountBox')
    <h1 style="padding: 15px 0 15px 0; font-size: 24px;">{{ __("Sanitation Systems") }}</h1>
    <div class="row">
        @foreach ($sanitationSystems as $sanitationSystem)
            <div class="col-lg-3 col-xs-6">
                <div class="info-box sanitation-system-info">
                    <span class="info-box-icon bg-info">
                        @if (
                            $sanitationSystem->icon_name &&
                                $sanitationSystem->icon_name != 'no_icon' &&
                                $sanitationSystem->icon_name != 'others.svg')
                            <img src="{{ asset('img/svg/imis-icons/' . $sanitationSystem->icon_name) }}"
                                alt="{{ __($sanitationSystem->sanitation_system) }}">
                        @else
                            <i class="fa fa-building" aria-hidden="true" title="{{ __('Building') }}"></i>
                        @endif
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">
                            <h3>{{ number_format($sanitationSystem->bin_count) }}</h3>
                        </span>
                        <span class="info-box-number">{{ __($sanitationSystem->sanitation_system) }}</span>
                    </div>
                </div>
            </div> <!-- sub col div -->
        @endforeach

        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._sanitationOffsiteContainmentCountBox')
        </div> <!-- sub col div -->
    </div> <!-- row div -->
@endcan


@can('Utility CountBox')
    <h1 style="padding: 15px 0 15px 0;font-size: 24px;">{{ __("Utilities") }}</h1>
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._sumRoadsCountBox')
        </div> <!--sub col div -->
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._sumSewersCountBox')
        </div> <!--sub col div -->
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._sumDrainsCountBox')
        </div> <!--sub col div -->
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._sumWatersupplyCountBox')
        </div> <!--sub col div -->
    </div> <!-- row div -->
@endcan

@can('FSM CountBox')
        <h1 style="padding: 15px 0 15px 0;font-size: 24px;">{{ __("FSM Services") }}</h1>
        <div class="row">
            <!-- ./col -->
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
                @include('dashboard.fsmCharts._emptyingServicesCountBox')
            </div>

            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._sludgeCollectionsEmptyingServicesBox')
            </div>
            <!-- ./col -->
            <div class="col-lg-4 col-xs-6">
                @include('dashboard.fsmCharts._costPaidByOwnerWithReceiptBox')
            </div>
        </div>
        <!-- /.row -->
@endcan

@can('PTCT CountBox')
    <h1 style="padding: 15px 0 15px 0;font-size: 24px;">{{ __("PT/CT") }}</h1>
        <div class="row">
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._pTCountBox')
        </div> <!--sub col div -->
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._cTCountBox')
        </div> <!--sub col div -->
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._totalPtUserCountBox')
        </div> <!--sub col div -->
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._totalCtUserCountBox')
        </div> <!--sub col div -->
    </div> <!-- row div -->
@endcan

@can('Public Health CountBox')
    <h1 style="padding: 15px 0 15px 0;font-size: 24px;">{{ __("Public Health") }}</h1>
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._totalHotspotCountBox')
        </div> <!--sub col div -->
        <div class="col-lg-3 col-xs-6">
            @include('dashboard.countBox._totalWaterBorneCasesCountBox')
        </div> <!--sub col div -->
    </div> <!-- row div -->
@endcan

<div class="row">
    @can('Ward-Wise Distribution of Buildings Chart')
        <div class="col-md-6">
            @include('dashboard.buildings._buildingsPerWardChart')
        </div>
    @endcan
    @can('Building Use Composition Chart')
        <div class="col-md-6">
            @include('dashboard.buildings._buildingUseChart')
        </div>
    @endcan
</div>

@can('Building Connections to Sanitation System Types Chart')
    <div class="row">
        <div class="col-md-12">
            @include('dashboard.buildings._sanitationSystemsChart')
        </div>
    </div>
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

<div class="row">
        @can('Containment Type-Wise Emptying Services Over the Last 5 Years Chart')
            <div class="col-md-6">
                @include('dashboard.fsmCharts._emptyingServiceByTypeYearChart')
            </div>
        @endcan
        @can('Sludge Collection Trends by Treatment Plants Over the Last 5 Years Chart')
            <div class="col-md-6">
                @include('dashboard.fsmCharts._sludgeCollectionByTreatmentPlant')
            </div>
        @endcan
</div>
@can('Ward-Wise Revenue Collected from Emptying Services Chart')
    <div class="row">
        <div class="col-md-12">
                @include('dashboard.cost-paid-emptying._costPaidByContainmentOwnerPerwardChart')
        </div>
    </div>
@endcan

<div class="row">
    @can('Property Tax Payment Chart')
            <div class="col-md-6">
                @include('dashboard.tax-revenue._taxRevenueChart')
            </div>
    @endcan
    @can('Distribution of Water Supply Payment Dues Chart')
            <div class="col-md-6">
                @include('dashboard.water-supply._waterSupplyPaymentChart')
            </div>
    @endcan
</div>

<div class="row">
    @can('Distribution of Water Supply Services by Ward Chart')
        <div class="col-md-6">
            @include('dashboard.buildings._waterSupplyPipeCodePresencebyWardChart')
        </div>
    @endcan

    @can('Outstanding Payments for SWM Services Chart')
        <div class="col-md-6">
            @include('dashboard.swm.swm_chart')
        </div>
    @endcan
</div>
@can('Distribution of SWM Services by Ward Chart')
    <div class="row">
        <div class="col-md-6">
            @include('dashboard.buildings._swmPresencebyWardChart')
        </div>
    </div>
@endcan


<div class="row">
        @can('Ward-Wise Total Road Length Chart')
            <div class="col-md-6">
                @include('dashboard.charts._roadLengthPerWardChart')
            </div>
        @endcan
        @can('Ward-Wise Sewer Network Length Chart')
            <div class="col-md-6">
                @include('dashboard.sewer._sewerLengthPerWardChart')
            </div>
        @endcan
    </div>
    <div class="row">
        @can('Yearly Distribution of Waterborne Disease Chart')
            <div class="col-md-6">
                @include('dashboard.charts._waterborneCasesChart')
            </div>
        @endcan
        @can('Performance of Municipal Treatment Plants by Last 5 Years Chart')
            <div class="col-md-6">
                @include('dashboard.fsmCharts._treatmentPlantTestbyYearChart')
            </div>
        @endcan
    </div>
<script>
        $('[id="year_select"]').change(function(e) {
            // e.preventDefault();
            var year_select = $(this).val();
            localStorage.setItem('year_select', year_select);
        });

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
        $(function() {
            $('[data-toggle="tooltip"]').tooltip({
                html: true
            });
        });
</script>

{{-- Chart partials push their initialisers here. The client executes them after insertion. --}}
@stack('scripts')
