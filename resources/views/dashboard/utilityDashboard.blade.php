@can('Road Count Box')
    
    <h1 style="padding: 15px 0 15px 0;font-size: 24px;">{{ __('Road')}}</h1>
    <div class="row">
        <div class="col-lg-3 col-md-12 col-xs-12  d-flex">
            @include('dashboard.countBox._sumRoadsCountBox')
        </div> <!-- main col div -->
        <div class="col-lg-9 col-md-12 col-xs-12  extra-padding">
            <h1 style="padding: 15px 0 15px 0;font-size: 18px;">{{ __('Road Length by Surface Type (m)')}}</h1>
            <div class="row">
                <div class="col-lg-3 d-flex">
                    @include('dashboard.countBox._sumRoadSurfaceTypeCountBox')
                </div> <!--sub col div -->
                <div class="col-lg-3  d-flex">
                    @include('dashboard.countBox._sumSurfaceType3CountBox')
                </div> <!--sub col div -->
                <div class="col-lg-3  d-flex">
                    @include('dashboard.countBox._sumSurfaceType2CountBox')
                </div> <!--sub col div -->

                <div class="col-lg-3  d-flex">
                    @include('dashboard.countBox._sumSurfaceType1CountBox')
                </div> <!--sub col div -->
            </div> <!-- sub row -->

            <h1 style="padding: 15px 0 15px 0;font-size: 18px;">{{__('Road Length by Width (m)')}}</h1>
            <div class="row">
                <div class="col-md-2  d-flex">
                    @include('dashboard.countBox._sumRoadWidth3')
                </div> <!--sub col div -->
                <div class="col-md-2  d-flex">
                    @include('dashboard.countBox._sumRoadWidth2')
                </div> <!--sub col div -->
                <div class="col-md-2 d-flex ">
                    @include('dashboard.countBox._sumRoadWidth1')
                </div>
                <div class="col-md-2  d-flex">
                    @include('dashboard.countBox._sumRoadWidth')
                </div> <!--sub col div -->
                <div class="col-md-2  d-flex">
                    @include('dashboard.countBox._sumRoadWidth4')
                </div> <!--sub col div -->
            </div>
            <h1 style="padding: 15px 0 15px 0;font-size: 18px;">{{ __('Road Length by Hierarchy (m)')}} </h1>
            <div class="row">
                <div class="col-lg-4 d-flex ">
                    @include('dashboard.countBox._sumHierarchy1CountBox')
                </div>
                <div class="col-lg-4  d-flex">
                    @include('dashboard.countBox._sumHierarchy2CountBox')
                </div> <!--sub col div -->

                <div class="col-lg-4  d-flex">
                    @include('dashboard.countBox._sumHierarchyCountBox')
                </div> <!--sub col div -->
            </div> <!--sub row -->
        </div> <!-- col div -->
    </div> <!-- row div -->

@endcan

<div class="row">
    @can('Ward-Wise Total Road Length Chart')
    <div class="col-md-6">
        @include('dashboard.charts._roadLengthPerWardChart')
    </div>
    @endcan
    
    @can('Ward-Wise Total Road Length by Surface Type Chart')
    <div class="col-md-6">
        @include('dashboard.charts._roadsSurfaceTypePerWardChart')
    </div>
    @endcan
    
    @can('Ward-Wise Total Road Length by Hierarchy Chart')
    <div class="col-md-6">
        @include('dashboard.charts._roadsHierarchyPerWardChart')
    </div>
    @endcan
    
    @can('Ward-Wise Total Road Length by Width Chart')
    <div class="col-md-6">
        @include('dashboard.charts._roadsWidthPerWardChart')
    </div>
    @endcan
    
</div>

@can('Sewer Count Box')
<h1 style="padding: 15px 0 15px 0;font-size: 24px;">{{ __('Sewer')}}</h1>
<div class="row">
    <div class="col-lg-3 col-md-12 col-xs-12  d-flex">
        @include('dashboard.countBox._sumSewersCountBox')
    </div> <!-- main col div -->
    <div class="col-lg-9 col-md-12 col-xs-12  extra-padding">


        <h1 style="padding: 15px 0 15px 0;font-size: 18px;">{{ __('Sewer Length by Diameter (m)')}}</h1>
        <div class="row">
            <div class="col-md-3  d-flex">
                @include('dashboard.countBox._sumSewerWidth3CountBox')
            </div> <!--sub col div -->
            <div class="col-md-3 d-flex ">
                @include('dashboard.countBox._sumSewerWidth1CountBox')
            </div>
            <div class="col-md-3  d-flex">
                @include('dashboard.countBox._sumSewerWidthCountBox')
            </div> <!--sub col div -->

            <div class="col-md-3  d-flex">
                @include('dashboard.countBox._sumSewerWidth2CountBox')
            </div> <!--sub col div -->
        </div>
    </div> <!-- col div -->
</div> <!-- row div -->
@endcan

<div class="row">
    @can('Ward-Wise Sewer Network Length Chart')
    <div class="col-md-6">
        @include('dashboard.sewer._sewerLengthPerWardChart')
    </div>
    @endcan
    
    @can('Ward-Wise Sewer Length by Diameter Chart')
    <div class="col-md-6">
        @include('dashboard.charts._sewerWidthPerWardChart')
    </div> 
    @endcan
    
</div>

@can('Drain Count Box')
<h1 style="padding: 15px 0 15px 0;font-size: 24px;">{{ __('Drain')}}</h1>
<div class="row">
    <div class="col-lg-3 col-md-12 col-xs-12  d-flex">
        @include('dashboard.countBox._sumDrainsCountBox')
    </div> <!-- main col div -->
    <div class="col-lg-9 col-md-12 col-xs-12  extra-padding">
        <h1 style="padding: 15px 0 15px 0;font-size: 18px;">{{ __('Drain Length by Diameter (m)')}} </h1>
        <div class="row">
            <div class="col-lg-3  d-flex">
                @include('dashboard.countBox._sumDrainWidth3')
            </div> <!--sub col div -->
            <div class="col-lg-3  d-flex">
                @include('dashboard.countBox._sumDrainWidth1')
            </div> <!--sub col div -->

            <div class="col-lg-3 d-flex">
                @include('dashboard.countBox._sumDrainWidth')
            </div> <!--sub col div -->
            <div class="col-lg-3  d-flex">
                @include('dashboard.countBox._sumDrainWidth2')
            </div> <!--sub col div -->
        </div> <!-- sub row -->
        <h1 style="padding: 15px 0 15px 0;font-size: 18px;">{{ __('Drain Length by Type (m)')}}</h1>
        <div class="row">
            <div class="col-lg-4  d-flex">
                @include('dashboard.countBox._sumDrainCoverType')
            </div> <!--sub col div -->
            <div class="col-lg-4 d-flex ">
                @include('dashboard.countBox._sumDrainCoverType1')
            </div>
        </div> <!--sub row -->
        <h1 style="padding: 15px 0 15px 0;font-size: 18px;">{{ __('Drain Length by Surface Type (m)')}}</h1>
        <div class="row">
            <div class="col-lg-4  d-flex">
                @include('dashboard.countBox._sumDrainSurfaceType')
            </div> <!--sub col div -->
            <div class="col-lg-4 d-flex ">
                @include('dashboard.countBox._sumDrainSurfaceType1')
            </div>



        </div> <!--sub row -->

    </div> <!-- col div -->
</div> <!-- row div -->
@endcan

<div class="row">
    @can('Ward-Wise Drain Length Chart')
    <div class="col-md-6">
        @include('dashboard.charts._drainLengthPerWardChart')
    </div>
    @endcan
   
    @can('Ward-Wise Drain Length by Type Chart')
    <div class="col-md-6">
        @include('dashboard.charts._drainsTypePerWardChart')
    </div>
    @endcan

    @can('Ward-Wise Drain Length by Size Chart')
    <div class="col-md-6">
        @include('dashboard.charts._drainWidthPerWardChart')
    </div> 
    @endcan
    
    @can('Ward-Wise Drain Length by Surface Type Chart')
    <div class="col-md-6">
        @include('dashboard.charts._drainsSurfaceTypePerWardChart')
    </div>
    @endcan
    
 
</div>

@can('Water Supply CountBox')
<h1 style="padding: 15px 0 15px 0;font-size: 24px;">{{ __('Water Supply')}}</h1>
<div class="row">
    <div class="col-lg-3 col-md-12 col-xs-12  d-flex">
        @include('dashboard.countBox._sumWatersupplyCountBox')
    </div> <!-- main col div -->
    <div class="col-lg-9 col-md-12 col-xs-12  extra-padding">
        <h1 style="padding: 15px 0 15px 0;font-size: 18px;">{{ __('Water Supply Length by Diameter (m)')}}</h1>
        <div class="row">
            <div class="col-lg-3  d-flex">
                @include('dashboard.countBox._sumWaterSupply3')
            </div> <!--sub col div -->
            <div class="col-lg-3  d-flex">
                @include('dashboard.countBox._sumWaterSupply1')
            </div> <!--sub col div -->

            <div class="col-lg-3 d-flex">
                @include('dashboard.countBox._sumWaterSupply')
            </div> <!--sub col div -->
            <div class="col-lg-3  d-flex">
                @include('dashboard.countBox._sumWaterSupply2')
            </div> <!--sub col div -->
        </div> <!-- sub row -->
    </div> <!-- col div -->
</div> <!-- row div -->
@endcan

<div class="row">
@can('Ward-Wise Water Supply Length Chart')
<div class="col-md-6">
        @include('dashboard.charts._watersupplyLengthPerWardChart')
    </div>
@endcan
   
@can('Ward-Wise Water Supply Length by Diameter Chart')
<div class="col-md-6">
        @include('dashboard.charts._watersupplyDiameterPerWardChart')
    </div>
@endcan
   
</div>

{{-- Chart partials push their initialisers here for asynchronous execution. --}}
@stack('scripts')
