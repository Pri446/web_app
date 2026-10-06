<!-- Last Modified Date: 07-05-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->

@extends('layouts.maps')
@section('title', __('Map'))
@section('content')

    <div class="content-wrapper map-container">
        <div class="map-toolbar clearfix">
            <div style="display: flex;flex-grow: 1;justify-content: space-between;" >
                <div class="controls-div" style="display: flex;">
                    <ul>
                        <a href="#" id="zoomin_control" class="btn btn-default map-control" data-toggle="tooltip"
                           data-placement="bottom" title="{{ __('Zoom In') }}"><i class="fa fa-search-plus fa-fw"></i></a>
                        <a href="#" id="zoomout_control" class="btn btn-default map-control" data-toggle="tooltip"
                           data-placement="bottom" title="{{ __('Zoom Out') }}"><i class="fa fa-search-minus fa-fw"></i></a>
                        <a href="#" id="zoomfull_control" class="btn btn-default map-control" data-toggle="tooltip"
                           data-placement="bottom" title="{{ __('Municipality') }}"><i class="fa fa-globe fa-fw"></i></a>
                        @can('Info Map Tools')
                         <a href="#" id="identify_control" class="btn btn-default map-control" data-toggle="tooltip"
                           data-placement="bottom" title="{{ __('Info') }}"><i class="fa fa-info-circle fa-fw"></i></a>
                        @endcan

                        <a href="#" id="coordinate_control" class="btn btn-default map-control"
                           style="padding:6px 14px!important;" data-toggle="tooltip" data-placement="bottom"
                           title="{{ __('Coordinate Information') }}"><i class="fa fa-map-pin fa-fw"></i></a>
                        <a href="#" id="getpointbycoordinates_control" class="btn btn-default map-control"
                           data-toggle="tooltip" data-placement="bottom" title="{{ __('Locate Point by Coordinate') }}"><i
                                    class="fa fa-location-arrow" aria-hidden="true"></i></a>
                        <a href="#" id="linemeasure_control" class="btn btn-default map-control"
                           data-toggle="tooltip" data-placement="bottom"
                           title="{{ __('Measure Distance') }}"><i class="fa-solid fa-ruler"></i></a>
                        <a href="#" id="polymeasure_control" class="btn btn-default map-control"
                           data-toggle="tooltip" data-placement="bottom"
                           title="{{ __('Measure Area') }}"><i class="fas fa-draw-polygon"></i></a>
                        <a href="#" id="print_control" class="btn btn-default map-control" data-toggle="tooltip"
                           data-placement="bottom" title="{{ __('Print') }}"><i class="fa fa-print fa-fw"></i></a>
                        <a target="_blank" href="{{ asset('pdf/tools-help.pdf') }}" class="btn btn-default map-control"
                           data-toggle="tooltip" data-placement="bottom" title="{{ __('Help') }}"><i class="fa-solid fa-file"></i></a>
                        <a href="#" id="nearestroad_control" class="btn btn-default map-control" data-toggle="tooltip"
                           data-placement="bottom" title="{{ __('Find Nearest Road') }}"> <img src="{{ asset('img/svg/imis-icons/nearestroad.svg')}}" style="height:24px;" alt="Nearest Road Icon"></a>
                        <a href="#" id="containmentbuilding_control" class="btn btn-default map-control"
                           data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Buildings Connected to Containment') }}"><img src="{{ asset('img/svg/imis-icons/building_to_containment.svg')}}" style="height:24px;" alt="Buildings Connected to Containment Icon"></a>
                        <a href="#" id="buildingcontainment_control" class="btn btn-default map-control"
                           data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Containments Connected to Building') }}"> <img src="{{ asset('img/svg/imis-icons/containment_to_building.svg')}}" style="height:24px;"alt="Containment to Buildings Connected Icon"></a>
                        <a href="#" id="associatedtomain_control" class="btn btn-default map-control"
                           data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Associated Buildings') }}"><img src="{{ asset('img/svg/imis-icons/associated_building.svg')}}" style="height:24px;"alt="Associated Buildings Icon"></a>
                        <a href="#" id="wms_layer" class="btn btn-default map-control"
                           data-toggle="tooltip" data-placement="bottom" title="{{ __('Import from WMS') }}"  ><i class="fas fa-layer-group"></i></a>
                        <a href="#" id="get_location" class="btn btn-default map-control"data-toggle="tooltip" data-placement="bottom" title="{{ __('Locate Me') }}" ><img src="{{ asset('img/locate_me.png')}}" style="height:17px;"alt="Location Icon"> </a>
                        <a href="#" id="kml_drag_drop" class="btn btn-default map-control"data-toggle="tooltip" data-placement="bottom" title="{{ __('KML Drag and Drop') }}" ><img src="{{ asset('img/kml_icon.png')}}" style="height:22px;"alt="Location Icon"> </a>
                        <a href="#" id="removemarkers_control" class="btn btn-default map-control" data-toggle="tooltip"
                           data-placement="bottom" title="{{ __('Remove Markers') }}"><i class="fa fa-trash fa-fw"></i></a>
                          
                    </ul>

                </div>

                <div class="">
                    <form class="form-inline" name="filterward_form" id="wardfilter_form">
                        <div class="form-group">
                            <div class="input-group">
                                <select class="form-control" id="filterward_select" name="ward" style="min-width:50px;">
                                    <option value="">All Wards</option>
                                    @foreach($pickWardResults as $unique)
                                    <option value= "{{ $unique->ward }}" > {{ $unique->ward }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="float-right">
                    <form class="form-inline" name="building_search_form" id="building_search_form">
                        <div class="form-row">

                                <input type="text" class="form-control" id="building_value_text" style="min-width:30% !important;" />
                            </div>
                            <div class="form-row">
                                <select class="form-control" id="building_field_select">
                                    <option value="bin">{{ __('BIN') }}</option>
                                    <option value="house_number">{{ __('House Number') }}</option>
                                    <option value="places_layer">{{ __('Places') }}</option>
                                    <option value="roadlines_layer">{{ __('Roads') }}</option>
                                </select>
                        </div>
                            <button class="btn btn-default" type="submit"><i class="fa fa-search fa-fw"></i></button>

                    </form>
                </div>
            </div>
            <div id="buildings-road-popup" class="ol-popup" style="display: none;">
                <h3 class="card-title py-2">{{ __('Summary Info') }}</h3>
                <div id="buildings-road-popup-content"></div>
                @can('Export in Decision Map Tools')
                <div id="buildings-road-popup-content-download">

                        <form method="get" action="{{ url("maps/export-buildings-road") }}">
                            <input type="hidden" name="road_codes" value="" id="road_codes"/>
                            <button type="submit" id="buildings-road-popup-export-excel-btn" class="btn btn-default">
                            {{ __('Export to Excel') }}
                            </button>
                            <button id="buildings-road-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer" >{{ __('Close') }}</button>
                        </form>
                </div>
                @endcan
            </div>
            <div id="waterbody-inaccessible-popup" class="ol-popup" style="display: none;">
                <div id="waterbody-inaccessible-popup-content"></div>
                    <div id="waterbody-inaccessible-popup-content-download">
                        <form method="get" action="{{ url("maps/waterbody-inaccessible-buildings-reports") }}">
                            <input type="hidden" name="waterbody_hose_length_report" value="" id="waterbody_hose_length_report"/>
                            <input type="hidden" name="waterbody_hose_length_unit_report" value="" id="waterbody_hose_length_unit_report"/>
                            <button type="submit" id="waterbody-inaccessible-export-csv-btn" class="btn btn-default">{{ __('Export to Excel') }}</button>
                            <button id="waterbody-inaccessible-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer">{{ __('Close') }}</button>
                        </form>

                </div>
            </div>

            <div id="kml-dragdrop-popup" class="ol-popup" style="display: none;">
                <a href="#" id="kml-dragdrop-popup-closer" class="ol-popup-closer"></a>
                <div id="kml-dragdrop-popup-content"></div>
                @can('Export in KML Drag And Drop')
                <div id="kml-dragdrop-popup-content-download">
                    <div class="btn-group">
                    <form method="POST" action="{{ url('maps/get-kml-info-report-csv') }}" id="kml-export-form">
                        @csrf
                        <input type="hidden" name="kml_dragdrop_geom" value="" id="kml_dragdrop_geom"/>
                        
                        <button type="submit" id="kml-dragdrop-export-excel-btn" class="btn btn-default">
                            {{ __('Export to Excel') }}
                        </button>
                    </form>

                        
                    </div>
                </div>
                @endcan
            </div>
            <div class="text-right">
                <a href="#" id="map-right-sidebar-toggle" class="btn btn-default">
                    <i class="fa fa-bars"></i>
                </a>
            </div>
        </div>

        <div id="map">
            <div id="gmap" style="width: 100%; height: 100%; visibility: hidden;"></div>
            <div id="olmap" style="position: absolute; top: 0; left: 0; right: 0; bottom:0;"></div>
            <div id="popup" class="ol-popup" style="display: none;">
                <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                <div id="popup-content"></div>
            </div>
            <div class="box-footer" id="layer-select-box" style="position: absolute;top: 10px;left: 15px;display: none;filter: drop-shadow(0 10px 4px rgba(0,0,0,0.2));border-radius: 5px;border: 1px solid #cccccc;">
                <div id="feature-info-popup-content">
                    <form class="form-inline" id="feature_info_form">
                        <div class="form-group">
                            <div class="input-group">
                                <select class="form-control" id="feature_info_overlay">
                                    <option value="">{{ __('Select a layer') }}</option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div id="feature_info_content"></div>
                </div>
            </div>
            <div class="box-footer" id="add-tool-box" style="display:none;position: absolute;top: 10px;left: 15px;filter: drop-shadow(0 10px 4px rgba(0,0,0,0.2));border-radius: 5px;border: 1px solid #cccccc;">
                <div id="add-road-tool-box-content">
                  
                        <div xclass="form-group">
                            <div class="input-group">

                                <a href="#" id="add_start_control" class="btn btn-default map-control" data-toggle="tooltip"
                                   data-placement="bottom" title="{{ __('Add') }}"><i class="fa fa-circle-plus fa-fw"></i></a>
                                <a href="#" id="add_undo_last_point_control" class="btn btn-default map-control ml-1" data-toggle="tooltip"
                                   data-placement="bottom" title="{{ __('Undo last point') }}"><i class="fa fa-clock-rotate-left fa-fw"></i></a>
                                <a href="#" id="add_edit_control" class="btn btn-default map-control ml-1" data-toggle="tooltip"
                                   data-placement="bottom" title="{{ __('Edit') }}"><i class="fa fa-pen-to-square fa-fw"></i></a>
                                <a href="#" id="add_undo_control" class="btn btn-default map-control ml-1" data-toggle="tooltip"
                                   data-placement="bottom" title="{{ __('Undo') }}"><i class="fa fa-rotate-left fa-fw"></i></a>
                                <a href="#" id="add_redo_control" class="btn btn-default map-control ml-1" data-toggle="tooltip"
                                   data-placement="bottom" title="{{ __('Redo') }}"><i class="fa fa-rotate-right fa-fw"></i></a>
                                <a href="#" id="add_delete_control" class="btn btn-default map-control ml-1" data-toggle="tooltip"
                                   data-placement="bottom" title="{{ __('Remove all drawn lines') }}"><i class="fa fa-trash fa-fw"></i></a>
                                <a href="javascript:void(0);" id="add_submit_control" class="btn btn-default map-control ml-1" data-toggle="tooltip"data-placement="bottom" title="{{ __('Save') }}" data-code="" >
                                    <i class="fa fa-floppy-disk fa-fw"></i>
                                </a>

                            </div>
                            <!-- Road Form -->
                            <form class="form-horizontal" id="add-road-form">
                                <div class="add-road-form" style="display: none">
                                    <div>
                                        <hr>
                                        <h4>{{ __('Add Road Network') }}</h4>
                                    </div>
                                    <div id="add-road-errors" tabindex="1"></div>

                                     <div class="add-road-form-group">
                                        {!! Form::label('name',__('Road Name') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::text('name',null,['class' => 'form-control', 'placeholder' => __('Road Name')]) !!}
                                    </div>
                                    
                                      <div class="add-road-form-group pt-2">
                                        {!! Form::label('hierarchy',__('Hierarchy'),['class' => 'control-label'],false) !!}
                                        {!! Form::select('hierarchy', $roadHierarchy, null, ['class' => 'form-control', 'placeholder' => __('Road Hierarchy')]);!!}
                                    </div>
                                    
                                    <div class="add-road-form-group pt-2">
                                        {!! Form::label('right_of_way',__('Right of Way (m)') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::number('right_of_way',null,['class' => 'form-control', 'placeholder' => __('Right of Way (m)'),'min' => 1]) !!}
                                    </div>
                                   
                                    <div class="add-road-form-group pt-2">
                                        {!! Form::label('carrying_width',__('Carrying Width (m)') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::number('carrying_width',null,['class' => 'form-control', 'placeholder' => __('Carrying Width (m)'),'min' => 1]) !!}
                                    </div>
                                    

                                    <div class="add-road-form-group pt-2">
                                        {!! Form::label('surface_type',__('Surface Type'),['class' => 'control-label'],false) !!}
                                        {!! Form::select('surface_type', $roadSurfaceTypes, null, ['class' => 'form-control', 'placeholder' => __('Road Surface Type')]);!!}
                                    </div>
                                    
                                    <div class="add-road-form-group pt-2">
                                        {!! Form::label('length',__('Length (m)') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::number('length',null,['class' => 'form-control', 'placeholder' => __('Road Length (m)'),'min' => 1]) !!}
                                    </div>
                                   
                                    
                                    <div class="add-road-form-group pt-2">
                                        {!! Form::button(__('Save'), ['class' => 'btn btn-info', 'id' => 'add_road_submit_btn']) !!}
                                    </div>
                                </div>
                            </form>

                     <form class="form-horizontal" id="add-sewer-form">
                        <div class="add-sewer-form" style="display: none">
                                <div>
                                    <hr>
                                    <h4>{{ __('Add Sewer Network')}}</h4>
                                </div>
                                <div id="add-sewer-errors" tabindex='1'>

                                </div>
                                    <div class="add-sewer-form-group">
                                    {!! Form::label('road_code',__('Road Code') .' <span style="color: red">*</span>', ['class' => 'control-label d-block'], false) !!}
                                    {!! Form::select('road_code', $road_code, null, ['class' => 'form-control', 'placeholder' => __('Road Code'), 'style' => 'width: 350px;']) !!}

                                    </div>
                                    <div class="add-sewer-form-group pt-2">
                                        {!! Form::label('location',__('Location') .'<span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::select('location', ['' => 'Location','middle' => 'middle','side' => 'side'], null, ['class' => 'form-control']) !!}
                                    </div>
                                    <div class="add-sewer-form-group pt-2">
                                        {!! Form::label('length',__('Length (m)') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::text('length', null, ['class' => 'form-control', 'id' => 'length_sewer', 'placeholder' => __('Length (m)'), 'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '');"]) !!}

                                    </div>
                                    <div class="add-sewer-form-group pt-2">
                                       {!! Form::label('diameter',__('Diameter (mm)') .' <span style="color: red">*</span>',['class' => ' control-label'],false) !!}
                                       {!! Form::text('diameter',null,['class' => 'form-control', 'placeholder' => __('Diameter (mm)'),'oninput' => "this.value = this.value.replace(/[^0-9.]/g, ''); ",]) !!}
                                    </div>
                                    <div class="add-sewer-form-group pt-2">
                                        {!! Form::label('treatment_plant_id',__('Treatment Plant'),['class' => 'control-label'],false) !!}
                                        {!! Form::select('treatment_plant_id', $treatmentPlants, null, ['class' => 'form-control', 'placeholder' => __('Treatment Plant')]);!!}
                                    </div>
                                    <div class="add-sewer-form-group pt-2">
                                        {!! Form::button(__('Save'), ['class' => 'btn btn-info','id'=>'add_sewer_submit_btn', 'style' => 'width: -webkit-fill-available; margin-top: 12px;']) !!}
                                    </div>

                            </div>

                    </form>

                    <form class="form-horizontal" id="add-drain-form">
                        <div class="add-drain-form" style="display: none">
                                <div>
                                    <hr>
                                     <h4>{{ __('Add Drain Network')}}</h4>
                                </div>
                                <div id="add-drain-errors" tabindex='1'>

                                </div>
                                    <div class="add-drain-form-group">
                                    {!! Form::label('road_code', __('Road Code') .' <span style="color: red">*</span>', ['class' => 'control-label d-block'], false) !!}
                                        {!! Form::select('road_code', $road_code, null, ['class' => 'form-control','id'=>'road_code_drain', 'placeholder' =>  __('Road Code'), 'style' => 'width: 350px;']) !!}
                                    </div>
                                    <div class="add-drain-form-group pt-2">
                                        {!! Form::label('cover_type', __('Cover Type'),['class' => 'control-label'],false) !!}
                                        {!! Form::select('cover_type', $cover_type, null, ['class' => 'form-control', 'placeholder' => __('Cover Type')])!!}
                                    </div>

                                    <div class="add-drain-form-group pt-2">
                                        {!! Form::label('surface_type', __('Surface Type'),['class' => 'control-label'],false) !!}
                                        {!! Form::select('surface_type', $surface_type, null, ['class' => 'form-control', 'placeholder' => __('Surface Type'), 'id'=>'surface_type_drain',])!!}
                                    </div>
                                    <div class="add-drain-form-group pt-2">
                                        {!! Form::label('size',__('Width (mm)') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::text('size', null, ['class' => 'form-control', 'placeholder' => __('Width (mm)'), 'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '')"]) !!}

                                    </div>
                                    <div class="add-drain-form-group pt-2">
                                        {!! Form::label('length',__('Length (m)') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::text('length', null, ['class' => 'form-control', 'id' => 'length_drain', 'placeholder' => __('Length (m)'), 'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '');"]) !!}

                                    </div>
                                    <div class="add-drain-form-group pt-2">
                                        {!! Form::label('treatment_plant_id',__('Treatment Plant'),['class' => 'control-label'],false) !!}
                                        {!! Form::select('treatment_plant_id', $treatmentPlants, null, ['class' => 'form-control', 'id' => 'tp_drain','placeholder' => __('Treatment Plant')]);!!}
                                    </div>
                                    <div class="add-drain-form-group pt-2">
                                        {!! Form::button(__('Save'), ['class' => 'btn btn-info','id'=>'add_drain_submit_btn', 'style' => 'width: -webkit-fill-available; margin-top: 12px;']) !!}
                                    </div>
                            </div>
                       
                    </form>

                    <form class="form-horizontal" id="add-watersupply-form">
                        <div class="add-watersupply-form" style="display: none">
                                <div>
                                    <hr>
                                    <h4>{{ __('Add Water Supply Network')}}</h4>
                                </div>
                                <div id="add-watersupply-errors" tabindex='1'>

                                </div>
                               
                                    <div class="add-watersupply-form-group">
                                    {!! Form::label('road_code', __('Road Code') .' <span style="color: red">*</span>', ['class' => 'control-label d-block'], false) !!}
                                        {!! Form::select('road_code', $road_code, null, ['class' => 'form-control','id'=>'road_code_watersupply', 'placeholder' =>  __('Road Code'), 'style' => 'width: 350px;']) !!}
                                    </div>
                                    <div class="add-watersupply-form-group pt-2">
                                        {!! Form::label('project_name', __('Project Name') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::text('project_name', null, ['class' => 'form-control', 'placeholder' => __('Project Name')]) !!}

                                    </div>
                                    <div class="add-watersupply-form-group pt-2">
                                        {!! Form::label('type',__('Type'),['class' => 'control-label'],false) !!}
                                        {!! Form::select('type', ['Main' => 'Main', 'Secondary' => 'Secondary'], null, ['class' => 'form-control', 'placeholder' => __('Type')]);!!}
                                    </div>
                                    <div class="add-watersupply-form-group pt-2">
                                        {!! Form::label('material_type',__('Material Type'),['class' => 'control-label'],false) !!}
                                        {!! Form::select('material_type', ['HDPE' => 'HDPE', 'GI' => 'GI'], null, ['class' => 'form-control', 'placeholder' => __('Material Type')])!!}
                                    </div>
                                    <div class="add-watersupply-form-group pt-2">
                                       {!! Form::label('diameter',__('Diameter (mm)') .' <span style="color: red">*</span>',['class' => ' control-label'],false) !!}
                                       {!! Form::text('diameter',null,['class' => 'form-control','id'=>'diameter_watersupply', 'placeholder' => __('Diameter (mm)'),'oninput' => "this.value = this.value.replace(/[^0-9.]/g, ''); ",]) !!}
                                    </div>
                                    <div class="add-watersupply-form-group pt-2">
                                        {!! Form::label('length',__('Length (m)') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        {!! Form::text('length', null, ['class' => 'form-control', 'id' => 'length_watersupply', 'placeholder' =>  __('Length (m)'), 'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '');"]) !!}

                                    </div>
                                   
                                    <div class="add-watersupply-form-group pt-2" >
                                    {!! Form::button(__('Save'), ['class' => 'btn btn-info', 'id' => 'add_watersupply_submit_btn', 'style' => 'width: -webkit-fill-available; margin-top: 12px;']) !!}


                                    </div>
                            </div>
                       
                    </form>
                </div>
                    <div id="feature_info_content"></div>
                </div>
            </div>
            <div class="box-footer" id="add-road-inaccessible-box" style="display:none;position: absolute;top: 10px;left: 15px;filter: drop-shadow(0 10px 4px rgba(0,0,0,0.2));border-radius: 5px;border: 1px solid #cccccc;">
                <div id="add-road-inaccessible-box-content">
                    <form class="form-horizontal" id="add-road-inaccessible-form">
                        <div class="form-group">
                            <div class="add-road-inaccessible-form" >
                                <div>
                                    <h4>{{ __('Hard to Reach Buildings') }}</h4>
                                </div>
                                <div id="add-road-inaccessible-errors" tabindex='1'>
                                </div>
                                    <div class="add-road-inaccessible-form-group">
                                        {!! Form::label('road_width', __('Carrying Width') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                         <div class="container-fluid">
                                        <div class="row">
                                        {!! Form::text('road_width',null,['class' => 'form-control col-md-6', 'placeholder' => __('Carrying Width')  ,'oninput' => "this.value = this.value < 0 ? '' : this.value", ]) !!}
                                        <select class="form-control col-md-6" id="road_width_unit"><option value="meter">{{ __('Meter') }}</option><option value="feet">{{ __('Feet') }}</option></select>
                                    </div></div>
                                        </div>
                                    <div class="add-road-inaccessible-form-group">
                                        {!! Form::label('hose_length', __('Hose Length') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        <div class="container-fluid">
                                        <div class="row">
                                            {!! Form::text('hose_length',null,['class' => 'form-control col-md-6', 'placeholder' => __('Hose Length') , 'oninput' => "this.value = this.value < 0 ? '' : this.value",]) !!}
                                        <select class="form-control col-md-6" id="hose_length_unit"><option value="meter">{{ __('Meter') }}</option><option value="feet">{{ __('Feet') }}</option></select>
                                    </div></div></div>
                                    <div class="add-road-inaccessible-form-group">
                                        {!! Form::button(__('Submit'), ['class' => 'btn btn-info','id'=>'add_road_inaccessible_submit_btn']) !!}
                                    </div>
                            </div>
                        </div>
                    </form>
                    <div id="feature_info_content"></div>
                </div>
            </div>
            <div class="box-footer" id="add-waterbody-inaccessible-box" style="display:none;position: absolute;top: 10px;left: 15px;filter: drop-shadow(0 10px 4px rgba(0,0,0,0.2));border-radius: 5px;border: 1px solid #cccccc;">
                <div id="add-waterbody-inaccessible-box-content">
                    <form class="form-horizontal" id="add-waterbody-inaccessible-form">
                        <div class="form-group">
                            <div class="add-waterbody-inaccessible-form" >
                                <div>
                                    <h4>{{ __('Buildings Close to Water Bodies') }}</h4>
                                </div>
                                <div id="add-waterbody-inaccessible-errors" tabindex='1'>
                                </div>
                                    <div class="add-waterbody-inaccessible-form-group">
                                        {!! Form::label('waterbody_hose_length', __('Buffer Distance') .' <span style="color: red">*</span>',['class' => 'control-label'],false) !!}
                                        <div class="container-fluid">
                                        <div class="row">

                                            {!! Form::text('waterbody_hose_length',null,['class' => 'form-control col-md-6', 'placeholder' => __('Buffer Distance'),'oninput' => "this.value = this.value < 0 ? '' : this.value",]) !!}
                                        <select class="form-control col-md-6" id="waterbody_hose_length_unit"><option value="meter">{{ __('Meter') }}</option><option value="feet">{{ __('Feet') }}</option></select>
                                        </div>
                                        </div>
                                    </div>
                                    <div class="add-waterbody-inaccessible-form-group">
                                        {!! Form::button(__('Submit'), ['class' => 'btn btn-info','id'=>'add_waterbody_inaccessible_submit_btn']) !!}
                                    </div>
                            </div>
                        </div>
                    </form>
                    <div id="feature_info_content"></div>
                </div>
            </div>
            <div id="popup-marker" class="ol-popup" style="display: none;">
                <a href="#" id="popup-marker-closer" class="ol-popup-closer"></a>
                <div id="popup-marker-content"></div>
            </div>
            <div id="feature-info-popup" class="ol-popup" style="display: none; position: fixed; top: 100px; left: 100px; max-height: 300px; min-width: 450px; max-width: 500px; border: 1px solid #ddd; background-color: #fff; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
    <a href="#" id="feature-info-popup-closer" class="ol-popup-closer" style="text-decoration: none; font-size: 16px; position: absolute; top: 10px; right: 10px;"></a>
    <div id="feature_information" style="max-height: 250px; min-width: 450px; overflow-y: auto; padding: 10px;">
        <!-- Content goes here -->
    </div>
</div>

            <div id="report-popup" class="ol-popup" style="display: none;">
                <a href="#" id="report-popup-closer" class="ol-popup-closer"></a>
                <div id="report-popup-content"></div>
                <div id="report-popup-content-download">
                    <div><strong>{{ __('Export to') }}:</strong></div>
                    <div class="btn-group">
                        <form method="post" action="{{ url("getPolygonReportCSV") }}">
                            <input type="hidden" name="geom" value="" id="report-export-geom"/>
                            <button type="submit" id="report-export-csv-btn" class="btn btn-default">{{ __('Excel') }}</button>
                        </form>
                    </div>
                </div>
            </div>
            <div id="drain-potentialSummary-popup" class="ol-popup" style="display: none;">
                <h3 class="card-title py-2">{{ __('Summary Info') }}</h3>
                <div id="drain-potentialSummary-popup-content"></div>
                <div id="drain-potentialSummary-popup-content-download">
                        <form method="get" action="{{ url("maps/export-drain-potential-buildings") }}">
                            <input type="hidden" name="db_code" value="" id="DrainBufferCode"/>
                            <input type="hidden" name="db_distance" value="" id="DBdistance"/>
                            @can('Export in Decision Map Tools')
                            <button type="submit" id="drain-potentialSummary-export-excel-btn" class="btn btn-default">
                                {{ __('Export to Excel') }}
                            </button>
                            @endcan
                            <button id="drain-potentialSummary-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer" >{{ __('Close') }}</button>
                        </form>
                </div>
            </div>
            <div id="water-body-popup" class="ol-popup" style="display: none;">
                <h3 class="card-title py-2">{{ __('Summary Info') }}</h3>
                <div id="water-body-popup-content"></div>

                <div id="water-body-popup-content-download">
                        <form method="get" action="{{ url("maps/export-buffer-polygon-waterbody") }}">
                            <input type="hidden" name="wb_code" value="" id="waterBodyCode"/>
                            <input type="hidden" name="wb_distance" value="" id="WBdistance"/>
                            @can('Export in Summary Information Map Tools')
                            <button type="submit" id="water-body-export-excel-btn" class="btn btn-default">{{ __('Export to Excel') }}
                            </button>
                            @endcan
                            <button id="water-body-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer" >{{ __('Close') }}</button>
                        </form>
                </div>

            </div>
            <div id="ward-buildings-popup" class="ol-popup" style="display: none;">
                <div id="ward-buildings-popup-content"></div>

                <div id="ward-buildings-popup-content-download">
                        <form method="get" action="{{ url("maps/export-ward-buildings") }}">
                            <input type="hidden" name="ward_building_no" value="" id="ward_building_no"/>
                            @can('Export in Summary Information Map Tools')
                            <button type="submit" id="ward-buildings-export-excel-btn" class="btn btn-default">{{ __('Export to Excel') }}
                            </button>
                            @endcan
                            <button id="ward-buildings-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer" >{{ __('Close') }}</button>
                        </form>
                </div>

            </div>
            <div id="buffer-polygon-popup" class="ol-popup" style="display: none;">


                    <h3 class="card-title py-2">{{ __('Summary Info') }}</h3>

                    <div id="buffer-polygon-popup-content"></div>



                <div id="buffer-polygon-popup-content-download">
                        <form method="get" action="{{ url("maps/export-buffer-polygon") }}">
                            <input type="hidden" name="buffer_polygon_geom" value="" id="buffer_polygon_geom"/>
                            <input type="hidden" name="buffer_polygon_distance" value="" id="buffer_polygon_distance"/>
                            @can('Export in Summary Information Map Tools')
                              <button type="submit" id="buffer-polygon-export-excel-btn" class="btn btn-default">{{ __('Export to Excel') }}
                              </button>
                             @endcan
                             <button id="buffer-polygon-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer" >{{ __('Close') }}</button>
                        </form>
                </div>



            </div>
            <div id="road-popup" class="ol-popup" style="display: none;">
                <h3 class="card-title py-2">{{ __('Summary Info') }}</h3>
                <div id="road-popup-content"></div>

                <div id="road-popup-content-download">
                        <form method="get" action="{{ url("maps/export-road-buildings") }}">
                            <input type="hidden" name="road_code" value="" id="RDCode"/>
                            <input type="hidden" name="rb_distance" value="" id="RDBdistance"/>
                            @can('Export in Summary Information Map Tools')
                            <button type="submit" id="road-export-excel-btn" class="btn btn-default">{{ __('Export to Excel') }}</button>
                            @endcan
                            <button id="road-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer" >{{ __('Close') }}</button>
                        </form>
                </div>

            </div>
            <div id="point-buffer-popup" class="ol-popup" style="display: none;">
                <h3 class="card-title py-2">{{ __('Summary Info') }}</h3>
                <div id="point-buffer-popup-content"></div>
                <div id="point-buffer-popup-content-download">
                        <form method="get" action="{{ url("maps/export-point-buildings") }}">
                            <input type="hidden" id="PTB-long-csv" name="PTB_long" value=""/>
                            <input type="hidden" id="PTB-lat-csv" name="PTB_lat" value=""/>
                            <input type="hidden" id="PTB-distance" name="PTB_distance" value=""/>
                            @can('Export in Summary Information Map Tools')
                            <button type="submit" id="ptb-export-excel-btn" class="btn btn-default">{{ __('Export to Excel') }}</button>
                            @endcan
                            <button id="point-buffer-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer" >{{ __('Close') }}</button>
                        </form>
                </div>
            </div>
            <div id="export-popup" class="ol-popup" style="display: none;">
                <a href="#" id="export-popup-closer" class="ol-popup-closer"></a>
                <h3 class="card-title py-2">{{ __('Export Data Set') }}</h3>
                <div id="export-popup-content">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >{{ __('Overlay') }}</span>
                    </div>
                    <select class="form-control"  id="export_overlay">
                        <option value="">{{ __('Select a layer') }}</option>
                    </select>
                    </div>
                    <div class="input-group">
            <div class="input-group-prepend">
                                        <span class="input-group-text" >{{ __('Export') }}</span>
                                </div>
            <div class="input-group-append">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ __('Select a format') }}</button>
                <div class="dropdown-menu">
                <a class="dropdown-item" id="export-csv-btn" href="#">CSV</a>
                <a class="dropdown-item"  id="export-kml-btn" href="#">KML</a>
                <a class="dropdown-item" id="export-shape-btn"  href="#">Shape File</a>
                </div>
            </div>
            </div>

                </div>
            </div>

            <div id="export-csv-popup" class="ol-popup" style="display: none;">
                <a href="#" id="export-csv-popup-closer" class="ol-popup-closer"></a>
                <h3 class="card-title py-2">{{ __('Buidings data with owner info') }}</h3>
                <div id="export-csv-popup-content">
                    <div class="input-group">
                        {{-- <div class="input-group-prepend">
                            <span class="input-group-text" >{{ __('Export') }}</span>
                        </div> --}}
                        <div class="input-group-append">
                            <input type="hidden" id="building-with-owner-polygon-geom" value=""/>
                            <button id="export-buildings-csv-btn" class="btn btn-default">{{ __('Export to Excel') }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="population-popup" class="ol-popup" style="display: none;">
                <a href="#" id="population-popup-closer" class="ol-popup-closer"></a>
                <div id="population-popup-content"></div>
            </div>
            <div id="road-inaccessible-popup" class="ol-popup" style="display: none;">
                <div id="road-inaccessible-popup-content"></div>
                <div id="road-inaccessible-popup-content-download">
                        <form method="get" action="{{ url("maps/road-inaccessible-buildings-reports") }}">
                            <input type="hidden" name="road_width_report" value="" id="road_width_report"/>
                            <input type="hidden" name="road_width_unit_report" value="" id="road_width_unit_report"/>
                            <input type="hidden" name="road_hose_length_report" value="" id="road_hose_length_report"/>
                            <input type="hidden" name="road_hose_length_unit_report" value="" id="road_hose_length_unit_report"/>
                            <button type="submit" id="report-road-inaccessible-export-csv-btn" class="btn btn-default">{{ __('Export to Excel') }}</button>
                            <button id="road-inaccessible-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer">{{ __('Close') }}</button>
                        </form>

                </div>
            </div>
            <div id="map-right-sidebar">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active nav-item"><a href="#layers-tab" aria-controls="layers" role="tab"
                                                              data-toggle="tab" class="nav-link active" aria-selected="true">{{ __('Layers') }}</a></li>
                    <li role="presentation" class="nav-item"><a href="#analysis-tab" aria-controls="analysis" role="tab"
                                               data-toggle="tab" class="nav-link">{{ __('Tools') }}</a></li>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade active show" id="layers-tab">
                        <div>
                            <div>
                                <label for="base_layer_select">{{ __('Base Layer') }}</label>
                            </div>
                            <div>
                                <select id="base_layer_select">
                                    <option value="">{{ __('None') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="overlay-spacer">
                            <div>
                                <label for="">{{ __('Overlays') }}</label>
                            </div>
                            <div id="overlay_checkbox_container"></div>
                        </div>

                    </div>
                    <div role="tabpanel" class="tab-pane fade" id="analysis-tab">
                        <!-- Service Providers Tools -->
                        @can('Service Delivery Map Tools')
                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Service Delivery') }}">
                            <a id="servicedelivery_control" class="btn btn-default collapse-control" role="button"
                            data-toggle="collapse" href="#collapse_service_delivery" aria-expanded="false"
                            aria-controls="collapse_service_delivery"><i class="fa-brands fa-servicestack"></i>{{ __('Service Delivery Tools') }}</a>
                            </span>
                            <div class="collapse" id="collapse_service_delivery">
                                <div class="card">
                                    <div class="card-body">
                                        @can('Applications Map Tools')
                                            <!-- find application -->
                                                <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Applications') }}">
                                                    <a id="applicationcontainments_control" class="btn btn-default collapse-control collapsed" role="button" data-toggle="collapse" href="#collapse_find_appications" aria-expanded="false" aria-controls="collapse_find_appications"><i
                                                    class="fa fa-file-text"></i>{{ __('Applications') }}</a>
                                                </span>
                                                <div class="collapse" id="collapse_find_appications">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <form role="form" class="form-inline" name="find_application_yearmonth"
                                                            id="find_application_yearmonth" style="margin-bottom: 15px">
                                                                <div class="form-group">
                                                                    <div class="input-group input-group-sm">
                                                                        <select name="applicaion_year" class="form-control col-md-4"
                                                                            id="applicaion_year">
                                                                            <option value="">{{ __('Year') }}</option>

                                                                            @foreach($pickDateResults as $unique)
                                                                                <option value= "{{ $unique->date1 }}" > {{ $unique->date1 }}</option>

                                                                            @endforeach
                                                                        </select>
                                                                        <select name="application_month" class="form-control col-md-4"
                                                                            id="application_month">
                                                                            <option value="">{{ __('Month') }}</option>
                                                                            @for ($mm=1; $mm<=12; $mm++)
                                                                                <option value="{{ $mm }}">{{ $mm }}</option>
                                                                            @endfor
                                                                        </select>
                                                                        <div class="input-group-append">
                                                                            <button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i></button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                            <form role="form" name="application_date_form" id="application_date_form" class="form-inline">
                                                                <div class="input-group input-group-sm">
                                                                    <div class="input-group-prepend"><span class="input-group-text">{{ __('Date') }}</span></div>
                                                                        <input type="date" class="form-control col-md-10" id="application_date_field"/>
                                                                        <div class="input-group-append">
                                                                            <button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i></button>
                                                                        </div>
                                                                    </div>
                                                            </form>
                                                            <br>
                                                            <table>
                                                                <tr>
                                                                    <td style="vertical-align: top;"><img
                                                                                src="{{ asset("/img/application.png") }}"></td>
                                                                    <td>-{{ __('Application only') }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="vertical-align: top;"><img
                                                                                src="{{ asset("/img/application-emptying.png") }}"></td>
                                                                    <td>-{{ __('Application and emptying service') }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="vertical-align: top;"><img
                                                                                src="{{ asset("/img/application-feedback.png") }}"></td>
                                                                    <td style="vertical-align: top;">-{{ __('Application, emptying service and feedback') }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="vertical-align: top;"><img
                                                                                src="{{ asset("/img/application-sludge-collection.png") }}"></td>
                                                                    <td style="vertical-align: top;">-{{ __('Application, emptying service, sludge collection and feedback') }}
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                        @endcan

                                        @can('Emptied Applications Not Reached to TP Map Tools')
                                            <!-- find emptied applications that have not reached to treatment-plants -->
                                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Emptied Applications that have not reached to Treatment Plant') }}">
                                                <a id="applications_not_tp" class="btn btn-default collapse-control collapsed" role="button" data-toggle="collapse" href="#collapse_applications_not_tp" aria-expanded="false" aria-controls="collapse_find_tax_due_buildings"><i class="fa-solid fa-calendar-xmark"></i>{{ __('Emptied Applications not reached to Treatment Plant') }}</a>
                                            </span>
                                            <div class="collapse" id="collapse_applications_not_tp">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <form role="form" class="form-inline"
                                                        name="find_application_not_tp_yearmonth"
                                                        id="find_application_not_tp_yearmonth" style="margin-bottom: 15px">
                                                            <div class="form-group">
                                                                <div class="input-group input-group-sm">
                                                                    <select name="applicaion_not_tp_year" class="form-control col-md-4" id="applicaion_not_tp_year">
                                                                        <option value="">{{ __('Year') }}</option>
                                                                        @foreach($pickDateResults as $unique)
                                                                            <option value= "{{ $unique->date1 }}" > {{ $unique->date1 }}</option>
                                                                        @endforeach
                                                                    </select>

                                                                    <select name="application_not_tp_month" class="form-control col-md-4" id="application_not_tp_month">
                                                                        <option value="">{{ __('Month') }}</option>
                                                                        @for ($mm=1; $mm<=12; $mm++)
                                                                            <option value="{{ $mm }}">{{ $mm }}</option>
                                                                        @endfor
                                                                    </select>
                                                                    <div class="input-group-append">
                                                                        <button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                        <form role="form" name="application_not_tp_date_form" id="application_not_tp_date_form" class="form-inline">
                                                            <div class="input-group input-group-sm">
                                                                <div class="input-group-prepend"><span class="input-group-text" id="basic-addon1">{{ __('Date') }}</span></div>
                                                                    <input type="date" class="form-control col-md-6"
                                                                id="application_not_tp_date_field"/>
                                                                <div class="input-group-append">
                                                                    <button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i></button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                        <br>
                                                    </div>
                                                </div>
                                            </div>
                                        @endcan

                                        @can('Containments Proposed To Be Emptied Map Tools')
                                            <!-- find containment proposed to be emptied-->
                                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Containments proposed to be emptied') }}">
                                                <a id="containments_proposed_to_be_emptied" class="btn btn-default collapse-control" role="button" data-toggle="collapse"  href="#collapse_proposed_emptying_containments" aria-expanded="false" aria-controls="collapse_proposed_emptying_containments"><i class="fa fa-square" aria-hidden="true"></i>{{ __('Containments proposed to be emptied') }}</a>
                                            </span>
                                            <div class="collapse" id="collapse_proposed_emptying_containments">
                                                <div class="card">
                                                <div class="card-body">
                                                        <form class="form-inline" name="proposed_emptying_days_form" id="proposed_emptying_days_form" style="margin-bottom: 15px">
                                                            <div class="form-group ">
                                                                <div class="input-group input-group-sm ">
                                                                    <div class="input-group-prepend"><span class="input-group-text" id="basic-addon1">{{ __('Next') }}</span></div>
                                                                    <input type="text" class="form-control col-md-1"
                                                                        id="proposed_emptying_days">
                                                                    <div class="input-group-append"><span class="input-group-text">{{ __('Days') }}</span></div>
                                                                    <div class="input-group-append">
                                                                        <button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                        <form class="form-inline" name="proposed_emptying_week_form"
                                                        id="proposed_emptying_week_form" style="margin-bottom: 15px">
                                                            <div class="form-group">
                                                                <div class="input-group input-group-sm">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text">{{ __('Next Week') }}</span>
                                                                    </div>
                                                                    <div class="input-group-append">
                                                                        <button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                        <form role="form" name="proposed_emptying_date_form" id="proposed_emptying_date_form">
                                                            <div class="input-group input-group-sm">
                                                                <div class="input-group-prepend"><span class="input-group-text">{{ __('Date') }}</span></div>
                                                                <input type="date" class="form-control" id="proposed_emptying_date"/>
                                                                <div class="input-group-append">
                                                                    <button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i></button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endcan

                                        @can('Service Feedback Map Tools')
                                            <!-- feedback chart -->
                                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Generate Feedback Chart within Custom Boundary') }}">
                                                <a href="#" id="feedback_control" class="btn btn-default map-control"><i
                                                class="fa fa-list-alt"></i>{{ __('Service Feedback') }}</a>
                                            </span>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcan

                        @can('General Map Tools')
                            <!-- General Tools -->
                            <span data-toggle="tooltip" data-placement="bottom" title= "General Tools">
                                <a id="generaltools_control" class="btn btn-default collapse-control" role="button"
                                data-toggle="collapse" href="#collapse_general_tools" aria-expanded="false"
                                aria-controls="collapse_general_tools"><i class="fa-brands fa-servicestack"></i>{{ __('General Tools') }}</a>
                            </span>
                            <div class="collapse" id="collapse_general_tools">
                                <div class="card">
                                    <div class="card-body">
                                        @can('Building by Structure Map Tools')
                                            <!-- Find Building By structure -->
                                            <span data-toggle="tooltip" data-placement="bottom" title= "{{ __('Buildings by Structure Type') }}">
                                                <a class="btn btn-default collapse-control" role="button" data-toggle="collapse" href="#collapse_building_structype_filter" aria-expanded="false" aria-controls="collapse_building_structype_filter"><i class="fa fa-building"></i>{{ __('Buildings by Structure Type') }}</a>
                                            </span>
                                            <div class="collapse" id="collapse_building_structype_filter">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <div id="building_structype_checkbox_container">
                                                            @foreach($pickStructureResults as $structype)
                                                                    <div class="checkbox">
                                                                        <label>
                                                                            <input type="checkbox"  name="{{ $structype->id }}"   value= "{{ $structype->id }}" /> {{ $structype->type }}
                                                                        </label>
                                                                    </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endcan

                                        @can('Property Tax Map Tools')
                                            <!-- Building by Tax Payments -->
                                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Property Tax Collection Status') }}">
                                                <a class="btn btn-default collapse-control" role="button" data-toggle="collapse" href="#collapse_building_tax_status" aria-expanded="false" aria-controls="collapse_building_tax_status"><i class="fa fa-building"></i>{{ __('Property Tax Collection Status') }}</a>
                                            </span>
                                            <div class="collapse" id="collapse_building_tax_status">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <form role="form" name="building_tax_payment_form" id="building_tax_payment_form">
                                                            <div id="building_tax_payment_checkbox_container">
                                                                @foreach($dueYears as $key => $val)
                                                                    <div class="checkbox">
                                                                        <label>
                                                                            <input type="checkbox" name="{{$key}}" value="{{$val}}"/>
                                                                            {{$val}}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>

                                                            <button type="button" class="btn btn-default" id="building_tax_payment_clear_button">{{ __('Filter') }}
                                                            </button>
                                                            @can('Export in General Map Tools')
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ __('Export') }}<span class="caret"></span>
                                                                    </button>
                                                                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                                        <a href="#" class="dropdown-item" id="export_building_tax_filter_csv">CSV</a>
                                                                        <a href="#" class="dropdown-item" id="export_building_tax_filter_kml">KML</a>
                                                                        <a href="#" class="dropdown-item" id="export_building_tax_filter_shp">Shape File</a>
                                                                    </div>
                                                                </div>
                                                            @endcan
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endcan

                                        @can('Water Payment Status Map Tools')
                                            <!-- water supply payments -->
                                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Water Payment Status') }}">
                                                <a class="btn btn-default collapse-control" role="button" data-toggle="collapse" href="#collapse_water_supply_status" aria-expanded="false" aria-controls="collapse_building_tax_status"><i class="fa fa-building"></i>{{ __('Water Payment Status') }}</a>
                                            </span>
                                            <div class="collapse" id="collapse_water_supply_status">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <form role="form" name="water_supply_payment_form" id="water_supply_payment_form">
                                                            <div id="water_supply_payment_checkbox_container">
                                                                @foreach($dueYears as $key => $val)
                                                                    <div class="checkbox">
                                                                        <label>
                                                                            <input type="checkbox" name="{{$key}}" value="{{$val}}"/>
                                                                            {{$val}}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                            <button type="button" class="btn btn-default" id="water_supply_payment_clear_button">{{ __('Filter') }}
                                                            </button>
                                                            @can('Export in General Map Tools')
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-default dropdown-toggle"
                                                                            data-toggle="dropdown" aria-haspopup="true"
                                                                            aria-expanded="false">
                                                                            {{ __('Export') }} <span class="caret"></span>
                                                                    </button>

                                                                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                                        <a href="#" class="dropdown-item"  id="export_water_supply_filter_csv">CSV</a>
                                                                        <a href="#" class="dropdown-item"  id="export_water_supply_filter_kml">KML</a>
                                                                        <a href="#" class="dropdown-item"  id="export_water_supply_filter_shp">Shape File</a>
                                                                    </div>
                                                                </div>
                                                            @endcan
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endcan

                                        @can('Solid Waste Payment Status Map Tools')
                                            <!-- Swm -->
                                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Solid Waste Payment Status') }}">
                                                <a class="btn btn-default collapse-control" role="button" data-toggle="collapse" href="#collapse_swm" aria-expanded="false" aria-controls="collapse_building_tax_status"><i class="fa fa-building"></i>{{ __('Solid Waste Payment Status') }}</a>
                                            </span> 
                                            <div class="collapse" id="collapse_swm">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <form role="form" name="swm_form"id="swm_form">
                                                            <div id="swm_checkbox_container">
                                                                @foreach($dueYears as $key => $val)
                                                                    <div class="checkbox">
                                                                        <label>
                                                                            <input type="checkbox" name="{{$key}}" value="{{$val}}"/>
                                                                            {{$val}}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                            <button type="button" class="btn btn-default"
                                                                    id="swm_clear_button">{{ __('Filter') }}
                                                            </button>
                                                            @can('Export in General Map Tools')
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-default dropdown-toggle"
                                                                            data-toggle="dropdown" aria-haspopup="true"
                                                                            aria-expanded="false">
                                                                            {{ __('Export') }} <span class="caret"></span>
                                                                    </button>
                                                                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                                        <a href="#" class="dropdown-item"  id="export_swm_filter_csv">CSV</a>
                                                                        <a href="#" class="dropdown-item"  id="export_swm_filter_kml">KML</a>
                                                                        <a href="#" class="dropdown-item"  id="export_swm_filter_shp">Shape File</a>
                                                                    </div>
                                                                </div>
                                                            @endcan
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcan

                        @can('Data Export Map Tools')
                            <!-- Data Export Tools -->
                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Data Export') }}">
                                <a id="dataexporttools_control" class="btn btn-default collapse-control" role="button"
                                data-toggle="collapse" href="#collapse_data_export_tools" aria-expanded="false"
                                aria-controls="collapse_data_export_tools"><i class="fa-solid fa-file-export"></i>{{ __('Data Export Tools') }}</a>
                            </span>
                            <div class="collapse" id="collapse_data_export_tools">
                                <div class="card">
                                    <div class="card-body">
                                        @can('Filter by Wards Map Tools')
                                            <!-- filter by wards -->
                                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Filter by Wards') }}">
                                                <a class="btn btn-default collapse-control" role="button" data-toggle="collapse"
                                                href="#collapse_ward_filter" aria-expanded="false"
                                                aria-controls="collapse_ward_filter"><i class="fa fa-map"></i>{{ __('Filter by Wards') }}</a>
                                            </span>
                                            <div class="collapse" id="collapse_ward_filter">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <form role="form" name="ward_form" id="ward_form">
                                                            <div class="form-group ">
                                                                <label for="ward" >{{ __('Wards') }}</label>
                                                                {!! Form::select('ward', $wards,null, ['id' => 'ward', 'multiple' => true, 'style' => 'width: 100%'])!!}
                                                            </div>
                                                            <div class="form-group ">
                                                                <label for="ward_overlay" >{{ __('Overlay') }}</label>
                                                                    <select id="ward_overlay" style="width: 100%" >
                                                                        <option value="">{{ __('Select a layer') }}</option>
                                                                    </select>
                                                            </div>
                                                            <button type="submit"  id="ward_filter" class="btn btn-default">{{ __('Filter') }}</button>
                                                            <button type="button" class="btn btn-default" id="ward_clear_button">
                                                                {{ __('Clear') }}
                                                            </button>
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-default dropdown-toggle"
                                                                        data-toggle="dropdown" aria-haspopup="true"
                                                                        aria-expanded="false">
                                                                    {{ __('Export') }}<span class="caret"></span>
                                                                </button>
                                                                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                                    <a class="dropdown-item" href="#" id="export_ward_filter_csv">CSV</a>
                                                                    <a class="dropdown-item" href="#" id="export_ward_filter_kml">KML</a>
                                                                    <a class="dropdown-item" href="#" id="export_ward_filter_shp">Shape File
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endcan

                                        @can('Export Data Map Tools')
                                            <!-- export data set -->
                                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Export Data Set within Custom Boundary') }}">
                                                <a href="#" id="export_control" class="btn btn-default map-control"><i class="fa-solid fa-file-export"></i>{{ __('Export Data Set') }}</a>
                                            </span>
                                        @endcan

                                        @can('Owner Information Map Tools')
                                            <!-- Building Owner Information -->
                                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Building Owner Information within Custom Boundary') }}">
                                                <a href="#" id="update_tax_zone" class="btn btn-default map-control"><i
                                                    class="fa fa-database"></i>{{ __('Building Owner Information') }}</a>
                                            </span>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcan

                        @can('Decision Map Tools')
                            <!-- Decision Tools -->
                            <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Decision Tools') }}">
                                    <a id="decisiontools_control" class="btn btn-default collapse-control" role="button"
                                    data-toggle="collapse" href="#collapse_decision_tools" aria-expanded="false"
                                    aria-controls="collapse_decision_tools"><i class="fa-solid fa-calendar-check"></i>{{ __('Decision Tools') }}</a>
                            </span>
                            <div class="collapse" id="collapse_decision_tools">
                                <div class="card">
                                    <div class="card-body">
                                            @can('Tax Due Map Tools')
                                                <!-- find tax due buildings(Commented - Might be needed in the future) -->
                                                <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Tax Due Buildings') }}">
                                                    <a id="duebuildings_control" class="btn btn-default collapse-control collapsed"
                                                    role="button" data-toggle="collapse" href="#collapse_find_tax_due_buildings"
                                                    aria-expanded="false" aria-controls="collapse_find_tax_due_buildings"><i class="fa-solid fa-building-circle-exclamation"></i>{{ __('Tax Due Buildings') }}</a>
                                                </span>
                                                <div class="collapse" id="collapse_find_tax_due_buildings">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <form role="form" name="tax_due_buildings_form" id="tax_due_buildings_form">
                                                                <div class="form-group">
                                                                    <label for="ward_tax_due">{{ __('Wards') }}</label>
                                                                        {!! Form::select('ward',$wards,null,['id' => 'ward_tax_due', 'multiple' => true, 'style' => 'width: 100%'])!!}
                                                                </div>
                                                                <button type="submit" class="btn btn-default">{{ __('Filter') }}</button>
                                                                <button type="button" class="btn btn-default" id="wardtaxzone_clear_button">{{ __('Close') }}
                                                                </button>
                                                            </form>
                                                            <table>
                                                                <tr>
                                                                    <td style="vertical-align: top;"><img
                                                                                src="{{ asset("/img/building-green.png") }}"></td>
                                                                    <td> - {{ __('Tax Due') }}</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endcan

                                            @can('Sewer Potential Map Tools')
                                                <!-- find sewer potential buildings -->
                                                <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Buildings Potential to Connect to Sewer') }}">
                                                    <a href="#" id="drainpotential_control" class="btn btn-default map-control"><i class="fa-solid fa-building"></i>{{ __('Sewers Potential Buildings') }}</a>
                                                </span>
                                            @endcan

                                            @can('Buildings to Sewer Map Tools')
                                                <!-- find building to sewer -->
                                                <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Buildings Connected to Sewer') }}">
                                                    <a href="#" id="drainbuildings_control" class="btn btn-default map-control"><i class="fa fa-building"></i>{{ __('Buildings to Sewer') }}</a>
                                                </span>
                                            @endcan

                                            @can('Buildings to Road Map Tools')
                                                <!-- find buildings connecet to road -->
                                                <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Buildings Connected to Road') }}">
                                                    <a href="#" id="roadbuildings_control" class="btn btn-default map-control"><i class="fa fa-building"></i>{{ __('Buildings to Road') }}</a>
                                                </span>
                                            @endcan

                                            @can('Hard to Reach Buildings Map Tools')
                                                <!-- find Hard to Reach Buildings -->
                                                <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Buildings that are Hard to Reach') }}">
                                                    <a href="#" id="road_inaccessible_control" class="btn btn-default map-control"><i class="fa-brands fa-buffer"  title="{{ __('Hard to Reach Building') }}"></i>{{ __('Hard to Reach Buildings') }}</a>
                                                </span>
                                            @endcan

                                            @can('Building Close to Water Bodies Map Tools')
                                                 <!-- find Building Close to Water Bodies -->
                                                <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Buildings that are close to Water Bodies') }}">
                                                    <a href="#" id="waterbody_inaccessible_control" class="btn btn-default map-control"><i class="fa-solid fa-water"></i>{{ __('Building Close to Water Bodies') }}</a>
                                                </span>
                                            @endcan

                                            @can('Community Toilets Map Tools')
                                                 <!-- find Buildings using Community Toilets -->
                                                <span data-toggle="tooltip" data-placement="bottom" title="{{ __('Find Buildings that use Community Toilet') }}">
                                                    <a href="#" id="ptct_network" class="btn btn-default map-control" ><i class="fa-solid fa-bezier-curve"></i>{{ __('Buildings using Community Toilets') }}</a>
                                                </span>
                                            @endcan

                                            @can('Area Population Map Tools')
                                                <!-- area population -->
                                                <span data-toggle="tooltip" data-placement="bottom"
                                                    title="{{ __('Estimate Population within Custom Boundary') }}">
                                                    <a href="#" id="areapopulation_control" class="btn btn-default map-control" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-bars" aria-hidden="true"></i>{{ __('Area Population') }}</a>
                                                </span>
                                            @endcan

                                            @can('Summary Information Buffer Map Tools')
                                                <!-- Summary Information -->
                                                <span data-toggle="tooltip" data-placement="bottom"
                                                title="{{ __('Generate Information of Buildings and Containment within Custom Boundary with Buffer') }}">
                                                    <a href="#" id="report_control_summary_buffer" class="btn btn-default map-control"><i
                                                    class="fa fa-list-alt"></i>{{ __('Summary Information Buffer Filter') }}</a>
                                                </span>
                                            @endcan

                                            @can('Summary Information Water Bodies Map Tools')
                                                <!-- water bodies buffer -->
                                                <span data-toggle="tooltip" data-placement="bottom"
                                                    title="{{ __('Generate Information of Buildings and Containment within Selected Water Body with Buffer') }}">
                                                    <a href="#" id="buildingswaterbodies_control" class="btn btn-default map-control"><i class="fa-solid fa-building"></i>{{ __('Water Bodies Buffer Summary Information') }}</a>
                                                </span>
                                            @endcan

                                            @can('Summary Information Wards Map Tools')
                                                <!-- by wards -->
                                                <span data-toggle="tooltip" data-placement="bottom"
                                                    title="{{ __('Generate Information of Buildings and Containment within Selected Ward') }}">
                                                    <a href="#" id="buildingswards_control" class="btn btn-default map-control"><i class="fa-solid fa-building"></i>{{ __('Wards Summary Information') }}</a>
                                                </span>
                                            @endcan

                                            @can('Summary Information Road Map Tools')
                                                <!-- road buffer -->
                                                <span data-toggle="tooltip" data-placement="bottom"
                                                    title="{{ __('Generate Information of Buildings and Containment within Selected Road with Buffer') }}">
                                                    <a href="#" id="buildingsroads_control" class="btn btn-default map-control"><i class="fa-solid fa-building"></i>{{ __('Road Buffer Summary Information') }}</a>
                                                </span>
                                            @endcan

                                            @can('Summary Information Point Map Tools')
                                                <!-- point buffer -->
                                                <span data-toggle="tooltip" data-placement="bottom"
                                                    title="{{ __('Generate Information of Buildings and Containment within Selected Point with Buffer') }}">
                                                    <a href="#" id="pointbuffer_control" class="btn btn-default map-control"><i
                                                    class="fa fa-building"></i>{{ __('Point Buffer Summary Information') }}</a>
                                                </span>
                                            @endcan
                                            @can('Containments Emptied Info Map Tools')
                                            <span data-toggle="tooltip" data-placement="bottom"
                                          title="{{ __('Containments emptied monthly') }}">
                            <a href="#" id="containments_emptied_monthly" class="btn btn-default map-control"><i
                                        class="fa fa-building"></i>{{ __('Containments Emptied Information') }}</a>
                             </span>
                             @endcan
                             @can('Toilet Isochrone Map Tools')
                            <!-- toilet isochrone map -->
                            <span data-toggle="tooltip" data-placement="bottom"
                                          title="{{ __('Generate isochrone information of CTPT by distance traversed (m)') }}">
                            <a href="#" id="toilet_isochrone_control" class="btn btn-default map-control"><i
                                        class="fa fa-building"></i>{{ __('Public Toilet Accessibility Tool') }}</a>
                             </span>
                             @endcan
                                    </div>
                                </div>
                            </div>
                        @endcan

                    </div>

                <div class="row main-row"></div>


            </div><!-- /.content-wrapper -->
            <div class="col-md-3 sidebar sidebar-top-left" style="display: none;">
                <div class="panel-group sidebar-body map__" id="accordion-left">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" href="#analysis">
                                    <i class="fa fa-list-alt"></i>
                                    {{ __('Spatial Analysis') }}
                                </a>
                                <span class="pull-right slide-submenu">
                    <i class="glyphicon glyphicon-chevron-left"></i>
                  </span>

                            </h4>
                        </div>
                        <div id="analysis" class="panel-collapse collapse in">
                            <div class="panel-body list-group">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 sidebar sidebar-top-right" style="display: none;">
                <div class="panel-group sidebar-body map__" id="accordion-left">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" href="#layers">
                                    <i class="fa fa-list-alt"></i>
                                    {{ __('Layers') }}
                                </a>
                                <span class="pull-right slide-submenu">
                  <i class="glyphicon glyphicon-chevron-right"></i>
                </span>

                            </h4>
                        </div>
                        <div id="layers" class="panel-collapse collapse in">
                            <div class="panel-body list-group" id="layerswitcher">

                            </div>
                        </div>

                    </div>


                </div>
            </div>
            <div class="col-md-6 sidebar-bottom-left">
                <div class="panel-group sidebar-body map__" style="display: none;">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" href="#featureinfo-collapse">
                                    <i class="fa fa-table"></i>
                                    {{ __('Info') }}
                                </a>
                                <span class="pull-right slide-submenu">
                    <i class="glyphicon glyphicon-chevron-left"></i>
                  </span>

                            </h4>
                        </div>
                        <div id="featureinfo-collapse" class="panel-collapse collapse in">
                            <div class="panel-body list-group">

                            </div>
                        </div>

                    </div>


                </div>
            </div>
            <div class="mini-submenu mini-submenu-top-left pull-left">
                <i class="glyphicon glyphicon-tasks"></i>
            </div>
            <div class="mini-submenu mini-submenu-top-right pull-right">
                <i class="glyphicon glyphicon-list-alt"></i>
            </div>
            <div class="mini-submenu mini-submenu-bottom-left pull-left" style="display: none;" hidden>
                <i class="fa fa-table"></i>
            </div>
        </div>
    </div>




    <footer class="main-footer" style="position:fixed;right:0;bottom:0;width: 100%;z-index:35;padding:7px;height:35px;">
        <!-- To the right -->
        <div class="float-right d-none d-sm-inline ">
        	<strong>Developed by:</strong> <a href="http://www.innovativesolution.com.np">Innovative Solution Pvt. Ltd.</a>
    	</div>
        <strong> Base IMIS <i class="fa-regular fa-copyright"> </i>  2022-{{ \Carbon\Carbon::now()->format('Y') }} by <a href="http://www.innovativesolution.com.np">
    Innovative Solution Pvt. Ltd.</a> & <a href="https://www.gwsc.ait.ac.th/">Global Water & Sanitation Center-Asian Institute of Technology (GWSC-AIT)</a> is licensed under <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/?ref=chooser-v1">CC BY-NC-SA 4.0 </a>
</strong>

        <!-- Default to the left -->
        <div id="footer-content">
            <div id="output"></div>
        </div>

    </footer>

    <div class="modal fade" id="print_modal" tabindex="-1" role="dialog" aria-labelledby="print_modal_label">
        <div class="modal-dialog modal-sm print_modal_dialog ui-draggable draggable" role="document">
            <div class="modal-content">
                <div class="modal-header">

                    <h4 class="modal-title" id="print_modal_label">{{ __('Print Map') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                </div>

                    <div class="modal-body">
                        <div class="form-group required">
                            <label class="control-label">{{ __('Title') }}</label>
                            <input type="text" class="form-control" id="print_map_title"/>
                        </div>
                        <div class="form-group">
                            <label class="control-label">{{ __('Description') }}</label>
                            <textarea class="form-control" rows="3" id="print_map_description"></textarea>
                        </div>
                        <div class="form-group">
                        <label class="control-label">{{ __('Scale') }}</label>
                        <select class="form-control" id="print_scale">
                            <option value="500" selected>1:500</option>
                            <option value="1000">1:1000</option>
                            <option value="2000">1:2000</option>
                            <option value="5000">1:5000</option>
                            <option value="10000">1:10000</option>
                            <option value="20000">1:20000</option>
                            <option value="25000">1:25,000</option>
                            <option value="50000">1:50,000</option>
                            <option value="75000">1:75,000</option>
                            <option value="100000">1:100,000</option>
                        </select>
                        </div>
                        <div class="form-group">
                        <label class="control-label">{{ __('Paper Size') }}</label>
                        <select class="form-control " id="print_paper_size">
                            <option value="A4" selected>A4</option>
                            <option value="A3">A3</option>
                        </select>
                        </div>
                        <div class="form-group">
                        <label class="control-label">{{ __('DPI') }}</label>
                        <select class="form-control " id="print_dpi">
                            <option value="75" >75</option>
                            <option value="150" selected>150</option>
                            <option value="200" >200</option>
                        </select>
                        </div>
                        <div class="form-group">
                        <label class="control-label">{{ __('Orientation') }}</label>
                        <select class="form-control " id="box_orientation">
                            <!--<option value="portrait" selected>Portrait</option>-->
                            <option value="landscape" >Landscape</option>
                        </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-info" id="print_map_fish">{{ __('Print') }}</button>
                    </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="coordinate_search_modal" tabindex="-1" role="dialog"
         aria-labelledby="print_modal_label">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content draggable">
                <div class="modal-header">

                    <h4 class="modal-title" id="print_modal_label">{{ __('Locate point by coordinate') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form name="latlong_form" id="latlong_form">
                    <div class="modal-body">

                        <div class="form-group">
                            <label>{{ __('Longitude') }}</label>
                            <input type="text" class="form-control" id="point_longitude" placeholder="85.372873" value="85.372873"/>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Latitude') }}</label>
                            <input type="text" class="form-control" id="point_latitude" placeholder="27.636295" value="27.636295"/>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-info">{{ __('Search') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="emptying_containments_modal" tabindex="-1" role="dialog"
         aria-labelledby="emptying_containments_modal_label">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content draggable">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="emptying_containments_modal_label">{{ __('Containments to be emptied') }}</h4>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="proposed_emptying_modal" tabindex="-1" role="dialog"
         aria-labelledby="proposed_emptying_modal_label">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content draggable">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="DEMModal" tabindex="-1" role="dialog" aria-labelledby="DEMModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content draggable">
                <div class="modal-header">
                    <h5 class="modal-title" id="DEMModalLabel">{{ __('Elevation') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <canvas id="DEMChart" width="100" height="100"></canvas>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div id="feedback-popup" class="ol-popup" style="display: none;">
        <div id="feedback-popup-content"></div>
        <button id="feedback-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer">{{ __('Close') }}</button>
    </div>

     <!-- Modal -->
     <div id="containment-report-popup" class="ol-popup" style="display: none;">
        <div id="containment-report-popup-content"></div>

                        <form method="get" style="margin-top: 12px;" action="{{ url("maps/export-containment-report") }}">
                            <input type="hidden" name="containment_report_polygon" value="" id="containment_report_polygon"/>
                            <input type="hidden" name="containment_report_year" value="" id="containment_report_year"/>
                             @can('Export Containment Report')
                           <button id="containment-report-popup-export" type="submit" class="btn btn-default">Export to Excel</button>
                           @endcan
                            <button id="containment-report-popup-closer" type="button" class="btn btn-default float-right xol-popup-closer">Close</button>
                        </form>
    </div>

    <!-- Modal -->
    <div id="popup-drain-potential" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">

            <!-- Modal content-->
            <div class="modal-content draggable">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" id="form-drain-potential">
                        <div class="form-group row">
                            <label class="col-form-label col-md-4">{{ __('Buffer Distance (m)') }}</label>
                            <input type="text" class="form-control col-md-4" id="buffer-distance" placeholder="" value="" oninput="this.value = this.value.replace(/[^0-9]/g, '')" >

                        <input type="hidden" id="drain-code" value=""/>
                        <input type="hidden" id="drain-long" value=""/>
                        <input type="hidden" id="drain-lat" value=""/>
                        <div class="col-md-4">
                        <button type="submit" class="btn btn-info">{{ __('Get Buildings') }}</button>
                        </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>

        </div>
    </div>
    <div id="popup-waterbodies-buildings" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">

            <!-- Modal content-->
            <div class="modal-content draggable">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" id="form-waterbodies-buildings">
                        <div class="form-group row">
                            <label class="col-form-label col-md-4">{{ __('Buffer Distance (m)') }}</label>
                           <input type="text" class="form-control col-md-4" id="buffer-distance-waterbodies" placeholder="" value="" oninput="this.value = this.value.replace(/[^0-9]/g, '')">

                            <div class="col-md-4">
                            <input type="hidden" id="water-body-code" value=""/>
                            <input type="hidden" id="water-body-long" value=""/>
                            <input type="hidden" id="water-body-lat" value=""/>
                            <button type="submit" class="btn btn-info">{{ __('Get Information') }}</button>
                            </div>
                        </div>
                    </form>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>

        </div>
    </div>
        </div>
    <div id="popup-polygon-buffer" class="modal fade" tabindex="-1" role="dialog"  aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">

            <!-- Modal content-->
            <div class="modal-content draggable">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" id="form-buffer-polygon">
                        <div class="form-group row">

                            <label class="col-form-label col-md-4">{{ __('Buffer Distance (m)') }}</label>

                                <input type="hidden" id="polygon-geom" value=""/>
                        <input type="hidden" id="polygon-coordinates" value=""/>
                                <input type="text" class="form-control  col-md-4" id="buffer-distance-polygon" placeholder=""
                                       value="" oninput="this.value = this.value.replace(/[^0-9]/g, '')">


                            <div class="col-md-4">
                                <button type="submit" class="btn btn-info">{{ __('Get Information') }}</button>
                            </div>
                        </div>


                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>

        </div>
    </div>

    <div id="popup-road-buildings" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">

            <!-- Modal content-->
            <div class="modal-content draggable">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" id="form-road-buildings">
                        <div class="form-group row">
                             <label class="col-form-label col-md-4">{{ __('Buffer Distance (m)') }}</label>

                            <input type="text" class="form-control col-md-4" id="buffer-distance-road" placeholder="" value="" oninput="this.value = this.value.replace(/[^0-9]/g, '')">

                        <input type="hidden" id="road-code" value=""/>
                        <input type="hidden" id="road-long" value=""/>
                        <input type="hidden" id="road-lat" value=""/>
                        <div class="col-md-4">
                        <button type="submit" class="btn btn-info">{{ __('Get Information') }}</button>
                        </div>
                         </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>

        </div>
    </div>
    <div id="popup-point-buffer" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">

            <!-- Modal content-->
            <div class="modal-content draggable">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" id="form-point-buffer-buildings">
                        <div class="form-group row">
                            <label class="col-form-label col-md-4">{{ __('Buffer Distance (m)') }}</label>
                             <input type="text" class="form-control col-md-4" id="buffer-distance-point" placeholder="" value="" oninput="this.value = this.value.replace(/[^0-9]/g, '')">


                        <div class="col-md-4">
                            <input type="hidden" id="point-buffer-long-pos" value=""/>
                        <input type="hidden" id="point-buffer-lat-pos" value=""/>
                        <input type="hidden" id="point-buffer-long" value=""/>
                        <input type="hidden" id="point-buffer-lat" value=""/>
                        <button type="submit" class="btn btn-info">{{ __('Get Information') }}</button>
                        </div>
                            </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>

        </div>
    </div>
    <div id="road-inaccessible-input-form" class="modal fade" tabindex="-1" role="dialog"  aria-hidden="true">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content draggable">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" id="form-road-inaccessible">
                        <div class="form-group row">
                            <label for="buffer-distance-polygon" class="control-label col-md-4">{{ __('Road Width(meters)') }}</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="road-width" placeholder=""
                                       value="">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="buffer-distance-polygon" class="control-label col-md-4">{{ __('Vacutug Pipe Range(feets)') }}</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="vacutug-range" placeholder=""
                                       value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="offset-md-4 col-md-4">
                                <button type="submit" class="btn btn-default">{{ __('Get Information') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>

        </div>
    </div>

    <div id="popup-toilet-isochrone" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">

            <!-- Modal content-->
            <div class="modal-content draggable">
                <div class="modal-header">
                    <span class="modal"> {{ __('Toilet Isochrone Map') }} </span>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" id="form-toilet-isochrone-map">
                        <div class="form-group row">
                            <label class="col-form-label col-md-4">{{ __('Estimated Travel Distance (m)') }}</label>
                            <input type="number" 
                                class="form-control col-md-4" 
                                id="toilet-isochrone-distance" 
                                placeholder="{{ __('Distance in meter') }}" 
                                min="1" 
                                step="1" />

                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <input type="hidden" id="isochrone-long-pos" value=""/>
                                <input type="hidden" id="isochrone-lat-pos" value=""/>
                                <input type="hidden" id="isochrone-long" value=""/>
                                <input type="hidden" id="isochrone-lat" value=""/>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-info">{{ __('Get Information') }}</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                        </div>
                    </form>
                </div>
              
            </div>

        </div>
    </div>

    <div class="modal fade" id="wmsModal" tabindex="-1" role="dialog" aria-labelledby="wmsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="wmsModalLabel">{{ __('Please Enter URL') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mt-3" id="wmsAddress">
                </input>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="wmsURL">{{ __('OK') }}</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="getLayerModal" tabindex="-1" role="dialog" aria-labelledby="getLayerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="getLayerModalLabel">{{ __('Select a layer to be displayed') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <select class="form-control mt-3" id="mapLayer">
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
    <script type="text/javascript">
        // Google Map
        var gmap;
        var markerColors = ['green', 'light-blue', 'orange', 'pink', 'purple', 'red', 'yellow', 'blue','gold'];

        function initMap() {
            gmap = new google.maps.Map(document.getElementById('gmap'), {
                disableDefaultUI: true,
                keyboardShortcuts: false,
                draggable: false,
                disableDoubleClickZoom: true,
                scrollwheel: false,
                streetViewControl: false,
                // zoom: 12
            });
        }
    </script>
@stop
@push('scripts')
<!--    <script src="{{ asset ('/old/js/html2canvas.min.js') }}"></script>
    <script src="{{ asset ('/old/js/html2canvas.js') }}"></script>-->


    <script type="text/javascript">
        $(document).ready(function () {
            //Information popup tab pane switching

            new autoComplete({
                cache: false,
                minChars: 1,
                selector: '#search_keyword_text',
                source: function(term, response){
                let layer = $('#search_layer_select').val();
                $.getJSON('{{ url("maps/search-auto-complete") }}/' + layer + '/' + term, function(data){ response(data); });
                }
            });
            new autoComplete({
                cache: false,
                minChars: 1,
                selector: '#building_value_text',
                source: function(term, response){
                let layer = $('#building_field_select').val();
                $.getJSON('{{ url("maps/search-auto-complete") }}/' + layer + '/' + term, function(data){ response(data); });
                }
            });

            $('#ward, #tax_zone, #watlog_overlay, #ward_tax_due, #tax_zone_tax_due').multipleSelect({
                placeholder: '{{__('Wards')}}',
                filter: true
            });

            function displayAjaxLoader() {
                if ($('.ajax-modal').length == 0) {
                    $('body').append('<div class="ajax-modal"><div class="ajax-modal-content"><div class="loader"></div></div></div>');
                }
            }

            function removeAjaxLoader() {
                $('.ajax-modal').remove();
            }

            function displayAjaxError() {
                displayAjaxErrorModal(@json(__('An error occurred')));
            }

            function displayAjaxErrorModal(message) {
                if ($('.ajax-modal').length > 0) {
                    var html = '<div class="ajax-modal-message">';
                    html += '<span>' + message + '</span>';
                    html += '<a href="#" class="ajax-modal-close-btn"><i class="fa fa-times"></i></a>';
                    html += '</div>';

                    $('.ajax-modal-content').html(html);
                }
            }

            $('body').on('click', 'a.ajax-modal-close-btn', function (e) {
                e.preventDefault();
                removeAjaxLoader();
            });

            $('#map-right-sidebar-toggle').click(function (e) {
                e.preventDefault();
                var sidebar = $('#map-right-sidebar');
                if (sidebar.css('right') == '0px') {
                    sidebar.animate({right: sidebar.outerWidth() * -1});
                } else {
                    sidebar.animate({right: 0});
                }
            });

            $(".sidebar-top-left .slide-submenu").on("click", function () {
                var i = $(this);
                i.closest(".sidebar-body").fadeOut("slide", function () {
                    $(".mini-submenu-top-left").fadeIn();
                    applyMargins();
                })
            });

            $(".mini-submenu-top-left").on("click", function () {
                var i = $(this);
                $(".sidebar-top-left .sidebar-body").fadeIn("slide");
                i.hide();
                applyMargins();
            });

            $(".sidebar-top-right .slide-submenu").on("click", function () {
                var i = $(this);
                i.closest(".sidebar-body").fadeOut("slide", function () {
                    $(".mini-submenu-top-right").fadeIn();
                    applyMargins();
                });
            });

            $(".mini-submenu-top-right").on("click", function () {
                var i = $(this);
                $(".sidebar-top-right .sidebar-body").fadeIn("slide");
                i.hide();
                applyMargins();
            });

            $(".sidebar-bottom-left .slide-submenu").on("click", function () {
                var i = $(this);
                i.closest(".sidebar-body").fadeOut("slide", function () {
                    $(".mini-submenu-bottom-left").fadeIn();
                    applyMargins();
                })
            });

            $(".mini-submenu-bottom-left").on("click", function () {
                var i = $(this);
                $(".sidebar-bottom-left .sidebar-body").fadeIn("slide");
                i.hide();
                applyMargins();
            });

            $(window).on("resize", applyMargins);

            $('#next_emptying_date').focus(function () {
                $(this).blur();
            });


            $('#application_date_field').focus(function () {
                $(this).blur();
            });

            $('#application_not_tp_date_field').focus(function () {
                $(this).blur();
            });

            $('#proposed_emptying_date').focus(function () {
                $(this).blur();
            });

            var layer = '{{ Input::get('layer') }}';
            var field = '{{ Input::get('field') }}';
            var val = '{{ Input::get('val') }}';
            var action = '{{ Input::get('action') }}';

            var currentControl = '';
            // Geoserver workspace name from constants
            var workspace = '<?php echo Config::get("constants.GEOSERVER_WORKSPACE"); ?>';
            // URL of GeoServer
            var gurl = "{{ url('/gis') }}/";
            // URL of WMS
            var gurl_wms = "{{ route('gis.wms') }}";
            // URL of WFS
            var gurl_wfs = "{{ route('gis.wfs') }}";
            // Authentication Keys
            var gurl_print = "{{ route('gis.print') }}";
            // BBOX Values
            var bboxstring = @json($bboxstring);
            // URL of GeoServer Legends
            var gurl_legend = gurl_wms + "?SERVICE=WMS&REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&BBOX="+@json($bboxstring)+"&LAYER=";

            // Get center from bbox string
            // Split the string into an array of numbers
            var bboxArray = bboxstring.split(',').map(Number);
            // Extract xmin, ymin, xmax, ymax
            var xmin = bboxArray[0];
            var ymin = bboxArray[1];
            var xmax = bboxArray[2];
            var ymax = bboxArray[3];
            // Step 2: Calculate the center coordinates
            var centerX = (xmin + xmax) / 2; //latitude
            var centerY = (ymin + ymax) / 2; //longitude

            // Coordinates of the city
            var coord = [centerX, centerY];  //[latitude,longitude]
            //Modifying these values will affect functions like zoomToCity(), setInitialZoom(), and tools like Hard to Reach Building,Buildings Close to Water Bodies.

            var dragAndDropInteraction = new ol.interaction.DragAndDrop({
                formatConstructors: [
                    ol.format.GPX,
                    ol.format.GeoJSON,
                    ol.format.IGC,
                    ol.format.KML,
                    ol.format.TopoJSON
                ]
            });
            // OpenLayers Map
            var map = new ol.Map({
                controls: ol.control.defaults().extend([new ol.control.ScaleLine()]),
                interactions: ol.interaction.defaults({
                    altShiftDragRotate: false,
                    dragPan: false,
                    rotate: false,
                    // mouseWheelZoom: false,
                    doubleClickZoom: false
                }).extend([new ol.interaction.DragPan({kinetic: null}), dragAndDropInteraction]),
                target: 'olmap',
                view: new ol.View({
                    minZoom: 13,
                    maxZoom: 21,
                    extent: ol.proj.transformExtent([xmin, ymin, xmax, ymax], 'EPSG:4326', 'EPSG:3857')
                })
            });

            //KML Drag and drop
            dragAndDropInteraction.on('addfeatures', function (event) {
                if (event.features.length > 1000) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ __("Too Many Features!")}}',
                        text: '{{ __("Upload a KML file with 1000 features or less")}}',
                        confirmButtonColor: '#d33'
                    });
                    return; // Stop the function execution here
                }
             displayAjaxLoader(); 
            var format = new ol.format.WKT();
            var geometries = []; // Store all geometries

            // Loop through all features and convert them to WKT
            for (var i = 0; i < event.features.length; i++) {
                var geom = format.writeGeometry(event.features[i].getGeometry().clone().transform('EPSG:3857', 'EPSG:4326'));
                geometries.push(geom);
            }

            // Send all geometries to the server for intersection check
            $.ajax({
                url: '{{ url("maps/check-geometry") }}',
                type: 'POST',
                data: {
                    geometries: geometries, // Send multiple geometries
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                 
                    // Check if at least one geometry does NOT intersect
                    var hasNonIntersection = data.details.some(item => item.intersects === false);

                    if (hasNonIntersection) {
                        Swal.fire({
                            icon: 'warning', // Change to 'error' if needed
                            title: '{{ __("Some Features do not Intersect")}}',
                            text: '{{ __("Some of the Features do not Intersect with the Municipality")}}',
                            confirmButtonColor: '#d33'
                        });
                        removeAjaxLoader();
                    }
                    else{
                        // Proceed with adding KML if none of the features intersect
                        if (eLayer.kml_features) {
                            eLayer.kml_features.layer.getSource().clear();
                        } else {
                            var layer = new ol.layer.Vector({
                                source: new ol.source.Vector()
                            });

                            addExtraLayer('kml_features', 'KML Features', layer);
                        }

                        for (var i = 0; i < event.features.length; i++) {
                        var feature = event.features[i];
                        var geometry = feature.getGeometry();

                        // Check if the geometry is a Point
                        if (geometry.getType() === 'Point') {

                            feature.setStyle(new ol.style.Style({
                                        image: new ol.style.Icon({
                                            anchor: [0.5, 1],
                                            src: '{{ url("/")}}/img/marker-green.png'
                                        })
                            }));
                        } else {
                            // Style for non-point geometries (e.g., polygons, lines)
                            feature.setStyle(new ol.style.Style({
                                stroke: new ol.style.Stroke({
                                    color: '#FF0000',
                                    width: 3
                                })
                            }));
                        }

                        // Add feature to the layer
                        eLayer.kml_features.layer.getSource().addFeature(feature);
                    }

                    map.getView().fit(
                        eLayer.kml_features.layer.getSource().getExtent(), /** @type {ol.Size} */ (map.getSize())
                    ); 
                    
                    var center = ol.extent.getCenter(eLayer.kml_features.layer.getSource().getExtent());

                    if (eLayer.kmlbuildings) {
                    eLayer.kmlbuildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('kmlbuildings', 'KML Buildings', layer);
                }
                        // Send the geometry to get KML summary information
                        var url1 = '{{ url("maps/get-kml-summary-info") }}';
                        displayAjaxLoader();
                       var polygonGeometries = geometries.filter(g => g.startsWith("POLYGON Z"));
                        $.ajax({
                            url: url1,
                            type: 'post',
                            data: {
                                geometries: geometries,
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (Response) {
                                // Check if response.success is false
                                if (Response.success === false) {
                                    displayAjaxErrorModal(Response.responseText);
                                } else {
                                    kmlDragDropPopupContent.innerHTML = Response.popContentsHtml;
                                    kmlDragDropPopupOverlay.setPosition(center);
                                    data1 = Response['buildings'];
                                     if (data1 && Array.isArray(data1)) {
                                        var format = new ol.format.WKT();

                                        for (var i = 0; i < data1.length; i++) {
                                            var feature = format.readFeature(data1[i].geom, {
                                                dataProjection: 'EPSG:4326',
                                                featureProjection: 'EPSG:3857'
                                            });

                                            feature.setStyle(styleFunction(data1[i].id));
                                            eLayer.kmlbuildings.layer.getSource().addFeature(feature);
                                        }
                                    }
                                 }
                                removeAjaxLoader();
                            },
                            error: function (error) {
                                // Show the error modal if AJAX fails
                                displayAjaxErrorModal(error.statusText);
                            }
                        });
               
                        $('#kml_dragdrop_geom').val(polygonGeometries.join(','));
                        document.getElementById('kml-export-form').addEventListener('submit', function (e) {
                            const sourceGeom = document.getElementById('kml_dragdrop_geom').value;
                            // Copy the geometry string to the actual form field
                            document.getElementById('kml_dragdrop_geom_post').value = sourceGeom;
                        });
                    }
                },

                error: function(xhr, status, error) {
                    console.error('Error checking geometry:', error);
                }
            });
        });
            // Base Layers Object
            var bLayer = {
                osm: {name: 'OpenStreetMap', type: 'osm'},
                bing_aerial: {name: 'Bing Aerial', type: 'bing', imagerySet: 'Aerial'},
                bing_aerial_labels: {name: 'Bing Aerial with Labels', type: 'bing', imagerySet: 'AerialWithLabels'},
                google_streets: {name: 'Google Streets', type: 'google', mapType: 'roadmap'},
                google_hybrid: {name: 'Google Hybrid', type: 'google', mapType: 'hybrid'},
                google_satellite: {name: 'Google Satellite', type: 'google', mapType: 'satellite'},
                google_terrain: {name: 'Google Terrain', type: 'google', mapType: 'terrain'}

            };


            // HTML for Base Layers select box
            var html = '';
            // Looping through Base Layers Object
            $.each(bLayer, function (key, value) {
                var layer;
                if (value.type == 'stamen') {
                    // Creating layer object
                    layer = new ol.layer.Tile({
                        visible: false,
                        source: new ol.source.Stamen({
                            layer: value.layer
                        })
                    });
                } else if (value.type == 'osm') {
                    // Creating layer object
                    layer = new ol.layer.Tile({
                        visible: false,
                        source: new ol.source.OSM()
                    });
                } else if (value.type == 'bing') {
                    // Creating layer object
                    layer = new ol.layer.Tile({
                        visible: false,
                        source: new ol.source.BingMaps({
                            key: '<?php echo Config::get("constants.API_KEY_BING"); ?>',
                            imagerySet: value.imagerySet
                        })
                    });
                }

                // if layer object has been created for the current Base Layer
                if (layer) {
                    // Adding layer to OpenLayers Map
                    map.addLayer(layer);
                    // Assigning layer to layer property of the current Base Layer
                    bLayer[key].layer = layer;

                }

                // select box option for the current Base Layer
                html += '<option value="' + key + '">' + value.name + '</option>'
            });


            // adding HTML to select box
            $('#base_layer_select').append(html);

            // Handler for Base Layer select box change
            $('#base_layer_select').change(function () {
                var selected = $(this).val();
                if (selected && bLayer[selected]) { // if selected option is not 'None' or selected option exists in Base Layers Object
                    if (bLayer[selected].type == 'google') { // if selected option is Google Map
                        // Hide all Base Layers
                        $.each(bLayer, function (key, value) {
                            if (bLayer[key].layer) {
                                bLayer[key].layer.setVisible(false);
                            }
                        });
                        // Set Google Map Type
                        gmap.setMapTypeId(bLayer[selected].mapType);
                        // Set centre and zoom of Google Map to those of OpenLayers Map
                        onCenterChanged();
                        onResolutionChanged();
                        // Add handler to OpenLayers view centre change and zoom change events
                        map.getView().on('change:center', onCenterChanged);
                        map.getView().on('change:resolution', onResolutionChanged);
                        // Make Google Map visible
                        $('#gmap').css('visibility', 'visible');
                        // Add handler to window resize
                        $(window).on('resize', onWindowResize);
                    } else { // if seleceted option is OpenStreetMap or Bing Map
                        // Make Google Map invisible
                        $('#gmap').css('visibility', 'hidden');
                        // Show selected Base Layer and hide other Base Layers
                        $.each(bLayer, function (key, value) {
                            if (bLayer[key].layer) {
                                bLayer[key].layer.setVisible(key == selected);
                            }
                        });
                        // Remove handler from OpenLayers view centre change and zoom change events
                        map.getView().un('change:center', onCenterChanged);
                        map.getView().un('change:resolution', onResolutionChanged);
                        // Remove handler to window resize
                        $(window).off('resize', onWindowResize);
                    }
                } else { // if selected option is 'None' or selected options does not exists in Base Layers Object
                    // Make Google Map invisible
                    $('#gmap').css('visibility', 'hidden');
                    // Hide all Base Layers
                    $.each(bLayer, function (key, value) {
                        if (bLayer[key].layer) {
                            bLayer[key].layer.setVisible(false);
                        }
                    });
                    // Remove handler from OpenLayers view centre change and zoom change events
                    map.getView().un('change:center', onCenterChanged);
                    map.getView().un('change:resolution', onResolutionChanged);
                    // Remove handler to window resize
                    $(window).off('resize', onWindowResize);
                }
            });

            // Handler for OpenLayers view centre change event
            function onCenterChanged() {
                // Get centre of OpenLayers Map
                var center = ol.proj.transform(map.getView().getCenter(), 'EPSG:3857', 'EPSG:4326');
                // Set centre of Google Map to that of OpenLayers Map
                gmap.setCenter(new google.maps.LatLng(center[1], center[0]));
            }

            // Handler for OpenLayers view zoom change event
            function onResolutionChanged() {
                // Set zoom of Google Map to that of OpenLayers map
                gmap.setZoom(map.getView().getZoom());
            }

            // Handler for window resize
            function onWindowResize() {
                google.maps.event.trigger(gmap, 'resize');
                // Set centre and zoom of Google Map to those of OpenLayers Map
                onCenterChanged();
                onResolutionChanged();
            }

            // Set Stamen Toner to Base Layer select box
            $('#base_layer_select').val('')

            // Trigger Base Layer select box change
            $('#base_layer_select').trigger('change');

            // Filters Object
            var mFilter = {
                ward: '',
                // toilet: '',
                buildings_layer_structure_type: '',
                buildings_tax_status_layer: '',
                buildings_water_payment_status_layer: '',
                buildings_swm_payment_status_layer: '',

            };

            // Overlays Object
            var mLayer = {
                @can('Municipality Map Layer')
                citypolys_layer: {
                    name: '{{ __("Municipality") }}',
                    styles: {},
                    clipLegend: false,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Ward Boundary Map Layer')
                wardboundary_layer: {
                    name: '{{ __("Ward Boundary") }}',
                    styles: {},
                    clipLegend: false,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Roads Map Layer')
                roadlines_layer: {
                    name: '{{ __("Road Network") }}',
                    styles: {
                        roadlines_layer_none: {
                            name: '{{ __("None") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        roadlines_layer_hierarchy: {
                            name: '{{ __("Hierarchy") }}',
                            clipLegend: false,
                            showCount: false
                        },
                        roadlines_layer_width: {
                            name: '{{ __("Carrying Width (m)") }}',
                            clipLegend: false,
                            showCount: false
                        },
                        roadlines_layer_surface_type: {
                            name: '{{ __("Surface Type") }}',
                            clipLegend: false,
                            showCount: false,
                        },

                    },
                    clipLegend: false,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Sewers Line Map Layer')
                sewerlines_layer: {
                    name: '{{ __("Sewer Network") }}',
                    styles: {
                        sewer_none: {
                            name: '{{ __("None") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        sewerlines_layer_size: {
                            name: '{{ __("Diameter (mm)") }}',
                            clipLegend: false,
                            showCount: false
                        },
                        sewerlines_layer_length: {
                            name: '{{ __("Length (m)") }}',
                            clipLegend: false,
                            showCount: false
                        },
                    },
                    clipLegend: false,
                    showCount: false,
                    filters: [],
                },
                @endcan

                @can('Drains Map Layer')
                    drains_layer: {
                    name: '{{ __("Drain Network") }}',
                    styles: {
                        drains_none: {
                            name: '{{ __("None") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        drain_type: {
                            name: '{{ __("Surface Type") }}',
                            clipLegend: false,
                            showCount: false
                        },
                        drain_covertype : {
                            name: '{{ __("Cover Type") }}',
                            clipLegend: false,
                            showCount: false
                        },
                    },
                    clipLegend: false,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('WaterSupply Network Map Layer')
                watersupply_network_layer: {
                    name: '{{ __("Water Supply Network") }}',
                    styles: {
                        watersupply_none: {
                            name: '{{ __("None") }}',
                            clipLegend: false,
                            showCount: false
                        },
                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Places Map Layer')
                places_layer: {
                    name: '{{ __("Places") }}',
                    styles: {},
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Buildings Map Layer')
                buildings_layer: {
                    name: '{{ __("Building") }}',
                    styles: {
                        buildings_layer_none: {
                            name: '{{ __("None") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        buildings_layer_structure_type: {
                            name: '{{ __("Structure Type") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        buildings_layer_flrcount: {
                            name: '{{ __("Number of Floors") }}',
                            clipLegend: false,
                            showCount: false
                        },
                        buildings_layer_functional_use: {
                            name: '{{ __("Functional Use of Building") }}',
                            clipLegend: true,
                            showCount: false
                        },

                        buildings_layer_building_associated_to: {
                            name: '{{ __("Associated Building") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        buildings_layer_toilet: {
                            name: '{{ __("Presence of Toilet") }}',
                            clipLegend: false,
                            showCount: false
                        },

                        buildings_layer_toilet_connection: {
                            name: '{{ __("Toilet Connection") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        buildings_layer_water_source: {
                            name: '{{ __("Main Drinking Water Source") }}',
                            clipLegend: false,
                            showCount: false
                        },
                        buildings_layer_well_presence: {
                            name: '{{ __("Well in Premises") }}',
                            clipLegend: false,
                            showCount: false
                        },

                        low_income_houses: {
                            name: '{{ __("Is Low Income House") }}',
                            clipLegend: false,
                            showCount: false
                        },
                        building_construction_year: {
                            name: '{{ __("Construction Date") }}',
                            clipLegend: false,
                            showCount: false
                        },
                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Containments Map Layer')
                containments_layer: {
                    name: '{{ __("Containments") }}',
                    styles: {
                        containments_layer_none: {
                            name: '{{ __("None") }}',
                            clipLegend: true,
                            showCount: false
                        },

                        containments_layer_type: {
                            name: '{{ __("Type") }}',
                            clipLegend: true,
                            showCount: false,
                        },

                        containments_outlet_connection: {
                            name: '{{ __("Outlet Connection") }}',
                            clipLegend: true,
                            showCount: false,
                        },

                        containments_layer_emptied_status: {
                            name: '{{ __("Emptied Status") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        containments_layer_no_of_times_emptied: {
                            name: '{{ __("Times Emptied") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        containments_period_from_construction: {
                            name: '{{ __("Year of Construction") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        /**
                         *containments_last_emptied_year uses a SQL View made in the GeoServer
                         * */
                        containments_last_emptied_year: {
                            name: '{{ __("Last Emptied Year") }}',
                            clipLegend: true,
                            showCount: false
                        },

                        containments_layer_location: {
                            name: '{{ __("Location") }}',
                            clipLegend: false,
                            showCount: false
                        }

                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan

                @can('Treatment Plants Map Layer')
                treatmentplants_layer: {
                    name: '{{ __("Treatment Plants") }}',
                    styles: {
                        treatmentplants_layer_none: {
                        name: '{{ __("None") }}',
                        clipLegend: true,
                        showCount: false
                    },
                    treatmentplants_layer_status: {
                        name: '{{ __("Status") }}',
                        clipLegend: true,
                        showCount: false
                    },
                    treatmentplants_layer_type: {
                        name: '{{ __("Type") }}',
                        clipLegend: true,
                        showCount: false
                    },
                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan


                @can('Sanitation System Map Layer')
                sanitation_system_layer: {
                    name: '{{ __("Sanitation System") }}',
                    styles: {},
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan


                @can('PT/CT Toilets Map Layer')
                toilets_layer: {
                    name: '{{ __("Toilets PT/CT") }}',
                    styles: {
                        toilets_layer_none: {
                        name: '{{ __("None") }}',
                        clipLegend: false,
                        showCount: false
                    },
                    toilets_layer_type: {
                        name: '{{ __("Toilet Type") }}',
                        clipLegend: false,
                        showCount: false
                    },
                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan

                @can('Public Health Map Layer')
                   waterborne_hotspots_layer:{
                        name: '{{ __("Waterborne Hotspots") }}',
                    styles:{
                        waterborne_hotspots_layer_none: {
                            name: '{{ __("None") }}',
                            clipLegend: false,
                            showCount: false
                        },
                        infected_disease: {
                            name: '{{ __("Infected Disease") }}',
                            clipLegend: false,
                            showCount: false
                        },


                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan

                @can('Water Samples Map Layer')
                water_samples_layer: {
                    name: '{{ __("Water Samples") }}',
                    styles: {},
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan

                @if(auth()->user()->can('Tax Payment Status Buildings Map Layer') || auth()->user()->can('Water Payment Status Map Layer'))
                @can('Tax Payment Status Buildings Map Layer')
                 buildings_tax_status_layer:{
                    name: '{{ __("Tax Payment Status") }}',
                    styles:{
                        buildings_tax_status_layer: {
                        name: '{{ __("Payment Status") }}',
                        clipLegend: true,
                        showCount: false
                    }
                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Water Payment Status Map Layer')
                buildings_water_payment_status_layer:{
                        name: '{{ __("Water Payment Status") }}',
                    styles:{
                        buildings_water_payment_status_layer: {
                            name: '{{ __("Payment Status") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        buildings_water_payment_service_layer: {
                            name: '{{ __("Service Status") }}',
                            clipLegend: false,
                            showCount: false
                        },


                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @endif
                @can('Solid Waste Status Map Layer')
                buildings_swm_payment_status_layer:{
                        name: '{{ __("Solid Waste Service Status") }}',
                    styles:{
                        buildings_swm_payment_status_layer: {
                            name: '{{ __("Payment Status") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        buildings_swm_payment_service_layer: {
                            name: '{{ __("Service Status") }}',
                            clipLegend: false,
                            showCount: false
                        },

                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Low Income Community Map Layer')
                low_income_communities_layer: {
                    name: '{{ __("Low Income Community") }}',
                    styles: {},
                    clipLegend: false,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Wards Map Layer')
                wards_layer: {
                    name: '{{ __("Ward Wise Info") }}',
                    styles: {
                        wards_layer_none: {
                            name: '{{ __("None") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_build: {
                            name: '{{ __("No. of Buildings") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_rcc_framed: {
                            name: '{{ __("No. of RCC Framed") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_wooden_mud: {
                            name: '{{ __("No. of Wooden/Mud") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_load_bearing: {
                            name: '{{ __("No. of Load Bearing") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_cgi_sheet: {
                            name: '{{ __("No. of CGI Sheet") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_build_directly_to_sewerage_network: {
                            name: '{{ __("No. of Building Connected to Sewerage Network") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_contain: {
                            name: '{{ __("No. of Containments") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_pit_holding_tank: {
                            name: '{{ __("No.of Pit/Holding Tank") }}',
                            clipLegend: true,
                            showCount: false
                        },

                wards_layer_no_septic_tank: {
                            name: '{{ __("No.of Septic Tank") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_total_rdlen: {
                            name: '{{ __("Total Length of Roads (km)") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_bldgtaxpdprprtn: {
                            name: '{{ __("Tax Paid %") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_wtrpmntprprtn: {
                            name: '{{ __("Watersupply Payment Paid %") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_swmsrvpmntprprtn: {
                            name: '{{ __("Solid Waste Payment Paid %") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_population_served: {
                            name: '{{ __("Population of Building") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_household_served: {
                            name: '{{ __("Number of Households") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_emptying: {
                            name: '{{ __("No. of Containments Emptied") }}',
                            clipLegend: true,
                            showCount: false
                        },
                        wards_layer_no_emptying: {
                            name: '{{ __("No. of Emptying Requests") }}',
                            clipLegend: true,
                            showCount: false
                        },

                    },
                    clipLegend: false,
                    showCount: false,
                    filters: [],
                },
                @endcan
                @can('Summarized Grids Map Layer')
                grids_layer: {
                    name: '{{ __("Summarized Grids (0.5 km)") }}',
                    styles: {
                        grids_layer_no_build: {
                    name: '{{ __("No. of Buildings") }}',
                    clipLegend: true,
                    showCount: false
                },

                grids_layer_no_contain: {
                    name: '{{ __("No. of Containments") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_no_rcc_framed: {
                    name: '{{ __("No. of RCC Framed") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_no_wooden_mud: {
                    name: '{{ __("No. of Wooden/Mud") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_no_load_bearing: {
                    name: '{{ __("No. of Load Bearing") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_no_cgi_sheet: {
                    name: '{{ __("No. of CGI Sheet") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_no_build_directly_to_sewerage_network: {
                    name: '{{ __("No. of Building Connected to Sewerage Network") }}',
                    clipLegend: true,
                    showCount: false
                },

                grids_layer_no_pit_holding_tank: {
                            name: '{{ __("No.of Pit/Holding Tank") }}',
                            clipLegend: true,
                            showCount: false
                        },
                grids_layer_no_septic_tank: {
                            name: '{{ __("No.of Septic Tank") }}',
                            clipLegend: true,
                            showCount: false
                        },

                grids_layer_total_rdlen: {
                    name: '{{ __("Total Length of Roads (km)") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_bldgtaxpdprprtn: {
                    name: '{{ __("Tax Paid %") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_wtrpmntprprtn: {
                    name: '{{ __("Watersupply Payment Paid %") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_swmsrvpmntprprtn : {
                    name: '{{ __("Solid Waste Payment Paid %") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_population_served: {
                    name: '{{ __("Population Served") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_household_served: {
                    name: '{{ __("Household Served") }}',
                    clipLegend: true,
                    showCount: false
                },
                grids_layer_no_emptying: {
                    name: '{{ __("No. of Emptying Requests") }}',
                    clipLegend: true,
                    showCount: false
                },
                    },
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan

        @can('Water Body Map Layer')
        waterbodys_layer: {
            name: '{{ __("Water Bodies") }}',
            styles: {},
            clipLegend: true,
            showCount: false,
            filters: [],
        },
        @endcan


                @can('Land Use Map Layer')
                landuses_layer: {
                    name: '{{ __("Land Use") }}',
                    styles: {},
                    clipLegend: true,
                    showCount: false,
                    filters: [],
                },
                @endcan

            };

            // Array of layers that should be visable only when zoom is greater than 14
            var conditionalLayers = ['buildings_layer', 'containments_layer', 'places_layer', 'taxzone', 'drains', 'contour'];

            // Add filter to layer
            function addFilterToLayer(layer, filter) {
                if (mLayer.hasOwnProperty(layer) && mFilter.hasOwnProperty(filter)) {
                    var index = mLayer[layer].filters.indexOf(filter);
                    if (index == -1) {
                        mLayer[layer].filters.push(filter);
                    }
                }
            }

            // Remove filter from layer
            function removeFilterFromLayer(layer, filter) {
                if (mLayer.hasOwnProperty(layer) && mFilter.hasOwnProperty(filter)) {
                    var index = mLayer[layer].filters.indexOf(filter);
                    if (index != -1) {
                        mLayer[layer].filters.splice(index, 1);
                    }
                }
            }

            addFilterToLayer('buildings_layer', 'buildings_layer_structure_type');
            addFilterToLayer('buildings_tax_status_layer', 'buildings_tax_status_layer');
            addFilterToLayer('buildings_water_payment_status_layer', 'buildings_water_payment_status_layer');
            addFilterToLayer('buildings_swm_payment_status_layer', 'buildings_swm_payment_status_layer');

            // HTML for Overlays checkboxes
            var html = '';
            // HTML for Export Overlays checkboxes
            var exportOverlayCheckboxesHTML = '';

            // HTML for Feature Info Overlay select options
            var featureInfoOverlayOptionsHTML = '';
            // HTML for Ward Filter Overlays select options
            var wardOverlayOptionsHTML = '';
            // HTML for Tax Zone Filter Overlays select options
            var taxZoneOverlayOptionsHTML = '';
            // HTML for Water Logging Area Filter Overlays select options
            var watlogOverlayOptionsHTML = '';
            // HTML for Export Overlays select options
            var exportOverlayOptionsHTML = '';

            // Looping through Overlays Object and creating layer object
            $.each(mLayer, function (key, value) {
                // creating layer object
                var layer = new ol.layer.Image({
                    visible: false,
                    source: new ol.source.ImageWMS({
                        url: gurl_wms,
                        params: {
                            'LAYERS': workspace + ':' + key,
                            'TILED': true,
                            'CQL_FILTER': 'deleted_at is NULL',
                        },
                        serverType: 'geoserver',
                        crossOrigin: 'anonymous'
                    })
                });

                // Setting name to layer
                layer.set('name', key);

                // Assigning layer to layer property of the current Overlay
                mLayer[key].layer = layer;

                // Adding layer to OpenLayers Map
                map.addLayer(layer);

                // Adding HTML for the current Overlay checkbox
                var checkboxHTML = '<div class="checkbox">';
                checkboxHTML += '<label>';
                checkboxHTML += '<input type="checkbox" name="' + key + '" value="' + key + '" />';
                checkboxHTML += value.name;
                checkboxHTML += '</label>';
                checkboxHTML += '</div>';

                // Adding checkbox HTML to Overlay checkboxes
                html += checkboxHTML;

                // Adding checkbox HTML to Export Overlay checkboxes
                if (['grids_layer', 'citypolys_layer', 'wards_layer'].indexOf(key) == -1) {
                    exportOverlayCheckboxesHTML += checkboxHTML;
                }

                // Adding HTML for the current Overlay style select container
                html += '<div id="' + key + '_overlay_style_select_container" style="display:none;padding-left:15px;">';
                var keys = Object.keys(value.styles);
                if (keys.length > 0) { // if the current Overlay has styles
                    html += '<select name="' + key + '">';
                    for (var i = 0; i < keys.length; i++) {
                        html += '<option value="' + keys[i] + '">' + value.styles[keys[i]].name + '</option>';
                    }
                    html += '</select>';
                }
                html += '</div>';

                // Adding HTML for the current Overlay legend container
                html += '<div id="' + key + '_overlay_legend_container" style="padding-left:15px;">';
                html += '</div>';

                // Adding HTML for the current Overlay option in Feature Info Overlay Select
                featureInfoOverlayOptionsHTML += '<option value="' + key + '">' + value.name + '</option>';

                // Adding HTML for the current Overlay option in Ward Filter Overlay Select
                if (['citypolys_layer', 'wards_layer', 'contour'].indexOf(key) == -1) {
                    if(key == 'roads_width_zoom_layer'){
                        key_new = 'roadlines_layer';
                    } else {
                        key_new = key;
                    }
                    wardOverlayOptionsHTML += '<option value="' + key_new + '">' + value.name + '</option>';
                }
                // Adding HTML for the current Overlay option in Tax Zone Filter Overlay Select
                if (['grids_layer', 'citypolys_layer', 'taxzone', 'contour', 'treatmentplants_layer', 'wards_layer'].indexOf(key) == -1) {
                    taxZoneOverlayOptionsHTML += '<option value="' + key + '">' + value.name + '</option>';
                }
                // Adding HTML for the current Overlay option in Water Logging Area Filter Overlay Select
                if (['citypolys_layer', 'waterbodys_layer', 'contour'].indexOf(key) == -1) {
                    watlogOverlayOptionsHTML += '<option value="' + key + '">' + value.name + '</option>';
                }

                // Adding HTML for the current Overlay option in Export Overlay Select
                if (['grids_layer', 'citypolys_layer', 'wards_layer'].indexOf(key) == -1) {
                    if(key == 'roads_width_zoom_layer'){
                        key_new = 'roadlines_layer';
                    } else {
                        key_new = key;
                    }
                    exportOverlayOptionsHTML += '<option value="' + key_new + '">' + value.name + '</option>';
                }
            });

            // Set Overlays checkboxes HTML to container
            $('#overlay_checkbox_container').html(html);

            // Set Export Overlays checkboxes HTML to container
            $('#export_overlay_checkbox_container').html(exportOverlayCheckboxesHTML);

            // Set Overlays options HTML to select boxes
            $('#feature_info_overlay').append(featureInfoOverlayOptionsHTML);
            $('#ward_overlay').append(wardOverlayOptionsHTML);
            $('#tax_zone_overlay').append(taxZoneOverlayOptionsHTML);
            $('#watlog_overlay').html(watlogOverlayOptionsHTML);
            $('#export_overlay').append(exportOverlayOptionsHTML);

            // Reload multiple select
            $('#watlog_overlay').multipleSelect('refresh');

            // Handler for Overlays checkbox change
            $('#overlay_checkbox_container').on('change', 'input[type=checkbox]', function () {
                // Get name attribute of the changed Overlay checkbox
                var key = $(this).attr('name');

                if ($(this).is(':checked')) { // if the Overlay checkbox is checked
                    // Make the Overlay layer visible in map
                    if (conditionalLayers.indexOf(key) == -1) {
                        mLayer[key].layer.setVisible(true);
                    } else {
                        mLayer[key].layer.setVisible(map.getView().getZoom() > 14);
                    }
                    // Show Ovelay style select container
                    $('#' + key + '_overlay_style_select_container').show();

                    if (Object.keys(mLayer[key].styles).length > 0) { // if the current Overlay has styles
                        // Trigger Overlay style select change
                        $('#overlay_checkbox_container select[name=' + key + ']').change();
                    } else { // if the current Overlay does not have styles
                        // Set legend image HTML for the Overlay
                        var html = '<img class="'+(mLayer[key].clipLegend ? 'clip-legend' : '')+'" src="' + gurl_legend + workspace + ':' + key + (mLayer[key].showCount ? '&LEGEND_OPTIONS=countMatched:TRUE;fontName:Lucida Bright;fontAntiAliasing:true;forceTitles:off;' : '') + '&STYLE=' + workspace + ':' + key + '" />';
                        // Set HTML to the Overlay container
                        $('#' + key + '_overlay_legend_container').html(html);}
                } else { // if the Overlay checkbox is unchecked
                    // Make the Overlay layer invisible in map
                    mLayer[key].layer.setVisible(false);
                    // Hide Ovelay style select container
                    $('#' + key + '_overlay_style_select_container').hide();
                    // Set empty HTML to the Overlay container
                    $('#' + key + '_overlay_legend_container').html('');
                }

                //Update Get information layer select dropdown
                var html = '<option value="">{{ __('Select a layer') }}</option>';

                $.each(mLayer, function (key, value) {
                    if (value.layer.getVisible()) {
                        html += '<option value="' + key + '">' + value.name + '</option>';
                    }
                });

                $('#feature_info_overlay').html(html);
                $('#feature_info_overlay').val('');
            });

            // Handler for Overlays style select change
            $('#overlay_checkbox_container').on('change', 'select', function () {

                // Get name attribute of the changed Overlay style select
                if(($(this).attr('name') == 'buildings_layer') && ($(this).val() == 'buildings_layer_water_payment_status'))
                {
                    var key = 'buildings_water_supply_payment_status';
                    var style = 'buildings_water_supply_payment_status';
                }

                // Get name attribute of the changed Overlay style select
                if(($(this).attr('name') == 'buildings_tax_status_layer'))
                {
                    var key = 'buildings_tax_status_layer';
                    var style = 'buildings_tax_status_layer';
                }

                // Get name attribute of the changed Overlay style select
                if(($(this).attr('name') == 'buildings_water_payment_status_layer'))
                {
                    var key = 'buildings_water_payment_status_layer';
                    var style = 'buildings_water_payment_status_layer';
                }

                // Get name attribute of the changed Overlay style select
                if(($(this).attr('name') == 'buildings_swm_payment_status_layer'))
                {
                    var key = 'buildings_swm_payment_status_layer';
                    var style = 'buildings_swm_payment_status_layer';
                }

                var key = $(this).attr('name');

                // Get selected style
                var style = $(this).val();
                // Set selected style to parameters
                mLayer[key].layer.get('source').updateParams({STYLES: workspace + ':' + style});
                // Set legend image HTML for the Overlay with selected style
                    var html = '<img class="' +
                        (mLayer[key].styles[style].clipLegend ? 'clip-legend' : '')
                        + '" src="' + gurl_legend + workspace + ':' + key +
                        (mLayer[key].styles[style].showCount ?
                            '&LEGEND_OPTIONS=countMatched:TRUE;fontName:Lucida Bright;fontAntiAliasing:true;forceTitles:off;' +((style==='roadlines_layer_surface_type' )?'hideEmptyRules:on;':'')
                            : '')
                        + '&STYLE=' + workspace + ':' + style + '" />';

                $('#' + key + '_overlay_legend_container').html(html);
            });

            // Handler for
            $('#overlay_checkbox_container').on('change', 'select[name=buildings]', function () {

                // Get name attribute of the changed Overlay style select
                if ($(this).val() == 'buildings_layer_tax_status') {

                    mFilter.buildings_layer_tax_status = 'building_associated_to IS NULL';
                    updateAllCQLFiltersParams();
                    showLayer('buildings_layer');


                } else {
                    mFilter.buildings_layer_tax_status = '';
                    updateAllCQLFiltersParams();
                    showLayer('buildings_layer');
                }
                if ($(this).val() == 'buildings_layer_water_payment_status') {

                    mFilter.buildings_layer_water_payment_status = 'building_associated_to IS NULL';
                    updateAllCQLFiltersParams();
                    showLayer('buildings_layer');


                } else {
                    mFilter.buildings_layer_water_payment_status = '';
                    updateAllCQLFiltersParams();
                    showLayer('buildings_layer');
                }

            });
            // Check citypl, building and contain Overlay checkbox
            showLayer('buildings_layer');
            showLayer('wardboundary_layer');
            showLayer('citypolys_layer');
            showLayer('roads_width_zoom_layer');


            // Check Overlay checkbox
            function showLayer(layer) {
                var elem = $('#overlay_checkbox_container input[type=checkbox][name=' + layer + ']');
                if (!elem.is(':checked')) { // if the checkbox is not checked
                    // Trigger checkbox click event
                    elem.click();
                }
            }

            // Uncheck Overlay checkbox
            function hideLayer(layer) {
                var elem = $('#overlay_checkbox_container input[type=checkbox][name=' + layer + ']');
                if (elem.is(':checked')) { // if the checkbox is checked
                    // Trigger checkbox click event
                    elem.click();
                }
            }


            // Change visible of conditional layers based on zoom level
            map.getView().on('change:resolution', function () {
                $.each(conditionalLayers, function (key, value) {
                    var elem = $('#overlay_checkbox_container input[type=checkbox][name=' + value + ']');
                    if (elem.is(':checked')) { // if the checkbox is checked
                        mLayer[value].layer.setVisible(map.getView().getZoom() > 14);
                    }
                });
            });

            // Extra Overlays Object
            var eLayer = {};

            // Add extra overlay to Extra Overlays Object
            function addExtraLayer(key, name, layer) {
                // adding as property of Extra Overlays Object
                eLayer[key] = {name: name, layer: layer};

                // Adding layer to OpenLayers Map
                map.addLayer(layer);


            }
            function validateForm() {
                var ward = document.getElementById('ward');
                var ward_overlay = document.getElementById('ward_overlay');

                if (ward.value == '' || ward_overlay.value == '') {
                    alert('Please select a ward and an overlay.');
                    return false;
                }

                return true;
            }

            // Disable all controls
            function disableAllControls() {
                map.removeInteraction(draw);
                map.removeInteraction(drag);
                map.un('pointermove', pointerMoveHandler);
                map.un('pointermove', hoverOnLayerHandler);
                map.un('pointermove', hoverOnDrainHandler);
                map.un('pointermove', hoverOnWaterBodiesHandler);
                map.un('pointermove', hoverOnWardsBuildingHandler);
                map.un('pointermove', hoverOnRoadsHandler);
                map.un('pointermove', hoverOnContainmentHandler);
                map.un('pointermove', hoverOnBuildingContainmentHandler);
                map.un('pointermove', hoverOnRoadBuildingHandler);
                map.getViewport().removeEventListener('mouseout', mouseOutHandler);
                createMeasureTooltip();
                createHelpTooltip();
                map.un('singleclick', displayFeatureInformation);
                map.un('singleclick', findNearestRoad);
                map.un('singleclick', displayCoordinateInformation);
                map.un('singleclick', displayDrainBuildings);
                map.un('singleclick', displayDrainPotentialBuildings);
                map.un('singleclick', displayWaterBodiesBuildings);
                map.un('singleclick', displayContainmentToBuildings);
                map.un('singleclick', displayBuildingToContainment);
                map.un('singleclick', displayAssociatedToMainBuilding);
                map.un('singleclick', displayRoadBuildings);
                map.un('singleclick', displayRoadsBuildings);
                map.un('singleclick', displayPopupPointBuffer);
                map.un('singleclick', displayWardsBuildings);
                map.un('singleclick', displayBuildingsToPTCT);
                $('#add-road-inaccessible-box').hide();
                $('#add-waterbody-inaccessible-box').hide();
                $('#add-tool-box').hide();
                // map.removeInteraction(removeDrawnFeatures);
              resetAddTool();

                if (eLayer.measure) {
                    eLayer.measure.layer.getSource().clear();
                }
                if (eLayer.report_polygon) {
                    eLayer.report_polygon.layer.getSource().clear();
                }
                if (eLayer.export_polygon) {
                    eLayer.export_polygon.layer.getSource().clear();
                }
                if (eLayer.export_tax_polygon) {
                    eLayer.export_tax_polygon.layer.getSource().clear();
                }
                map.removeOverlay(staticMeasureTooltip);
                $('#layer-select-box').hide();
            }

               // Add handler to get current location click
               $('#get_location').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';
            
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(showLoc, errHand);
                }
                });
                 function showLoc(pos) {
                    latt = pos.coords.latitude;
                    long = pos.coords.longitude;
                    
                displayAjaxLoader();
                $.ajax({
                    url: "{{url('/maps/check-location-within-boundary')}}",
                    type: 'get',
                    data: {
                        latt: latt,
                        long: long,
                    },
                    success: function (data) {
                        if (data.length > 0) {
                                
                                displayPointByCoordinates(latt, long);
                                removeAjaxLoader();
                            }
                         else {
                             displayAjaxErrorModal( "{{ __('Your Current Location is Outside the Municipality Boundary')}}");
                        }
                        
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                })
                    
                }

                    function errHand(err) {
                    switch (err.code) {
                        case err.PERMISSION_DENIED:
                        result.innerHTML = "{{ __('The application does not have the permission to make use of location services')}}"
;
                        break;
                        case err.POSITION_UNAVAILABLE:
                        result.innerHTML =  "{{ __('The location of the device is uncertain')}}";
                        break;
                        case err.TIMEOUT:
                        result.innerHTML = "{{ __('The request to get user location timed out')}}";
                        break;
                        case err.UNKNOWN_ERROR:
                        result.innerHTML = "{{ __('Time to fetch location information exceeded the maximum timeout interval')}}";
                        break;
                    }
                    }
           
           
               $('#kml_drag_drop').click(function (e) {
                e.preventDefault();
                disableAllControls();
                currentControl = '';
                 Swal.fire({
                    icon: 'info',
                    title: "{{ __('KML Viewer')}}",
                    text: "{{ __('To View KML, Please Drag and Drop the KML File Directly onto the Map')}}",
                    confirmButtonText: "{{ __('OK')}}"
                });

            });

            // Add handler to zoom in button click
            $('#zoomin_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';

                if (map.getView().getZoom() < map.getView().getMaxZoom()) {
                    map.getView().setZoom(map.getView().getZoom() + 1);
                }
            });

            // Add handler to zoom out button click
            $('#zoomout_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';

                if (map.getView().getZoom() > map.getView().getMinZoom()) {
                    map.getView().setZoom(map.getView().getZoom() - 1);
                }
            });

            // Add handler to zoom city button click
            $('#zoomfull_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';

                zoomToCity();
            });
            // Add handler to info button click
            $('#identify_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                var html = '<option value="">{{ __('Select a layer') }}</option>';

                $.each(mLayer, function (key, value) {
                    if (value.layer.getVisible()) {
                        html += '<option value="' + key + '">' + value.name + '</option>';
                    }
                });

                $('#feature_info_overlay').html(html);
                $('#feature_info_overlay').val('');
                if (currentControl == 'identify_control') {
                    $('#layer-select-box').hide();
                    currentControl = '';

                } else {
                    $('#layer-select-box').show();
                    currentControl = 'identify_control';
                    $('#identify_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnLayerHandler);
                    map.on('singleclick', displayFeatureInformation);
                }
            });

            // Add handler to info button click
            $('#coordinate_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'coordinate_control') {
                    currentControl = '';

                } else {
                    currentControl = 'coordinate_control';
                    $('#coordinate_control').addClass('map-control-active');
                    map.on('singleclick', displayCoordinateInformation);
                }
            });

            // Add handler to point buffer button click
            $('#pointbuffer_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'pointbuffer_control') {
                    currentControl = '';

                } else {
                    currentControl = 'pointbuffer_control';
                    $('#pointbuffer_control').addClass('map-control-active');
                    map.on('singleclick', displayPopupPointBuffer);
                }
            });

            // Add handler to Road Inaccessible button click
            $('#road_inaccessible_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                displayAjaxLoader();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'road_inaccessible_control') {
                    currentControl = '';
                    removeAjaxLoader();
                    $('#add-road-inaccessible-box').hide();
                } else {
                    currentControl = 'road_inaccessible_control';
                    $('#road_inaccessible_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnRoadsHandler);
                    removeAjaxLoader();
                    $('#add-road-inaccessible-box').show();
                }

            });

             $('#add_road_inaccessible_submit_btn').click(function(e){
                e.preventDefault();
                roadInaccessiblePopupOverlay.setPosition(undefined);
                if (eLayer.summary_road_inaccessible) {
                    eLayer.summary_road_inaccessible.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#FF0000',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('summary_road_inaccessible', 'Summary Road Inaccessible', layer);
                }

                if (eLayer.road_inaccessible_buildings) {
                    eLayer.road_inaccessible_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('road_inaccessible_buildings', 'Road Inaccessbile Buildings', layer);
                }
                displayAjaxLoader();
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    }
                });
                $.ajax({
                    url: '{{url('/maps/road-inaccessible-buildings')}}',
                    type: 'post',
                    data: {
                        'road_width' : $('#road_width').val(),
                        'hose_length' : $('#hose_length').val(),
                        'road_width_unit' : $('#road_width_unit').val(),
                        'hose_length_unit' : $('#hose_length_unit').val(),
                    },
                    success: function (Response) {
                        removeAjaxLoader();
                        map.getView().setCenter(ol.proj.transform(coord, 'EPSG:4326', 'EPSG:3857'));
                        map.getView().setZoom(14);
                        $('#add-road-inaccessible-errors').empty();
                        $('#road_width_report').val($('#road_width').val());
                        $('#road_width_unit_report').val($('#road_width_unit').val());
                        $('#road_hose_length_report').val($('#hose_length').val());
                        $('#road_hose_length_unit_report').val($('#hose_length_unit').val());
                        roadInaccessiblePopupContent.innerHTML = Response['popContentsHtml'];
                        roadInaccessiblePopupOverlay.setPosition(map.getView().getCenter());
                        $(roadInaccessiblePopupContainer).show();
                        data1 = Response['buildings'];
                        if (data1 && Array.isArray(data1)) {
                            var format = new ol.format.WKT();

                            for (var i = 0; i < data1.length; i++) {
                                var feature = format.readFeature(data1[i].geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });

                                eLayer.road_inaccessible_buildings.layer.getSource().addFeature(feature);
                            }
                        }
                        var featureP = format.readFeature(Response['polygon'], {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        });
                        eLayer.summary_road_inaccessible.layer.getSource().addFeature(featureP);

                    },
                    error: function (data) {
                        $('#add-road-inaccessible-errors').empty();
                        let html = '<ul class="alert alert-danger">';
                        if (data.responseText) {
                            Object.values(JSON.parse(data.responseText).errors).forEach(function (error) {
                                html += "<li>" + error[0] + "</li>";
                            });
                        }else{
                            html+= "<li>Error</li>";
                        }
                        html+='</ul';
                        $('#add-road-inaccessible-errors').append(html);
                        $('#add-road-inaccessible-errors').focus();
                        removeAjaxLoader();
                    }
                });
            });

            // Add handler to Waterbody Inaccessible button click
            $('#waterbody_inaccessible_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                displayAjaxLoader();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'waterbody_inaccessible_control') {
                    currentControl = '';
                    removeAjaxLoader();
                    $('#add-waterbody-inaccessible-box').hide();
                } else {
                    currentControl = 'waterbody_inaccessible_control';
                    $('#waterbody_inaccessible_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnRoadsHandler);
                    removeAjaxLoader();
                    $('#add-waterbody-inaccessible-box').show();
                }

            });



            $('#add_waterbody_inaccessible_submit_btn').click(function(e){
                e.preventDefault();
                waterbodyInaccessiblePopupOverlay.setPosition(undefined);
                if (eLayer.summary_waterbody_inaccessible) {
                    eLayer.summary_waterbody_inaccessible.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#FF0000',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('summary_waterbody_inaccessible', 'Summary Waterbody Inaccessible', layer);
                }

                if (eLayer.waterbody_inaccessible_buildings) {
                    eLayer.waterbody_inaccessible_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('waterbody_inaccessible_buildings', 'Waterbody Inaccessbile Buildings', layer);
                }
                displayAjaxLoader();
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    }
                });
                $.ajax({
                    url: '{{url('/maps/waterbody-inaccessible-buildings')}}',
                    type: 'post',
                    data: {
                        'hose_length' : $('#waterbody_hose_length').val(),
                        'hose_length_unit' : $('#waterbody_hose_length_unit').val(),
                    },
                    success: function (Response) {
                        removeAjaxLoader();
                        showLayer('waterbodys_layer');
                        map.getView().setCenter(ol.proj.transform(coord, 'EPSG:4326', 'EPSG:3857'));
                        map.getView().setZoom(14);
                        $('#add-waterbody-inaccessible-errors').empty();
                        $('#waterbody_hose_length_report').val($('#waterbody_hose_length').val());
                        $('#waterbody_hose_length_unit_report').val($('#waterbody_hose_length_unit').val());
                        waterbodyInaccessiblePopupContent.innerHTML = Response['popContentsHtml'];
                        waterbodyInaccessiblePopupOverlay.setPosition(map.getView().getCenter());
                        $(waterbodyInaccessiblePopupContainer).show();
                        data1 = Response['buildings'];
                        if (data1 && Array.isArray(data1)) {
                            var format = new ol.format.WKT();

                            for (var i = 0; i < data1.length; i++) {
                                var feature = format.readFeature(data1[i].geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });

                                eLayer.waterbody_inaccessible_buildings.layer.getSource().addFeature(feature);
                            }
                        }
                        var featureP = format.readFeature(Response['polygon'], {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        });
                        eLayer.summary_waterbody_inaccessible.layer.getSource().addFeature(featureP);

                    },
                    error: function (data) {
                        $('#add-waterbody-inaccessible-errors').empty();
                        let html = '<ul class="alert alert-danger">';
                        if (data.responseText) {
                            Object.values(JSON.parse(data.responseText).errors).forEach(function (error) {
                                html += "<li>" + error[0] + "</li>";
                            });
                        }else{
                            html+= "<li>Error</li>";
                        }
                        html+='</ul';
                        $('#add-waterbody-inaccessible-errors').append(html);
                        $('#add-waterbody-inaccessible-errors').focus();
                        removeAjaxLoader();
                    }
                });
            });

             // Add handler to toilet isochrone map button click
             $('#toilet_isochrone_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'toilet_isochrone_control') {
                    currentControl = '';
                    // $('#pan_control').addClass('map-control-active');
                } else {
                    currentControl = 'toilet_isochrone_control';
                    $('#toilet_isochrone_control').addClass('map-control-active');
                    $('#popup-toilet-isochrone').modal('show');

                }
            });

            // Add handler to length measure button click
            $('#linemeasure_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'linemeasure_control') {
                    currentControl = '';

                } else {
                    currentControl = 'linemeasure_control';
                    addMeasureControl('length');
                    $('#linemeasure_control').addClass('map-control-active');
                }
            });

            // Add handler to area measure button click
            $('#polymeasure_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'polymeasure_control') {
                    currentControl = '';

                } else {
                    currentControl = 'polymeasure_control';
                    addMeasureControl('area');
                    $('#polymeasure_control').addClass('map-control-active');
                }
            });


           //*Add road tool start*

           var currentAddControl='',
                vectorSource,
                vectorLayer,
                drawSource,
                drawLayer,
                selectInteraction,
                layerDrawInteraction,
                layerSnapInteraction,
                layerDrawnSnapInteraction,
                undoInteraction,
                modifyInteraction,
                originalRoadFeature,
                modifiedFeature,
                hasModification = false;

        function handleAddRoadControlClick(code) {
                    currentLayerType = 'Road';

                    // Set tool titles or other properties
                    $('#add_edit_control').attr( currentLayerType);
                    $('#add_submit_road_btn').attr(currentLayerType);
                    $('#add_submit_control')
                        .attr('data-code', code)
                        .attr(currentLayerType);
                    $('#add_start_control').attr(currentLayerType);

                    handleMapControl(
                        '#add_road_control',
                        'add-roads-layer',          // Layer name
                        'add-tool-box',             // Toolbox ID
                        'roadlines_layer'           // GeoServer layer
                    );

                  // Trigger the click event immediately after binding
            $('#add_road_control').trigger('click');
        }

            // On page load, check if the hash is #add_road_control
            if (window.location.hash === '#add_road_control') {
                    $('#add_edit_control').hide();
                    let code = '';
                        handleAddRoadControlClick(code);

                }

                // On page load, check if the hash is #add_road_control
                if (window.location.hash.startsWith('#edit_road_control-')) {
                    const code = window.location.hash.split('-')[1];
                    $('#add_start_control').hide();
                    $('#add_undo_last_point_control').hide();
                    $('#add_delete_control').hide();
                    $('#add_edit_control').hide();
                     handleAddRoadControlClick(code);
                     currentAddControl = 'Modify Road'; 
                    addModifyInteractions(currentAddControl, code, 'roadlines');

                }

        function handleAddSewerControlClick(code) {
            currentLayerType = 'Sewer';
              // Set tool titles or other properties
                $('#add_edit_control').attr( currentLayerType);
                $('#add_submit_sewer_btn').attr(currentLayerType);
                $('#add_submit_control')
                        .attr('data-code', code)
                        .attr(currentLayerType);
                $('#add_start_control').attr(  currentLayerType);

                handleMapControl(
                    '#add_sewer_control',
                   'add-sewers-layer',          // Layer name
                     'add-tool-box',             // Toolbox ID
                      'sewerlines_layer'           // GeoServer layer
                            );
                    $('#add_sewer_control').trigger('click');
                }

                // On page load, check if the hash is #add_road_control
                if (window.location.hash === '#add_sewer_control') {
                    $('#add_edit_control').hide();
                    let code = '';
                    handleAddSewerControlClick(code);
                }

                // On page load, check if the hash is #add_road_control
                if (window.location.hash.startsWith('#edit_sewer_control-')) {
                    const code = window.location.hash.split('-')[1];
                    
                    $('#add_start_control').hide();
                    $('#add_undo_last_point_control').hide();
                    $('#add_delete_control').hide();
                    $('#add_edit_control').hide();
                    handleAddSewerControlClick(code);
                    currentAddControl = 'Modify Sewer'; 
                    addModifyInteractions(currentAddControl, code , 'sewerlines');

                }


            function handleAddDrainControlClick(code) {
                    currentLayerType = 'Drain';

              // Set tool titles or other properties
                $('#add_edit_control').attr( currentLayerType);
                $('#add_submit_drain_btn').attr( currentLayerType);
                $('#add_submit_control')
                        .attr('data-code', code)
                        .attr(currentLayerType);
                $('#add_start_control').attr(  currentLayerType);

                handleMapControl(
                    '#add_drain_control',
                   'add-drains-layer',          // Layer name
                     'add-tool-box',             // Toolbox ID
                      'drains_layer'           // GeoServer layer
                            );
                    $('#add_drain_control').trigger('click');
                }

                 // On page load, check if the hash is #add_road_control
                 if (window.location.hash === '#add_drain_control') {
                    $('#add_edit_control').hide();
                    let code = '';
                    handleAddDrainControlClick(code);
                }

                // On page load, check if the hash is #add_road_control
                if (window.location.hash.startsWith('#edit_drain_control-')) {
                    const code = window.location.hash.split('-')[1];
                                        
                    $('#add_start_control').hide();
                    $('#add_undo_last_point_control').hide();
                    $('#add_delete_control').hide();
                    $('#add_edit_control').hide();

                    handleAddDrainControlClick(code);
                    currentAddControl = 'Modify Drain'; 
                    addModifyInteractions(currentAddControl, code , 'drains');
                }



                function handleAddWatersupplyControlClick(code) {
                    currentLayerType = 'Watersupply';

              // Set tool titles or other properties
                $('#add_edit_control').attr( currentLayerType);
                $('#add_submit_watersupply_btn').attr( currentLayerType);
                $('#add_submit_control')
                        .attr('data-code', code)
                        .attr(currentLayerType);
                $('#add_start_control').attr( currentLayerType);

                handleMapControl(
                    '#add_watersupply_control',
                   'add-watersupplys-layer',          // Layer name
                     'add-tool-box',             // Toolbox ID
                      'watersupply_network_layer'           // GeoServer layer
                            );
                    $('#add_watersupply_control').trigger('click');
                }

                  // On page load, check if the hash is #add_road_control
                  if (window.location.hash === '#add_watersupply_control') {
                    $('#add_edit_control').hide();
                    let code = '';
                     handleAddWatersupplyControlClick(code);
                }

                // On page load, check if the hash is #add_road_control
                if (window.location.hash.startsWith('#edit_watersupplys_control-')) {
                    const code = window.location.hash.split('-')[1];
                    $('#add_start_control').hide();
                    $('#add_undo_last_point_control').hide();
                    $('#add_delete_control').hide();
                    $('#add_edit_control').hide();
                     handleAddWatersupplyControlClick(code);
                     currentAddControl = 'Modify Watersupply'; 
                     addModifyInteractions(currentAddControl, code , 'watersupplys');
                }


                function handleMapControl(controlId, layerName, toolBoxId, geoServerLayer) {
                
                        disableAllControls();
                        var allLayers = map.getLayers().getArray();
                        $('.map-control').removeClass('map-control-active');

                        if (currentControl === controlId) {
                            $('#' + toolBoxId).hide();
                            currentControl = '';
                            resetAddTool();
                            removeAjaxLoader();
                            disableAllControls();
                        } else {
                            currentControl = controlId;
                            $('#' + toolBoxId).show();

                            vectorSource = new ol.source.Vector({
                                url: gurl_wfs + '?service=WFS&' +
                                    'version=1.1.0&request=GetFeature&typeName=<?php echo Config::get("constants.GEOSERVER_WORKSPACE"); ?>:' + geoServerLayer + '&' +
                                    'CQL_FILTER=deleted_at is null&SRS=EPSG:4326&outputFormat=json',
                                format: new ol.format.GeoJSON(),
                            });

                            vectorLayer = new ol.layer.Vector({
                                background: '#1a2b39',
                                source: vectorSource,
                                name: layerName
                            });

                            drawSource = new ol.source.Vector({ format: new ol.format.GeoJSON() });
                            drawLayer = new ol.layer.Vector({
                                background: '#1a2b39',
                                source: drawSource,
                                name: layerName + '-draw-layer'
                            });

                            if (!allLayers.some(layer => layer.get('name') === layerName)) {
                                map.addLayer(vectorLayer);
                            } else {
                                removeAjaxLoader();
                            }

                            if (!allLayers.some(layer => layer.get('name') === layerName + '-draw-layer')) {
                                map.addLayer(drawLayer);
                            }

                            var sourceEventListener = vectorSource.on('change', function (e) {
                                if (vectorSource.getState() === 'ready') {
                                    vectorSource.un('change', sourceEventListener);
                                    removeAjaxLoader();
                                }
                            });
                        }
                }

                
            // Add draw,snap & undo interactions.
            $('#add_start_control').click(function (e) {
                e.preventDefault();
                hideAddForm();
                if (currentAddControl !== 'Add ' + currentLayerType){
                    currentAddControl = 'Add ' + currentLayerType;
                    addDrawInteractions();
                }
            });

            //Undo the last drawn point.
            $('#add_undo_last_point_control').click(function (e) {
                e.preventDefault();
                hideAddForm();
                layerDrawInteraction?.removeLastPoint();
            });

            //Undo the entire drawn line.
            $('#add_undo_control').click(function (e) {
                e.preventDefault();
                hideAddForm();
                undoInteraction?.undo();
            });

            //Redo the drawing that was undoed
            $('#add_redo_control').click(function (e) {
                e.preventDefault();
                hideAddForm();
                undoInteraction?.redo();
            });

            //Remove the drawn lines.
            $('#add_delete_control').click(function (e) {
                e.preventDefault();
                hideAddForm();
                if (hasModification && modifiedFeature && originalRoadFeature) {
                    const source = vectorLayer.getSource();

                    // Remove modified feature
                    source.removeFeature(modifiedFeature);

                    // Add original feature back
                    source.addFeature(originalRoadFeature.clone());

                    // Clear selection and state
                    selectInteraction.getFeatures().clear();
                    originalRoadFeature = null;
                    modifiedFeature = null;
                    hasModification = false;
                } else {
                    removeDrawnFeatures(); // fallback for drawn lines

                }
            });


            $('#add_submit_control').click(function (e) {
                    e.preventDefault();
                 
                    let code = $('#add_submit_control').data('code');

                    const layerTypeLower = currentLayerType.toLowerCase();

                    if (currentAddControl === 'Add ' + currentLayerType) {
                        const features = drawLayer.getSource().getFeatures();

                        if (!features || features.length < 1) {
                            Swal.fire({
                                title: "{{ __('Error') }}",

                                text: `${layerTypeLower}` +` {{ __('must be drawn before saving') }}`,
                                icon: "warning",
                            });
                        } else {
                            $(`.add-${layerTypeLower}-form`).slideToggle();
                        }

                    } else if (currentAddControl === 'Modify ' + currentLayerType) {
                        hideAddForm();

                        if (hasModification && modifiedFeature) {
                            Swal.fire({
                                title:"{{__('Are you sure?')}}",
                                text: "{{__('The changes made will be saved')}}",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: "{{ __('Yes') }}",
                                cancelButtonText: "{{ __('No') }}",
                                reverseButtons: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    displayAjaxLoader();

                                    const geometry = modifiedFeature.getGeometry().clone(); 
    
                                    // Check the type of geometry and calculate length
                                    if (geometry instanceof ol.geom.LineString) {
                                        var length = geometry.getLength(); // Get length in meters
                                    } else if (geometry instanceof ol.geom.MultiLineString) {
                                        var length = 0 ;
                                        // If it's a MultiLineString, sum the lengths of individual lines
                                        var coordinates = geometry.getCoordinates();
                                        coordinates.forEach(function(coords) {
                                            var lineString = new ol.geom.LineString(coords);
                                            length += lineString.getLength(); // Get length for each line in the MultiLineString
                                        });

                                    }
                                   
                                    const lineFormat = new ol.format.WKT();
                                    const geom = lineFormat.writeGeometry(
                                        modifiedFeature.getGeometry().clone().transform('EPSG:3857', 'EPSG:4326')
                                    );
            
                                    // Dynamic values based on the layer type
                                    let formData = {};
                                    let updateUrl = '';
                                    let return_url = '';

                                    switch (currentLayerType) {
                                        case 'Road':
                                            formData = {
                                                'roadcd':code,
                                                'geom': geom,
                                                'length': length.toFixed(2)
                                            };
                                            return_url = '{{ url("utilityinfo/roadlines/:code/edit") }}'.replace(':code',code);
                                            updateUrl = '{{ url("/utilityinfo/roadlines/update-road-geom") }}';
                                            break;

                                        case 'Drain':
                                            formData = {
                                                'code': code,
                                                'geom': geom,
                                                'length': length.toFixed(2)
                                            };
                                            return_url = '{{ url("utilityinfo/drains/:code/edit") }}'.replace(':code',code);
                                            updateUrl = '{{ url("/utilityinfo/drains/update-drain-geom") }}';
                                            break;

                                        case 'Sewer':
                                            formData = {
                                                'code': code,
                                                'geom': geom,
                                                'length': length.toFixed(2)
                                            };
                                            return_url = '{{ url("utilityinfo/sewerlines/:code/edit") }}'.replace(':code',code);
                                            updateUrl = '{{ url("/utilityinfo/sewerlines/update-sewer-geom") }}';
                                            break;

                                        case 'Watersupply':
                                            formData = {
                                                'code': code,
                                                'geom': geom,
                                                'length': length.toFixed(2)
                                            };
                                            return_url = '{{ url("utilityinfo/watersupplys/:code/edit") }}'.replace(':code',code);

                                            updateUrl = '{{ url("/utilityinfo/watersupplys/update-watersupply-geom") }}';
                                            break;

                                        default:
                                            removeAjaxLoader();
                                            Swal.fire("{{ __('Error') }}", "{{ __('Invalid Layer') }}", 'error');
                                            return;
                                    }
                                    

                                    $.ajaxSetup({
                                        headers: {
                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                            'Accept': 'application/json'
                                        }
                                    });

                                    $.ajax({
                                        url: updateUrl,
                                        type: 'POST',
                                        data: formData,
                                        success: function (data) {
                                            removeAjaxLoader();
                                            Swal.fire("{{ __('Saved') }}", "{{ __('The changes have been saved') }}", 'success')
                                                .then((res) => {
                                                    if (res.isConfirmed) {
                                                        displayAjaxLoader();
                                                        window.location.href = return_url;
                                                    }
                                                });
                                        },
                                        error: function () {
                                            removeAjaxLoader();
                                            Swal.fire("{{ __('Error') }}",  "{{ __('There was an error while saving changes') }}", 'error');
                                        }
                                    });
                                } else {
                                    Swal.fire("{{ __('Cancelled') }}",  "{{ __('The changes have been removed') }}", 'info');
                                    removeAjaxLoader();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "{{ __('Nothing to save') }}",
                                icon: "warning",
                            });
                        }

                    } else {
                        hideAddForm();
                        Swal.fire({
                            title: "{{ __('Nothing to save') }}",
                            icon: "warning",
                        });
                    }
                });

            function submitDetails(controlType) {
            // Set the dynamic fields based on control type
            let formData = {};
            let geom = null;
            let fieldNameMapping = {};

            if (controlType === 'road') {
                geom = getGeometryLayer();
                fieldNameMapping = {
                    "name": "{{ __('Road Name') }}",
                    "length": "{{ __('Length (m)') }}",
                    "carrying_width": "{{ __('Carrying Width (m)') }}",
                    "right_of_way": "{{ __('Right of Way (m)') }}"
                };

                // Dynamically get the road form data
                formData = {
                    'name': $('#name').val(),
                    'hierarchy': $('#hierarchy').val(),
                    'surface_type': $('#surface_type').val(),
                    'length': $('#length').val(),
                    'carrying_width': $('#carrying_width').val(),
                    'right_of_way': $('#right_of_way').val(),
                    "geom": geom
                };

                url = '{{url("/utilityinfo/roadlines/add-road")}}';
                return_url = '{{ route("roadlines.index") }}';
            } else if (controlType === 'sewer') {
                geom = getGeometryLayer();
                fieldNameMapping = {
                    "road_code": "{{ __('Road Code') }}",
                    "treatment_plant_id": "{{ __('Treatment Plant') }}",
                    "length": "{{ __('Length (m)') }}",
                    "location": "{{ __('Location') }}",
                    "diameter": "{{ __('Diameter (mm)') }}",

                };

                // Dynamically get the drain form data
                formData = {
                    'road_code': $('#road_code').val(),
                    'treatment_plant_id': $('#treatment_plant_id').val(),
                    'length': $('#length_sewer').val(),
                    'location': $('#location').val(),
                    'diameter': $('#diameter').val(),
                    "geom": geom
                };

                
                url = '{{url("/utilityinfo/sewerlines/add-sewer")}}';
                return_url = '{{ route("sewerlines.index") }}';

            }else if (controlType === 'drain') {
                geom = getGeometryLayer();
                fieldNameMapping = {
                    "road_code": "{{ __('Road Code') }}",
                    "cover_type": "{{ __('Cover Type') }}",
                    "surface_type": "{{ __('Surface Type') }}",
                    "size": "{{ __('Width (mm)') }}",
                    "length": "{{ __('Length (m)') }}",
                    "treatment_plant_id": "{{ __('Treatment Plant') }}",
                };

                // Dynamically get the drain form data
                formData = {
                    'road_code': $('#road_code_drain').val(),
                    "cover_type": $('#cover_type').val(),
                    "surface_type": $('#surface_type_drain').val(),
                    'size': $('#size').val(),
                    'length': $('#length_drain').val(),
                    'treatment_plant_id': $('#tp_drain').val(), 
                    "geom": geom
                };
                url = '{{url("/utilityinfo/drains/add-drain")}}';
                return_url = '{{ route("drains.index") }}';

            }else if (controlType === 'watersupply') {
                geom = getGeometryLayer();
                fieldNameMapping = {
                    "road_code": "{{ __('Road Code') }}",
                    "diameter": "{{ __('Diameter (mm)') }}",
                    "length": "{{ __('Length (m') }})",
                    "project_name": "{{ __('Project Name') }}",
                    "type": "{{ __('Type') }}",
                    "material_type": "{{ __('Material Type') }}",
                };

                // Dynamically get the drain form data
                formData = {
                    'road_code': $('#road_code_watersupply').val(),
                    "diameter": $('#diameter_watersupply').val(),
                    "length": $('#length_watersupply').val(),
                    'project_name': $('#project_name').val(),
                    'type': $('#type').val(),
                    'material_type': $('#material_type').val(), 
                    "geom": geom
                };
                url = '{{url("/utilityinfo/watersupplys/add-watersupply")}}';
                return_url = '{{ route("watersupplys.index") }}';

            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });
            displayAjaxLoader();
            $.ajax({
                url: url,
                type: 'post',
                data: formData,
                success: function(data) {
                    $('#add-' + controlType + '-errors').empty();
                    $('.add-' + controlType + '-form').slideUp();
                    removeAjaxLoader(); 
                    Swal.fire({
                        title: 'Success!',
                        text: `${controlType === 'watersupply' ? 'Water Supply' : controlType.charAt(0).toUpperCase() + controlType.slice(1)} {{ __('added successfully') }}`,
                        icon: 'success',
                        confirmButtonText: 'OK!',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = return_url; 
                             displayAjaxLoader();
                        }
                    });
                },
                error: function (data) {
                    $('#add-' + controlType + '-errors').empty();
                    let html = '<ul class="alert alert-danger">';

                    if (data.responseText) {
                        let errors = JSON.parse(data.responseText).errors;
                        Object.entries(errors).forEach(function ([field, messages]) {
                            let fieldTitle = fieldNameMapping[field] || field;
                            let message = messages[0]; // Get the first error message for this field

                            // Optional: Customize message if needed
                            let customMessage = `${fieldTitle} ` + `{{ __("is required")}}`;

                            html += `<li>${customMessage}</li>`;
                        });
                    } else {
                        html += "<li>Error</li>";
                    }

                    html += '</ul>';
                    $('#add-' + controlType + '-errors').append(html);
                    $('#add-' + controlType + '-errors').focus();
                    removeAjaxLoader();
                }

            });
        }

        function getGeometryLayer() {
            let lineFormat = new ol.format.WKT();
            return lineFormat.writeGeometry(
                drawSource.getFeatures()[drawSource.getFeatures().length - 1].getGeometry().clone().transform('EPSG:3857', 'EPSG:4326')
            );
        }

        // Event listeners for submit buttons
        $('#add_road_submit_btn').click(function (e) {
            e.preventDefault();
            submitDetails('road');
        });

          $('#add_sewer_submit_btn').click(function (e) {
            e.preventDefault();
            submitDetails('sewer');
        });

        $('#add_drain_submit_btn').click(function (e) {
            e.preventDefault();
            submitDetails('drain');
        });

        $('#add_watersupply_submit_btn').click(function (e) {
            e.preventDefault();
            submitDetails('watersupply');
        });
        
            function hideAddForm(){
                $('.add-road-form').slideUp();
                $('.add-drain-form').slideUp();
                $('.add-sewer-form').slideUp();
                $('.add-watersupply-form').slideUp();
            }

            function addDrawInteractions() {

                map.removeInteraction(modifyInteraction);
                map.removeInteraction(selectInteraction);
                layerDrawInteraction = new ol.interaction.Draw({
                    source: drawSource,
                    type: "MultiLineString",
                });
                layerSnapInteraction = new ol.interaction.Snap({source: vectorSource});
                layerDrawnSnapInteraction = new ol.interaction.Snap({source: drawSource});
                undoInteraction = new ol.interaction.UndoRedo();
                map.addInteraction(layerDrawInteraction);
                map.addInteraction(layerSnapInteraction);
                map.addInteraction(layerDrawnSnapInteraction);
                map.addInteraction(undoInteraction);
                layerDrawInteraction.on("drawstart",function(e){
                    hideAddForm();
                    removeDrawnFeatures();
                });
    
                layerDrawInteraction.on("drawend", function(e) {
                // Get the geometry of the drawn feature
                var feature = e.feature;
                var geometry = feature.getGeometry();

                // Initialize length variable
                var length = 0;

                // Check the type of geometry and calculate length
                if (geometry instanceof ol.geom.LineString) {
                    length = geometry.getLength(); // Get length in meters
                } else if (geometry instanceof ol.geom.MultiLineString) {
                    // If it's a MultiLineString, sum the lengths of individual lines
                    var coordinates = geometry.getCoordinates();
                    coordinates.forEach(function(coords) {
                        var lineString = new ol.geom.LineString(coords);
                        length += lineString.getLength(); // Get length for each line in the MultiLineString
                    });
                }

                if (window.location.hash === '#add_sewer_control') {
                    document.getElementById('length_sewer').value = length.toFixed(2);
                }else if(window.location.hash === '#add_road_control'){
                    document.getElementById('length').value = length.toFixed(2);
                }
                else if(window.location.hash === '#add_drain_control'){
                    document.getElementById('length_drain').value = length.toFixed(2);
                }
                else if(window.location.hash === '#add_watersupply_control'){
                    document.getElementById('length_watersupply').value = length.toFixed(2);
                }
                
            });
            }
            
            function addModifyInteractions(currentAddControl, code, utilityType) {

                // Clear existing interactions
                map.removeInteraction(layerDrawInteraction);
                map.removeInteraction(modifyInteraction);
                map.removeInteraction(selectInteraction);
                map.removeInteraction(undoInteraction);

                // Clear any previous features
                vectorSource.clear();

                // AJAX call to fetch the geometry for the given utility type and code
                $.ajax({
                    url: `utilityinfo/${utilityType}/${code}/geometry`, // Dynamic endpoint
                    method: 'GET',
                    success: function (response) {
                        const format = new ol.format.WKT();
                        var feature = format.readFeature(response.geometry, {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        });

                        const featureCollection = new ol.Collection([feature]);

                        selectInteraction = new ol.interaction.Select({
                            features: featureCollection,
                            style: new ol.style.Style({
                                stroke: new ol.style.Stroke({
                                    color: [191, 17, 183, 1],
                                    width: 5
                                })
                            })
                        });

                        modifyInteraction = new ol.interaction.ModifyFeature({
                            features: featureCollection
                        });

                        undoInteraction = new ol.interaction.UndoRedo();
                        layerSnapInteraction = new ol.interaction.Snap({ source: vectorSource });
                        layerDrawnSnapInteraction = new ol.interaction.Snap({ source: drawSource });

                        map.addInteraction(modifyInteraction);
                        map.addInteraction(selectInteraction);
                        map.addInteraction(layerSnapInteraction);
                        map.addInteraction(layerDrawnSnapInteraction);
                        map.addInteraction(undoInteraction);

                        modifyInteraction.on('modifyend', function (e) {
                            hasModification = true;
                            modifiedFeature = e.features[0];
                        });

                        selectInteraction.on('select', function (e) {
                            const selectedFeatures = selectInteraction.getFeatures();

                            // If the selected feature is already selected, do nothing
                            if (e.selected.length > 0 && e.selected[0] !== feature) {
                                // Revert the selection to only the desired feature
                                selectedFeatures.clear();
                                selectedFeatures.push(feature);

                                // Optional: Show warning
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Warning',
                                    text: 'You can only modify the geometry for ' + code,
                                    confirmButtonColor: '#3085d6',
                                    confirmButtonText: 'OK'
                                });
                            } else if (e.selected.length === 0) {
                                // If user clicks on empty space, prevent deselection
                                if (!selectedFeatures.getArray().includes(feature)) {
                                    selectedFeatures.clear();
                                    selectedFeatures.push(feature);
                                }
                            }
                        });


                        undoInteraction.clear();
                    },
                    error: function () {
                        alert("Failed to load geometry for " + utilityType + " code: " + code);
                    }
                });
                }


            function removeDrawnFeatures(){
                drawLayer.getSource().clear();
            }
            function removeAllAddInteractions(){
                map.removeInteraction(layerDrawInteraction);
                map.removeInteraction(layerSnapInteraction);
                map.removeInteraction(layerDrawnSnapInteraction);
                map.removeInteraction(undoInteraction);
                map.removeInteraction(modifyInteraction);
                map.removeInteraction(selectInteraction);
            }
            function clearAddlayers(){
                if (drawSource){
                    drawSource.clear();
                }
                map.removeLayer(vectorLayer);
                map.removeLayer(drawLayer);
            }
            function resetAddTool(){
                currentAddControl='';
                removeAllAddInteractions();
                clearAddlayers();
            }

            //*Add road tool end*
            // variable for storing draw interaction
            var draw;

            var wgs84Sphere = new ol.Sphere(6378137);

            /**
             * Currently drawn feature.
             * @type {ol.Feature}
             */
            var sketch;

            /**
             * The help tooltip element.
             * @type {Element}
             */
            var helpTooltipElement;

            /**
             * Overlay to show the help messages.
             * @type {ol.Overlay}
             */
            var helpTooltip;

            /**
             * The measure tooltip element.
             * @type {Element}
             */
            var measureTooltipElement;

            /**
             * Overlay to show the measurement.
             * @type {ol.Overlay}
             */
            var measureTooltip;

            /**
             * Overlay to show the static measurement.
             * @type {ol.Overlay}
             */
            var staticMeasureTooltip;

            /**
             * Message to show when the user is drawing a polygon.
             * @type {string}
             */
            var continuePolygonMsg = "{{ __('Click to continue drawing the polygon.') }}";

            /**
             * Message to show when the user is drawing a line.
             * @type {string}
             */
            var continueLineMsg = "{{ __('Click to continue drawing the line.') }}";

            /**
             * Handle pointer move.
             * @param {ol.MapBrowserEvent} evt The event.
             */
            var pointerMoveHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                /** @type {string} */
                var helpMsg = "{{ __('Click to start drawing')}}";

                if (sketch) {
                    var geom = (sketch.getGeometry());
                    if (geom instanceof ol.geom.Polygon) {
                        helpMsg = continuePolygonMsg;
                    } else if (geom instanceof ol.geom.LineString) {
                        helpMsg = continueLineMsg;
                    }
                }

                helpTooltipElement.innerHTML = helpMsg;
                helpTooltip.setPosition(evt.coordinate);

                helpTooltipElement.classList.remove('hidden');
            };

            /**
             * Format length output.
             * @param {ol.geom.LineString} line The line.
             * @return {string} The formatted length.
             */
            var formatLength = function (line) {
                var length;

                var coordinates = line.getCoordinates();
                length = 0;
                var sourceProj = map.getView().getProjection();
                for (var i = 0, ii = coordinates.length - 1; i < ii; ++i) {
                    var c1 = ol.proj.transform(coordinates[i], sourceProj, 'EPSG:4326');
                    var c2 = ol.proj.transform(coordinates[i + 1], sourceProj, 'EPSG:4326');
                    length += wgs84Sphere.haversineDistance(c1, c2);
                }

                var output;
                if (length > 100) {
                    output = (Math.round(length / 1000 * 100) / 100) + ' ' + 'km';
                } else {
                    output = (Math.round(length * 100) / 100) + ' ' + 'm';
                }
                return output;
            };

            /**
             * Format area output.
             * @param {ol.geom.Polygon} polygon The polygon.
             * @return {string} Formatted area.
             */
            var formatArea = function (polygon) {
                var area;

                var sourceProj = map.getView().getProjection();
                var geom = /** @type {ol.geom.Polygon} */(polygon.clone().transform(
                    sourceProj, 'EPSG:4326'));
                var coordinates = geom.getLinearRing(0).getCoordinates();
                area = Math.abs(wgs84Sphere.geodesicArea(coordinates));

                var output;
                if (area > 10000) {
                    output = (Math.round(area / 1000000 * 100) / 100) + ' ' + 'km<sup>2</sup>';
                } else {
                    output = (Math.round(area * 100) / 100) + ' ' + 'm<sup>2</sup>';
                }
                return output;
            };

            var mouseOutHandler = function () {
                helpTooltipElement.classList.add('hidden');
            }

            // Add measure control to map
            function addMeasureControl(measureType) {
                var type = (measureType == 'area' ? 'Polygon' : 'LineString');

                map.on('pointermove', pointerMoveHandler);
                map.getViewport().addEventListener('mouseout', mouseOutHandler);
                if (helpTooltipElement) {
                    helpTooltipElement.classList.remove('hidden');
                }

                if (!eLayer.measure) {
                    var measureLayer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            fill: new ol.style.Fill({
                                color: 'rgba(255, 255, 255, 0.2)'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#ffcc33',
                                width: 2
                            }),
                            image: new ol.style.Circle({
                                radius: 7,
                                fill: new ol.style.Fill({
                                    color: '#ffcc33'
                                })
                            })
                        })
                    });

                    addExtraLayer('measure', 'Measure', measureLayer);
                }

                draw = new ol.interaction.Draw({
                    source: eLayer.measure.layer.getSource(),
                    type: /** @type {ol.geom.GeometryType} */ (type),
                    style: new ol.style.Style({
                        fill: new ol.style.Fill({
                            color: 'rgba(255, 255, 255, 0.2)'
                        }),
                        stroke: new ol.style.Stroke({
                            color: 'rgba(0, 0, 0, 0.5)',
                            lineDash: [10, 10],
                            width: 2
                        }),
                        image: new ol.style.Circle({
                            radius: 5,
                            stroke: new ol.style.Stroke({
                                color: 'rgba(0, 0, 0, 0.7)'
                            }),
                            fill: new ol.style.Fill({
                                color: 'rgba(255, 255, 255, 0.2)'
                            })
                        })
                    })
                });
                map.addInteraction(draw);

                createMeasureTooltip();
                createHelpTooltip();

                var listener;
                draw.on('drawstart',
                    function (evt) {
                        eLayer.measure.layer.getSource().clear();
                        map.removeOverlay(staticMeasureTooltip);

                        // set sketch
                        sketch = evt.feature;

                        /** @type {ol.Coordinate|undefined} */
                        var tooltipCoord = evt.coordinate;

                        listener = sketch.getGeometry().on('change', function (evt) {
                            var geom = evt.target;
                            var output;
                            if (geom instanceof ol.geom.Polygon) {
                                output = formatArea(geom);
                                tooltipCoord = geom.getInteriorPoint().getCoordinates();
                            } else if (geom instanceof ol.geom.LineString) {
                                output = formatLength(geom);
                                tooltipCoord = geom.getLastCoordinate();
                            }
                            measureTooltipElement.innerHTML = output;
                            measureTooltip.setPosition(tooltipCoord);
                        });
                    }, this);

                draw.on('drawend',
                    function () {
                        // showExtraLayer('measure');
                        measureTooltipElement.className = 'tooltip tooltip-static';
                        measureTooltip.setOffset([0, -7]);
                        // copy reference of measureTooltip object to staticMeasureTooltip so that it can be referenced later
                        staticMeasureTooltip = measureTooltip;
                        // unset sketch
                        sketch = null;
                        // unset tooltip so that a new one can be created
                        measureTooltipElement = null;
                        createMeasureTooltip();
                        ol.Observable.unByKey(listener);
                    }, this);
            }

            /**
             * Creates a new help tooltip
             */
            function createHelpTooltip() {
                if (helpTooltipElement) {
                    helpTooltipElement.parentNode.removeChild(helpTooltipElement);
                }
                helpTooltipElement = document.createElement('div');
                helpTooltipElement.className = 'tooltip hidden';
                helpTooltip = new ol.Overlay({
                    element: helpTooltipElement,
                    offset: [15, 0],
                    positioning: 'center-left',
                    stopEvent: false
                });
                map.addOverlay(helpTooltip);
            }

            /**
             * Creates a new measure tooltip
             */
            function createMeasureTooltip() {
                if (measureTooltipElement) {
                    measureTooltipElement.parentNode.removeChild(measureTooltipElement);
                }
                measureTooltipElement = document.createElement('div');
                measureTooltipElement.className = 'tooltip tooltip-measure';
                measureTooltip = new ol.Overlay({
                    element: measureTooltipElement,
                    offset: [0, -15],
                    positioning: 'bottom-center',
                    stopEvent: false
                });
                map.addOverlay(measureTooltip);
            }

            // Add handler to print button click
            $('#print_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';

                // printMap();
                $('#print_title_text').val('');
                $('#print_comment_text').val('');
                if (!($('#print_modal .modal.in').length)) {
                    $('#print_modal .modal-dialog').css({
                      top: 90,
                      left: 20
                    });
                  }

                  $('#print_modal').modal({
                    backdrop: false,
                    show: true
                  });

                    //$('#print_modal .modal-dialog').draggable({
                    //handle: ".modal-header"
                    //});
                  //$('#print_modal').modal('show');
                  $('#print_modal').modal({
                    backdrop: 'static',
                    keyboard: false
                })

                let print_map_title = $("#print_map_title").val();
                let print_map_description = $("#print_map_description").val();
                printBox();
            });

            //Add handler to find coordinate click
            $('#getpointbycoordinates_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';

                // printMap();
                //$('#print_title_text').val('');
                //$('#print_comment_text').val('');
                $('#coordinate_search_modal').modal('show');
            });

            $('#print_modal').on('hidden.bs.modal', function () {
                if (eLayer.print_box_layer) {
                   eLayer.print_box_layer.layer.getSource().clear();
               }
               $("#print_map_title").val("");
               $("#print_map_description").val("");
               $("#print_scale").val("500");
               $("#print_paper_size").val("A4");
               $("#print_dpi").val("150");
               $("#box_orientation").val("landscape");
           });
            // Add handler to print form submit
            $("#print_map_fish").on("click", function() {


                var base_layer_select = $("#base_layer_select").val();
                //var projection1 = new ol.proj.Projection({ code: 'EPSG:4326', units: 'degrees', axisOrientation: 'neu' });
                //var baseLayer = new ol.layer.Tile({ projection : projection1, source: new ol.source.OSM(), isBaseLayer: true });

                if (base_layer_select == 'stamen') {
                    // Creating layer object
                    var baseLayer = new ol.layer.Tile({
                        isBaseLayer: true,
                        source: new ol.source.Stamen({
                            layer: toner
                        })
                    });
                } else if (base_layer_select == 'osm') {
                    // Creating layer object
                   var baseLayer = new ol.layer.Tile({
                        isBaseLayer: true,
                        source: new ol.source.OSM()
                    });
                } else if (base_layer_select == 'bing') {
                    // Creating layer object
                   var baseLayer = new ol.layer.Tile({
                        isBaseLayer: true,
                        source: new ol.source.BingMaps({
                            key: '<?php echo Config::get("constants.API_KEY_BING"); ?>',
                            imagerySet: value.imagerySet
                        })
                    });
                }



        ///****mapfish print
                let print_map_title = $("#print_map_title").val();
                let print_map_description = $("#print_map_description").val();
                let print_scale = parseInt($("#print_scale").val());
                let print_paper_size = $("#print_paper_size").val();
                let box_orientation = $("#box_orientation").val();
                if(print_paper_size == "A3")
                {

                    var mapWidth = 1092;
                    var mapHeight = 674.31;
                    var mapHeader = 20;

                }
                else{
                    var mapWidth = 756.21;
                    var mapHeight = 436.8;
                    var mapHeader = 16;

                }

                let print_dpi = $("#print_dpi").val();
                let map_print_center = $('#map-print-polygon-center').val();
                if (!print_map_title) {
                    Swal.fire({
                        title: "{{ __('Please provide a title!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    });
                    return;
                }


                let alllayers = [];
                $.each(mLayer, function (key, value) {
                    if (value.layer.getVisible()) {
                        if(key == "roads_width_zoom_layer"){
                            key = "roadlines_layer";
                        }
                        alllayers.push(key);
                    }
                });

                let allstyles = [];
                var multistylelayers = ["containments_layer", "buildings_layer", "wards_layer", "grids_layer", "roadlines_layer","toilets_layer", "sewerlines_layer", "waterborne_hotspots_layer", "drains_layer"];
                $.each(mLayer, function (key, value) {

                    if (value.layer.getVisible()) {
                        if (multistylelayers.indexOf(key) > -1) {
                            var style = $('#' + key + '_overlay_style_select_container').find(":selected").val();
                        }
                        else{
                            if(key == "roads_width_zoom_layer")
                            {
                                var style = "roadlines_layer_width";
                            }
                            else{
                            var style = key;
                        }
                        }
                        allstyles.push(style);
                    }
                });

                // debugger;

                var printBounds = eLayer.print_box_layer.layer.getSource().getExtent();
                var printBoundsTransform = ol.proj.transformExtent(printBounds,'EPSG:3857','EPSG:4326');
                var HIS = moment().format('LTS');
                var date = moment().format('L');
                var fulldate = moment().format('LLLL');
                var LAYOUT_NAME = print_paper_size + " Portrait";

                var json = {

                    mapWidth : mapWidth,
                    mapHeight : mapHeight,
                    mapHeader : mapHeader,

                    HIS : HIS,
                    date : date,
                    fulldate : fulldate,
                    mapTitle: print_map_title,
                    description: print_map_description,
                    //dpi: print_dpi,
                    //scale: print_scale,
                    layout: LAYOUT_NAME,
                    orientation: box_orientation,
                    srs: "EPSG:4326",
                    units: "degrees",
                    geodetic: false,
                    outputFilename: "malahaxmi_map",
                    outputFormat: "pdf",
                    pageSize: print_paper_size,
                    layers: [
                        {
                            type: "WMS",
                            layers: alllayers,
                            baseURL: gurl_wms,
                            format: "image/jpeg",
                            styles: allstyles,
                        }

                    ],
                    pages: [
                        {

                            bbox: printBoundsTransform,
                            mapTitle: print_map_title,
                            scale: print_scale,
                            dpi: print_dpi,
                            geodetic: false,
                            strictEpsg4326: false,
                        }
                    ]
                };

                getDevices = async () => {
                            try {
                                const fetchResponse = await fetch(gurl_print, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({spec: json})
                                });
                                if (!fetchResponse.ok) throw new Error('Print failed.');
                                const blob = await fetchResponse.blob();
                                const link = document.createElement('a');
                                link.href = window.URL.createObjectURL(blob);
                                link.download = "IMIS-Map-" + moment().format('YYYYMMDD_HHmmss') + ".pdf";
                                link.click();
                                return e;
                            } catch (e) {
                                return e;
                            }

                        }
                        getDevices();

                $('#print_modal').modal('hide');

                ///****mapfish print

                //printMap(title, comment);

                return false;
            });

            // Add handler to print form submit
            $('#latlong_form').submit(function () {
                var lat = $('#point_latitude').val().trim();
                var long = $('#point_longitude').val().trim();
                //
                displayPointByCoordinates(lat, long);
                //printMap(title, comment);
                $('#coordinate_search_modal').modal('hide');
                return false;
            });

            function displayPointByCoordinates(lat, long) {
                if (eLayer.selected_pointcoordinate) {
                    eLayer.selected_pointcoordinate.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('selected_pointcoordinate', 'Selected Point Coordinate', layer);
                }

                // showExtraLayer('selected_pointcoordinate');

                var feature = new ol.Feature({
                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(long), parseFloat(lat)], 'EPSG:4326', 'EPSG:3857'))
                });

                var style = new ol.style.Style({
                    image: new ol.style.Icon({
                        anchor: [0.5, 1],
                        src: '{{ url("/")}}/img/containment-green.png'
                    })
                });

                feature.setStyle(style);

                eLayer.selected_pointcoordinate.layer.getSource().addFeature(feature);

                map.getView().setCenter(ol.proj.transform([parseFloat(long), parseFloat(lat)], 'EPSG:4326', 'EPSG:3857'));
            }

            function displayPointBufferByCoordinates(lat, long) {
                if (eLayer.selected_pointbuffercoordinate) {
                    eLayer.selected_pointbuffercoordinate.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('selected_pointbuffercoordinate', 'Selected Point Buffer Coordinate', layer);
                }

                // showExtraLayer('selected_pointbuffercoordinate');

                var feature = new ol.Feature({
                    geometry: new ol.geom.Point([parseFloat(long), parseFloat(lat)])
                });

                var style = new ol.style.Style({
                    image: new ol.style.Icon({
                        anchor: [0.5, 1],
                        src: '{{ url("/")}}/img/containment-green.png'
                    })
                });

                feature.setStyle(style);

                eLayer.selected_pointbuffercoordinate.layer.getSource().addFeature(feature);

            }

            // Add handler to remove markers button click
            $('#removemarkers_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';

                $.each(eLayer, function (key, value) {
                    value.layer.getSource().clear();
                });
                map.removeOverlay(staticMeasureTooltip);
            });

            function printMap(title, comment) {
                var mapWidth = $('#map').width();
                var mapHeight = $('#map').height();
                var body = $('body'),
                    mapContainer = $('div#map'),
                    mapContainerParent = mapContainer.parent(),

                    printContainer = $('<div>');

                body.prepend(printContainer);
                // printContainer.css('margin-left','-210px').append(mapContainer);
                printContainer.append(mapContainer);

                var content = body.children()
                    .not('script')
                    .not(printContainer)
                    .detach();

                var script_html = '<head>';
                script_html += '<style type="text/css">';
                script_html += 'body { margin: 0; }';
                script_html += ' ';
                script_html += '.print-table { height: 100%; }';
                script_html += ' ';
                script_html += '.print-logo, .print-title { display: inline-block; vertical-align: middle; }';
                script_html += ' ';
                script_html += '.print-logo { margin-right: 20px; }';
                script_html += ' ';
                script_html += '.print-title { font-size: 30px; font-weight: bold; color: #065d2c; }';
                script_html += ' ';
                script_html += '.print-date { margin-top: 20px; font-size: 20px; /*text-align: right;*/ }';
                script_html += ' ';
                script_html += '.print-map-title { margin-top: 40px; font-size: 24px; font-weight: bold; text-align: center; }';
                script_html += ' ';
                script_html += '.print-map-comment { margin-top: 20px; font-size: 20px; }';
                script_html += ' ';
                script_html += '@page { size: landscape; }';
                script_html += '@media print { .page-break { page-break-after: always; }  img { width: 100%; page-break-inside: avoid; } }';
                script_html += '</style>';
                script_html += '</head>';

                script_html += '<table class="print-table">';

                script_html += '<thead>';
                script_html += '<tr>';
                script_html += '<td>';

                script_html += '<div class="print-header">';
                script_html += '<div class="print-logo">';
                script_html += '<img src="{{ asset("/img/logo-imis.png") }}" />';
                script_html += '</div>';
                script_html += '<div class="print-title">';
                script_html += 'IMIS  Municipality';
                script_html += '</div>';
                script_html += '</div>';

                script_html += '<div class="print-date">';
                script_html += 'Date: ' + moment().format('YYYY-MM-DD');
                script_html += '</div>';

                if (title) {
                    script_html += '<div class="print-map-title">';
                    script_html += title;
                    script_html += '</div>';
                }

                if (comment) {
                    script_html += '<div class="print-map-comment">';
                    script_html += comment;
                    script_html += '</div>';
                }

                script_html += '</td>';
                script_html += '</tr>';
                script_html += '</thead>';

                script_html += '<tbody>';
                script_html += '<tr>';
                script_html += '<td>';
                script_html += printContainer.wrap('<div/>').parent().html();
                script_html += '\r\n</td>';
                script_html += '</tr>';
                script_html += '<tr>';
                script_html += '<td>';
                script_html += '</td>';
                script_html += '</tr>';
                script_html += '</tbody>';
                script_html += '</table>';
                script_html += '<div class="page-break"></div>';
                script_html += '\r\n<img class="print-legends" src="{{ asset("/img/map-legends/legends.PNG") }}">\r\n';
                script_html += '<div class="page-break"></div>';
                script_html += '\r\n<img class="print-legends" src="{{ asset("/img/map-legends/legends-3.PNG") }}">';
                $(this).unwrap('<div/>');


                var win = window.open();
                win.document.write(script_html);
                win.document.getElementById('map-right-sidebar').remove();

                win.document.getElementsByClassName('ol-overlaycontainer-stopevent')[0].remove();

                win.document.getElementById('map').style.position = 'relative';
                win.document.getElementById('map').style.width = mapWidth;
                win.document.getElementById('map').style.height = mapHeight;

                win.document.getElementsByTagName('canvas')[0].style.width = 'auto';
                win.document.getElementsByTagName('canvas')[0].style.height = 'auto';

                var oldCanvas = document.getElementsByTagName('canvas')[0];
                var newCanvas = win.document.getElementsByTagName('canvas')[0];
                var context = newCanvas.getContext('2d');

                context.drawImage(oldCanvas, 0, 0);

                //win.document.write('<img src="{{ asset("/pdf/legends-1.png") }}">\n');
                //win.document.write('<img src="{{ asset("/pdf/legends-2.png") }}">');

                if ((navigator.userAgent.indexOf("Opera") || navigator.userAgent.indexOf('OPR')) != -1) {
                    alert('Opera');
                } else if (navigator.userAgent.indexOf("Chrome") != -1) {
                    setTimeout(function () {

                        win.document.close();
                        win.focus();
                        win.print();
                        win.close();


                    }, 2000);
                } else if (navigator.userAgent.indexOf("Safari") != -1) {
                    setTimeout(function () {

                        win.document.close();
                        win.focus();
                        win.print();
                        win.close();

                    }, 2000);
                } else if (navigator.userAgent.indexOf("Firefox") != -1) {
                    win.close();
                } else {
                    setTimeout(function () {

                        win.document.close();
                        win.focus();
                        win.print();
                        win.close();
                        //win.close();
                        //return true;
                    }, 6000);
                }


                body.prepend(content);
                mapContainerParent.prepend(mapContainer);
                printContainer.remove();


            }

            // Add handler to nearest road button click
            $('#nearestroad_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'nearestroad_control') {
                    currentControl = '';

                } else {
                    currentControl = 'nearestroad_control';
                    $('#nearestroad_control').addClass('map-control-active');
                    map.on('singleclick', findNearestRoad);
                }
            });

            // Add handler to due buildings button click
            $('#duebuildings_control').click(function (e) {
                if ($('#duebuildings_control').attr("class").split(/\s+/).includes("collapsed")) {
                    e.preventDefault();
                    disableAllControls();
                    $('.map-control').removeClass('map-control-active');
                    currentControl = '';

                    displayDueBuildings();
                }
            });

            // Add handler to applications not reached treatment plant button click
            $('#applications_not_tp').click(function (e) {
                if ($('#applications_not_tp').attr("class").split(/\s+/).includes("collapsed")) {
                    e.preventDefault();
                    disableAllControls();
                    $('.map-control').removeClass('map-control-active');
                    currentControl = '';

                    displayApplicationNotTP();
                }else{
                    if (eLayer.application_notTP_markers) {
                        eLayer.application_notTP_markers.layer.getSource().clear();
                    }
                    if (eLayer.application_NotTPDate_containment_markers) {
                        eLayer.application_NotTPDate_containment_markers.layer.getSource().clear();
                    }
                    if (eLayer.application_NotTP_containment_markers) {
                        eLayer.application_NotTP_containment_markers.layer.getSource().clear();
                    }
                    $('#application_not_tp_date_field').val('');
                    $('#application_not_tp_month').val('');
                    $('#applicaion_not_tp_year').val('');
                }
            });
            // Add handler to application containments button click
            $('#applicationcontainments_control').click(function (e) {
                if ($('#applicationcontainments_control').attr("class").split(/\s+/).includes("collapsed")) {
                    e.preventDefault();
                    disableAllControls();
                    $('.map-control').removeClass('map-control-active');
                    currentControl = '';

                    displayApplicationContainments();
                }else{
                    e.preventDefault();
                    disableAllControls();
                    if (eLayer.application_containment_markers) {
                        eLayer.application_containment_markers.layer.getSource().clear();
                    }
                }
            });
            // Add handler to containments proposed to be emptied
            $('#containments_proposed_to_be_emptied').click(function(e) {
                    if (!$('#containments_proposed_to_be_emptied').attr("class").split(/\s+/).includes("collapsed")) {
                        if (eLayer.proposed_emptying_containments) {
                            eLayer.proposed_emptying_containments.layer.getSource().clear();
                        }
                    }
                });


            function getHeightWidthPrintBox(print_scale, print_paper_size, print_dpi, orientation){

                //  Calculation of print_dpi_x
                //  If dpi = 300
                //  300 DPI | PPI (Px per inch) = 1 / 300 inch per px
                //  inch to mm (per px)
                //  1 / 300 * 25.4 mm per px  (1 inch = 25.4 mm)
                //  = 0.846 mm (= 0.00085 m)

                var print_dpi_x_mm = 1 / print_dpi * 25.4 ; //mm per px
                var print_dpi_x_m = print_dpi_x_mm / 1000 ; //m per px

                if (print_paper_size == 'A4') {
                    // paper size in mm
                    var paper_length_mm = 297;
                    var paper_breadth_mm = 210;
                }
                if (print_paper_size == 'A3') {
                    // paper size in mm
                    var paper_length_mm = 420;
                    var paper_breadth_mm = 297;
                }

                if (orientation == 'landscape') {
                    var image_width_mm = paper_length_mm ;
                    var image_height_mm = paper_breadth_mm ;
                    var image_width_mm = 260;
                    var image_height_mm = 150;
                } else {
                    // orientation == 'portrait'
                    var image_width_mm = paper_breadth_mm ;
                    var image_height_mm = paper_length_mm ;
                }

                var map_real_width_m = print_scale * image_width_mm / 1000;
                var map_real_height_m = print_scale * image_height_mm / 1000;

                var dimension = new Array();
                    dimension['W'] = map_real_width_m;
                    dimension['H'] = map_real_height_m;

                return dimension;
            }


            /**
             * Elements that make up the popup for report.
             */
            var reportPopupContainer = document.getElementById('report-popup');
            var reportPopupContent = document.getElementById('report-popup-content');
            var reportPopupCloser = document.getElementById('report-popup-closer');


            /**
             * Create an overlay to anchor the popup to the map.
             */
            var reportPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: reportPopupContainer,
                autoPan: true,
                dragging: false,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(reportPopupContainer).show();

            map.addOverlay(reportPopupOverlay);


            /**
             * Add a click handler to hide the popup for report.
             * @return {boolean} Don't follow the href.
             */
            reportPopupCloser.onclick = function () {
                reportPopupOverlay.setPosition(undefined);
                reportPopupCloser.blur();
                return false;
            };

              /**
             * Elements that make up the popup for report.
             */
            var reportContainmentPopupContainer = document.getElementById('containment-report-popup');
            var reportContainmentPopupContent = document.getElementById('containment-report-popup-content');
            var reportContainmentPopupCloser = document.getElementById('containment-report-popup-closer');


            /**
             * Create an overlay to anchor the popup to the map.
             */
            var reportContainmentPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: reportContainmentPopupContainer,
                autoPan: true,
                dragging: false,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(reportContainmentPopupContainer).show();

            map.addOverlay(reportContainmentPopupOverlay);


            /**
             * Add a click handler to hide the popup for report.
             * @return {boolean} Don't follow the href.
             */
            reportContainmentPopupCloser.onclick = function () {
                reportContainmentPopupOverlay.setPosition(undefined);
                reportContainmentPopupCloser.blur();
                return false;
            };
            

            /**
             * Elements that make up the popup for feedback.
             */
            var feedbackPopupContainer = document.getElementById('feedback-popup');
            var feedbackPopupContent = document.getElementById('feedback-popup-content');
            var feedbackPopupCloser = document.getElementById('feedback-popup-closer');


            /**
             * Create an overlay to anchor the popup to the map.
             */
            var feedbackPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: feedbackPopupContainer,
                autoPan: true,
                dragging: false,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(feedbackPopupContainer).show();

            map.addOverlay(feedbackPopupOverlay);


            /**
             * Add a click handler to hide the popup for feedback.
             * @return {boolean} Don't follow the href.
             */
            feedbackPopupCloser.onclick = function () {
                feedbackPopupOverlay.setPosition(undefined);
                feedbackPopupCloser.blur();
                return false;
            };


            /**
             * Elements that make up the popup for Drain Potential Buildings Summary.
             */
            var drainPotentialSummaryPopupContainer = document.getElementById('drain-potentialSummary-popup');
            var drainPotentialSummaryuPopupContent = document.getElementById('drain-potentialSummary-popup-content');
            var drainPotentialSummaryPopupCloser = document.getElementById('drain-potentialSummary-popup-closer');

            /**
             * Create an overlay to anchor the Drain Potential Buildings Summary popup to the map.
             */
            var drainPotentialSummaryPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: drainPotentialSummaryPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(drainPotentialSummaryPopupContainer).show();

            map.addOverlay(drainPotentialSummaryPopupOverlay);

            /**
             * Add a click handler to hide the  popup for drain potential summary.
             * @return {boolean} Don't follow the href.
             */
            @can('Decision Map Tools')
            drainPotentialSummaryPopupCloser.onclick = function () {
                drainPotentialSummaryPopupOverlay.setPosition(undefined);
                drainPotentialSummaryPopupCloser.blur();
                return false;
            };
            @endcan
            /**
             * Elements that make up the popup for water body.
             */
            var waterBodyPopupContainer = document.getElementById('water-body-popup');
            var waterBodyPopupContent = document.getElementById('water-body-popup-content');
            var waterBodyPopupCloser = document.getElementById('water-body-popup-closer');

            /**
             * Create an overlay to anchor the water body popup to the map.
             */
            var waterBodyPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: waterBodyPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(waterBodyPopupContainer).show();

            map.addOverlay(waterBodyPopupOverlay);

            /**
             * Add a click handler to hide the  popup for water body.
             * @return {boolean} Don't follow the href.
             */
            @can('Summary Information Water Bodies Map Tools')
            waterBodyPopupCloser.onclick = function () {
                waterBodyPopupOverlay.setPosition(undefined);
                waterBodyPopupCloser.blur();
                return false;
            };
            @endcan
            /**
             * Elements that make up the popup for ward buildings.
             */
            var wardBuildingsPopupContainer = document.getElementById('ward-buildings-popup');
            var wardBuildingsPopupContent = document.getElementById('ward-buildings-popup-content');
            var wardBuildingsPopupCloser = document.getElementById('ward-buildings-popup-closer');

            /**
             * Create an overlay to anchor the ward buildings popup to the map.
             */
            var wardBuildingsPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: wardBuildingsPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(wardBuildingsPopupContainer).show();

            map.addOverlay(wardBuildingsPopupOverlay);

            /**
             * Add a click handler to hide the  popup for ward buildings.
             * @return {boolean} Don't follow the href.
             */
            @can('Summary Information Wards Map Tools')
            wardBuildingsPopupCloser.onclick = function () {
                wardBuildingsPopupOverlay.setPosition(undefined);
                wardBuildingsPopupCloser.blur();
                return false;
            };
            @endcan
            /**
             * Elements that make up the popup for buffer polygon.
             */
            var bufferPolygonPopupContainer = document.getElementById('buffer-polygon-popup');
            var bufferPolygonPopupContent = document.getElementById('buffer-polygon-popup-content');
            var bufferPolygonPopupCloser = document.getElementById('buffer-polygon-popup-closer');

            /**
             * Create an overlay to anchor the buffer polygon popup to the map.
             */
            var bufferPolygonPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: bufferPolygonPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(bufferPolygonPopupContainer).show();

            map.addOverlay(bufferPolygonPopupOverlay);

            /**
             * Add a click handler to hide the  popup for polygon buffer.
             * @return {boolean} Don't follow the href.
             */
            @can('Summary Information Buffer Map Tools')
            bufferPolygonPopupCloser.onclick = function () {
                bufferPolygonPopupOverlay.setPosition(undefined);
                bufferPolygonPopupCloser.blur();
                return false;
            };
            @endcan

            /**
             * Elements that make up the popup for road.
             */
            var roadPopupContainer = document.getElementById('road-popup');
            var roadPopupContent = document.getElementById('road-popup-content');
            var roadPopupCloser = document.getElementById('road-popup-closer');
            /**
             * Create an overlay to anchor the road building popup to the map.
             */
            var roadPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: roadPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(roadPopupContainer).show();

            map.addOverlay(roadPopupOverlay);


            /**
             * Add a click handler to hide the  popup for road.
             * @return {boolean} Don't follow the href.
             */
            @can('Summary Information Road Map Tools')
            roadPopupCloser.onclick = function () {
                roadPopupOverlay.setPosition(undefined);
                roadPopupCloser.blur();
                return false;
            };
            @endcan
            // Addded by Abhisan March 16
            var dragPan;
            map.getInteractions().forEach(function (interaction) {
                if (interaction instanceof ol.interaction.DragPan) {
                    dragPan = interaction;
                }
            });

            /*roadPopupContainer.addEventListener('mousedown', function (evt) {
                dragPan.setActive(false);
                roadPopupOverlay.set('dragging', true);

            });

            map.on('pointerdrag', function (evt) {
                if (roadPopupOverlay.get('dragging') === true) {
                    roadPopupOverlay.setPosition(evt.coordinate);

                }
            });*/

            //End addition by Abhisan
            /**
             * Elements that make up the popup for feature info.
             */
            var popupMarkerContainer = document.getElementById('popup-marker');
            var popupMarkerContent = document.getElementById('popup-marker-content');
            var popupMarkerCloser = document.getElementById('popup-marker-closer');


            /**
             * Create an overlay to anchor the popup to the map.
             */
            var popupMarkerOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: popupMarkerContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(popupMarkerContainer).show();

            map.addOverlay(popupMarkerOverlay);

            /**
             * Add a click handler to hide the popup.
             * @return {boolean} Don't follow the href.
             */
            popupMarkerCloser.onclick = function () {
                popupMarkerOverlay.setPosition(undefined);
                popupMarkerCloser.blur();
                return false;
            };
            /**
             * Elements that make up the popup for buildings to road.
             */
            var buildingsToRoadPopupContainer = document.getElementById('buildings-road-popup');
            var buildingsToRoadPopupContent = document.getElementById('buildings-road-popup-content');
            var buildingsToRoadPopupCloser = document.getElementById('buildings-road-popup-closer');

            /**
             * Create an overlay to anchor the buffer polygon popup to the map.
             */
            var buildingsToRoadPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: buildingsToRoadPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(buildingsToRoadPopupContainer).show();

            map.addOverlay(buildingsToRoadPopupOverlay);

            /**
             * Add a click handler to hide the  popup for polygon buffer.
             * @return {boolean} Don't follow the href.
             */
            @can('Export in Decision Map Tools')
            buildingsToRoadPopupCloser.onclick = function () {
                buildingsToRoadPopupOverlay.setPosition(undefined);
                buildingsToRoadPopupCloser.blur();
                return false;
            };
            @endcan
            // display popup on click
            map.on('click', function (evt) {
                var feature = map.forEachFeatureAtPixel(evt.pixel,
                    function (feature) {
                        return feature;
                    });
                if (feature && feature.get('application_id')) {
                    var coordinate = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
                    var innerHTML = 'Application ID : ' + feature.get('application_id');
                    if (feature.get('bin')) {
                        innerHTML += '<br>BIN : ' + feature.get('bin');
                    }
                    if (feature.get('service_provider')) {
                        innerHTML += '<br>Service Provider : ' + feature.get('service_provider');
                    }
                    if (feature.get('application_date')) {
                        innerHTML += '<br>Application Date : ' + feature.get('application_date');
                    }
                    if (feature.get('emptying_date')) {
                        innerHTML += '<br>Emptying Date : ' + feature.get('emptying_date');
                    }
                    popupMarkerContent.innerHTML = innerHTML;
                    popupMarkerOverlay.setPosition(evt.coordinate);
                }
            });


            /**
             * Elements that make up the popup for point-buffer.
             */
            var pointBufferPopupContainer = document.getElementById('point-buffer-popup');
            var pointBufferPopupContent = document.getElementById('point-buffer-popup-content');
            var pointBufferPopupCloser = document.getElementById('point-buffer-popup-closer');

            /**
             * Create an overlay to anchor the pointBuffer building popup to the map.
             */
            var pointBufferPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: pointBufferPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(pointBufferPopupContainer).show();

            map.addOverlay(pointBufferPopupOverlay);

            /**
             * Add a click handler to hide the  popup for pointBuffer.
             * @return {boolean} Don't follow the href.
             */
            @can('Summary Information Point Map Tools')
            pointBufferPopupCloser.onclick = function () {
                pointBufferPopupOverlay.setPosition(undefined);
                pointBufferPopupCloser.blur();
                return false;
            };
            @endcan
 /**
             * Elements that make up the popup for KML drag and drop.
             */
            var kmlDragDropPopupContainer = document.getElementById('kml-dragdrop-popup');
            var kmlDragDropPopupContent = document.getElementById('kml-dragdrop-popup-content');
            var kmlDragDropPopupCloser = document.getElementById('kml-dragdrop-popup-closer');

            /**
             * Create an overlay to anchor the KML drag and drop popup to the map.
             */
            var kmlDragDropPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: kmlDragDropPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(kmlDragDropPopupContainer).show();

            map.addOverlay(kmlDragDropPopupOverlay);

            /**
             * Add a click handler to hide the  popup for KML drag and drop.
             * @return {boolean} Don't follow the href.
             */
            kmlDragDropPopupCloser.onclick = function () {
                kmlDragDropPopupOverlay.setPosition(undefined);
                kmlDragDropPopupCloser.blur();
                return false;
            };

            
            var drag;

            /**
             * @constructor
             */
            var Drag = function () {

                ol.interaction.Pointer.call(this, {
                    handleDownEvent: Drag.prototype.handleDownEvent,
                    handleDragEvent: Drag.prototype.handleDragEvent,
                    handleMoveEvent: Drag.prototype.handleMoveEvent,
                    handleUpEvent: Drag.prototype.handleUpEvent
                });

                /**
                 * @type {ol.Pixel}
                 * @private
                 */
                this.coordinate_ = null;

                /**
                 * @type {string|undefined}
                 * @private
                 */
                this.cursor_ = 'pointer';

                /**
                 * @type {ol.Feature}
                 * @private
                 */
                this.feature_ = null;

                /**
                 * @type {string|undefined}
                 * @private
                 */
                this.previousCursor_ = undefined;

                this.layer = '';

                this.dragged_ = false;

            };
            ol.inherits(Drag, ol.interaction.Pointer);


            /**
             * @param {ol.MapBrowserEvent} evt Map browser event.
             * @return {boolean} `true` to start the drag sequence.
             */
            Drag.prototype.handleDownEvent = function (evt) {
                var map = evt.map;
                var layer = this.layer;

                var feature = map.forEachFeatureAtPixel(evt.pixel,
                    function (feature) {
                        if (eLayer[layer] && eLayer[layer].layer.getSource().getFeatures().indexOf(feature) != -1) {
                            return feature;
                        }
                    });

                if (feature) {
                    this.coordinate_ = evt.coordinate;
                    this.feature_ = feature;
                }

                return !!feature;
            };


            /**
             * @param {ol.MapBrowserEvent} evt Map browser event.
             */
            Drag.prototype.handleDragEvent = function (evt) {
                if (this.layer == 'export_polygon') {
                    $('#export-popup-closer').click();
                }

                this.dragged_ = true;

                var deltaX = evt.coordinate[0] - this.coordinate_[0];
                var deltaY = evt.coordinate[1] - this.coordinate_[1];

                var geometry = /** @type {ol.geom.SimpleGeometry} */
                    (this.feature_.getGeometry());
                geometry.translate(deltaX, deltaY);

                this.coordinate_[0] = evt.coordinate[0];
                this.coordinate_[1] = evt.coordinate[1];
            };


            /**
             * @param {ol.MapBrowserEvent} evt Event.
             */
            Drag.prototype.handleMoveEvent = function (evt) {
                if (this.cursor_) {
                    var map = evt.map;
                    var layer = this.layer;

                    var feature = map.forEachFeatureAtPixel(evt.pixel,
                        function (feature) {
                            if (eLayer[layer] && eLayer[layer].layer.getSource().getFeatures().indexOf(feature) != -1) {
                                return feature;
                            }
                        });
                    var element = evt.map.getTargetElement();
                    if (feature) {
                        if (element.style.cursor != this.cursor_) {
                            this.previousCursor_ = element.style.cursor;
                            element.style.cursor = this.cursor_;
                        }
                    } else if (this.previousCursor_ !== undefined) {
                        element.style.cursor = this.previousCursor_;
                        this.previousCursor_ = undefined;
                    }
                }
            };


            /**
             * @return {boolean} `false` to stop the drag sequence.
             */
            Drag.prototype.handleUpEvent = function () {
                if (this.dragged_) {
                    if (this.layer == 'export_polygon') {
                        displayExportPopup(this.feature_.getGeometry());
                    }
                    this.dragged_ = false;
                }

                this.coordinate_ = null;
                this.feature_ = null;
                return false;
            };

            // Add handler to feedback information tool button click
            $('#feedback_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'feedback_control') {
                    currentControl = '';

                } else {
                    currentControl = 'feedback_control';
                    $('#feedback_control').addClass('map-control-active');

                    if (!eLayer.feedback_report_polygon) {
                        var reportPolygonLayer = new ol.layer.Vector({
                            // visible: false,
                            source: new ol.source.Vector()
                        });

                        addExtraLayer('feedback_report_polygon', 'Feedback Report Polygon', reportPolygonLayer);
                    }

                    // map.removeInteraction(draw);
                    draw = new ol.interaction.Draw({
                        source: eLayer.feedback_report_polygon.layer.getSource(),
                        type: 'Polygon'
                    });

                    draw.on('drawstart', function (evt) {
                        eLayer.feedback_report_polygon.layer.getSource().clear();
                        reportPopupOverlay.setPosition(undefined);
                    });
                    draw.on('drawend', function (evt) {
                        // showExtraLayer('feedback_report_polygon');
                        var format = new ol.format.WKT();
                        var geom = format.writeGeometry(evt.feature.getGeometry().clone().transform('EPSG:3857', 'EPSG:4326'));
                        $('#report-export-geom').val(geom);
                        displayAjaxLoader();
                        var url = '{{ url("maps/feedback-report") }}';
                        $.ajax({
                            url: url,
                            type: 'post',
                            data: {geom: geom, "_token": "{{ csrf_token() }}"},
                            success: function (data) {
                                if (data['values'] != "") {

                                    var borderColor = data['borderColor'];
                                    var hoverBackgroundColor = data['hoverBackgroundColor'];
                                    var hoverBorderColor = data['hoverBorderColor'];
                                    var labels = data['labels'];
                                    var values = data['values'];
                                    var colors = data['colors'];
                                    var labels7 = data['labels7'];
                                    var values7 = data['values7'];
                                    var colors7 = data['colors7'];
                                    var uniqueContainCodeEmptiedCount = data['uniqueContainCodeEmptiedCount'];
                                    var feedbackCount = data['feedbackCount'];

                                    var html = '<div style="min-width: 500px;">';
                                                html += '<p class="h4 text-center">Feedbacks Chart</p>';
                                                html += '<div class="row"><div class="col-md-12"><ul class="list-group"><li class="list-group-item d-flex justify-content-between align-items-center">No. of containment emptied <span class="badge badge-primary badge-pill">'+uniqueContainCodeEmptiedCount+'</span></li><li class="list-group-item d-flex justify-content-between align-items-center">No. of feedbacks : <span class="badge badge-primary badge-pill">'+feedbackCount+'</span></li></ul></div></div>';
                                                html += '<div class="row">';
                                                html += '<div class="col-md-6"><b>FSM quality</b><canvas id="pie-chart" width="200" height="200"></canvas></div>';
                                                html += '<div class="col-md-6"><b>Sanitation Workers Wearing PPE</b><canvas id="pie-chart4" width="200" height="200"></canvas></div>';
                                                html += '</div>';
                                                html += '</div>';
                                                feedbackPopupContent.innerHTML = html;

                                    var chart = new Chart(document.getElementById("pie-chart"), {
                                        type: 'doughnut',
                                        data: {
                                            labels: labels,
                                            datasets: [{
                                                label: "FSM quality",
                                                backgroundColor: colors,
                                                data: values
                                            }]
                                        },
                                        options: {
                                            title: {
                                                display: true,
                                            }
                                        }
                                    });

                                    var chart = new Chart(document.getElementById("pie-chart4"), {
                                        type: 'doughnut',
                                        data: {
                                            labels: labels7,
                                            datasets: [{
                                                label: "Sanitation Workers Wearing PPE",
                                                backgroundColor: colors7,
                                                data: values7
                                            }]
                                        },
                                        options: {
                                            title: {
                                                display: true,
                                            }
                                        }
                                    });

                                } else {
                                    feedbackPopupContent.innerHTML = "No feedback data available.";
                                }
                                removeAjaxLoader();

                                feedbackPopupOverlay.setPosition(evt.feature.getGeometry().getInteriorPoint().getCoordinates());
                                $('#feedback_control').removeClass('map-control-active');
                                currentControl = '';
                                map.removeInteraction(draw);
                            },
                            error: function (data) {
                                displayAjaxError();
                            }
                        });
                    });

                    map.addInteraction(draw);
                }
            });

            // Add handler to report_control_summary_buffer information tool button click
            $('#report_control_summary_buffer').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'report_control_summary_buffer') {
                    currentControl = '';

                } else {
                    currentControl = 'report_control_summary_buffer';
                    $('#report_control_summary_buffer').addClass('map-control-active');

                    if (!eLayer.report_polygon_buffer) {
                        var reportPolygonBufferLayer = new ol.layer.Vector({
                            // visible: false,
                            source: new ol.source.Vector(),
                            style: new ol.style.Style({
                                stroke: new ol.style.Stroke({
                                    color: '#0000FF',
                                    width: 3
                                }),
                            })
                        });


                        addExtraLayer('report_polygon_buffer', 'Report Polygon Buffer', reportPolygonBufferLayer);
                    }

                    // map.removeInteraction(draw);
                    draw = new ol.interaction.Draw({
                        source: eLayer.report_polygon_buffer.layer.getSource(),
                        type: 'Polygon'
                    });

                    draw.on('drawstart', function (evt) {
                        eLayer.report_polygon_buffer.layer.getSource().clear();
                        reportPopupOverlay.setPosition(undefined);
                    });
                    draw.on('drawend', function (evt) {
                        var format = new ol.format.WKT();
                        var geom = format.writeGeometry(evt.feature.getGeometry().clone().transform('EPSG:3857', 'EPSG:4326'));
                        $('#buffer-distance-polygon').val('');

                        $('#polygon-geom').val(geom);
                        $('#polygon-coordinates').val(evt.feature.getGeometry().getInteriorPoint().getCoordinates());
                        $('#report_control_summary_buffer').removeClass('map-control-active');
                        currentControl = "";
                        map.removeInteraction(draw);
                        $('#popup-polygon-buffer').modal('show');
                        $(bufferPolygonPopupContainer).hide();
                        bufferPolygonPopupOverlay.setPosition(evt.feature.getGeometry().getInteriorPoint().getCoordinates());
                    });

                    map.addInteraction(draw);
                }
            });

              // Add handler to containments_emptied_monthly information tool button click
              $('#containments_emptied_monthly').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'containments_emptied_monthly') {
                    currentControl = '';
                    // $('#pan_control').addClass('map-control-active');
                } else {
                    currentControl = 'containments_emptied_monthly';
                    $('#containments_emptied_monthly').addClass('map-control-active');

                    if (!eLayer.containments_report_polygon) {
                        var reportContainmentPolygonLayer = new ol.layer.Vector({
                            // visible: false,
                            source: new ol.source.Vector(),
                            style: new ol.style.Style({
                                stroke: new ol.style.Stroke({
                                    color: '#0000FF',
                                    width: 3
                                }),
                            })
                        });


                        addExtraLayer('containments_report_polygon', 'Report Containment Polygon', reportContainmentPolygonLayer);
                    }

                    // map.removeInteraction(draw);
                    draw = new ol.interaction.Draw({
                        source: eLayer.containments_report_polygon.layer.getSource(),
                        type: 'Polygon'
                    });

                    draw.on('drawstart', function (evt) {
                        eLayer.containments_report_polygon.layer.getSource().clear();
                        reportContainmentPopupOverlay.setPosition(undefined);
                    });
                    draw.on('drawend', function (evt) {
                        // showExtraLayer('feedback_report_polygon');
                        var format = new ol.format.WKT();
                        var geom = format.writeGeometry(evt.feature.getGeometry().clone().transform('EPSG:3857', 'EPSG:4326'));
                        $('#containment_report_polygon').val(geom);
                        $('#containment_report_year').val("All");
                        displayAjaxLoader();
                        var url = '{{ url("maps/containment-report") }}';
                        $.ajax({
                            url: url,
                            type: 'post',
                            data: {geom: geom, "_token": "{{ csrf_token() }}"},
                            success: function (data) {
                                if (data['values'] != "") {
                                    var values = data['values'];
                                    var valuesAll = data['valuesAll'];
                                    var values_m_one = data['values_m_one'];
                                    var values_m_two = data['values_m_two'];
                                    var values_m_three = data['values_m_three'];
                                    var values_m_four = data['values_m_four'];
                                    var colors = data['colors'];
                                    var borderColor = data['borderColor'];
                                    var hoverBackgroundColor = data['hoverBackgroundColor'];
                                    var hoverBorderColor = data['hoverBorderColor'];
                                    var labels = data['labels'];
                                    var uniqueContainCodeEmptiedCount = data['year'];
                                    var feedbackCount = data['feedbackCount'];
                                    var current_year = data['current_year'];
                                    var from_year = data['from_year'];
                                    var html = '<div style="min-width: 500px;">';
                                        html += '<p class="h4 text-center">Containments Emptied</p>';

                                        // Row for dropdown and button
                                        html += '<div class="row justify-content-end align-items-center">';
                                        html += '<form class="form-inline d-flex align-items-center">';
                                       
                                        // Year selection dropdown
                                        html += '<div class="form-group d-flex align-items-center pr-3">';
                                        html += '<label class="p-2" for="year_select">Select Year</label>';
                                        html += '<select id="year_select" class="form-control">';
                                        html += '<option value="All">All Year</option>';
                                        for (var i = from_year; i <= current_year; i++) {
                                            html += '<option value="' + i + '">' + i + '</option>';
                                        }
                                        html += '</select>';
                                        html += '<button id="downloadChart" class=" btn btn-primary"><i class="fa fa-download"></i></button>';

                                        html += '</div>';

                                        html += '</form>';
                                        html += '</div>';

                                        // Chart canvas row
                                        html += '<div class="row mt-3">';
                                        html += '<div class="col-md-12"><canvas id="pie-chart" width="200" height="200"></canvas></div>';
                                        html += '</div>';

                                        html += '</div>'; // Closing main container div

                                                reportContainmentPopupContent.innerHTML = html;
                                    var chart = new Chart(document.getElementById("pie-chart"), {
                                        type: 'bar',
                                        data: {
                                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', "Nov", "Dec"],
                                            datasets: [{
                                                label: "No. of containments",
                                                backgroundColor: "rgba(90, 155, 212,0.2)",
                                                    borderColor: "rgba(90, 155, 212,1)",
                                                    borderWidth: 1,
                                                    hoverBackgroundColor: "rgba(90, 155, 212,0.4)",
                                                    hoverBorderColor: "rgba(90, 155, 212,1)",
                                                borderWidth: 1,
                                                data: valuesAll,
                                                skipNull: true,
                                            }]
                                        },
                                         options : {
                                        scales: {
                                            yAxes: [{
                                                ticks: {
                                                    beginAtZero: true,
                                                    userCallback: function(label, index, labels) {
                                                        // when the floored value is the same as the value we have a whole number
                                                        if (Math.floor(label) === label) {
                                                            return label;
                                                        }

                                                    },
                                                }
                                            }],
                                        },
                                    }
                                    });
                                    // Chart download functionality
                                    document.getElementById("downloadChart").addEventListener("click", function(e) {
                                         e.preventDefault();
                                        var link = document.createElement("a");
                                        link.href = document.getElementById("pie-chart").toDataURL("image/png");
                                        link.download = "Containment Emptied Chart.png";
                                        link.click();
                                    });
                                   $( "#year_select" ).change(function() {
                                       $('#containment_report_year').val($(this).val());
                                       if($(this).val() == new Date().getFullYear()){
                                           chart.data.datasets[0].data = values;
                                       } else if($(this).val() == (new Date().getFullYear())-1){
                                           chart.data.datasets[0].data = values_m_one;
                                       } else if($(this).val() == (new Date().getFullYear())-2){
                                           chart.data.datasets[0].data = values_m_two;
                                       } else if($(this).val() == (new Date().getFullYear())-3){
                                           chart.data.datasets[0].data = values_m_three;
                                       } else if($(this).val() == (new Date().getFullYear())-4){
                                           chart.data.datasets[0].data = values_m_four;
                                       } else{
                                           chart.data.datasets[0].data = valuesAll;
                                       }
                                      chart.update();
                                    });
                                

                                } else {
                                    reportContainmentPopupContent.innerHTML = "No Containment data available.";
                                }
                                removeAjaxLoader();

                                reportContainmentPopupOverlay.setPosition(evt.feature.getGeometry().getInteriorPoint().getCoordinates());
                                $('#containments_emptied_monthly').removeClass('map-control-active');
                                currentControl = '';
                                map.removeInteraction(draw);
                            },
                            error: function (data) {
                                displayAjaxError();
                            }
                        });
                    
                       
                       
                       
                    });

                    map.addInteraction(draw);
                }
            });


            //report_control_summary_buffer
            ///DEM Chart
            // Add handler to road building button click
            $('#dem_profile').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'dem_profile') {
                    currentControl = '';

                } else {
                    currentControl = 'dem_profile';
                    $('#dem_profile').addClass('map-control-active');
                    //map.on('pointermove', hoverOnPopulationsHandler);

                    var DEMChart = document.getElementById("DEMChart");
                    const mapDraw = document.getElementById("dem_profile");
                    let myChart;

                    draw = new ol.interaction.Draw({
                        type: "LineString",
                        maxPoints: 2
                    });
                    map.addInteraction(draw);
                    draw.on("drawend", function (e) {
                        var format = new ol.format.WKT();
                        var geom = format.writeGeometry(
                            e.feature
                                .getGeometry()
                                .clone()
                                .transform("EPSG:3857", "EPSG:4326")
                        );
                        displayAjaxLoader();
                        var url = '{{ url("get_DEM_line_geom") }}';
                        $.ajax({
                            url: url,
                            type: "POST",
                            data: {geom: geom, "_token": "{{ csrf_token() }}"},
                            success: function (data) {
                                let elevation = new Array();
                                for (let i = 0; i < data.length; i++) {
                                    elevation[i] = data[i];
                                }

                                let labelForChart = new Array();
                                for (let i = 0; i < elevation.length; i++) {
                                    labelForChart[i] = i * 12; //pixel size
                                }
                                var speedData = {
                                    labels: labelForChart,
                                    datasets: [
                                        {
                                            label: "Elevation",
                                            data: elevation,
                                            borderColor: "#3e95cd"
                                        }
                                    ]
                                };
                                myChart = new Chart(DEMChart, {
                                    type: "line",
                                    data: speedData
                                });
                                $("#DEMModal").modal();
                                //map.removeInteraction(draw);
                                removeAjaxLoader();
                            }
                        });

                    });
                }
            });

            $("#DEMModal").on("hidden.bs.modal", function (event) {
                myChart.destroy();
            });


// Add handler to Population information tool button click
            $('#areapopulation_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'areapopulation_control') {
                    currentControl = '';

                } else {
                    currentControl = 'areapopulation_control';
                    $('#areapopulation_control').addClass('map-control-active');

                    if (!eLayer.areapopulation_polygon) {
                        var populationPolygonLayer = new ol.layer.Vector({
                            // visible: false,
                            source: new ol.source.Vector()
                        });

                        addExtraLayer('areapopulation_polygon', 'Population Polygon', populationPolygonLayer);
                    }

                    // map.removeInteraction(draw);
                    draw = new ol.interaction.Draw({
                        source: eLayer.areapopulation_polygon.layer.getSource(),
                        type: 'Polygon'
                    });

                    draw.on('drawstart', function (evt) {
                        eLayer.areapopulation_polygon.layer.getSource().clear();
                        populationPopupOverlay.setPosition(undefined);
                    });
                    draw.on('drawend', function (evt) {
                        // showExtraLayer('areapopulation_polygon');
                        var format = new ol.format.WKT();
                        var geom = format.writeGeometry(evt.feature.getGeometry().clone().transform('EPSG:3857', 'EPSG:4326'));

                        $('#population-export-geom').val(geom);
                        displayAjaxLoader();
                        var url = '{{ url("maps/area-population-Polygon-sum") }}';
                        $.ajax({
                            url: url,
                            type: 'post',
                            data: {geom: geom, "_token": "{{ csrf_token() }}"},
                            success: function (data) {
                                removeAjaxLoader();
                                populationPopupContent.innerHTML = data;
                                populationPopupOverlay.setPosition(evt.feature.getGeometry().getInteriorPoint().getCoordinates());
                            },
                            error: function (data) {
                                displayAjaxError();
                            }
                        });
                        $('#areapopulation_control').removeClass('map-control-active');
                        currentControl = "";
                        map.removeInteraction(draw);
                    });

                    map.addInteraction(draw);
                }
            });

            //Add handler to find wms_layer click
            $('#wms_layer').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';
                $("#wmsModal").modal('show');
            });
            

            // Handler for building structype checkbox change
            $('#building_structype_checkbox_container').on('change', 'input[type=checkbox]', function () {

                    var checkedList = [];
                    $('#building_structype_checkbox_container input[type=checkbox]:checked').each(function () {
                        checkedList.push("structure_type_id = '" + $(this).attr('name') + "'");
                    });

                    if (checkedList.length > 0) {
                        mFilter.buildings_layer_structure_type = '(' + checkedList.join(' OR ') + ')';
                    } else {
                        mFilter.buildings_layer_structure_type = '';
                    }
                    updateAllCQLFiltersParams();
                    showLayer('buildings_layer');
                    });


            // Handler for building tax payment checkbox change
            $('#building_tax_payment_checkbox_container').on('change', 'input[type=checkbox]', function () {

                var checkedList = [];
                $('#building_tax_payment_checkbox_container input[type=checkbox]:checked').each(function () {
                    checkedList.push("due_year = '" + $(this).attr('name') + "'");
                });

                if (checkedList.length > 0) {
                    mFilter.buildings_tax_status_layer = '(' + checkedList.join(' OR ') + ')';
                } else {
                    mFilter.buildings_tax_status_layer = '';
                }
                updateAllCQLFiltersParams();
                showLayer('buildings_tax_status_layer');
            });

            // Handler for water supply payment checkbox change
            $('#water_supply_payment_checkbox_container').on('change', 'input[type=checkbox]', function () {
                var checkedList = [];
                $('#water_supply_payment_checkbox_container input[type=checkbox]:checked').each(function () {
                     checkedList.push("due_year = '" + $(this).attr('name') + "'");
                });
                if (checkedList.length > 0) {
                    mFilter.buildings_water_payment_status_layer = '(' + checkedList.join(' OR ') + ')';
                } else {
                    mFilter.buildings_water_payment_status_layer = '';
                }

                updateAllCQLFiltersParams();
                showLayer('buildings_water_payment_status_layer');
            });

            $('#print_scale').on('change', function() {
                printBox();
                });

            $('#box_orientation').on('change', function() {
                printBox();

            });

            $('#print_paper_size').on('change', function() {
                printBox();
            });


          function printBox(){
                let print_scale = $("#print_scale").val();
                let print_paper_size = $("#print_paper_size").val();
                let print_dpi = $("#print_dpi").val();
                let orientation = $("#box_orientation").val();
                //**calculate coordinates of rectangular print box */
                var dimension = getHeightWidthPrintBox(print_scale, print_paper_size, print_dpi, orientation);
                var center = map.getView().getCenter();

                var x1 = ( center[0] - ( dimension['W'] / 2 ) );
                var y1 = ( center[1] - ( dimension['H'] / 2) );
                var x2 = ( center[0] + ( dimension['W'] / 2) )
                var y2 = ( center[1] - ( dimension['H'] / 2) );
                var x3 = ( center[0] + ( dimension['W'] / 2) )
                var y3 = ( center[1] + ( dimension['H'] / 2) );
                var x4 = ( center[0] - ( dimension['W'] / 2) )
                var y4 = ( center[1] + ( dimension['H'] / 2) );

                var ring = [
                                [x1,y1], [x2,y2],
                                [x3,y3], [x4,y4],
                                [x1,y1]
                           ];

                if (eLayer.print_box_layer) {
                    eLayer.print_box_layer.layer.getSource().clear();
                } else {
                   var layer = new ol.layer.Vector({
                                    // visible: false,
                                    source: new ol.source.Vector()
                                });
                    addExtraLayer('print_box_layer', 'Print Box', layer);
                }

                var feature = new ol.Feature({
                    geometry: new ol.geom.Polygon([ring])
                });

                var style = new ol.style.Style({
                            fill: new ol.style.Fill({
                                color: [0, 0, 255, 0.3]
                            }),
                            stroke: new ol.style.Stroke({
                                color: [255, 160, 25, 1],
                                width: 0.6,
                            }),
                             zIndex:999999,
                        });
                feature.setStyle(style);
                eLayer.print_box_layer.layer.getSource().addFeature(feature);
                var control = new ol.interaction.Translate({
                    features: new ol.Collection([feature])
                });
                map.addInteraction(control);
          }
            /**
             * Elements that make up the popup for export.
             */
            var exportPopupContainer = document.getElementById('export-popup');
            var exportPopupContent = document.getElementById('export-popup-content');
            var exportPopupCloser = document.getElementById('export-popup-closer');

            /**
             * Create an overlay to anchor the popup to the map.
             */
            var exportPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: exportPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(exportPopupContainer).show();

            map.addOverlay(exportPopupOverlay);

            /**
             * Add a click handler to hide the popup for export.
             * @return {boolean} Don't follow the href.
             */
            exportPopupCloser.onclick = function () {
                exportPopupOverlay.setPosition(undefined);
                exportPopupCloser.blur();
                return false;
            };

            // Add handler to report information tool button click
            $('#export_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'export_control') {
                    currentControl = '';

                } else {
                    currentControl = 'export_control';
                    $('#export_control').addClass('map-control-active');
                     if (eLayer.export_polygon) {
                        eLayer.export_polygon.layer.getSource().clear();
                    } else {
                        var exportPolygonLayer = new ol.layer.Vector({
                            // visible: false,
                            source: new ol.source.Vector()
                        });

                        addExtraLayer('export_polygon', 'Export Polygon', exportPolygonLayer);
                    }

                    // map.removeInteraction(draw);
                    draw = new ol.interaction.Draw({
                        source: eLayer.export_polygon.layer.getSource(),
                        type: 'Polygon'
                    });

                    draw.on('drawstart', function (evt) {
                        eLayer.export_polygon.layer.getSource().clear();
                        exportPopupOverlay.setPosition(undefined);
                    });
                    draw.on('drawend', function (evt) {
                        displayExportPopup(evt.feature.getGeometry());
                        $('#export_control').removeClass('map-control-active');
                        currentControl = "";
                        map.removeInteraction(draw);
                    });

                    map.addInteraction(draw);
                    drag = new Drag();
                    drag.layer = 'export_polygon';
                    map.addInteraction(drag);
                }
            });

            /**
             * Elements that make up the popup for CSV export.
             */
            var exportCsvPopupContainer = document.getElementById('export-csv-popup');
            var exportCsvPopupContent = document.getElementById('export-csv-popup-content');
            var exportCsvPopupCloser = document.getElementById('export-csv-popup-closer');

            /**
             * Create an overlay to anchor the popup to the map.
             */
            var exportCsvPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: exportCsvPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(exportCsvPopupContainer).show();

            map.addOverlay(exportCsvPopupOverlay);

            /**
             * Add a click handler to hide the popup for export.
             * @return {boolean} Don't follow the href.
             */
            exportCsvPopupCloser.onclick = function () {
                exportCsvPopupOverlay.setPosition(undefined);
                exportCsvPopupCloser.blur();
                return false;
            };

            /**
             * Elements that make up the popup for population.
             */
            var populationPopupContainer = document.getElementById('population-popup');
            var populationPopupContent = document.getElementById('population-popup-content');
            var populationPopupCloser = document.getElementById('population-popup-closer');

            /**
             * Create an overlay to anchor the population popup to the map.
             */
            var populationPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: populationPopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(populationPopupContainer).show();

            map.addOverlay(populationPopupOverlay);
            /**
             * Add a click handler to hide the popup for population.
             * @return {boolean} Don't follow the href.
             */
            populationPopupCloser.onclick = function () {
                populationPopupOverlay.setPosition(undefined);
                populationPopupCloser.blur();
                return false;
            };
            /**
             * Elements that make up the popup for population.
             */
            var roadInaccessiblePopupContainer = document.getElementById('road-inaccessible-popup');
            var roadInaccessiblePopupContent = document.getElementById('road-inaccessible-popup-content');
            var roadInaccessiblePopupCloser = document.getElementById('road-inaccessible-popup-closer');
            /**
             * Create an overlay to anchor the road inaccessible popup to the map.
             */
            var roadInaccessiblePopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: roadInaccessiblePopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(roadInaccessiblePopupContainer).show();

            map.addOverlay(roadInaccessiblePopupOverlay);
            /**
             * Add a click handler to hide the popup for population.
             * @return {boolean} Don't follow the href.
             */
            roadInaccessiblePopupCloser.onclick = function () {
                roadInaccessiblePopupOverlay.setPosition(undefined);
                roadInaccessiblePopupCloser.blur();
                return false;
            };
            /**
             * Elements that make up the popup for waterbody inaccessible.
             */
            var waterbodyInaccessiblePopupContainer = document.getElementById('waterbody-inaccessible-popup');
            var waterbodyInaccessiblePopupContent = document.getElementById('waterbody-inaccessible-popup-content');
            var waterbodyInaccessiblePopupCloser = document.getElementById('waterbody-inaccessible-popup-closer');
            /**
             * Create an overlay to anchor the waterbody inaccessible popup to the map.
             */
            var waterbodyInaccessiblePopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: waterbodyInaccessiblePopupContainer,
                autoPan: true,
                stopEvent: false,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(waterbodyInaccessiblePopupContainer).show();

            map.addOverlay(waterbodyInaccessiblePopupOverlay);
            /**
             * Add a click handler to hide the popup for population.
             * @return {boolean} Don't follow the href.
             */
            waterbodyInaccessiblePopupCloser.onclick = function () {
                waterbodyInaccessiblePopupOverlay.setPosition(undefined);
                waterbodyInaccessiblePopupCloser.blur();
                return false;
            };
            // Add handler to export csv for buidins to update tax zones information tool button click
            $('#update_tax_zone').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'update_tax_zone') {
                    currentControl = '';

                } else {
                    currentControl = 'update_tax_zone';
                    $('#update_tax_zone').addClass('map-control-active');
                     if (eLayer.export_tax_polygon) {
                        eLayer.export_tax_polygon.layer.getSource().clear();
                    } else {
                        var exportPolygonLayer = new ol.layer.Vector({
                            // visible: false,
                            source: new ol.source.Vector()
                        });
                        addExtraLayer('export_tax_polygon', 'Export Polygon', exportPolygonLayer);
                    }

                    // map.removeInteraction(draw);
                    draw = new ol.interaction.Draw({
                        source: eLayer.export_tax_polygon.layer.getSource(),
                        type: 'Polygon'
                    });

                    draw.on('drawstart', function (evt) {
                        eLayer.export_tax_polygon.layer.getSource().clear();
                        exportPopupOverlay.setPosition(undefined);
                    });
                    draw.on('drawend', function (evt) {
                        var format = new ol.format.WKT();
                        var geom = format.writeGeometry(evt.feature.getGeometry().clone().transform('EPSG:3857', 'EPSG:4326'));
                        displayExportPopupTaxZone(evt.feature.getGeometry());
                        $('#building-with-owner-polygon-geom').val(geom);
                        currentControl = '';
                        $('#update_tax_zone').removeClass('map-control-active');
                        map.removeInteraction(draw);
                    });

                    map.addInteraction(draw);
                    drag = new Drag();
                    drag.layer = 'export_tax_polygon';
                    map.addInteraction(drag);
                }
            });

            // Add handler to drain building button click
            $('#drainbuildings_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'drainbuildings_control') {
                    currentControl = '';

                } else {
                    currentControl = 'drainbuildings_control';
                    $('#drainbuildings_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnDrainHandler);
                    map.on('singleclick', displayDrainBuildings);
                }
            });

            // Add handler to road building button click
            $('#roadbuildings_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'roadbuildings_control') {
                    currentControl = '';

                } else {
                    currentControl = 'roadbuildings_control';
                    $('#roadbuildings_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnRoadBuildingHandler);
                    map.on('singleclick', displayRoadBuildings);
                }
            });

            // Add handler to containment to building button click
            $('#containmentbuilding_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'containmentbuilding_control') {
                    currentControl = '';

                } else {
                    currentControl = 'containmentbuilding_control';
                    $('#containmentbuilding_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnContainmentHandler);
                    map.on('singleclick', displayContainmentToBuildings);

                }
            });

            // Add handler to building to containment button click
            $('#buildingcontainment_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'buildingcontainment_control') {
                    currentControl = '';

                } else {
                    currentControl = 'buildingcontainment_control';
                    $('#buildingcontainment_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnBuildingContainmentHandler);
                    map.on('singleclick', displayBuildingToContainment);
                }
            });

            // Add handler to building to containment button click
            $('#associatedtomain_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'associatedtomain_control') {
                    currentControl = '';

                } else {
                    currentControl = 'associatedtomain_control';
                    $('#associatedtomain_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnBuildingContainmentHandler);
                    map.on('singleclick', displayAssociatedToMainBuilding);
                }
            });

            // Add handler to drain potential building button click
            $('#drainpotential_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'drainpotential_control') {
                    currentControl = '';

                } else {
                    currentControl = 'drainpotential_control';
                    $('#drainpotential_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnDrainHandler);
                    map.on('singleclick', displayDrainPotentialBuildings);
                }
            });

            // Add handler to buildings waterbodies button click
            $('#buildingswaterbodies_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'buildingswaterbodies_control') {
                    currentControl = '';

                } else {
                    currentControl = 'buildingswaterbodies_control';
                    $('#buildingswaterbodies_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnWaterBodiesHandler);
                    map.on('singleclick', displayWaterBodiesBuildings);
                }
            });

            // Add handler to buildings roads button click
            $('#buildingsroads_control').click(function (e) {

                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'buildingsroads_control') {
                    currentControl = '';

                } else {
                    currentControl = 'buildingsroads_control';
                    $('#buildingsroads_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnRoadsHandler);
                    map.on('singleclick', displayRoadsBuildings);
                }
            });

            // Add handler to buildings ward tool button click
            $('#buildingswards_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'buildingswards_control') {

                    currentControl = '';

                } else {
                    currentControl = 'buildingswards_control';
                    $('#buildingswards_control').addClass('map-control-active');
                    map.on('pointermove', hoverOnWardsBuildingHandler);
                    map.on('singleclick', displayWardsBuildings);

                }
            });

            // Add handler to PTCT network button click
            $('#ptct_network').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                if (currentControl == 'ptct_network') {
                    currentControl = '';

                } else {
                    currentControl = 'ptct_network';
                    $('#ptct_network').addClass('map-control-active');
                    map.on('pointermove', hoverOnPTCTHandler);
                    map.on('singleclick', displayBuildingsToPTCT);
                }
            });

            function displayExportPopup(geometry) {
                $('#export_overlay').val('');

                $('#export-csv-btn, #export-kml-btn, #export-shape-btn').off('click');

                $('#export-csv-btn').on('click', function () {
                    openExportLink(geometry, 'CSV')
                });

                $('#export-kml-btn').on('click', function () {
                    openExportLink(geometry, 'KML')
                });

                $('#export-shape-btn').on('click', function () {
                    openExportLink(geometry, 'SHAPE-ZIP')
                });

                exportPopupOverlay.setPosition(geometry.getInteriorPoint().getCoordinates());
            }

            function displayExportPopupTaxZone(geometry) {

                $('#export-buildings-csv-btn').on('click', function () {
                    var geom = $('#building-with-owner-polygon-geom').val();
                    openBuildingsExportCsvLink(geom, 'CSV')
                });

                exportCsvPopupOverlay.setPosition(geometry.getInteriorPoint().getCoordinates());
            }


            function openExportLink(geometry, outputFormat) {
                var selectedLayer = $('#export_overlay').val();

                if (!selectedLayer) {
                    Swal.fire({
                        title: "{{ __('Please select an overlay!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                var format = new ol.format.WKT();
                var geom = format.writeGeometry(geometry.clone().transform('EPSG:3857', 'EPSG:4326'));

                var exportLink = gurl_wfs + '?request=GetFeature&service=WFS&version=1.0.0&typeName=' + workspace + ':' + selectedLayer + '&CQL_FILTER=deleted_at is null AND WITHIN(geom, ' + geom + ')';

                csvdata(outputFormat, selectedLayer, exportLink);
            }

            function openBuildingsExportCsvLink(geom, outputFormat) {

                var layers = [];
                var format = new ol.format.WKT();
                var exportLink = '{!! url("maps/export-buildings-taxzone") !!}/' + geom;

                window.open(exportLink);
            }

            var hoverOnDrainHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                var pixel = map.getEventPixel(evt.originalEvent);
                var hit = map.forEachLayerAtPixel(pixel, function (layer) {
                    if (layer.get('name') == 'sewerlines_layer') {
                        return true;
                    }
                });
                map.getTargetElement().style.cursor = hit ? 'pointer' : '';
            };

            var hoverOnRoadBuildingHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                var pixel = map.getEventPixel(evt.originalEvent);
                var hit = map.forEachLayerAtPixel(pixel, function (layer) {
                    if (layer.get('name') == 'roads_width_zoom_layer') {
                        return true;
                    }
                });
                map.getTargetElement().style.cursor = hit ? 'pointer' : '';
            };

            // Handler for hover events on containment layers
            var hoverOnContainmentHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                var pixel = map.getEventPixel(evt.originalEvent);
                var hit = map.forEachLayerAtPixel(pixel, function (layer) {
                    if (layer.get('name') == 'containments_layer') {
                        return true;
                    }
                });
                map.getTargetElement().style.cursor = hit ? 'pointer' : '';
            };

             // Handler for hover events on Buildings layers
            var hoverOnBuildingContainmentHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                var pixel = map.getEventPixel(evt.originalEvent);
                var hit = map.forEachLayerAtPixel(pixel, function (layer) {
                    if (layer.get('name') == 'buildings_layer') {
                        return true;
                    }
                });
                map.getTargetElement().style.cursor = hit ? 'pointer' : '';
            };

            var hoverOnWaterBodiesHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                var pixel = map.getEventPixel(evt.originalEvent);
                var hit = map.forEachLayerAtPixel(pixel, function (layer) {
                    if (layer.get('name') == 'waterbodys_layer') {
                        return true;
                    }
                });
                map.getTargetElement().style.cursor = hit ? 'pointer' : '';
            };


            var hoverOnRoadsHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                var pixel = map.getEventPixel(evt.originalEvent);
                var hit = map.forEachLayerAtPixel(pixel, function (layer) {
                    if (layer.get('name') == 'roads_width_zoom_layer') {
                        return true;
                    }
                });
                map.getTargetElement().style.cursor = hit ? 'pointer' : '';
            };

            var hoverOnWardsBuildingHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                var pixel = map.getEventPixel(evt.originalEvent);
                var hit = map.forEachLayerAtPixel(pixel, function (layer) {
                    if (layer.get('name') == 'wards_layer') {
                        return true;
                    }
                });
                map.getTargetElement().style.cursor = hit ? 'pointer' : '';
            };


            var hoverOnPTCTHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                var pixel = map.getEventPixel(evt.originalEvent);
                var hit = map.forEachLayerAtPixel(pixel, function (layer) {
                    if (layer.get('name') == 'toilets_layer') {
                        return true;
                    }
                });
                map.getTargetElement().style.cursor = hit ? 'pointer' : '';
            };

            // Display markers on buildings associated to drain
            function displayDrainBuildings(evt) {
                if (eLayer.selected_drains) {
                    eLayer.selected_drains.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#FF0000',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('selected_drains', 'Selected Roads', layer);
                }

                // showExtraLayer('selected_drains');

                if (eLayer.drain_buildings) {
                    eLayer.drain_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('drain_buildings', 'Drain Buildings', layer);
                }

                // showExtraLayer('drain_buildings');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':sewerlines_layer',
                        'FEATURE_COUNT': 10
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                displayAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {
                        if (data && data.features && Array.isArray(data.features)) {
                            if (data.features.length > 0) {
                                var drainCodes = [];
                                for (var i = 0; i < data.features.length; i++) {
                                    drainCodes.push(data.features[i].properties.code);
                                }

                                var url1 = '{{ url("maps/drain-buildings") }}';
                                $.ajax({
                                    url: url1,
                                    type: 'post',
                                    data: {
                                        drain_codes: drainCodes,
                                        "_token": "{{ csrf_token() }}"
                                    },
                                    success: function (data1) {
                                        eLayer.selected_drains.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(data));

                                        if (data1 && Array.isArray(data1)) {
                                            var format = new ol.format.WKT();

                                            for (var i = 0; i < data1.length; i++) {
                                                var feature = format.readFeature(data1[i].geom, {
                                                    dataProjection: 'EPSG:4326',
                                                    featureProjection: 'EPSG:3857'
                                                });

                                                eLayer.drain_buildings.layer.getSource().addFeature(feature);
                                            }
                                        }

                                        removeAjaxLoader();
                                    },
                                    error: function (data1) {
                                        displayAjaxError();
                                    }
                                });
                            } else {
                                displayAjaxErrorModal(@json(__('Sewer Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                        $('#drainbuildings_control').removeClass('map-control-active');
                        currentControl = "";
                        map.un('pointermove', hoverOnDrainHandler);
                        map.un('singleclick', displayDrainBuildings);
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                });
            }


            // Display markers on buildings associated to road
            function displayRoadBuildings(evt) {
                if (eLayer.selected_road_buildings) {
                    eLayer.selected_road_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#ffff00',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('selected_road_buildings', 'Selected Roads', layer);
                }

                // showExtraLayer('selected_road_buildings');

                if (eLayer.road_to_buildings) {
                    eLayer.road_to_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#ffff00',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('road_to_buildings', 'Road Buildings', layer);
                }


                // showExtraLayer('road_to_buildings');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':roadlines_layer',
                        'FEATURE_COUNT': 10
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                displayAjaxLoader();

                $.ajax({
                    url: url,
                    type: 'get',

                    success: function (response) {
                        if (response && response.features && Array.isArray(response.features)) {
                            if (response.features.length > 0) {
                                var roadCodes = [];
                                for (var i = 0; i < response.features.length; i++) {
                                    roadCodes.push(response.features[i].properties.code);
                                }
                                $('#road_codes').val(roadCodes);
                                var url1 = '{{ url("maps/buildings-to-road") }}';
                                $.ajax({
                                    url: url1,
                                    type: 'post',
                                    data: {
                                        road_codes: roadCodes,
                                        "_token": "{{ csrf_token() }}",
                                    },
                                    success: function (Response) {
                                        eLayer.selected_road_buildings.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(response));
                                        buildingsToRoadPopupContent.innerHTML = Response['popContentsHtml'];
                                        buildingsToRoadPopupOverlay.setPosition(evt.coordinate);
                                        $(buildingsToRoadPopupContainer).show();

                                        data1 = Response['buildings'];

                                        if (data1 && Array.isArray(data1)) {
                                            var format = new ol.format.WKT();

                                            for (var i = 0; i < data1.length; i++) {
                                                var feature = format.readFeature(data1[i].geom, {
                                                    dataProjection: 'EPSG:4326',
                                                    featureProjection: 'EPSG:3857'
                                                });

                                                eLayer.road_to_buildings.layer.getSource().addFeature(feature);
                                            }
                                        }


                                        removeAjaxLoader();
                                    },
                                    error: function (data1) {
                                        displayAjaxError();
                                    }
                                });
                            } else {
                                displayAjaxErrorModal(@json(__('Road Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                        $('#roadbuildings_control').removeClass('map-control-active');
                        currentControl = "";
                        map.un('pointermove', hoverOnRoadBuildingHandler);
                        map.un('singleclick', displayRoadBuildings);
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                });
            }

            // Display markers on buildings associated to containment
            function displayContainmentToBuildings(evt) {
                if (eLayer.selected_containment) {
                    eLayer.selected_containment.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            image: new ol.style.Icon({
                                anchor: [0.5, 1],
                                src: '{{ url("/")}}/img/containment.png'
                            })
                        })
                    });

                    addExtraLayer('selected_containment', 'Selected Containment', layer);
                }

                // showExtraLayer('selected_drains');

                if (eLayer.containment_buildings) {
                    eLayer.containment_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('containment_buildings', 'Containment Buildings', layer);
                }

                // showExtraLayer('containment_buildings');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':containments_layer',
                        'FEATURE_COUNT': 1
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }
                displayAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {
                         var dataWms = data;
                        if (dataWms && dataWms.features && Array.isArray(dataWms.features)) {
                            if (dataWms.features.length > 0) {
                                var url1 = '{{ url("maps/containment-buildings") }}';
                                $.ajax({
                                    url: url1,
                                    type: 'get',
                                    data: {
                                        "_token": "{{ csrf_token() }}",
                                        containmentId: dataWms.features[0].properties.id
                                    },
                                    success: function (data1) {

                                        eLayer.selected_containment.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(dataWms));


                                        if (data1 && Array.isArray(data1)) {
                                            var format = new ol.format.WKT();

                                            for (var i = 0; i < data1.length; i++) {
                                                var feature = format.readFeature(data1[i].geom, {
                                                    dataProjection: 'EPSG:4326',
                                                    featureProjection: 'EPSG:3857'
                                                });

                                                eLayer.containment_buildings.layer.getSource().addFeature(feature);
                                            }
                                        }

                                        removeAjaxLoader();
                                    },
                                    error: function (data1) {
                                        displayAjaxError();
                                    }
                                });
                            } else {
                                displayAjaxErrorModal(@json(__('Containment Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                    },
                    error: function (dataWms) {
                        displayAjaxError();
                    }
                });
            }

            // Display markers on containment associated to building
            function displayBuildingToContainment(evt) {
                if (eLayer.selected_building) {
                    eLayer.selected_building.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#ff0000',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('selected_building', 'Selected Building', layer);
                }

                // showExtraLayer('selected_drains');

                if (eLayer.building_containments) {
                    eLayer.building_containments.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('building_containments', 'Buildingc Containments', layer);
                }

                // showExtraLayer('containment_buildings');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':buildings_layer',
                        'FEATURE_COUNT': 1
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                displayAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {
                        if (data && data.features && Array.isArray(data.features)) {
                            if (data.features.length > 0) {
                                var url1 = '{{ url("maps/building-containment") }}';
                                $.ajax({
                                    url: url1,
                                    type: 'get',
                                    data: {
                                        "_token": "{{ csrf_token() }}",
                                        bin: data.features[0].properties.bin
                                    },
                                    success: function (data1) {
                                        eLayer.selected_building.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(data));

                                        for (var i = 0; i < data1.length; i++) {
                                            if (data1[i].lat && data1[i].long) {
                                                if (!eLayer.markers) {
                                                    var markerLayer = new ol.layer.Vector({
                                                        // visible: false,
                                                        source: new ol.source.Vector()
                                                    });

                                                    addExtraLayer('markers', 'Markers', markerLayer);
                                                    // showExtraLayer('markers');
                                                }

                                                var feature = new ol.Feature({
                                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(data1[i].long), parseFloat(data1[i].lat)], 'EPSG:4326', 'EPSG:3857'))
                                                });

                                                var style = new ol.style.Style({
                                                    image: new ol.style.Icon({
                                                        anchor: [0.5, 1],
                                                        src: '{{ url("/")}}/img/containment-green.png'
                                                    })
                                                });

                                                feature.setStyle(style);

                                                eLayer.building_containments.layer.getSource().addFeature(feature);
                                            }
                                        }

                                        removeAjaxLoader();
                                    },
                                    error: function (data1) {
                                        displayAjaxError();
                                    }
                                });
                            } else {
                                displayAjaxErrorModal(@json(__('Building Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                });
            }

            // Display markers on containment associated to building
            function displayAssociatedToMainBuilding(evt) {
                if (eLayer.selected_building) {
                    eLayer.selected_building.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#ff0000',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('selected_building', 'Selected Building', layer);
                }

                // showExtraLayer('selected_drains');

                if (eLayer.associated_to_main_building) {
                    eLayer.associated_to_main_building.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('associated_to_main_building', 'Associated to Main Buildings', layer);
                }

                // showExtraLayer('containment_buildings');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':buildings_layer',
                        'FEATURE_COUNT': 1
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                displayAjaxLoader();

                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {
                        if (data && data.features && Array.isArray(data.features)) {
                            if (data.features.length > 0) {
                                var url1 = '{{ url("maps/getassociated-mainbuilding") }}';
                                $.ajax({
                                    url: url1,
                                    type: 'get',
                                    data: {
                                        "_token": "{{ csrf_token() }}",
                                        bin: data.features[0].properties.bin
                                    },
                                    success: function (data1) {
                                        eLayer.selected_building.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(data));

                                        for (var i = 0; i < data1.length; i++) {
                                            if (data1[i].lat && data1[i].long) {
                                                if (!eLayer.markers) {
                                                    var markerLayer = new ol.layer.Vector({
                                                        // visible: false,
                                                        source: new ol.source.Vector()
                                                    });

                                                    addExtraLayer('markers', 'Markers', markerLayer);
                                                    // showExtraLayer('markers');
                                                }

                                                var feature = new ol.Feature({
                                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(data1[i].long), parseFloat(data1[i].lat)], 'EPSG:4326', 'EPSG:3857'))
                                                });

                                                var style = new ol.style.Style({
                                                    image: new ol.style.Icon({
                                                        anchor: [0.5, 1],
                                                        src: '{{ url("/") }}/img/building-purple.png'
                                                    })
                                                });

                                                feature.setStyle(style);

                                                eLayer.associated_to_main_building.layer.getSource().addFeature(feature);
                                            }
                                        }

                                        removeAjaxLoader();
                                    },
                                    error: function (data1) {
                                        displayAjaxError();
                                    }
                                });
                            } else {
                                displayAjaxErrorModal(@json(__('Main Building Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                });
            }
            function displayDrainPotentialBuildings(evt) {
                if (eLayer.selected_drains) {
                    eLayer.selected_drains.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('selected_drains', 'Selected Sewersline', layer);
                }

                // showExtraLayer('selected_drains');

                if (eLayer.drain_buildings) {
                    eLayer.drain_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('drain_buildings', 'Sewersline Buildings', layer);
                }

                // showExtraLayer('drain_buildings');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':sewerlines_layer',
                        'FEATURE_COUNT': 10
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                displayAjaxLoader();

                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                            "_token": "{{ csrf_token() }}",
                    },
                    success: function (response) {
                        if (response && response.features && Array.isArray(response.features)) {
                            if (response.features.length > 0) {
                                if (response.features.length == 1) {
                                    eLayer.selected_drains.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(response));

                                    var drainCode = response.features[0].properties.code;
                                    $('#drain-code').val(drainCode);
                                    $('#drain-long').val(evt.coordinate[0]);
                                    $('#drain-lat').val(evt.coordinate[1]);
                                    $('#buffer-distance').val('');
                                    removeAjaxLoader();
                                    $('#popup-drain-potential').modal('show');
                                } else {
                                    displayAjaxErrorModal(@json(__('More than One Sewer lines Found, Please Zoom In or Select another Sewer line')));
                                }
                            } else {
                                displayAjaxErrorModal(@json(__('Sewer Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                        $('#drainpotential_control').removeClass('map-control-active');
                        currentControl = "";
                        map.un('pointermove', hoverOnDrainHandler);
                        map.un('singleclick', displayDrainPotentialBuildings);
                    },
                    error: function (response) {
                        displayAjaxError();
                    }
                })


                //displayAjaxLoader();
            }

            function displayWaterBodiesBuildings(evt) {
                if (eLayer.selected_waterbodies) {
                    eLayer.selected_waterbodies.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('selected_waterbodies', 'Selected Waterbodies', layer);
                }

                // showExtraLayer('selected_waterbodies');

                if (eLayer.waterbodies_buildings) {
                    eLayer.waterbodies_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('waterbodies_buildings', 'Water Bodies Buildings', layer);
                }

                // showExtraLayer('waterbodies_buildings');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':waterbodys_layer',
                        'FEATURE_COUNT': 10
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                displayAjaxLoader();

                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {
                        if (data && data.features && Array.isArray(data.features)) {
                            if (data.features.length > 0) {
                                if (data.features.length == 1) {
                                    eLayer.selected_waterbodies.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(data));

                                    var waterBodyCode = data.features[0].properties.id;
                                    $('#water-body-code').val(waterBodyCode);
                                    $('#water-body-long').val(evt.coordinate[0]);
                                    $('#water-body-lat').val(evt.coordinate[1]);
                                    $('#buffer-distance-waterbodies').val('');
                                    removeAjaxLoader();
                                    $('#popup-waterbodies-buildings').modal('show');
                                } else {
                                    displayAjaxErrorModal(@json(__('More than One water bodies Found, Please Zoom In or Select another Water Body')));
                                }
                            } else {
                                displayAjaxErrorModal(@json(__('Water Body Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }

                        $('#buildingswaterbodies_control').removeClass('map-control-active');
                        currentControl = "";
                        map.un('pointermove', hoverOnWaterBodiesHandler);
                        map.un('singleclick', displayWaterBodiesBuildings);
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                })


                //displayAjaxLoader();
            }

            function displayRoadsBuildings(evt) {
                if (eLayer.selected_roads) {
                    eLayer.selected_roads.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('selected_roads', 'Selected Roads', layer);
                }

                // showExtraLayer('selected_roads');

                if (eLayer.roads_buildings) {
                    eLayer.roads_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('roads_buildings', 'Roads Buildings', layer);
                }

                // showExtraLayer('roads_buildings');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':roadlines_layer',
                        'FEATURE_COUNT': 10
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                displayAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {
                        if (data && data.features && Array.isArray(data.features)) {
                            if (data.features.length > 0) {
                                if (data.features.length == 1) {
                                    eLayer.selected_roads.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(data));

                                    var roadCode = data.features[0].properties.code;
                                    $('#road-code').val(roadCode);
                                    $('#road-long').val(evt.coordinate[0]);
                                    $('#road-lat').val(evt.coordinate[1]);
                                    $('#buffer-distance-road').val('');
                                    removeAjaxLoader();
                                    $('#popup-road-buildings').modal('show');
                                } else {
                                    displayAjaxErrorModal(@json(__('More than One Roads Found, Please Zoom In or Select another Road')));
                                }
                            } else {
                                displayAjaxErrorModal(@json(__('Road Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                        $('#buildingsroads_control').removeClass('map-control-active');
                        map.un('pointermove', hoverOnRoadsHandler);
                        map.un('singleclick', displayRoadsBuildings);
                        currentControl = "";
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                })


                //displayAjaxLoader();
            }


            function displayBuildingsToPTCT(evt) {

                if (eLayer.selected_ptct) {
                    eLayer.selected_ptct.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            image: new ol.style.Icon({
                                anchor: [0.5, 1],
                                src: '{{ url("/")}}/img/containment-green.png'
                            })
                        })
                    });

                    addExtraLayer('selected_ptct', 'Selected PTCT', layer);
                }

                // showExtraLayer('selected_drains');

                if (eLayer.buildings_ptct) {
                    eLayer.buildings_ptct.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#A9A9A9',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('buildings_ptct', 'PTCT Buildings', layer);
                }

                // showExtraLayer('containment_buildings');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':toilets_layer',
                        'FEATURE_COUNT': 1
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                displayAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {
                        if (data && data.features && Array.isArray(data.features)) {
                            if (data.features.length > 0) {
                                var url1 = '{{ url("maps/buildings-toilet-network") }}';
                                $.ajax({
                                    url: url1,
                                    type: 'get',
                                    data: {
                                        "_token": "{{ csrf_token() }}",
                                        bin: data.features[0].properties.bin
                                    },
                                    success: function (data1) {

                                        eLayer.selected_ptct.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(data));

                                        if (data1 && Array.isArray(data1)) {
                                            var format = new ol.format.WKT();

                                            for (var i = 0; i < data1.length; i++) {

                                                var feature = format.readFeature(data1[i].geom, {
                                                    dataProjection: 'EPSG:4326',
                                                    featureProjection: 'EPSG:3857'
                                                });

                                                eLayer.buildings_ptct.layer.getSource().addFeature(feature);
                                            }
                                        } else {
                                            displayAjaxErrorModal(@json(__('Buildings not found')));
                                        }

                                        removeAjaxLoader();
                                    },
                                    error: function (data1) {
                                        displayAjaxError();
                                    }
                                });
                            } else {
                                displayAjaxErrorModal(@json(__('PTCT not found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                });

            }

            function displayWardsBuildings(evt) {
                if (eLayer.selected_ward) {
                    eLayer.selected_ward.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('selected_ward', 'Selected Ward', layer);
                }

                // showExtraLayer('selected_ward');

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: {
                        'LAYERS': workspace + ':wards_layer',
                        'FEATURE_COUNT': 10
                    }
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    evt.coordinate, viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                $.ajax({
                    url: url,
                    type: 'get',

                    success: function (response) {
                        if (response && response.features && Array.isArray(response.features)) {
                            if (response.features.length > 0) {
                                if (response.features.length == 1) {
                                    eLayer.selected_ward.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(response));
                                    var ward = response.features[0].properties.ward;
                                    $('#ward_building_no').val(ward);
                                    var url1 = '{{ url("maps/ward-buildings") }}';
                                    var long = evt.coordinate[0];
                                    var lat = evt.coordinate[1];
                                    displayAjaxLoader();
                                    $.ajax({
                                        url: url1,
                                        type: 'post',
                                        data: {
                                            "_token": "{{ csrf_token() }}",
                                            ward: ward,
                                        },
                                        success: function (Response) {
                                            wardBuildingsPopupContent.innerHTML = Response['popContentsHtml'];
                                            wardBuildingsPopupOverlay.setPosition([long, lat]);
                                            data1 = Response['buildings'];
                                            if (data1 && Array.isArray(data1)) {
                                                var format = new ol.format.WKT();

                                                for (var i = 0; i < data1.length; i++) {
                                                    var feature = format.readFeature(data1[i].geom, {
                                                        dataProjection: 'EPSG:4326',
                                                        featureProjection: 'EPSG:3857'
                                                    });

                                                }
                                            }

                                            removeAjaxLoader();
                                        },
                                        error: function (data1) {
                                            displayAjaxError();
                                        }
                                    });

                                    removeAjaxLoader();
                                } else {
                                    displayAjaxErrorModal(@json(__('More than One wards Found, Please Zoom In or Select another Water Body')));
                                }
                            } else {
                                displayAjaxErrorModal(@json(__('Ward Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                         $('#buildingswards_control').removeClass('map-control-active');
                        map.un('pointermove', hoverOnWardsBuildingHandler);
                        map.un('singleclick', displayWardsBuildings);
                        currentControl = "";
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                })

                //displayAjaxLoader();
            }

            function kmlDragDropSummaryInformation(evt) {
                var format = new ol.format.WKT();
                var geom = format.writeGeometry(evt.feature.getGeometry().clone().transform('EPSG:3857', 'EPSG:4326'));
                var url1 = '{{ url("maps/ward-buildings") }}';
                var long = evt.coordinate[0];
                var lat = evt.coordinate[1];
                displayAjaxLoader();
                $.ajax({
                    url: url1,
                    type: 'post',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        geom: geom,
                    },
                    success: function (Response) {
                        wardBuildingsPopupContent.innerHTML = Response['popContentsHtml'];
                        wardBuildingsPopupOverlay.setPosition([long, lat]);
                        data1 = Response['buildings'];
                        if (data1 && Array.isArray(data1)) {
                            var format = new ol.format.WKT();

                            for (var i = 0; i < data1.length; i++) {
                                var feature = format.readFeature(data1[i].geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });

                            }
                        }

                        removeAjaxLoader();
                    },
                    error: function (data1) {
                        displayAjaxError();
                    }
                });

            }

            $("#form-drain-potential").submit(function (event) {
            event.preventDefault();

            var distance = $('#buffer-distance').val();

            // Validation: Check if the distance is a positive number
            if (distance === "" || isNaN(distance) || distance < 0) {
                let message = "{{ __('Please enter a valid Buffer Distance.') }}";

                // Append specific message for negative values
                if (distance < 0) {
                    message += "{{ __('Negative numbers are not allowed.') }}";
                }
                else if (!Number.isInteger(Number(distance))) {
                    message += "{{ __('Float values are not accepted.') }}";
                }
                Swal.fire({
                    title: "{{ __('Invalid Input') }}",
                    text: message,
                    icon: "warning",
                    button: "{{ __('OK') }}"
                });

                return false;
            }




    if (eLayer.drainbuffer_polygon) {
        eLayer.drainbuffer_polygon.layer.getSource().clear();
    } else {
        var layer = new ol.layer.Vector({
            // visible: false,
            source: new ol.source.Vector(),
            style: new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: '#FF0000',
                    width: 3
                }),
            })
        });

        addExtraLayer('drainbuffer_polygon', 'Point Buffer Polygon', layer);
    }

    var url1 = '{{ url("maps/drain-potential-buildings") }}';
    var drainCode = $('#drain-code').val();
    var long = $('#drain-long').val();
    var lat = $('#drain-lat').val();
    $('#DrainBufferCode').val(drainCode);
    $('#DBdistance').val(distance);
    displayAjaxLoader();

    $.ajax({
        url: url1,
        type: 'post',
        data: {
            "_token": "{{ csrf_token() }}",
            drain_code: drainCode,
            distance: distance
        },
        success: function (Response) {
            drainPotentialSummaryuPopupContent.innerHTML = Response['popContentsHtml'];
            drainPotentialSummaryPopupOverlay.setPosition([long, lat]);
            data1 = Response['buildings'];

            if (data1 && Array.isArray(data1)) {
                var format = new ol.format.WKT();

                for (var i = 0; i < data1.length; i++) {
                    var feature = format.readFeature(data1[i].geom, {
                        dataProjection: 'EPSG:4326',
                        featureProjection: 'EPSG:3857'
                    });

                    eLayer.drain_buildings.layer.getSource().addFeature(feature);
                }
            }

            var featureP = format.readFeature(Response['polygon'], {
                dataProjection: 'EPSG:4326',
                featureProjection: 'EPSG:3857'
            });
            eLayer.drainbuffer_polygon.layer.getSource().addFeature(featureP);
            $('#popup-drain-potential').modal('hide');
            removeAjaxLoader();
        },
        error: function (data1) {
            displayAjaxError();
        }
    });
});

            $("#form-waterbodies-buildings").submit(function (event) {
                event.preventDefault();

                if (eLayer.wbbuffer_polygon) {
                    eLayer.wbbuffer_polygon.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#FF0000',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('wbbuffer_polygon', 'Water Body Polygon', layer);
                }
                var url1 = '{{ url("maps/water-bodies-buildings") }}';
                var distance = $('#buffer-distance-waterbodies').val();
                if (distance === "" || isNaN(distance) || distance < 0) {
                let message = "{{ __('Please enter a valid Buffer Distance.') }}";

                // Append specific message for negative values
                if (distance < 0) {
                    message += "{{ __('Negative numbers are not allowed.') }}";
                }
                else if (!Number.isInteger(Number(distance))) {
                    message += "{{ __('Float values are not accepted.') }}";
                }
                Swal.fire({
                    title: "{{ __('Invalid Input') }}",
                    text: message,
                    icon: "warning",
                    button: "{{ __('OK') }}"
                });

                return false;
            }




                var waterBodyCode = $('#water-body-code').val();
                var long = $('#water-body-long').val();
                var lat = $('#water-body-lat').val();
                $('#waterBodyCode').val(waterBodyCode);
                $('#WBdistance').val(distance);

                displayAjaxLoader();
                $.ajax({
                    url: url1,
                    type: 'post',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        waterbody_code: waterBodyCode,
                        distance: distance

                    },
                    success: function (Response) {
                        waterBodyPopupContent.innerHTML = Response['popContentsHtml'];
                        waterBodyPopupOverlay.setPosition([long, lat]);
                        data1 = Response['buildings'];
                        if (data1 && Array.isArray(data1)) {
                            var format = new ol.format.WKT();

                            for (var i = 0; i < data1.length; i++) {
                                var feature = format.readFeature(data1[i].geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });

                                eLayer.waterbodies_buildings.layer.getSource().addFeature(feature);
                            }
                        }
                        var featureP = format.readFeature(Response['polygon'], {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        });
                        eLayer.wbbuffer_polygon.layer.getSource().addFeature(featureP);
                        $('#popup-waterbodies-buildings').modal('hide');
                        removeAjaxLoader();
                    },
                    error: function (data1) {
                        displayAjaxError();
                    }
                });
            });


            $("#form-buffer-polygon").submit(function (event) {

event.preventDefault();

if (eLayer.summary_buffer_polygon) {
    eLayer.summary_buffer_polygon.layer.getSource().clear();
} else {
    var layer = new ol.layer.Vector({
        // visible: false,
        source: new ol.source.Vector(),
        style: new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: '#FF0000',
                width: 3
            }),
        })
    });

    addExtraLayer('summary_buffer_polygon', 'Summary Buffer Polygon', layer);
}
var url1 = '{{ url("maps/buffer-polygon-buildings") }}';
var bufferDisancePolygon = $('#buffer-distance-polygon').val();
var bufferPolygonGeom = $('#polygon-geom').val();
var polygonCoordinates = $('#polygon-coordinates').val();
$('#buffer_polygon_geom').val(bufferPolygonGeom);
$('#buffer_polygon_distance').val(bufferDisancePolygon);
var bufferDistancePolygon = $('#buffer-distance-polygon').val();

// Validation: Check if the distance is a non-negative number
if (bufferDistancePolygon === "" || isNaN(bufferDistancePolygon)) {
    Swal.fire({
        title: "{{ __('Invalid Input') }}",
        text: "{{ __('Please enter a valid Buffer Distance.') }}",
        icon: 'warning',
        confirmButtonText: "{{ __('OK') }}"
    });
    return false;
} else if (parseFloat(bufferDistancePolygon) < 0) {
    Swal.fire({
        title: "{{ __('Invalid Input') }}",
        text: "{{ __('Negative numbers are not allowed.') }}",
        icon: 'warning',
        confirmButtonText: "{{ __('OK') }}"
    });
    return false;
}
displayAjaxLoader();
$.ajax({
    url: url1,
    type: 'post',
    data: {
        "_token": "{{ csrf_token() }}",
        bufferDisancePolygon: bufferDisancePolygon,
        bufferPolygonGeom: bufferPolygonGeom
    },
    success: function (Response) {
        bufferPolygonPopupContent.innerHTML = Response['popContentsHtml'];
        $(bufferPolygonPopupContainer).show();
        data1 = Response['buildings'];
        if (data1 && Array.isArray(data1)) {
            var format = new ol.format.WKT();

            for (var i = 0; i < data1.length; i++) {
                var feature = format.readFeature(data1[i].geom, {
                    dataProjection: 'EPSG:4326',
                    featureProjection: 'EPSG:3857'
                });

                eLayer.report_polygon_buffer.layer.getSource().addFeature(feature);
            }
        }
        var featureP = format.readFeature(Response['polygon'], {
            dataProjection: 'EPSG:4326',
            featureProjection: 'EPSG:3857'
        });
        eLayer.summary_buffer_polygon.layer.getSource().addFeature(featureP);
        $('#popup-polygon-buffer').modal('hide');
        removeAjaxLoader();
    },
    error: function (data1) {
        displayAjaxError();
    }
});
});




            $("#form-road-buildings").submit(function (event) {
                event.preventDefault();
                if (eLayer.roadbuffer_polygon) {
                    eLayer.roadbuffer_polygon.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#FF0000',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('roadbuffer_polygon', 'Road Buffer Polygon', layer);
                }
                var url1 = '{{ url("maps/road-buildings") }}';
                var distance = $('#buffer-distance-road').val();
                var roadCode = $('#road-code').val();
                var long = $('#road-long').val();
                var lat = $('#road-lat').val();
                $('#RDCode').val(roadCode);
                $('#RDBdistance').val(distance);

                if (distance === "" || isNaN(distance) || distance < 0) {
                    let message = "{{ __('Please enter a valid Buffer Distance.') }}";

                    // Append specific message for negative values
                    if (distance < 0) {
                        message += "{{ __('Negative numbers are not allowed.') }}";
                    }
                    else if (!Number.isInteger(Number(distance))) {
                    message += "{{ __('Float values are not accepted.') }}";
                }
                    Swal.fire({
                        title: "{{ __('Invalid Input') }}",
                        text: message,
                        icon: "warning",
                        button: "{{ __('OK') }}"
                    });

                    return false;
                }



    displayAjaxLoader();
                $.ajax({
                    url: url1,
                    type: 'post',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        road_code: roadCode,
                        distance: distance
                    },
                    success: function (Response) {
                        roadPopupContent.innerHTML = Response['popContentsHtml'];
                        roadPopupOverlay.setPosition([long, lat]);
                        data1 = Response['buildings'];
                        if (data1 && Array.isArray(data1)) {
                            var format = new ol.format.WKT();

                            for (var i = 0; i < data1.length; i++) {
                                var feature = format.readFeature(data1[i].geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });

                                eLayer.roads_buildings.layer.getSource().addFeature(feature);
                            }
                        }
                        var featureP = format.readFeature(Response['polygon'], {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        });
                        eLayer.roadbuffer_polygon.layer.getSource().addFeature(featureP);

                        $('#popup-road-buildings').modal('hide');
                        removeAjaxLoader();
                    },
                    error: function (data1) {
                        displayAjaxError();
                    }
                });
            });

            $("#form-point-buffer-buildings").submit(function (event) {
                event.preventDefault();
                if (eLayer.pointbuffer_polygon) {
                    eLayer.pointbuffer_polygon.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#FF0000',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('pointbuffer_polygon', 'Point Buffer Polygon', layer);
                }
                if (eLayer.pointbuffer_buildings) {
                    eLayer.pointbuffer_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('pointbuffer_buildings', 'Point Buffer Buildings', layer);
                }


                var url1 = '{{ url("maps/point-buffer-buildings") }}';
                var distance = $('#buffer-distance-point').val();
                var long_pos = $('#point-buffer-long-pos').val();
                var lat_pos = $('#point-buffer-lat-pos').val();
                var long = $('#point-buffer-long').val();
                var lat = $('#point-buffer-lat').val();

                if (distance === "" || isNaN(distance) || distance < 0) {
                let message = "{{ __('Please enter a valid Buffer Distance.') }}";

                // Append specific message for negative values
                if (distance < 0) {
                    message += "{{ __('Negative numbers are not allowed.') }}";
                }
                else if (!Number.isInteger(Number(distance))) {
                    message += "{{ __('Float values are not accepted.') }}";
                }
                Swal.fire({
                    title: "{{ __('Invalid Input') }}",
                    text: message,
                    icon: "warning",
                    button: "{{ __('OK') }}"
                });

                return false;
            }



                $('#PTB-long-csv').val(long);
                $('#PTB-lat-csv').val(lat);
                $('#PTB-distance').val(distance);

                displayPointBufferByCoordinates(lat_pos, long_pos);

                displayAjaxLoader();
                $.ajax({
                    url: url1,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        distance: distance,
                        long: long,
                        lat: lat,
                    },
                    success: function (Response) {
                        pointBufferPopupContent.innerHTML = Response['popContentsHtml'];
                        pointBufferPopupOverlay.setPosition([parseFloat(long_pos), parseFloat(lat_pos)]);
                        data1 = Response['buildings'];
                        if (data1 && Array.isArray(data1)) {
                            var format = new ol.format.WKT();

                            for (var i = 0; i < data1.length; i++) {
                                var feature = format.readFeature(data1[i].geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });

                                eLayer.pointbuffer_buildings.layer.getSource().addFeature(feature);
                            }
                        }
                        var featureP = format.readFeature(Response['polygon'], {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        });
                        eLayer.pointbuffer_polygon.layer.getSource().addFeature(featureP);
                        $('#popup-point-buffer').modal('hide');
                        removeAjaxLoader();
                    },
                    error: function (data1) {
                        displayAjaxError();
                    }
                });
            });


             // submit function for toilet isochrone map
             $("#form-toilet-isochrone-map").submit(function (event) {
                event.preventDefault();
                var i =0;
                if (eLayer.toilet_isochrone_polygon) {
                    eLayer.toilet_isochrone_polygon.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#FF0000',
                                width: 3
                            })
                    }),
                    });
                    addExtraLayer('toilet_isochrone_polygon', 'Toilets Isochrone Polygon', layer);
                }

                if (eLayer.toilets_isochrone_buildings) {
                    eLayer.toilets_isochrone_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#FF0000',
                                width: 3
                            })
                    }),
                    });
                addExtraLayer('toilets_isochrone_buildings', 'Toilets Isochrone Buffer Buildings', layer);
                }
               
                var urliso = '{{ url("maps/toilet-isochrone") }}';

                var distance = $('#toilet-isochrone-distance').val() ; //distance calculated in meter

                displayAjaxLoader();
                $.ajax({
                    url: urliso,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        distance: distance,
                    },
                    success: function (Response) {
                        data2 = Response['polygon'];
                        if (data2 && Array.isArray(data2)) {
                            for (var i = 0; i < data2.length; i++) {
                                var format = new ol.format.WKT();
                                var featureP = format.readFeature(data2[i].geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });
                                featureP.setStyle(styleFunction(i));
                                eLayer.toilet_isochrone_polygon.layer.getSource().addFeature(featureP);
                        }
                        }
                        else
                        {
                            var featureP = format.readFeature(data2.geom, {
                                dataProjection: 'EPSG:4326',
                                featureProjection: 'EPSG:3857'
                            });
                            eLayer.toilet_isochrone_polygon.layer.getSource().addFeature(featureP);
                        }
                        showLayer('toilets_layer');
                        showLayer('roadlines_layer');


                        $('#popup-toilet-isochrone').modal('hide');
                        removeAjaxLoader();
                    },
                    error: function (data2) {
                        displayAjaxError();
                    }
                });
                
             });

             function styleFunction(i, resolution) {
                i+=1;
                return(getBikeStyle(i));
            }
            var bikeStyleCache = {};
            function createBikeStyle(id) {
                //  var strokeColor = randomColor();
                var strokeColor = '#397B7D' ;
                var fillColor = strokeColor.slice();
                fillColor[3] = 0.2;
                var bikeStyle  = new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: strokeColor,
                        width: 4,
                    }),
                });
                bikeStyleCache[id] = bikeStyle;
            }
            function getBikeStyle(id) {
                if (!bikeStyleCache[id]) {
                    createBikeStyle(id);
                }
                return(bikeStyleCache[id]);
            }
            function randomColor() {
                var r = Math.floor(Math.random() * 256);
                var g = Math.floor(Math.random() * 256);
                var b = Math.floor(Math.random() * 256);
                var color = [r, g, b];
                return(color);
            } 
             $("#form-road-inaccessible").submit(function (event) {
                event.preventDefault();
                roadInaccessiblePopupOverlay.setPosition(undefined);
                if (eLayer.summary_road_inaccessible) {
                    eLayer.summary_road_inaccessible.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#FF0000',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('summary_road_inaccessible', 'Summary Road Inaccessible', layer);
                }

                if (eLayer.road_inaccessible_buildings) {
                    eLayer.road_inaccessible_buildings.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            stroke: new ol.style.Stroke({
                                color: '#0000FF',
                                width: 3
                            }),
                        })
                    });

                    addExtraLayer('road_inaccessible_buildings', 'Road Inaccessbile Buildings', layer);
                }
                var url1 = '{{ url("maps/road-inaccessible-summary-info") }}';
                var roadLength = $('#road-length').val();
                var vacutugRange = $('#vacutug-range').val();
                displayAjaxLoader();
                $.ajax({
                    url: url1,
                    type: 'post',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        roadLength: roadLength,
                        vacutugRange: vacutugRange
                    },
                    success: function (Response) {
                        roadInaccessiblePopupContent.innerHTML = Response['popContentsHtml'];
                        roadInaccessiblePopupOverlay.setPosition(map.getView().getCenter());
                        $(roadInaccessiblePopupContainer).show();
                        data1 = Response['buildings'];
                        if (data1 && Array.isArray(data1)) {
                            var format = new ol.format.WKT();

                            for (var i = 0; i < data1.length; i++) {
                                var feature = format.readFeature(data1[i].geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });

                                eLayer.road_inaccessible_buildings.layer.getSource().addFeature(feature);
                            }
                        }
                        var featureP = format.readFeature(Response['polygon'], {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        });
                        eLayer.summary_road_inaccessible.layer.getSource().addFeature(featureP);
                        $('#road-inaccessible-input-form').modal('hide');
                        removeAjaxLoader();
                    },
                    error: function (data1) {
                        displayAjaxError();
                    }
                });
            });

            if (layer != '' && field != '' && val != '') {
                handleZoomToExtent(layer, field, val, true, null);

                if (layer == 'containments_layer') {
                    if (action == 'containment-buildings') {
                        displayContainmentBuildings(field, val);
                    } else if (action == 'containment-road') {
                        displayContainmentRoad(field, val);
                    }
                }

                if (layer == 'buildings_layer') {
                    if (action == 'building-road') {
                        displayBuildingRoad(field, val);
                    }
                }
            }

            setInitialZoom();

            function setInitialZoom() {
                map.getView().setCenter(ol.proj.transform(coord, 'EPSG:4326', 'EPSG:3857'));
                map.getView().setZoom(14);
            }

            // Set map zoom to city
            // zoomToCity();

            // Set map zoom to city
            function zoomToCity() {
                map.getView().setCenter(ol.proj.transform(coord, 'EPSG:4326', 'EPSG:3857'));
                map.getView().setZoom(12);
            }

            var hoverOnLayerHandler = function (evt) {
                if (evt.dragging) {
                    return;
                }
                var pixel = map.getEventPixel(evt.originalEvent);
                var hit = map.forEachLayerAtPixel(pixel, function () {
                    return true;
                });
                map.getTargetElement().style.cursor = hit ? 'pointer' : '';
            };

            /**
             * Elements that make up the popup for feature info.
             */
            var featureInfoPopupContainer = document.getElementById('feature-info-popup');
            var featureInfoPopupContent = document.getElementById('feature-info-popup-content');
            var featureInfoPopupCloser = document.getElementById('feature-info-popup-closer');


            /**
             * Create an overlay to anchor the popup to the map.
             */
            var featureInfoPopupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: featureInfoPopupContainer,
                autoPan: true,
                stopEvent: true,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(featureInfoPopupContainer).show();

            map.addOverlay(featureInfoPopupOverlay);

            /**
             * Add a click handler to hide the popup.
             * @return {boolean} Don't follow the href.
             */
            featureInfoPopupCloser.onclick = function () {
                featureInfoPopupOverlay.setPosition(undefined);
                featureInfoPopupCloser.blur();
                return false;
            };


            // Display information about feature
            function displayFeatureInformation(evt) {

                $('#feature_info_content').html('');
                var layer = $('#feature_info_overlay').val();
                if (layer == "buildings_layer"){
                    layer = "buildings_layer"
                }
                var long = evt.coordinate[0];
                var lat = evt.coordinate[1];

                if (!layer) {
                    Swal.fire({
                        title: "{{ __('Please select a layer!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }

                var viewResolution = /** @type {number} */ (map.getView().getResolution());

                var params = {
                    'LAYERS': workspace + ':' + layer,
                    'FEATURE_COUNT': 1
                };


                if (layer == 'buildings_layer')
                {
                    params.PROPERTYNAME = 'bin,owner_name,nid,owner_gender,owner_contact,building_associated_to,ward,road_code,house_number,house_locality,tax_code,structure_type_name,surveyed_date,construction_year,floor_count,functional_use_name,use_category_name,office_business_name,household_served,male_population,female_population,other_population,population_served,diff_abled_male_pop,diff_abled_female_pop,diff_abled_others_pop,lic_community,lic_community,water_source_name,water_customer_id,watersupply_pipe_code,well_presence_status,distance_from_well,swm_customer_id,toilet_status,toilet_count,household_with_private_toilet,population_with_private_toilet,building_sanitation_system,sewer_code,drain_code,desludging_vehicle_accessible,estimated_area,toilet_name,verification_status,house_image'
                    // 'bin,house_number,house_locality,ward,road_code,estimated_area,floor_count,household_served,population_served,office_business_name,building_associated_to,well_presence_status,toilet_status,toilet_count,sewer_code,drain_code,surveyed_date,tax_code,desludging_vehicle_accessible,construction_year,distance_from_well,owner_name,owner_gender,owner_contact,nid,functional_use_name,structure_type_type,use_category_name,sanitation_system_technology_name,water_source_source,house_image'; 
                }

                if (layer == 'wardboundary_layer') {
                    params.PROPERTYNAME = 'ward,area';
                }
                if (layer == 'buildings_swm_payment_status_layer') {
                    params.PROPERTYNAME = 'swm_customer_id,bin,tax_code,ward,customer_name,customer_contact,due_year';
                }
                if (layer == 'containments_layer')
                {
                    params.PROPERTYNAME = 'id,containment_type,tank_length,tank_width,depth,pit_diameter,size,location,septic_criteria,construction_date,emptied_status,last_emptied_date,next_emptying_date,no_of_times_emptied,responsible_bin';
                }
                if (layer == 'treatmentplants_layer') {
                    params.PROPERTYNAME = 'name,location,capacity_per_day,caretaker_name,caretaker_number,caretaker_gender,treatmentplant,status';
                }
                if (layer == 'roadlines_layer') {
                    //removed ,rdcarwdth
                    params.PROPERTYNAME = 'code,name,hierarchy,surface_type,length,carrying_width,right_of_way';
                }
                if (layer == 'roads_width_zoom_layer') {
                    //removed ,rdcarwdth
                    params.PROPERTYNAME = 'code,name,hierarchy,surface_type,length,carrying_width';
                }
                if (layer == 'drains_layer') {
                    params.PROPERTYNAME = 'code,utilitysize,length,cover_type,surface_type,road_code,treatmentplant_type';
                }
                if (layer == 'places_layer') {
                    params.PROPERTYNAME = 'name';
                }
                if (layer == 'water_samples_layer') {
                    params.PROPERTYNAME = 'sample_date,sample_location,water_coliform_test_result';
                }
                if (layer == 'taxzone') {
                    params.PROPERTYNAME = 'taxzoneids';
                }
                if (layer == 'waterbodys_layer') {
                    params.PROPERTYNAME = 'id,name,type';
                }
                if (layer == 'wards_layer') {
                    params.PROPERTYNAME = 'ward,area,no_build,no_contain,no_rcc_framed,no_load_bearing,no_wooden_mud,no_cgi_sheet,no_emptying,no_septic_tank';
                }
                if (layer == 'grids_layer') {
                    // removed no_pucca,no_katcha,
                    params.PROPERTYNAME = 'no_build,no_contain,total_rdlen,no_rcc_framed,no_load_bearing,no_wooden_mud,no_cgi_sheet,no_emptying,no_septic_tank';
                }
                if (layer == 'sewerlines_layer') {
                    params.PROPERTYNAME = 'code,length,location,treatmentplant_type,diameter';
                }
                if (layer == 'waterborne_hotspots_layer') {
                    params.PROPERTYNAME = 'hotspot_location,ward,no_of_cases,male_cases,female_cases,other_cases,no_of_fatalities,male_fatalities,female_fatalities,other_fatalities,disease_type,notes';
                }
                if (layer == 'watersupply_network_layer') {
                    params.PROPERTYNAME = 'code,diameter,length,project_name,type,material_type,road_code';
                }
                if (layer == 'sanitation_system_layer') {
                    params.PROPERTYNAME = 'id,area,type';
                }
                if (layer == 'buildings_tax_status_layer')
                {
                    params.PROPERTYNAME = 'tax_code,bin,ward,owner_name,owner_contact,due_year';
                }
                if (layer == 'buildings_water_payment_status_layer')
                {
                    params.PROPERTYNAME = 'water_customer_id,bin,tax_code,ward,customer_name,customer_contact,due_year';
                }
                if (layer == 'toilets_layer') {
                    params.PROPERTYNAME = 'id,name,type,access_frm_nearest_road,male_seats,female_seats,male_or_female_facility_option,handicap_facility_option,children_facility_option,sanitary_supplies_disposal_option,owner,operator_or_maintainer,indicative_sign_option,fee_collected_option,caretaker_name,caretaker_contact_number,ward,bin,house_address,status_name,total_no_of_toilets,amount_of_fee_collected,frequency_of_fee_collected,pwd_seats,caretaker_gender,location_name,total_no_of_urinals,separate_facility_with_universal_design_option,owning_institution_name,operator_or_maintainer_name';
                }
                if (layer == 'landuses_layer')
                {
                    params.PROPERTYNAME = 'id,area_sqm,class';
                }
                if (layer == 'citypolys_layer')
                {
                    params.PROPERTYNAME = 'name,area';
                }
                if (layer == 'low_income_communities_layer')
                {
                    params.PROPERTYNAME = 'population_total,number_of_households,population_male,population_female,population_others,no_of_septic_tank,no_of_holding_tank,no_of_pit,no_of_sewer_connection,no_of_buildings,community_name,no_of_community_toilets';
                }

                var wmsSource = new ol.source.ImageWMS({
                    url: gurl_wms,
                    params: params
                });

                var url = wmsSource.getGetFeatureInfoUrl(
                    [+long, +lat], viewResolution, 'EPSG:3857',
                    {'INFO_FORMAT': 'application/json'});
                if (!url) {
                    Swal.fire({
                        title: "{{ __('Failed to generate URL!') }}",
                        icon: "error",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    })
                    return;
                }




                $('#feature_info_content').html('');

                displayAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {

                       if (data && data.features && Array.isArray(data.features)) {
                            if (data.features.length > 0) {
                                var html = '<div class="table-responsive">'+
                                    '<table class="table table-striped table-hover table-sm">'+
                                    '<thead class="table-info">'+
                                    '<tr>'+
                                    '<th>Attribute</th>'+
                            '<th>Value</th>'+
                            '</tr>'+
                            '</thead>'+
                            '<tbody>';

                            $.each(data.features[0].properties, function (k, v) {
                                    html += '<tr>';
                                    if (k == 'bin'  && layer != 'buildings_layer') {
                                        kk = 'BIN';
                                    }
                                    else if (k == 'owner_name') {
                                        kk = 'Owner Name';
                                    }else if (k == 'owner_gender') {
                                        kk = 'Owner Gender';
                                    }else if (k == 'owner_contact') {
                                        kk = 'Owner Contact';
                                    }else if (k == 'nid') {
                                        kk = 'NID';
                                    }
                                    else if (k == 'male_population') {
                                        kk = 'Male Population';
                                    }
                                    else if (k == 'female_population') {
                                        kk = 'Female Population';
                                    }
                                    else if (k == 'other_population') {
                                        kk = 'Other Population';
                                    }
                                    else if (k == 'diff_abled_male_pop') {
                                        kk = 'Differently Abled Male Population';
                                    }
                                    else if (k == 'diff_abled_female_pop') {
                                        kk = 'Differently Abled Female Population';
                                    }
                                    else if (k == 'population_with_private_toilet') {
                                        kk = 'Population with Private Toilet';
                                    }
                                    else if (k == 'household_with_private_toilet') {
                                        kk = 'Households with Private Toilet';
                                    }

                                    else if (k == 'diff_abled_others_pop') {
                                        kk = 'Differently Abled Other Population';
                                    }

                                    else if (k == 'house_address') {
                                        kk = 'House Number';
                                    }
                                    else if (k == 'house_locality') {
                                        kk = 'House Locality/Address';
                                    }else if (k == 'pit_diameter') {
                                        kk = 'Pit Diameter';
                                    }
                                    else if (k == 'gid') {
                                        kk = 'GID';
                                    }
                                    else if (k == 'house_number') {
                                        kk = 'House Number';
                                    }else if (k == 'holding') {
                                        kk = 'Holding';
                                    } else if (k == 'ward') {
                                        kk = 'Ward Number';
                                    } else if (k == 'toilet_status') {
                                        kk = 'Presence of Toilet';
                                    } else if (k == 'toilet_count') {
                                        kk = 'Number of Toilets';
                                    } else if (k == 'floor_count') {
                                        kk = 'Number of Floors';
                                    } else if (k == 'estimated_area') {
                                        kk = 'Estimated Area of the Building ( ㎡ )';
                                    } else if (k == 'road_code') {
                                        kk = 'Road Code';
                                     } else if (k == 'right_of_way') {
                                        kk = 'Right of Way (m)';
                                    }  else if (k == 'low_income_hh') {
                                        kk = 'Is Low Income House?';
                                    }
                                    else if (k == 'lic_community') {
                                        kk = 'LIC Name';
                                    }
                                    else if (k == 'building_associated_to') {
                                        kk = 'BIN of Main Building';
                                    } else if (k == 'containcd') {
                                        kk = 'Containment ID';
                                    } else if (k == 'office_business_name') {
                                        kk = 'Office or Business Name';
                                    } else if (k == 'structure_type_name') {
                                        kk = 'Structure Type';
                                    }
                                    else if (k == 'cover_type') {
                                        kk = 'Cover Type';
                                    }
                                    else if (k == 'material_type') {
                                        kk = 'Material Type';
                                    }
                                    else if (k == 'water_customer_id') {
                                        kk = 'Water Customer ID';
                                    }
                                    else if (k == 'watersupply_pipe_code') {
                                        kk = 'Water Supply Pipe Code';
                                    }
                                    else if (k == 'functional_use_name') {
                                        kk = 'Functional Use of Building';
                                        //bldguse = JSON.parse("{{$bldguse}}".replace(/&quot;/g, '"'));
                                        v != null ? v = v : v = "No data";
                                    } else if (k == 'use_category_name') {
                                        kk = 'Use Category of Building';
                                        //usecatg = JSON.parse("{{$usecatg}}".replace(/&quot;/g, '"'));
                                        v != null ? v = v : v = "No data";
                                    }
                                    else if (k == 'building_sanitation_system') {
                                        kk = 'Toilet Connection/ Defecation Area';
                                    }
                                    else if (k == 'toilet_name') {
                                        kk = 'Community Toilet Name';
                                    }
                                    
                                    else if (k == 'verification_status') {
                                        kk = 'Verification Status';
                                    }
                                    else if (k ==  'treatmentplant_type')
                                    {
                                        kk = 'Treatment Plant';
                                    }
                                    else if (k ==  'utilitysize')
                                    {
                                        kk = 'Width (mm)';
                                    }

                                    else if (k ==  'treatmentplant')
                                    {
                                        kk = 'Treatment Plant Type';
                                    }
                                    else if(k == 'status')
                                    {
                                        kk = 'Operational Status';
                                    }
                                    else if (k == 'surveyed_date') {
                                        kk = 'Surveyed Date';
                                        //( Commented - Might be needed in the future )
                                        /*} else if (k == 'draincode') {
                                            kk = 'Drain Code';
                                        } else if (k == 'drain_connected_date') {
                                            kk = 'Drain Connected Date';*/
                                    } else if (k == 'id') {
                                        kk = 'ID';
                                    } else if (k == 'type') {
                                        kk = 'Type';
                                        if (v ==1 ) {
                                            v = 'Centralized WWTP';
                                        }else if(v==2){
                                            v = 'Decentralized WWTP';
                                        }
                                        else if(v==3){
                                            v = 'FSTP';
                                        }
                                        else if(v==4){
                                            v = 'Co-Treatment Plant';
                                        }
                                    } else if (k == 'size') {
                                        kk = 'Containment Volume (m³)';
                                    } else if (k == 'location') {
                                        kk = 'Location';
                                    } else if (k == 'population_served') {
                                        kk = 'Population of Building';
                                    }  else if (k == 'household_served') {
                                        kk = 'Number of Households';
                                    } else if (k == 'roadcd') {
                                        kk = 'Road Code';
                                    } else if (k == 'pit_number') {
                                        kk = 'Pit Number';
                                    } else if (k == 'pit_diameter') {
                                        kk = 'Pit Diameter (m)';
                                    } else if (k == 'depth') {
                                        kk = 'Depth (m)';
                                    } else if (k == 'tank_length') {
                                        kk = 'Tank Length (m)';
                                    } else if (k == 'tank_width') {
                                        kk = 'Tank Width (m)';
                                    } else if (k == 'name') {
                                        kk = 'Name';
                                    } else if (k == 'next_emptying_date') {
                                        kk = 'Next Emptying Date';
                                    } else if (k == 'emptied_status') {
                                        kk = 'Emptied Status';
                                    } else if (k == 'next_emptying_date') {
                                        kk = 'Next Emptied Date';
                                    }
                                    else if (k == 'last_emptied_date') {
                                        kk = 'Last Emptied Date';
                                    } else if (k == 'no_of_times_emptied') {
                                        kk = 'No. of Times Emptied';
                                    }
                                    else if (k == 'code') {
                                        kk = 'Code';
                                    } else if (k == 'width') {
                                        kk = 'Width (m)';
                                    } else if (k == 'hierarchy') {
                                        kk = 'Hierarchy';
                                    } else if (k == 'surface_type') {
                                        kk = 'Surface Type';
                                    } else if (k == 'length') {
                                        kk = 'Length (m)';
                                    } else if (k == 'carrying_width') {
                                        kk = 'Carrying Width (m)';
                                    } else if (k == 'contact_info') {
                                        kk = 'Contact Info';
                                    } else if (k == 'roadhier') {
                                        kk = 'Road Hierarchy';
                                    } else if (k == 'rdsurf') {
                                        kk = 'Road Surface Type';
                                    } else if (k == 'lining') {
                                        kk = 'Buiding';
                                    } else if (k == 'area') {
                                        kk = 'Area (sqkm)';
                                    } else if (k == 'area_sqm') {
                                        kk = 'Area (sqm)';
                                    } else if (k == 'lengthm') {
                                        kk = 'Length (m)';
                                    } else if (k == 'cpacitym3') {
                                        kk = 'Capacity (m³)';
                                    }  else if (k == 'srvtype') {
                                        kk = 'Service Type';
                                    } else if (k == 'srvname') {
                                        kk = 'Service Name';
                                    } else if (k == 'no_build') {
                                        kk = 'No. of Buildings';
                                    } else if (k == 'no_contain') {
                                        kk = 'No. of Containments';
                                    } else if (k == 'total_rdlen') {
                                        kk = 'Total Length of Road';
                                    }
                                     else if (k == 'no_septic_tank') {
                                        kk = 'No. of Septic Tank';
                                    } else if (k == 'drain_code') {
                                        kk = 'Drain Code';
                                    } else if (k == 'sewer_code') {
                                        kk = 'Sewer Code';
                                    }  else if (k == 'office_business_name') {
                                        kk = 'Office Business Name';
                                    } else if (k == 'water_source_name') {
                                        kk = 'Main Drinking Water Source';
                                    }else if (k == 'water_quality_satisfaction') {
                                        kk = 'Water Quality Satisfaction';
                                    } else if (k == 'well_presence') {
                                        kk = 'Well Presence';
                                    } else if (k == 'septic_criteria') {
                                        kk = 'Septic Tank Standard Compliance';
                                    } else if (k == 'construction_date') {
                                        kk = 'Containment Construction Date';
                                    } else if (k == 'no_rcc_framed') {
                                        kk = 'No. of RCC Framed';
                                    } else if (k == 'no_load_bearing') {
                                        kk = 'No. of Load Bearing';
                                    } else if (k == 'no_wooden_mud') {
                                        kk = 'No. of Wooden Bearing';
                                    } else if (k == 'no_cgi_sheet') {
                                        kk = 'No. of CGI Sheet';
                                    } else if (k == 'roadname') {
                                        kk = 'Sewer Name';
                                    } else if (k == 'length_m_1') {
                                        kk = 'Length in Meters';
                                    } else if (k == 'sewer_p') {
                                        kk = 'Sewer P';
                                    } else if (k == 'sewer_loc') {
                                        kk = 'Sewer Location';
                                    } else if (k == 'sewer_size') {
                                        kk = 'Sewer Size';
                                    } else if (k == 'diameter') {
                                        kk = 'Diameter (mm)';
                                    }
                                    else if (k == 'treatment_plant_id') {
                                        kk = 'Treatment Plant';
                                    }else if (k == 'date_added') {
                                        kk = 'Date Added';
                                        v=v.substring(0,10);
                                    } else if (k == 'start_date') {
                                        kk = 'Start Date';
                                        v=v.substring(0,10);
                                    } else if (k == 'end_date') {
                                        kk = 'End Date';
                                        v=v.substring(0,10);
                                    } else if (k == 'project_name') {
                                        kk = 'Project Name';
                                    } else if (k == 'supported_by') {
                                        kk = 'Supported By';
                                    } else if (k == 'owner_name') {
                                        kk = 'Owner Name';
                                    } else if (k == 'owner_contact') {
                                        kk = 'Owner Contact Number';
                                    } else if (k == 'owner_gender') {
                                         kk = 'Owner Gender';
                                    }else if (k == 'nid') {
                                         kk = 'NID';
                                    }
                                     else if (k == 'desludging_vehicle_accessible') {
                                         kk = 'Building Accessible to Desludging Vehicle';
                                    } else if (k == 'construction_year') {
                                         kk = 'Construction Date';
                                    } else if (k == 'distance_from_well') {
                                         kk = 'Distance of Well from Closest Containment (m)';
                                    } else if (k == 'house_image') {
                                         kk = 'House Image';
                                    } else if (k == 'well_presence_status') {
                                         kk = 'Well in Premises';
                                    } else if (k == 'access_frm_nearest_road') {
                                         kk = 'Access From Nearest Road';
                                    } else if (k == 'caters_male_female') {
                                         kk = 'Caters Male Female';
                                    } else if (k == 'male_toilets') {
                                         kk = 'Male Toilets';
                                    } else if (k == 'female_toilets') {
                                         kk = 'Female Toilets';
                                    } else if (k == 'male_or_female_facility_option') {
                                         kk = 'Male or Female Facility';
                                    } else if (k == 'handicap_facility_option') {
                                         kk = 'Handicap Facility';
                                    } else if (k == 'children_facility_option') {
                                         kk = 'Children Facility';
                                    } else if (k == 'supplyy_disposal_facility') {
                                         kk = 'Supplyy Disposal Facility';
                                    } else if (k == 'owner') {
                                         kk = 'Owner';
                                    }
                                    else if (k == 'status_name') {
                                         kk = 'Status';
                                    }
                                    else if (k == 'status') {
                                         kk = 'Status';
                                    }
                                    else if (k == 'total_no_of_toilets') {
                                         kk = 'Total Number of Seats';
                                    }else if (k == 'amount_of_fee_collected') {
                                         kk = 'Uses Fee Rate';
                                    }
                                    else if (k == 'pwd_seats') {
                                         kk = 'No. of Seats for People with Disability Users';
                                    }else if (k == 'frequency_of_fee_collected') {
                                         kk = 'Frequency of Fee Collection';
                                    }   else if (k == 'location_name') {
                                         kk = 'Location';
                                    }  else if (k == 'operator_or_maintainer_name') {
                                         kk = 'Name of Operate and Maintained by';
                                    }    else if (k == 'total_no_of_urinals') {
                                         kk = 'Total Number of Urinals';
                                    } else if (k == 'separate_facility_with_universal_design_option') {
                                         kk = 'Adherence with Universal Design Principles';
                                    }  else if (k == 'owning_institution_name') {
                                         kk = 'Name of Owning Institution';
                                    }else if (k == 'operator_or_maintainer') {
                                         kk = 'Operator or Maintainer';
                                    } else if (k == 'not_used_time') {
                                         kk = 'Not Used Time';
                                    } else if (k == 'indicative_sign_option') {
                                         kk = 'Indicative Sign';
                                    } else if (k == 'fee_collected_option') {
                                         kk = 'Fee Collected';
                                    } else if (k == 'caretaker') {
                                         kk = 'Caretaker';
                                    } else if (k == 'capacity_per_day') {
                                         kk = 'Capacity Per Day (m³)';
                                    } else if (k == 'caretaker_name') {
                                         kk = 'Caretaker Name';
                                    } else if (k == 'caretaker_number') {
                                         kk = 'Caretaker Number';
                                    } else if (k == 'caretaker_gender') {
                                         kk = 'Caretaker Gender';
                                    }  else if (k == 'date') {
                                         kk = 'Date';
                                    }  else if (k == 'hotspot_location') {
                                         kk = 'Hotspot Location';
                                    }  else if (k == 'no_of_cases') {
                                         kk = 'No. of Cases';
                                    }
                                    else if (k == 'male_cases') {
                                         kk = 'Male';
                                    }
                                    else if (k == 'female_cases') {
                                         kk = 'Female';
                                    } else if (k == 'other_cases') {
                                         kk = 'Other';

                                    } else if (k == 'class') {
                                            kk = 'Class';
                                    } else if (k == 'no_of_fatalities') {
                                         kk = 'No. of Fatalities';
                                    }else if (k == 'female_fatalities') {
                                         kk = 'Female';
                                    }else if (k == 'male_fatalities') {
                                         kk = 'Male ';
                                    }else if (k == 'other_fatalities') {
                                         kk = 'Other ';
                                    }else if (k == 'disease') {
                                         kk = 'Disease';
                                    }
                                    else if (k == 'sample_date') {
                                         kk = 'Sample Date';
                                    }
                                    else if (k == 'sample_location') {
                                         kk = 'Sample Location';
                                    }
                                    else if (k == 'water_coliform_test_result') {
                                         kk = 'Water Coliform Test Result';
                                    }
                                    else if (k == 'tax_code') {
                                         kk = 'Tax Code/Holding ID';
                                    }  else if (k == 'due_year') {
                                         kk = 'Years Due';
                                    }  else if (k == 'male_seats') {
                                         kk = 'Male Seats';
                                    }  else if (k == 'female_seats') {
                                         kk = 'Female Seats';
                                    }  else if (k == 'sanitary_supplies_disposal_option') {
                                         kk = 'Sanitary Supplies Disposal Facility';
                                    }  else if (k == 'caretaker_contact_number') {
                                         kk = 'Caretaker Contact Number';
                                    }   else if (k == 'community_name') {
                                         kk = 'Community Name';
                                    }    else if (k == 'no_of_buildings') {
                                         kk = 'No. of Buildings';
                                    }   else if (k == 'no_of_community_toilets') {
                                         kk = 'No. of Community Toilets';
                                    }  else if (k == 'population_total') {
                                         kk = 'Population';
                                    }    else if (k == 'number_of_households') {
                                         kk = 'No. of Households';
                                    }    else if (k == 'population_male') {
                                         kk = 'Male Population';
                                    }  else if (k == 'population_female') {
                                         kk = 'Female Population';
                                    }  else if (k == 'population_others') {
                                         kk = 'Other Population';
                                    }  else if (k == 'no_of_septic_tank') {
                                         kk = 'No. of Septic Tanks';
                                    }    else if (k == 'no_of_holding_tank') {
                                         kk = 'No. of Holding Tanks';
                                    }   else if (k == 'no_of_pit') {
                                         kk = 'Number of Pit';
                                    }   else if (k == 'no_of_sewer_connection') {
                                         kk = 'No. of Sewer Connections';
                                    }    else if (k == 'no_emptying') {
                                         kk = 'No. of Emptying';
                                    }    else if (k == 'no_septic_tank_with_soak_pit') {
                                         kk = 'No. of Septic Tank With Soak Pit';
                                    }
                                    else if (k == 'containment_type') {
                                         kk = 'Containment Type';
                                    }
                                    else if (k == 'responsible_bin') {
                                         kk = 'Responsible BIN';
                                    }
                                    else if (k == 'notes') {
                                         kk = 'Notes';
                                    }
                                    else if (k == 'swm_customer_id') {
                                         kk = 'SWM Customer ID';
                                    }
                                    else if (k == 'disease_type') {
                                         kk = 'Disease';
                                    }
                                    else if (k == 'last_payment_date') {
                                         kk = 'SWM Customer ID';
                                    }
                                    else if (k == 'treatmentplant_type') {
                                         kk = 'Treatment Plant';
                                    }
                                    else {
                                        kk = k;
                                    }
                                    if (v == null) {
                                        vv = '';
                                    }
                                    else {
                                        if(v === true) {
                                            vv = 'Yes';
                                        }
                                        else if(v === false){
                                             vv = 'No';
                                        }
                                        else if (checkNumberIfFloat(v) == true){
                                            vv = v.toFixed(2);
                                        }
                                        else{
                                            vv = v;
                                        }
                                    }

                                if(k=='bin' && layer =='buildings_layer')
                                    {
                                        html += '<td><strong> BIN </strong></td>';
                                        html += '<td><strong>' + vv + '</strong>@can('Edit Building Structure')<br>Click <a href="{{ url("building-info/buildings") }}/'+vv+'/edit">here</a> to edit building info @endcan</td>';
                                    }
                                else if (k == 'house_image' && layer == 'buildings_layer') {
                                        let vv = data.features[0].properties.bin; 
                                        let basePath = "{{ asset('') }}";
                                      
                                        let imagePathJpg = basePath + 'storage/emptyings/houses/' + vv + '.jpg';
                                        let imagePathJpeg = basePath + 'storage/emptyings/houses/' + vv + '.jpeg';
                                        // let defaultImage ="{{ asset('emptyings/houses/default_img.jpg') }}";


                                        checkImageExistence(imagePathJpg)
                                                .done(function (data, textStatus, jqXHR) {
                                                    if (jqXHR.status === 200) {
                                                        html += `<div class="row">`;
                                                        html += `<div class="col-sm-3"><strong>${kk}</strong></div>`;
                                                        html += `<div class="col-sm-8"><img src="${imagePathJpg}" alt="House Image" style="max-width: 300px; max-height: 300px;" /></div>`;
                                                        html += `</div>`;

                                                        $('#feature_information').html(html);
                                                    }
                                                })
                                                .fail(function () {
                                                    checkImageExistence(imagePathJpeg)
                                                        .done(function (data, textStatus, jqXHR) {
                                                            if (jqXHR.status === 200) {
                                                                html += `<div class="row">`;
                                                                html += `<div class="col-sm-3"><strong>${kk}</strong></div>`;
                                                                html += `<div class="col-sm-8"><img src="${imagePathJpeg}" alt="House Image" style="max-width: 300px; max-height: 300px;" /></div>`;
                                                                html += `</div>`;

                                                                $('#feature_information').html(html);
                                                            }
                                                        })
                                                        .fail(function () {
                                                            html += `<div class="row">`;
                                                                html += `<div class="col-sm-6">${kk}</div>`;
                                                                html += `<div class="col-sm-5"><strong>No House Image</strong></div>`;
                                                                html += `</div>`;
                                                            $('#feature_information').html(html);
                                                        });
                                                });

                                    }
                                    else{
                                    html += '<td>' + kk + '</td>';
                                    html += '<td>' + vv + '</td>';
                                }
                                    html += '</tr>';
                                });
                                html += '</tbody></table>';
                                $('#feature_information').html(html);
                                removeAjaxLoader();
                            } else {
                                $('#feature_information').html('');
                                $('#feature-info-popup-closer').click();
                                displayAjaxErrorModal(@json(__('Feature Not Found')));
                            }
                        } else {
                            displayAjaxError();
                        }
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                });

                featureInfoPopupOverlay.setPosition(evt.coordinate);
            }
            function checkImageExistence(imagePath) {
                return $.ajax({
                    url: imagePath,
                    type: 'HEAD',
                    error: function() {

                    }
                });
            }



            /**
             * Elements that make up the popup.
             */
            var popupContainer = document.getElementById('popup');
            var popupContent = document.getElementById('popup-content');
            var popupCloser = document.getElementById('popup-closer');


            /**
             * Create an overlay to anchor the popup to the map.
             */
            var popupOverlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: popupContainer,
                autoPan: true,
                stopEvent: true,
                autoPanAnimation: {
                    duration: 250
                }
            }));

            $(popupContainer).show();

            map.addOverlay(popupOverlay);

            /**
             * Add a click handler to hide the popup.
             * @return {boolean} Don't follow the href.
             */
            popupCloser.onclick = function () {
                popupOverlay.setPosition(undefined);
                popupCloser.blur();
                return false;
            };

            //DRAGGABLE OVERLAYS 
// Drag interaction
            var ol_Drag = new ol.interaction.DragOverlay({
                overlays: [
                    reportPopupOverlay,
                    drainPotentialSummaryPopupOverlay,
                    waterBodyPopupOverlay,
                    wardBuildingsPopupOverlay,
                    bufferPolygonPopupOverlay,
                    roadPopupOverlay,
                    popupMarkerOverlay,
                    buildingsToRoadPopupOverlay,
                    pointBufferPopupOverlay,
                    exportCsvPopupOverlay,
                    populationPopupOverlay,
                    roadInaccessiblePopupOverlay,
                    feedbackPopupOverlay,
                    kmlDragDropPopupOverlay,
                ]
            });
            map.addInteraction(ol_Drag);

//END DRAGGABLE OVERLAYS


            // Display information about coordinate
            function displayCoordinateInformation(evt) {
                var coordinate = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');

                var html = '<div style="text-decoration:underline;">EPSG:3857</div>';
                html += '<table style="margin-bottom: 10px;">';
                html += '<tr>';
                html += '<td style="padding-right:5px;">Longitude</td>';
                html += '<td>' + evt.coordinate[0].toFixed(6) + '</td>';
                html += '</tr>';
                html += '<tr>';
                html += '<td style="padding-right:5px;">Latitude</td>';
                html += '<td>' + evt.coordinate[1].toFixed(6) + '</td>';
                html += '</tr>';
                html += '<table>';
                html += '<div style="text-decoration:underline;">EPSG:4326</div>';
                html += '<table>';
                html += '<tr>';
                html += '<td style="padding-right:5px;">Longitude</td>';
                html += '<td>' + coordinate[0].toFixed(6) + '</td>';
                html += '</tr>';
                html += '<tr>';
                html += '<td style="padding-right:5px;">Latitude</td>';
                html += '<td>' + coordinate[1].toFixed(6) + '</td>';
                html += '</tr>';
                html += '<table>';

                popupContent.innerHTML = html;
                popupOverlay.setPosition(evt.coordinate);
            }

            // Display popup form point buffer
            function displayPopupPointBuffer(evt) {
                var coordinate = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
                $('#point-buffer-long-pos').val(evt.coordinate[0].toFixed(6));
                $('#point-buffer-lat-pos').val(evt.coordinate[1].toFixed(6));
                $('#point-buffer-long').val(coordinate[0].toFixed(6));
                $('#point-buffer-lat').val(coordinate[1].toFixed(6));
                $('#pointbuffer_control').removeClass('map-control-active');
                map.un('singleclick', displayPopupPointBuffer);
                currentControl = "";
                $('#popup-point-buffer').modal('show');


            }


             // Display popup form isochrone map 
             function displayPopupToiletIsochrone(evt) {
                var coordinate = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
                $('#isochrone-long-pos').val(evt.coordinate[0].toFixed(6));
                $('#isochrone-lat-pos').val(evt.coordinate[1].toFixed(6));
                $('#isochrone-long').val(coordinate[0].toFixed(6));
                $('#isochrone-lat').val(coordinate[1].toFixed(6));
                $('#isochrone_control').removeClass('map-control-active');
                map.un('singleclick', displayPopupToiletIsochrone);
                currentControl = "";
                $('#popup-toilet-isochrone').modal('show');

            }
            

            function displayContainmentBuildings(field, val) {
                var url = '{{ url("maps/containment-buildings") }}' + '/' + field + '/' + val;

                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                            "_token": "{{ csrf_token() }}",
                    },
                    success: function (data) {
                        for (var i = 0; i < data.length; i++) {
                            if (data[i].lat && data[i].long) {
                                if (!eLayer.markers) {
                                    var markerLayer = new ol.layer.Vector({
                                        // visible: false,
                                        source: new ol.source.Vector()
                                    });

                                    addExtraLayer('markers', 'Markers', markerLayer);
                                    // showExtraLayer('markers');
                                }

                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(data[i].long), parseFloat(data[i].lat)], 'EPSG:4326', 'EPSG:3857'))
                                });

                                var style = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/building-purple.png'
                                    })
                                });

                                feature.setStyle(style);

                                eLayer.markers.layer.getSource().addFeature(feature);
                            }
                        }

                        showLayer('buildings_layer');
                    },
                    error: function (data) {

                    }
                });
            }

            function displayContainmentRoad(field, val) {
                var url = '{{ url("maps/containment-road") }}' + '/' + field + '/' + val;
                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function (dataResponse) {
                        if (dataResponse.c_lat && dataResponse.c_long && dataResponse.r_lat && dataResponse.r_long) {
                            if (!eLayer.markers) {
                                var markerLayer = new ol.layer.Vector({
                                    // visible: false,
                                    source: new ol.source.Vector()
                                });

                                addExtraLayer('markers', 'Markers', markerLayer);
                                // showExtraLayer('markers');
                            }

                            var markerFeature = new ol.Feature({
                                geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse.r_long), parseFloat(dataResponse.r_lat)], 'EPSG:4326', 'EPSG:3857'))
                            });

                            var markerStyle = new ol.style.Style({
                                image: new ol.style.Icon({
                                    anchor: [0.5, 1],
                                    src: '{{ url("/")}}/img/road-blue.png'
                                })
                            });

                            markerFeature.setStyle(markerStyle);

                            eLayer.markers.layer.getSource().addFeature(markerFeature);

                            if (!eLayer.line) {
                                var lineLayer = new ol.layer.Vector({
                                    // visible: false,
                                    source: new ol.source.Vector()
                                });

                                addExtraLayer('line', 'Line', lineLayer);
                                // showExtraLayer('line');
                            }

                            var lineFeature = new ol.Feature({
                                geometry: new ol.geom.LineString([
                                    ol.proj.transform([parseFloat(dataResponse.c_long), parseFloat(dataResponse.c_lat)], 'EPSG:4326', 'EPSG:3857'),
                                    ol.proj.transform([parseFloat(dataResponse.r_long), parseFloat(dataResponse.r_lat)], 'EPSG:4326', 'EPSG:3857')
                                ])
                            });

                            var lineStyle = new ol.style.Style({
                                stroke: new ol.style.Stroke({
                                    color: '#ff0000',
                                }),
                            });

                            lineFeature.setStyle(lineStyle);

                            eLayer.line.layer.getSource().addFeature(lineFeature);
                        }

                        showLayer('roadlines_layer');
                    },
                    error: function (dataResponse) {

                    }
                });
            }

            function displayBuildingRoad(field, val) {
                var url = '{{ url("maps/building-road") }}' + '/' + field + '/' + val;
                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function (dataResponse) {
                        if (dataResponse.c_lat && dataResponse.c_long && dataResponse.r_lat && dataResponse.r_long) {
                            if (!eLayer.markers) {
                                var markerLayer = new ol.layer.Vector({
                                    // visible: false,
                                    source: new ol.source.Vector()
                                });

                                addExtraLayer('markers', 'Markers', markerLayer);
                                // showExtraLayer('markers');
                            }

                            var markerFeature = new ol.Feature({
                                geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse.r_long), parseFloat(dataResponse.r_lat)], 'EPSG:4326', 'EPSG:3857'))
                            });

                            var markerStyle = new ol.style.Style({
                                image: new ol.style.Icon({
                                    anchor: [0.5, 1],
                                    src: '{{ url("/")}}/img/road-blue.png'
                                })
                            });

                            markerFeature.setStyle(markerStyle);

                            eLayer.markers.layer.getSource().addFeature(markerFeature);

                            if (!eLayer.line) {
                                var lineLayer = new ol.layer.Vector({
                                    // visible: false,
                                    source: new ol.source.Vector()
                                });

                                addExtraLayer('line', 'Line', lineLayer);
                                // showExtraLayer('line');
                            }

                            var lineFeature = new ol.Feature({
                                geometry: new ol.geom.LineString([
                                    ol.proj.transform([parseFloat(dataResponse.c_long), parseFloat(dataResponse.c_lat)], 'EPSG:4326', 'EPSG:3857'),
                                    ol.proj.transform([parseFloat(dataResponse.r_long), parseFloat(dataResponse.r_lat)], 'EPSG:4326', 'EPSG:3857')
                                ])
                            });

                            var lineStyle = new ol.style.Style({
                                stroke: new ol.style.Stroke({
                                    color: '#ff0000',
                                }),
                            });

                            lineFeature.setStyle(lineStyle);

                            eLayer.line.layer.getSource().addFeature(lineFeature);
                        }

                        showLayer('roadlines_layer');
                    },
                    error: function (dataResponse) {

                    }
                });
            }

            function findNearestRoad(evt) {
                var coordinate = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
                var long = coordinate[0];
                var lat = coordinate[1];

                if (eLayer.nearest_road_markers) {
                    eLayer.nearest_road_markers.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('nearest_road_markers', 'Nearest Road Markers', layer);
                }

                if (eLayer.nearest_road_line) {
                    eLayer.nearest_road_line.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('nearest_road_line', 'Nearest Road Line', layer);
                }

                // showExtraLayer('nearest_road_markers');
                // showExtraLayer('nearest_road_line');

                var markerFeature = new ol.Feature({
                    geometry: new ol.geom.Point(ol.proj.transform(coordinate, 'EPSG:4326', 'EPSG:3857'))
                });

                var markerStyle = new ol.style.Style({
                    image: new ol.style.Icon({
                        anchor: [0.5, 1],
                        src: '{{ url("/")}}/img/pin-blue.png'
                    })
                });

                markerFeature.setStyle(markerStyle);

                eLayer.nearest_road_markers.layer.getSource().addFeature(markerFeature);

                displayAjaxLoader();
                var url = '{{ url("maps/nearest-road") }}' + '/' + long + '/' + lat;
                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",

                    },
                    success: function (dataResponse) {
                        if (dataResponse.lat && dataResponse.long) {
                            var markerFeature1 = new ol.Feature({
                                geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse.long), parseFloat(dataResponse.lat)], 'EPSG:4326', 'EPSG:3857'))
                            });

                            var markerStyle1 = new ol.style.Style({
                                image: new ol.style.Icon({
                                    anchor: [0.5, 1],
                                    src: '{{ url("/")}}/img/road-yellow.png'
                                })
                            });

                            markerFeature1.setStyle(markerStyle1);

                            eLayer.nearest_road_markers.layer.getSource().addFeature(markerFeature1);

                            var lineFeature = new ol.Feature({
                                geometry: new ol.geom.LineString([
                                    ol.proj.transform(coordinate, 'EPSG:4326', 'EPSG:3857'),
                                    ol.proj.transform([parseFloat(dataResponse.long), parseFloat(dataResponse.lat)], 'EPSG:4326', 'EPSG:3857')
                                ])
                            });

                            var lineStyle = new ol.style.Style({
                                stroke: new ol.style.Stroke({
                                    color: '#9000ff',
                                }),
                            });

                            lineFeature.setStyle(lineStyle);

                            eLayer.nearest_road_line.layer.getSource().addFeature(lineFeature);
                        }

                        // map.getView().setCenter(ol.proj.transform([long, lat], 'EPSG:4326', 'EPSG:3857'));
                        // map.getView().setZoom(14);

                        removeAjaxLoader();

                        showLayer('roadlines_layer');
                    },
                    error: function (dataResponse) {
                        displayAjaxError();
                    }
                });
            }

            // Display markers on buildings that have taxes on due
            function displayDueBuildings() {
                if (eLayer.building_markers) {
                    eLayer.building_markers.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('building_markers', 'Building Markers', layer);
                }

                // showExtraLayer('building_markers');

                displayAjaxLoader();
                var url = '{{ url("maps/due-buildings") }}';
                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                            "_token": "{{ csrf_token() }}",

                        },
                    success: function (dataResponse) {
                        for (var i = 0; i < dataResponse.length; i++) {
                            if (dataResponse[i].lat && dataResponse[i].long) {
                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse[i].long), parseFloat(dataResponse[i].lat)], 'EPSG:4326', 'EPSG:3857'))
                                });

                                var style = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/building-green.png'
                                    })
                                });

                                feature.setStyle(style);

                                eLayer.building_markers.layer.getSource().addFeature(feature);
                            }
                        }

                        removeAjaxLoader();

                        showLayer('buildings_layer');
                        zoomToCity();
                    },
                    error: function (dataResponse) {
                        displayAjaxError();
                    }
                });
            }

            // Display markers on containments of applications
            function displayApplicationContainments() {
                if (eLayer.application_containment_markers) {
                    eLayer.application_containment_markers.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('application_containment_markers', 'Building Markers', layer);
                }

                // showExtraLayer('application_containment_markers');

                displayAjaxLoader();
                var url = '{{ url("maps/application-containments") }}';
                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",

                    },
                    success: function (dataResponse) {
                        for (var i = 0; i < dataResponse.length; i++) {
                            if (dataResponse[i].lat && dataResponse[i].long) {
                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse[i].long), parseFloat(dataResponse[i].lat)], 'EPSG:4326', 'EPSG:3857')),
                                    application_id: dataResponse[i].application_id,
                                    house_number: dataResponse[i].bin,
                                    service_provider: dataResponse[i].service_provider,
                                    application_date: dataResponse[i].application_date
                                });

                                var filename = 'application.png';
                                if (dataResponse[i].sludge_collection_status) {
                                    filename = 'application-sludge-collection.png';
                                }
                                else if (dataResponse[i].feedback_status) {
                                    filename = 'application-feedback.png';
                                } else if (dataResponse[i].emptying_status) {
                                    filename = 'application-emptying.png';
                                }

                                var style = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/' + filename,
                                        scale: 0.4
                                    })
                                });

                                feature.setStyle(style);

                                eLayer.application_containment_markers.layer.getSource().addFeature(feature);
                            }
                        }

                        removeAjaxLoader();

                        showLayer('containments_layer');
                        zoomToCity();
                    },
                    error: function (dataResponse) {
                        displayAjaxError();
                    }
                });
            }


            // Display markers on applications not reached to treatment plants
            function displayApplicationNotTP() {
                if (eLayer.application_notTP_markers) {
                    eLayer.application_notTP_markers.layer.getSource().clear();
                }
                if (eLayer.application_NotTPDate_containment_markers) {
                    eLayer.application_NotTPDate_containment_markers.layer.getSource().clear();
                }
                if (eLayer.application_NotTP_containment_markers) {
                    eLayer.application_NotTP_containment_markers.layer.getSource().clear();
                }

                var layer = new ol.layer.Vector({
                    // visible: false,
                    source: new ol.source.Vector()
                });

                addExtraLayer('application_notTP_markers', 'Applications Markers', layer);


                displayAjaxLoader();
                var url = '{{ url("maps/application-not-tp") }}';
                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",

                    },
                    success: function (dataResponse) {
                        var service_provider_legends = '';
                        var service_provider_markers = {};
                        dataResponse["service_providers"].forEach(function(service_provider,index){
                            service_provider_legends += "<tr>" +
                                '<td style="vertical-align: top;">' +
                                '<img src="{{ url("/")}}/img/marker-' + markerColors[index] + '.png"></td>' +
                                '<td> - ' + service_provider.company_name + '</td>'+
                                '</tr>';
                            service_provider_markers[service_provider.company_name]=markerColors[index];
                        });
                        $('#service_providers_legends').html(service_provider_legends);

                        for (var i = 0; i < dataResponse["data"].length; i++) {
                            if (dataResponse["data"][i].lat && dataResponse["data"][i].long) {
                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse["data"][i].long), parseFloat(dataResponse["data"][i].lat)], 'EPSG:4326', 'EPSG:3857')),
                                    application_id: dataResponse["data"][i].application_id,
                                    house_number: dataResponse["data"][i].bin,
                                    application_date: dataResponse["data"][i].application_date,
                                    service_provider: dataResponse["data"][i].service_provider,
                                    service_provider_id: dataResponse["data"][i].service_provider_id,
                                    emptying_date: dataResponse["data"][i].emptying_date
                                });

                                var filename = 'application.png';

                                var styles = [
                                    new ol.style.Style({
                                        image: new ol.style.Icon({
                                            anchor: [0.5, 1],
                                            src: '{{ url("/")}}/img/marker-' + service_provider_markers[dataResponse["data"][i].service_provider] + '.png',
                                            scale: 0.5
                                        })
                                    })
                                ];

                                feature.setStyle(styles);
                                eLayer.application_notTP_markers.layer.getSource().addFeature(feature);
                            }
                        }

                        removeAjaxLoader();
                        showLayer('containments_layer');
                        zoomToCity();
                    },
                    error: function (dataResponse) {
                        displayAjaxError();
                    }
                });
            }

            // Add handler to building search form submit
            $('#building_search_form').submit(function () {
                var val = $('#building_value_text').val().trim();
                var field = $('#building_field_select').val();
                var field_text = $('#building_field_select option:selected').text();

                if (!val) {
                    Swal.fire({
                        title: '{{ __("Please enter") }}' + ' ' + field_text + '!',
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    });
                } else if (field == 'places_layer' || field == 'roadlines_layer' || field == ' bin') {

                    findPlacesRoads(val, field);
                } else {
                    if (eLayer.searchResultBuilding) {
                        eLayer.searchResultBuilding.layer.getSource().clear();
                    } else {
                        var searchResultBuildingLayer = new ol.layer.Vector({
                            // visible: false,
                            source: new ol.source.Vector(),
                            /*style: new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#00bfff',
                        width: 3
                    }),
                    })*/
                        });

                        addExtraLayer('searchResultBuilding', 'Search Result Building', searchResultBuildingLayer);
                    }

                    // showExtraLayer('searchResultBuilding');

                    displayAjaxLoader();
                    var url = '{{ url("maps/search-building") }}' + '/' + field + '/' + val;
                    $.ajax({
                        url: url,
                        type: 'get',
                        data: {
                            "_token": "{{ csrf_token() }}",

                        },
                        success: function (dataResponse) {
                            if (dataResponse) {
                                //eLayer.searchResultBuilding.layer.getSource().addFeatures((new ol.format.GeoJSON()).readFeatures(dataResponse));

                                if (dataResponse && Array.isArray(dataResponse)) {
                                    var format = new ol.format.WKT();

                                    for (var i = 0; i < dataResponse.length; i++) {
                                        var feature = format.readFeature(dataResponse[i].geom, {
                                            dataProjection: 'EPSG:4326',
                                            featureProjection: 'EPSG:3857'
                                        });
                                        if (dataResponse[i].building_associated_to == null) {
                                            var colorFeature = '#008000';
                                        } else {
                                            var colorFeature = '#00bfff';
                                        }
                                        feature.setStyle(
                                            new ol.style.Style({
                                                stroke: new ol.style.Stroke({
                                                    color: colorFeature,
                                                    width: 3
                                                })
                                            })
                                        );
                                        eLayer.searchResultBuilding.layer.getSource().addFeature(feature);
                                        if(dataResponse[0].bin){
                                            handleZoomToExtent('buildings_layer', 'bin', dataResponse[0].bin, false, function () {
                                            removeAjaxLoader();
                                            });
                                        }
                                        if(dataResponse[0].house_number){
                                            handleZoomToExtent('buildings_layer', 'house_number', dataResponse[0].house_number, false, function () {
                                            removeAjaxLoader();
                                            });
                                        }

                                    }
                                }

                                removeAjaxLoader();
                            } else {
                                displayAjaxErrorModal(@json(__('Building Not Found')));
                            }
                        },
                        error: function (dataResponse) {
                            displayAjaxError();
                        }
                    });
                }

                return false;
            });


            // Add handler to ward filter while removing other
           $('#filterward_select').on('change', function(e) {
                e.preventDefault();
                disableAllControls();
                $('#print_modal').modal('hide');
                var ward = $('#filterward_select').val();

                if(eLayer.searchResultMarkers) {
                    eLayer.searchResultMarkers.layer.getSource().clear();
                }
                else {
                    var searchResultMarkerLayer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('searchResultMarkers', 'Search Result Markers', searchResultMarkerLayer);
                }
                //displayAjaxLoader();
                var url = '{{ url("maps/clipward/ward-center-coordinates") }}';
                $.ajax({
                    url:url,
                    type: 'get',
                    data: { ward: ward },
                    success: function(data){
                        var format = new ol.format.WKT();
                        var feature = format.readFeature(data.geom, {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        });

                        feature.setStyle(
                            new ol.style.Style({

                                fill: new ol.style.Fill({
                                    color: 'rgba(239,241,246,1.0)'
                                }),
                                stroke: new ol.style.Stroke({
                                    color: 'rgba(239,241,246,1.0)'
                                })
                            })
                        );
                        eLayer.searchResultMarkers.layer.getSource().addFeature(feature);
                        handleZoomToExtent('ward_overlay', 'ward', data.ward, false, function(){
                            removeAjaxLoader();
                        });
                    },
                    error: function(data) {
                        displayAjaxError();
                    }
                });
            });


            // Add handler to text search form submit
            $('#text_search_form').submit(function () {
                var keywords = $('#search_keyword_text').val().trim();
                var layer = $('#search_layer_select').val();

                if (!keywords) {
                    Swal.fire({
                        title: "{{ __('Please type keyword to search!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    });
                    return;
                } else {
                    if (eLayer.searchResultMarkers) {
                        eLayer.searchResultMarkers.layer.getSource().clear();
                    } else {
                        var searchResultMarkerLayer = new ol.layer.Vector({
                            // visible: false,
                            source: new ol.source.Vector()
                        });

                        addExtraLayer('searchResultMarkers', 'Search Result Markers', searchResultMarkerLayer);
                    }

                    // showExtraLayer('markers');

                    displayAjaxLoader();
                    var url = '{{ url("searchByKeywords") }}';
                    $.ajax({
                        url: url,
                        data: {
                            layer: layer,
                            keywords: keywords,
                            "_token": "{{ csrf_token() }}",
                        },
                        type: 'get',
                        success: function (dataResponse) {
                            if (dataResponse.gid) {
                                if (dataResponse.point) {
                                    var format = new ol.format.WKT();
                                    var markerFeature = format.readFeature(dataResponse.point, {
                                        dataProjection: 'EPSG:4326',
                                        featureProjection: 'EPSG:3857'
                                    });

                                    var markerStyle = new ol.style.Style({
                                        image: new ol.style.Icon({
                                            anchor: [0.5, 1],
                                            src: '{{ url("/")}}/img/pin-purple.png'
                                        })
                                    });

                                    markerFeature.setStyle(markerStyle);

                                    eLayer.searchResultMarkers.layer.getSource().addFeature(markerFeature);
                                }

                                if (dataResponse.geom) {
                                    var format = new ol.format.WKT();
                                    var feature = format.readFeature(dataResponse.geom, {
                                        dataProjection: 'EPSG:4326',
                                        featureProjection: 'EPSG:3857'
                                    });

                                    if (feature.getGeometry() instanceof ol.geom.MultiLineString) {
                                        eLayer.searchResultMarkers.layer.getSource().addFeature(feature);
                                    }
                                }

                                handleZoomToExtent(layer, 'gid', dataResponse.gid, false, function () {
                                    removeAjaxLoader();
                                });
                            } else {
                                displayAjaxErrorModal(@json(__('No Results Found.')));
                            }
                        },
                        error: function (dataResponse) {
                            displayAjaxError();
                        }
                    });
                }

                return false;
            });

            //search places and roads
            function findPlacesRoads(keywords, layer) {
                if (!keywords) {
                    Swal.fire({
                        title: "{{ __('Please type keyword to search!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    });
                    return;
                } else {
                    if (eLayer.searchResultMarkers) {
                        eLayer.searchResultMarkers.layer.getSource().clear();
                    } else {
                        var searchResultMarkerLayer = new ol.layer.Vector({
                            // visible: false,
                            source: new ol.source.Vector()
                        });

                        addExtraLayer('searchResultMarkers', 'Search Result Markers', searchResultMarkerLayer);
                    }

                    // showExtraLayer('markers');

                    displayAjaxLoader();
                    var url = '{{ url("maps/search-by-keywords") }}';

                    $.ajax({
                        url: url,
                        data: {
                            layer: layer,
                            keywords: keywords,
                            "_token": "{{ csrf_token() }}",
                        },
                        type: 'get',
                        success: function (data) {
                            if (data.gid) {
                                if (data.point) {
                                    var format = new ol.format.WKT();
                                    var markerFeature = format.readFeature(data.point, {
                                        dataProjection: 'EPSG:4326',
                                        featureProjection: 'EPSG:3857'
                                    });

                                    var markerStyle = new ol.style.Style({
                                        image: new ol.style.Icon({
                                            anchor: [0.5, 1],
                                            src: '{{ url("/")}}/img/pin-purple.png'
                                        })
                                    });

                                    markerFeature.setStyle(markerStyle);

                                    eLayer.searchResultMarkers.layer.getSource().addFeature(markerFeature);
                                }

                                if (data.geom) {
                                    var format = new ol.format.WKT();
                                    var feature = format.readFeature(data.geom, {
                                        dataProjection: 'EPSG:4326',
                                        featureProjection: 'EPSG:3857'
                                    });

                                    if (feature.getGeometry() instanceof ol.geom.MultiLineString) {
                                        eLayer.searchResultMarkers.layer.getSource().addFeature(feature);
                                    }
                                }
                                if (layer == 'roadlines_layer') {
                                    var column = 'code';
                                } else {
                                    var column = 'id';
                                }
                                handleZoomToExtent(layer, column, data.gid, false, function () {
                                    removeAjaxLoader();
                                });
                            } else {
                                displayAjaxErrorModal("{{ __('No Results Found.') }}");
                            }
                        },
                        error: function (data) {
                            displayAjaxError();
                        }
                    });
                }

                return false;

            }

            // Add handler to ward filer form submit
            $('#ward_form').submit(function () {
                var selectedWards = $('#ward').val();
                var selectedLayer = $('#ward_overlay').val();
                if (!selectedWards || selectedWards.length === 0 || !selectedLayer || selectedLayer.length === 0) {
                    Swal.fire({
                        title: "{{ __('Please select wards and overlay!') }}",
                        icon: 'warning',
                        button: "{{ __('Close') }}",
                        className: 'custom-swal',
                    });
                    // Prevent the form from submitting
                    event.preventDefault();
                    return false;
                }
                if (selectedWards && selectedLayer) {
                    var filters = [];
                    $.each(selectedWards, function (index, value) {
                        filters.push('ward=' + value);
                    });

                    mFilter.ward = "INTERSECTS(geom, collectGeometries(queryCollection('" + workspace + ":wards_layer', 'geom', '" + filters.join(' OR ') + "')))";

                    $.each(mLayer, function (key, value) {
                        if (selectedLayer == key) {
                            addFilterToLayer(key, 'ward');
                        } else {
                            removeFilterFromLayer(key, 'ward');
                        }
                    });

                    // showLayer('wards_layer');
                } else {
                    mFilter.ward = '';
                    $.each(mLayer, function (key, value) {
                        removeFilterFromLayer(key, 'ward');
                    });
                }

                updateAllCQLFiltersParams();
                if (selectedLayer) {
                    showLayer(selectedLayer);
                }
                return false;
            });

            // Add handler to ward filter clear button
            $('#ward_clear_button').click(function () {
                $('#ward_form').submit();
                $('#ward_overlay').val('');
                $('#ward').multipleSelect('uncheckAll');

            });


            // Add handler to tax due buildings filer form submit
            $('#tax_due_buildings_form').submit(function () {
                var selectedWards = $('#ward_tax_due').val();
                var selectedTaxZones = $('#tax_zone_tax_due').val();

                if (eLayer.building_markers) {
                    eLayer.building_markers.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('building_markers', 'Building Markers', layer);
                }

                // showExtraLayer('building_markers');

                displayAjaxLoader();
                var url = '{{ url("maps/due-buildings-ward-taxzone") }}';
                $.ajax({
                    url: url,
                    type: 'post',
                    data: {selectedWards: selectedWards, selectedTaxZones: selectedTaxZones, "_token": "{{ csrf_token() }}"},
                    success: function (data) {
                        for (var i = 0; i < data.length; i++) {
                            if (data[i].lat && data[i].long) {
                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(data[i].long), parseFloat(data[i].lat)], 'EPSG:4326', 'EPSG:3857'))
                                });

                                var style = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/building-green.png'
                                    })
                                });

                                feature.setStyle(style);

                                eLayer.building_markers.layer.getSource().addFeature(feature);
                            }
                        }

                        removeAjaxLoader();

                        showLayer('buildings_layer');
                        zoomToCity();
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                });

                return false;
            });
            // Add handler to wardtaxzone filter clear button
            $('#wardtaxzone_clear_button').click(function () {
                $('#ward_tax_due').multipleSelect('uncheckAll');
                $('#tax_zone_tax_due').multipleSelect('uncheckAll');
                $('#tax_due_buildings_form').submit();
            });



            // Add handler to export ward filter to csv button
            $('#export_ward_filter_csv').click(function (e) {
                e.preventDefault();
                exportWardFilter('csv');
            });


            // Add handler to export ward filter to kml button
            $('#export_ward_filter_kml').click(function (e) {
                e.preventDefault();
                exportWardFilter('kml');
            });

            // Add handler to export ward filter to shp button
            $('#export_ward_filter_shp').click(function (e) {
                e.preventDefault();
                exportWardFilter('shp');
            });

            function exportWardFilter(outputFormat) {
                if (['csv', 'kml', 'shp'].indexOf(outputFormat) == -1) {
                    return;
                }

                var selectedWards = $('#ward').val();
                var selectedLayer = $('#ward_overlay').val();
                if (!selectedWards || selectedWards.length === 0 || !selectedLayer || selectedLayer.length === 0) {
                    Swal.fire({
                        title: "{{ __('Please select wards and overlay!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    });
                    return;
                }

                var filters = [];
                $.each(selectedWards, function (index, value) {
                    filters.push('ward=' + value);
                });

                var outputFormat, propertyName;
                var exportLink = gurl_wfs + "?request=GetFeature&service=WFS&version=1.0.0&typeName=" + workspace + ":" + selectedLayer + "&CQL_FILTER=deleted_at is null AND INTERSECTS(geom, collectGeometries(queryCollection('" + workspace + ":wards_layer', 'geom', '" + filters.join(' OR ') + "')))";

                csvdata(outputFormat, selectedLayer, exportLink);

        }

        function csvdata(outputFormat, selectedLayer, exportLink)
        {
            const layerFilenames = {
                    'treatmentplants_layer': '{{ __("Treatment Plants") }}',
                    'drains_layer': '{{ __("Drain Network") }}',
                    'roadlines_layer': '{{ __("Road Network") }}',
                    'sewerlines_layer': '{{ __("Sewer Network") }}',
                    'buildings_tax_status_layer': '{{ __("Property Tax Collection ISS") }}',
                    'buildings_water_payment_status_layer': '{{ __("Water Supply ISS") }}',
                    'watersupply_network_layer': '{{ __("Water Supply Network") }}',
                    'wardboundary_layer': '{{ __("Ward Boundary") }}',
                    'water_samples_layer' : '{{ __("Water Samples") }}',
                    'toilets_layer': '{{ __("Toilets PTCT") }}',
                    'buildings_layer': '{{ __("Buildings") }}',
                    'buildings_swm_payment_status_layer' : '{{ __("SWM ISS") }}',
                    'containments_layer' : '{{ __("Containment") }}',
                    'buildings_layer' : '{{ __("Buildings") }}',
                    'low_income_communities_layer': '{{ __("Low Income Community") }}',
                    'grids_layer': '{{ __("Summarized Grids") }}',
                    'landuses_layer': '{{ __("Land Use") }}',
                    'waterbodys_layer': '{{ __("Water Bodies") }}',
                    'places_layer': '{{ __("Places") }}',
                    'sanitation_system_layer': '{{ __("Sanitation System") }}',
                    'waterborne_hotspots_layer': '{{ __("Waterborne Hotspots") }}',

                };

            const propertyNames = {
                    'buildings_layer' : 'bin,owner_name,nid,owner_gender,owner_contact,building_associated_to,ward,road_code,house_number,house_locality,tax_code,structure_type_name,surveyed_date,construction_year,floor_count,functional_use_name,use_category_name,office_business_name,household_served,male_population,female_population,other_population,population_served,diff_abled_male_pop,diff_abled_female_pop,diff_abled_others_pop,low_income_hh,lic_community,water_source_name,water_customer_id,watersupply_pipe_code,well_presence_status,distance_from_well,swm_customer_id,toilet_status,toilet_count,household_with_private_toilet,population_with_private_toilet,building_sanitation_system,sewer_code,drain_code,desludging_vehicle_accessible,estimated_area,toilet_name,verification_status',
                    'containments_layer' : 'id,containment_type,tank_length,tank_width,depth,pit_diameter,size,location,septic_criteria,construction_date,emptied_status,last_emptied_date,next_emptying_date,no_of_times_emptied,responsible_bin',
                    'places_layer':'name',
                    'grids_layer':'no_build,no_contain,total_rdlen,no_rcc_framed,no_load_bearing,no_wooden_mud,no_cgi_sheet,no_emptying,no_septic_tank',
                    'landuses_layer':'id,area_sqm,class',
                    'waterbodys_layer': 'id,name,type',
                    'water_samples_layer': 'sample_date,sample_location,water_coliform_test_result,',
                    'toilets_layer': 'bin,id,name,type,access_frm_nearest_road,male_seats,female_seats,male_or_female_facility_option,handicap_facility_option,children_facility_option,sanitary_supplies_disposal_option,owner,operator_or_maintainer,indicative_sign_option,fee_collected_option,caretaker_name,caretaker_contact_number,ward,bin,status_name,total_no_of_toilets,amount_of_fee_collected,frequency_of_fee_collected,pwd_seats,caretaker_gender,location_name,total_no_of_urinals,separate_facility_with_universal_design_option,owning_institution_name,operator_or_maintainer_name',
                    'wardboundary_layer': 'ward,area',
                    'treatmentplants_layer': 'name,location,capacity_per_day,caretaker_name,caretaker_number,caretaker_gender,treatmentplant',
                    'drains_layer': 'code,cover_type,surface_type,utilitysize,length,treatmentplant_type',
                    'roadlines_layer': 'code,name,carrying_width,hierarchy,surface_type,length,right_of_way',
                    'sewerlines_layer': 'code,diameter,location,length,road_code,treatmentplant_type',
                    'waterborne_hotspots_layer': 'hotspot_location,ward,disease_type,no_of_cases,male_cases,female_cases,other_cases,no_of_fatalities,male_fatalities,female_fatalities,other_fatalities,notes',
                    'buildings_tax_status_layer': 'tax_code,bin,ward,owner_name,owner_contact,due_year',
                    'buildings_water_payment_status_layer': 'water_customer_id,bin,ward,customer_name,customer_contact,due_year',
                    'watersupply_network_layer': 'code,diameter,length,project_name,type,material_type',
                    'buildings_swm_payment_status_layer' : 'swm_customer_id,bin,ward,customer_name,customer_contact,due_year',
                    'low_income_communities_layer' : 'population_total,number_of_households,population_male,population_female,population_others,no_of_septic_tank,no_of_holding_tank,no_of_pit,no_of_sewer_connection,no_of_buildings,community_name,no_of_community_toilets',
                    'sanitation_system_layer': 'id,area,type',
                };

            const customFilename = layerFilenames[selectedLayer] || selectedLayer;

            const setExportLink = (format, ext) => {
                exportLink += `&outputFormat=${format}`;
                if (ext) exportLink += `&format_options=filename:${customFilename}.${ext}`;
                window.open(exportLink);
            };

            switch (outputFormat.toLowerCase()) {
                case 'csv':
                    const propertyName = propertyNames[selectedLayer];
                    exportLink += "&outputFormat=CSV";
                    if (propertyName) {
                        exportLink += `&propertyName=${propertyName}`;
                    }
                    fetch(exportLink)
                        .then(response => response.text())
                        .then(csvData => {
                            const transformedCSV = transformCSVAfterDownload(csvData);
                            const blob = new Blob([transformedCSV], { type: 'text/csv' });
                            const url = URL.createObjectURL(blob);
                            const downloadLink = document.createElement('a');
                            downloadLink.href = url;
                            downloadLink.setAttribute('download', `${customFilename}.csv`);
                            document.body.appendChild(downloadLink);
                            downloadLink.click();
                            document.body.removeChild(downloadLink);
                        })
                        .catch(error => console.error('Error fetching CSV:', error));
                    break;
                case 'shp':
                case 'shape-zip':
                    setExportLink('SHAPE-ZIP', 'zip');
                    break;
                case 'kml':
                    setExportLink('KML', 'kml');
                    break;
                default:
                    console.error("{{ __('Unsupported format') }}"+':', outputFormat);
            }
        }


            // Function to transform CSV after download
            function transformCSVAfterDownload(csvData) {

                var nameMapping = {
                    'id': 'ID',
                    'sample_date': 'Sample Date',
                    'sample_location': 'Sample Location',
                    'water_coliform_test_result': 'Water Coliform Test Result',
                    'longitude': 'Longitude',
                    'latitude': 'Latitude',
                    'name': 'Name',
                    'type': 'Type',
                    'access_frm_nearest_road':'Distance from Nearest Road (m)',
                    'male_seats': 'No. of Seats for Male Users',
                    'female_seats': 'No. of Seats for Female Users',
                    'male_or_female_facility_option': 'Separate Facility for Male and Female',
                    'handicap_facility_option': 'Separate Facility for People with Disability',
                    'children_facility_option':'Separate Facility for Children',
                    'sanitary_supplies_disposal_option':'Sanitary Supplies and Disposal Facilities',
                    'owner':'Owning Institution ',
                    'operator_or_maintainer':'Operate and Maintained by',
                    'indicative_sign_option':'Presence of Indicative Sign',
                    'fee_collected_option':'Uses Fee Collection',
                    'caretaker_name':'Caretaker Name',
                    'caretaker_contact_number':'Caretaker Contact ',
                    'ward':'Ward',
                    'bin': 'BIN',
                    'status': 'Operational Status',
                    'total_no_of_toilets':'Total Number of Seats ',
                    'amount_of_fee_collected': 'Uses Fee Rate',
                    'frequency_of_fee_collected':'Frequency of Fee Collection ',
                    'pwd_seats':'No. of Seats for People with Disability Users',
                    'caretaker_gender':'Caretaker Gender',
                    'location_name':'Location',
                    'total_no_of_urinals':'Total Number of Urinals ',
                    'separate_facility_with_universal_design_option':'Adherence with Universal Design Principles',
                    'owning_institution_name':'Name of Owning Institution',
                    'operator_or_maintainer_name':'Name of Operate and Maintained by',
                    'area':'Area',
                    'capacity_per_day' :'Capacity Per Day (m3)',
                    'caretaker_number'  :'Caretaker Number',
                    'code':'Code',
                    'cover_type':'Cover Type',
                    'surface_type':'Surface Type',
                    'size':'Width (mm)',
                    'length':'Length (m)',
                    'treatment_plant_id' : 'Treatment Plant',
                    'carrying_width':'Carrying Width (m)',
                    'hierarchy' : 'Hierarchy',
                    'right_of_way' : 'Right of Way (m)',
                    'diameter':'Diameter (mm)',
                    'project_name': 'Project Name',
                    'material_type':'Material Type',
                    'location': 'Location',
                    'hotspot_location': 'Hotspot Location',
                    'no_of_cases':'No. of Cases',
                    'male_cases': 'Male Cases',
                    'female_cases': 'Female Cases',
                    'other_cases': 'Other Cases',
                    'no_of_fatalities':'No. of Fatalities',
                    'male_fatalities':'Male Fatalities',
                    'female_fatalities':'Female Fatalities',
                    'other_fatalities': 'Other Fatalities',
                    'disease': 'Infected Disease',
                    'notes':'Notes',
                    'tax_code' : 'Tax Code',
                    'due_year': 'Due Year',
                    'last_payment_date' : 'Last Payment Date',
                    'treatmentplant' : 'Treatment Plant Type',
                    'no_build':'No. of Buildings',
                    'no_contain':'No. of Containments',
                    'total_rdlen':'Total Length of Road',
                    'no_rcc_framed':'No. of RCC Framed',
                    'no_load_bearing':'No. of Load Bearing',
                    'no_wooden_mud':'No. of Wooden Bearing',
                    'no_cgi_sheet':'No. of CGI Sheet',
                    'no_emptying':'No. of Emptying',
                    'no_septic_tank': 'No. of Septic Tank',
                    'class': 'Class',
                    'area_sqm': 'Area (sqm)',

                    //for bUilding

                    'bin':'BIN',
                    'owner_name':'Owner Name',
                    'nid':'Owner NID',
                    'owner_gender':'Owner Gender',
                    'owner_contact':'Owner Contact Number',
                    'building_associated_to':'BIN of Main Building',
                    'ward':'Ward',
                    'road_code':'Road Code',
                    'house_number':'House Number',
                    'house_locality':'House Locality/Address',
                    'tax_code':'Tax Code/Holding ID',
                    'structure_type_name':'Structure Type',
                    'surveyed_date':'Surveyed Date',
                    'construction_year':'Construction Date',
                    'floor_count':'Number of Floors',
                    'functional_use_name':'Functional Use of Building',
                    'use_category_name':'Use Category of Building',
                    'office_business_name':'Office or Business Name',
                    'household_served':'Number of Households',
                    'male_population':'Male Population',
                    'female_population':'Female Population',
                    'other_population':'Other Population',
                    'population_served':'Population of Building',
                    'diff_abled_male_pop':'Differently Abled Male Population',
                    'diff_abled_female_pop':'Differently Abled Female Population',
                    'diff_abled_others_pop':'Differently Abled Other Population',
                    'low_income_hh':'Is Low Income House',
                    'lic_community':'LIC Name',
                    'water_source_name':'Main Drinking Water Source',
                    'water_customer_id':'Water Supply Customer ID',
                    'watersupply_pipe_code':'Water Supply Pipe Line Code',
                    'well_presence_status':'Well in Premises',
                    'distance_from_well':'Distance of Well from Closest Containment (m)',
                    'swm_customer_id':'SWM Customer ID',
                    'toilet_status':'Presence of Toilet',
                    'toilet_count':'Number of Toilets',
                    'household_with_private_toilet':'Households with Private Toilet',
                    'population_with_private_toilet':'Population with Private Toilet',
                    'building_sanitation_system':'Toilet Connection/ Defecation Area',
                    'sewer_code':'Sewer Code',
                    'drain_code':'Drain Code',
                    'desludging_vehicle_accessible':'Building Accessible to Desludging Vehicle',
                    'estimated_area':'Estimated Area of the Building (㎡)',
                    'toilet_name':'Community Toilet Name',
                    'verification_status':'Verification Status',
                    // for containment
                    'containment_type':'Containment Type',
                    'tank_length':'Tank Length (m)',
                    'tank_width':'Tank Width (m)',
                    'depth':'Depth (m)',
                    'pit_diameter':'Pit Diameter (m)',
                    'size':'Containment Volume (m³)',
                    'location':'Containment Location',
                    'septic_criteria':'Septic Tank Standard Compliance',
                    'construction_date':'Construction Date',
                    'emptied_status':'Emptied Status',
                    'last_emptied_date':'Last Emptied Date',
                    'next_emptying_date':'Next Emptying Date',
                    'no_of_times_emptied':'Number of Times Emptied',
                    'responsible_bin':'Responsible BIN',
                   
                    // For LIC 
                    'population_total':'Population',
                    'number_of_households':'No. of Households',
                    'population_male':'Male Population',
                    'population_female':'Female Population',
                    'population_others':'Other Population',
                    'no_of_septic_tank':'No. of Septic Tanks',
                    'no_of_holding_tank':'No. of Holding Tanks',
                    'no_of_pit':'No. of Pits',
                    'no_of_sewer_connection':'No. of Sewer Connections',
                    'no_of_buildings':'No. of Buildings',
                    'community_name':'Community Name',
                    'no_of_community_toilets':'No. of Community Toilets',
                    'desludging_vehicle_accessible':'Desludging Vehicle Accessible',
                    'treatmentplant_type' : 'Treatment Plant',
                    'utilitysize' : 'Width (mm)',
                    'status_name':'Status',
                    'disease_type':' Infected Disease ',
                    //For Containment
                    'distance_closest_well':'Distance From the Closest Well(m)'
                    };
                var lines = csvData.split('\n');

                // Process header line (assuming first line is headers)
                var headers = lines[0].split(',');
                headers = headers.map(header => {
                    return nameMapping[header.trim()] || header.trim();
                });

                // Process data lines
                var transformedLines = [headers.join(',')];
                for (var i = 1; i < lines.length; i++) {
                    var dataLine = lines[i].split(',');
                    transformedLines.push(dataLine.join(','));
                }

                var transformedCSV = transformedLines.join('\n');
                return transformedCSV;
            }



            // Add handler to tax zone filer form submit
            $('#tax_zone_form').submit(function () {
                var selectedTaxZones = $('#tax_zone').val();
                var selectedLayer = $('#tax_zone_overlay').val();
                if (selectedTaxZones && selectedLayer) {
                    var filters = [];
                    $.each(selectedTaxZones, function (index, value) {
                        filters.push("taxzoneid=''" + value + "''");
                    });

                    mFilter.tax_zone = "INTERSECTS(geom, collectGeometries(queryCollection('" + workspace + ":taxzone', 'geom', '" + filters.join(' OR ') + "')))";

                    $.each(mLayer, function (key, value) {
                        if (selectedLayer == key) {
                            addFilterToLayer(key, 'tax_zone');
                        } else {
                            removeFilterFromLayer(key, 'tax_zone');
                        }
                    });

                    showLayer('taxzone');
                } else {
                    mFilter.tax_zone = '';
                    $.each(mLayer, function (key, value) {
                        removeFilterFromLayer(key, 'tax_zone');
                    });
                }

                updateAllCQLFiltersParams();
                if (selectedLayer) {
                    showLayer(selectedLayer);
                }
                return false;
            });

            // Add handler to tax zone filter clear button
            $('#tax_zone_clear_button').click(function () {
                $('#tax_zone').multipleSelect('uncheckAll');
                $('#tax_zone_overlay').val('');
                $('#tax_zone_form').submit();
            });


        $('#export_building_tax_filter_csv').click(function (e) {
            e.preventDefault();
            exportBuildingTaxFilter('csv');
        });

        // Add handler to export building tax payment filter to KML button
        $('#export_building_tax_filter_kml').click(function (e) {
            e.preventDefault();
            exportBuildingTaxFilter('kml');
        });

        // Add handler to export building tax payment filter to SHP button
        $('#export_building_tax_filter_shp').click(function (e) {
            e.preventDefault();
            exportBuildingTaxFilter('shp');
        });

        function exportBuildingTaxFilter(format) {
            if (['csv', 'kml', 'shp'].indexOf(format) === -1) {
                return;
            }

            var checkedList = [];
            $('#building_tax_payment_checkbox_container input[type=checkbox]:checked').each(function () {
                checkedList.push("due_year = '" + $(this).attr('name') + "'");
            });

            if (checkedList.length > 0) {
                checklistparam = checkedList.join(' OR ');
            } else {
                Swal.fire({
                    title: "{{ __('Please check one or more options!') }}",
                    icon: "warning",
                    button: "{{ __('Close') }}",
                    className: "custom-swal",
                });
                return;
            }

            var outputFormat = format === 'csv' ? 'CSV' : (format === 'kml' ? 'KML' : 'SHAPE-ZIP');

            var exportLink = gurl_wfs + "?request=GetFeature&service=WFS&version=1.0.0&typeName=" + workspace + ":buildings_tax_status_layer&CQL_FILTER=deleted_at is null AND " + checklistparam + "&PROPERTYNAME=tax_code,bin,ward,owner_name,owner_contact,due_year,geom&outputFormat=" + outputFormat;

            if (format === 'csv') {
                fetch(exportLink)
                    .then(response => response.text())
                    .then(csvData => {
                        const transformedCSV = transformCSVAfterDownload(csvData);
                        const blob = new Blob([transformedCSV], { type: 'text/csv' });
                        const url = URL.createObjectURL(blob);
                        const downloadLink = document.createElement('a');
                        downloadLink.href = url;
                        downloadLink.setAttribute('download', `{{ __("Property Tax Collection ISS") }}.csv`);
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    })
                    .catch(error => console.error('Error fetching CSV:', error));
            } else {
                if (outputFormat === 'SHAPE-ZIP') {
                    exportLink += '&format_options=filename:' + '{{ __("Property Tax Collection ISS") }}' + '.zip';
                } else if (outputFormat === 'KML') {
                    exportLink += '&format_options=filename:' + '{{ __("Property Tax Collection ISS") }}' + '.kml';
                }
                window.open(exportLink);
            }
        }




                $('#export_water_supply_filter_csv').click(function (e) {
            e.preventDefault();
            exportWaterSupplyFilter('csv');
        });

            // Add handler to export water supply filter to KML button
            $('#export_water_supply_filter_kml').click(function (e) {
                e.preventDefault();
                exportWaterSupplyFilter('kml');
            });

            // Add handler to export water supply filter to SHP button
            $('#export_water_supply_filter_shp').click(function (e) {
                e.preventDefault();
                exportWaterSupplyFilter('shp');
            });

            function exportWaterSupplyFilter(format) {
                if (['csv', 'kml', 'shp'].indexOf(format) === -1) {
                    return;
                }

                var checkedList = [];
                $('#water_supply_payment_checkbox_container input[type=checkbox]:checked').each(function () {
                    checkedList.push("due_year = '" + $(this).attr('name') + "'");
                });

                if (checkedList.length > 0) {
                    checklistparam = checkedList.join(' OR ');
                } else {
                    Swal.fire({
                        title: "{{ __('Please check one or more options!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    });
                    return;
                }

                var outputFormat = format === 'csv' ? 'CSV' : (format === 'kml' ? 'KML' : 'SHAPE-ZIP');

            var exportLink = gurl_wfs + "?request=GetFeature&service=WFS&version=1.0.0&typeName=" + workspace + ":buildings_water_payment_status_layer&CQL_FILTER=deleted_at is null AND " + checklistparam + "&PROPERTYNAME=water_customer_id,bin,tax_code,ward,customer_name,customer_contact,due_year,geom&outputFormat=" + outputFormat;

                if (format === 'csv') {
                    fetch(exportLink)
                        .then(response => response.text())
                        .then(csvData => {
                            const transformedCSV = transformCSVAfterDownload(csvData);
                            const blob = new Blob([transformedCSV], { type: 'text/csv' });
                            const url = URL.createObjectURL(blob);
                            const downloadLink = document.createElement('a');
                            downloadLink.href = url;
                            downloadLink.setAttribute('download', `{{ __("Water Supply ISS") }}.csv`);
                            document.body.appendChild(downloadLink);
                            downloadLink.click();
                            document.body.removeChild(downloadLink);
                        })
                        .catch(error => console.error('Error fetching CSV:', error));
                } else {
                     if (outputFormat === 'SHAPE-ZIP') {
                    exportLink += '&format_options=filename:' + '{{ __("Water Supply ISS") }}' + '.zip';
                }
                    else if (outputFormat === 'KML') {
                    exportLink += '&format_options=filename:' + '{{ __("Water Supply ISS") }}' + '.kml';
                }
                    window.open(exportLink);
                }
            }



            // Add handler to export water supply filter to KML button
            $('#export_swm_filter_kml').click(function (e) {
                e.preventDefault();
                exportswmFilter('kml');
            });

            // Add handler to export water supply filter to SHP button
            $('#export_swm_filter_shp').click(function (e) {
                e.preventDefault();
                exportswmFilter('shp');
            });

            function exportswmFilter(format) {
                if (['csv', 'kml', 'shp'].indexOf(format) === -1) {
                    return;
                }

                var checkedList = [];
                $('#swm_checkbox_container input[type=checkbox]:checked').each(function () {
                    checkedList.push("due_year = '" + $(this).attr('name') + "'");
                });

                if (checkedList.length > 0) {
                    checklistparam = checkedList.join(' OR ');
                } else {
                    Swal.fire({
                        title: "{{ __('Please check one or more options!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    });
                    return;
                }

                var outputFormat = format === 'csv' ? 'CSV' : (format === 'kml' ? 'KML' : 'SHAPE-ZIP');

            var exportLink = gurl_wfs + "?request=GetFeature&service=WFS&version=1.0.0&typeName=" + workspace + ":buildings_swm_payment_status_layer&CQL_FILTER=deleted_at is null AND " + checklistparam + "&PROPERTYNAME=swm_customer_id,bin,ward,customer_name,customer_contact,due_year,geom&outputFormat=" + outputFormat;

                if (format === 'csv') {
                    fetch(exportLink)
                        .then(response => response.text())
                        .then(csvData => {
                            const transformedCSV = transformCSVAfterDownload(csvData);
                            const blob = new Blob([transformedCSV], { type: 'text/csv' });
                            const url = URL.createObjectURL(blob);
                            const downloadLink = document.createElement('a');
                            downloadLink.href = url;
                            downloadLink.setAttribute('download', `{{ __("Solid Waste ISS") }}.csv`);
                            document.body.appendChild(downloadLink);
                            downloadLink.click();
                            document.body.removeChild(downloadLink);
                        })
                        .catch(error => console.error('Error fetching CSV:', error));
                } else {
                     if (outputFormat === 'SHAPE-ZIP') {
                    exportLink += '&format_options=filename:' + '{{ __("Solid Waste ISS") }}' + '.zip';
                }
                    else if (outputFormat === 'KML') {
                    exportLink += '&format_options=filename:' + '{{ __("Solid Waste ISS") }}' + '.kml';
                }
                    window.open(exportLink);
                }
            }

        // Function to transform CSV after download

            // Add handler to building tax payment filter clear button
            $('#building_tax_payment_clear_button').click(function () {
                $('#building_tax_payment_checkbox_container input[type=checkbox]:checked').each(function () {
                    $(this).prop( "checked", false )
                });
            });
            // Add handler to building tax payment filter clear button
            $('#water_supply_payment_clear_button').click(function () {
                $('#water_supply_payment_checkbox_container input[type=checkbox]:checked').each(function () {
                    $(this).prop( "checked", false )
                });
            })
             // Add handler to swm filter clear button
             $('#swm_clear_button').click(function () {
                $('#swm_checkbox_container input[type=checkbox]:checked').each(function () {
                    $(this).prop( "checked", false )
                });
            })


            $('#application_date_form').submit(function () {
                var date = $('#application_date_field').val();
                if (!date) {
                    Swal.fire({
                        title: "{{ __('Please select a date!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                        confirmButtonText: "{{ __('OK') }}",
                    });
                } else {
                    var message = "{{ __('Number of applications on') }}" + ' '+ date + ': ';

                    displaySelectedDateApplications(date, message);
                }

                return false;
            });


            $('#application_not_tp_date_form').submit(function () {
                var date = $('#application_not_tp_date_field').val();
                if (!date) {
                    Swal.fire({
                        title: "{{ __('Please select a date!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                        confirmButtonText: "{{ __('OK') }}",
                    });
                } else {
                    var message = "{{ __('Number of applications on') }}" + ' '+ date + ': ';

                    displaySelectedDateApplicationsNotTP(date, message);
                }

                return false;
            });

            // Display markers on applications
            function displaySelectedDateApplicationsNotTP(startDate, message) {
                if (eLayer.application_notTP_markers) {
                    eLayer.application_notTP_markers.layer.getSource().clear();
                }
                if (eLayer.application_NotTPDate_containment_markers) {
                    eLayer.application_NotTPDate_containment_markers.layer.getSource().clear();
                }
                if (eLayer.application_NotTP_containment_markers) {
                    eLayer.application_NotTP_containment_markers.layer.getSource().clear();
                }

                var layer = new ol.layer.Vector({
                    // visible: false,
                    source: new ol.source.Vector()
                });

                addExtraLayer('application_NotTPDate_containment_markers', 'Building Markers', layer);


                // showExtraLayer('application_NotTPDate_containment_markers');

                displayAjaxLoader();
                var url = '{{ url("maps/application-not-tp-on-date") }}' + '/' + startDate;
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {
                        var service_provider_legends = '';
                        var service_provider_markers = {};
                        data["service_providers"].forEach(function(service_provider,index){
                            service_provider_legends += "<tr>" +
                                '<td style="vertical-align: top;">' +
                                '<img src="{{ url("/")}}/img/marker-' + markerColors[index] + '.png"></td>' +
                                '<td> - ' + service_provider.company_name + '</td>'+
                                '</tr>';
                            service_provider_markers[service_provider.company_name]=markerColors[index];
                        });
                        $('#service_providers_legends').html(service_provider_legends);

                        for (var i = 0; i < data["data"].length; i++) {
                            if (data["data"][i].lat && data["data"][i].long) {
                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(data["data"][i].long), parseFloat(data["data"][i].lat)], 'EPSG:4326', 'EPSG:3857')),
                                    application_id: data["data"][i].application_id,
                                    house_number: data["data"][i].bin,
                                    application_date: data["data"][i].application_date,
                                    service_provider: data["data"][i].service_provider,
                                    emptying_date: data["data"][i].emptying_date
                                });

                                var filename = 'application.png';

                                var style = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/marker-' + service_provider_markers[data["data"][i].service_provider] + '.png'
                                    })
                                });

                                feature.setStyle(style);

                                eLayer.application_NotTPDate_containment_markers.layer.getSource().addFeature(feature);
                            }
                        }

                        removeAjaxLoader();

                        showLayer('containments_layer');
                        zoomToCity();
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                });
            }

                 //Display application based on selected year and months
            $('#find_application_yearmonth').submit(function () {

                var applicaion_year = $('#applicaion_year').val();
                var application_month = $('#application_month').val();
                if (!applicaion_year) {
                    Swal.fire({
                        title: "{{ __('Please select a year!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                        confirmButtonText: "{{ __('OK') }}",
                    });
                    return false;
                } else if (!applicaion_year && !application_month) {
                    Swal.fire({
                        title: "{{ __('Please select a month!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                        confirmButtonText: "{{ __('OK') }}",
                    });
                    return false;
                } else {
                    var message = "{{ __('Number of applications on') }}" + ' '+ applicaion_year + ' ' + application_month + ': ';
                    displaySelectedYearMonthApplications(applicaion_year, application_month, message);
                }

                return false;
            });


            // Display markers on applications on selected years and months
            function displaySelectedYearMonthApplications(year, month, message) {
                if (eLayer.application_containment_markers) {
                    eLayer.application_containment_markers.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('application_containment_markers', 'Building Markers', layer);
                }

                // showExtraLayer('application_containment_markers');

                displayAjaxLoader();
                var url = '{{ url("maps/application-containments-year-month") }}';
                $.ajax({
                    url: url,
                    type: 'post',
                    data: {year: year, month: month, "_token": "{{ csrf_token() }}"},
                    success: function (dataResponse) {
                        for (var i = 0; i < dataResponse.length; i++) {
                            if (dataResponse[i].lat && dataResponse[i].long) {
                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse[i].long), parseFloat(dataResponse[i].lat)], 'EPSG:4326', 'EPSG:3857')),
                                    application_id: dataResponse[i].application_id,
                                    house_number: dataResponse[i].bin,
                                    application_date: dataResponse[i].application_date,
                                    service_provider: dataResponse[i].service_provider,
                                    emptying_date: dataResponse[i].emptying_date
                                });

                                var filename = 'application.png';

                                if (dataResponse[i].sludge_collection_status) {
                                    filename = 'application-sludge-collection.png';
                                }
                                else if (dataResponse[i].feedback_status) {
                                    filename = 'application-feedback.png';
                                } else if (dataResponse[i].emptying_status) {
                                    filename = 'application-emptying.png';
                                }

                                var style = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/' + filename
                                    })
                                });

                                feature.setStyle(style);

                                eLayer.application_containment_markers.layer.getSource().addFeature(feature);
                            }
                        }

                        removeAjaxLoader();

                        showLayer('containments_layer');
                        zoomToCity();
                    },
                    error: function (dataResponse) {
                        displayAjaxError();
                    }
                });
            }


            $('#find_application_not_tp_yearmonth').submit(function () {

                var applicaion_not_tp_year = $('#applicaion_not_tp_year').val();
                var application_not_tp_month = $('#application_not_tp_month').val();
                if (!applicaion_not_tp_year) {
                    Swal.fire({
                        title: "{{ __('Please select a year!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                        confirmButtonText: "{{ __('OK') }}"
                    });
                    return false;
                } else if (!applicaion_not_tp_year && !application_not_tp_month) {
                    Swal.fire({
                        title: "{{ __('Please select a month!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                        confirmButtonText: "{{ __('OK') }}"
                    });
                    return false;
                } else {
                    var message = "{{ __('Number of applications on') }}" + ' ' + applicaion_not_tp_year + ' ' + application_not_tp_month + ': ';
                    displaySelectedYearMonthApplicationsNotTP(applicaion_not_tp_year, application_not_tp_month, message);
                }

                return false;
            });

            // Display markers on applications not reached on treatment plants on selected years and months
            function displaySelectedYearMonthApplicationsNotTP(year, month, message) {
                if (eLayer.application_notTP_markers) {
                    eLayer.application_notTP_markers.layer.getSource().clear();
                }
                if (eLayer.application_NotTPDate_containment_markers) {
                    eLayer.application_NotTPDate_containment_markers.layer.getSource().clear();
                }
                if (eLayer.application_NotTP_containment_markers) {
                    eLayer.application_NotTP_containment_markers.layer.getSource().clear();
                }

                var layer = new ol.layer.Vector({
                    // visible: false,
                    source: new ol.source.Vector()
                });

                addExtraLayer('application_NotTP_containment_markers', 'Building Markers', layer);


                // showExtraLayer('application_NotTP_containment_markers');

                displayAjaxLoader();
                var url = '{{ url("maps/application-not-tp-containments-year-month") }}';
                $.ajax({
                    url: url,
                    type: 'post',
                    data: {year: year, month: month, "_token": "{{ csrf_token() }}"},
                    success: function (data) {
                        var service_provider_legends = '';
                        var service_provider_markers = {};
                        data["service_providers"].forEach(function(service_provider,index){
                            service_provider_legends += "<tr>" +
                                '<td style="vertical-align: top;">' +
                                '<img src="{{ url("/")}}/img/marker-' + markerColors[index] + '.png"></td>' +
                                '<td> - ' + service_provider.company_name + '</td>'+
                                '</tr>';
                            service_provider_markers[service_provider.company_name]=markerColors[index];
                        });
                        $('#service_providers_legends').html(service_provider_legends);

                        for (var i = 0; i < data["data"].length; i++) {
                            if (data["data"][i].lat && data["data"][i].long) {
                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(data["data"][i].long), parseFloat(data["data"][i].lat)], 'EPSG:4326', 'EPSG:3857')),
                                    application_id: data["data"][i].application_id,
                                    house_number: data["data"][i].bin,
                                    application_date: data["data"][i].application_date,
                                    service_provider: data["data"][i].service_provider,
                                    emptying_date: data["data"][i].emptying_date
                                });
                                var filename = 'application.png';

                                var style = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/marker-' + service_provider_markers[data["data"][i].service_provider] + '.png',

                                    })
                                });

                                feature.setStyle(style);

                                eLayer.application_NotTP_containment_markers.layer.getSource().addFeature(feature);
                            }
                        }

                        removeAjaxLoader();

                        showLayer('containments_layer');
                        zoomToCity();
                    },
                    error: function (data) {
                        displayAjaxError();
                    }
                });
            }

            // Display markers on applications
            function displaySelectedDateApplications(startDate, message) {
                if (eLayer.application_containment_markers) {
                    eLayer.application_containment_markers.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('application_containment_markers', 'Building Markers', layer);
                }

                // showExtraLayer('application_containment_markers');

                displayAjaxLoader();
                var url = '{{ url("maps/application-on-date") }}' + '/' + startDate;
                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",

                    },
                    success: function (dataResponse) {
                        for (var i = 0; i < dataResponse.length; i++) {
                            if (dataResponse[i].lat && dataResponse[i].long) {
                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse[i].long), parseFloat(dataResponse[i].lat)], 'EPSG:4326', 'EPSG:3857')),
                                    application_id: dataResponse[i].application_id,
                                    house_number: dataResponse[i].bin,
                                    application_date: dataResponse[i].application_date,
                                    service_provider: dataResponse[i].service_provider,
                                    emptying_date: dataResponse[i].emptying_date
                                });

                                var filename = 'application.png';

                                if (dataResponse[i].sludge_collection_status) {
                                    filename = 'application-sludge-collection.png';
                                }
                                else if (dataResponse[i].feedback_status) {
                                    filename = 'application-feedback.png';
                                } else if (dataResponse[i].emptying_status) {
                                    filename = 'application-emptying.png';
                                }

                                var style = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/' + filename
                                    })
                                });

                                feature.setStyle(style);

                                eLayer.application_containment_markers.layer.getSource().addFeature(feature);
                            }
                        }

                        removeAjaxLoader();

                        showLayer('containments_layer');
                        zoomToCity();
                    },
                    error: function (dataResponse) {
                        displayAjaxError();
                    }
                });
            }

            $('#proposed_emptying_days_form').submit(function () {
                var days = Number($('#proposed_emptying_days').val());
                if (!days) {
                    Swal.fire({
                        title: "{{ __('Please enter number of days!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    });
                } else if (!Number.isInteger(days) || days < 0) {
                    Swal.fire({
                        title: "{{ __('Invalid input for number of days!') }}",
                        icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                    });
                } else {
                    var startDate = moment().format('YYYY-MM-DD');
                    var endDate = moment().add(days, 'days').format('YYYY-MM-DD');
                    var message = '{{ __('Number of containments proposed to be emptied in the next few days:') }}' + ' ' + days ;

                    displayProposedEmptyingContainments(startDate, endDate, message);
                }

                return false;
            });


            $('#proposed_emptying_week_form').submit(function () {
                var startDate = moment().format('YYYY-MM-DD');
                var endDate = moment().add(7, 'days').format('YYYY-MM-DD');
                var message = '{{ __('Number of containments proposed to be emptied next week:') }}';

                displayProposedEmptyingContainments(startDate, endDate, message);

                return false;
            });

            $('#proposed_emptying_date_form').submit(function () {
                var date = $('#proposed_emptying_date').val();
                if (!date) {
                    Swal.fire({
                        title: "{{ __('Please select a date!') }}",
                       icon: "warning",
                        button: "{{ __('Close') }}",
                        className: "custom-swal",
                        confirmButtonText: "{{ __('OK') }}",
                    });
                } else {
                    var message = "{{ __('Number of containments proposed to be emptied on') }} " + date + ": ";

                    displayProposedEmptyingContainments(date, date, message);
                }

                return false;
            });

            // Display markers on containments proposed to be emptied
            function displayProposedEmptyingContainments(startDate, endDate, message) {
                if (eLayer.proposed_emptying_containments) {
                    eLayer.proposed_emptying_containments.layer.getSource().clear();
                } else {
                    var layer = new ol.layer.Vector({
                        // visible: false,
                        source: new ol.source.Vector()
                    });

                    addExtraLayer('proposed_emptying_containments', 'Containment Markers', layer);
                }

                // showExtraLayer('proposed_emptying_containments');

                displayAjaxLoader();
                var url = '{{ url("maps/proposed-emptying-containments") }}' + '/' + startDate + '/' + endDate;

                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function (dataResponse) {
                        for (var i = 0; i < dataResponse.length; i++) {
                            if (dataResponse[i].lat && dataResponse[i].long) {
                                var feature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse[i].long), parseFloat(dataResponse[i].lat)], 'EPSG:4326', 'EPSG:3857'))
                                });

                                var style = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/containment-blue.png'
                                    })
                                });

                                feature.setStyle(style);

                                eLayer.proposed_emptying_containments.layer.getSource().addFeature(feature);
                            }
                        }

                        var html = message + dataResponse.length;
                        $("#proposed_emptying_modal .modal-body").html(html);
                        $('#proposed_emptying_modal').modal('show');

                        removeAjaxLoader();

                        showLayer('containments_layer');
                        zoomToCity();
                    },
                    error: function (dataResponse) {
                        displayAjaxError();
                    }
                });
            }


            // Add handler to water logging area filer form submit
            $('#watlog_form').submit(function () {
                var selectedLayers = $('#watlog_overlay').val();
                if (selectedLayers) {
                    mFilter.watlog = "INTERSECTS(geom, collectGeometries(queryCollection('" + workspace + ":waterbodys_layer', 'geom', 'INCLUDE')))"

                    $.each(mLayer, function (key, value) {
                        if (selectedLayers.indexOf(key) != -1) {
                            addFilterToLayer(key, 'watlog');
                        } else {
                            removeFilterFromLayer(key, 'watlog');
                        }
                    });

                    showLayer('waterbodys_layer');
                } else {
                    mFilter.watlog = '';
                    $.each(mLayer, function (key, value) {
                        removeFilterFromLayer(key, 'watlog');
                    });
                }

                updateAllCQLFiltersParams();
                if (selectedLayers) {
                    $.each(selectedLayers, function (index, value) {
                        showLayer(value);
                    });
                }
                return false;
            });


            function updateAllCQLFiltersParams() {
                $.each(mLayer, function (key, value) {
                    updateCQLFilterParams(key);
                });
            }

            function updateCQLFilterParams(layer) {

                if (mLayer[layer]) {

                    var cqlFilters = [];
                    $.each(mLayer[layer].filters, function (index, value) {
                        if (mFilter[value]) {
                            cqlFilters.push(mFilter[value]);
                        }
                    });
                    var cql_filter = cqlFilters.length > 0 ? cqlFilters.join(" AND ") : 'deleted_at is null';
                    mLayer[layer].layer.get('source').updateParams({CQL_FILTER: cql_filter});
                }
            }

            function handleZoomToExtent(layer, field, val, showMarker, callback) {
                var url = '{{ url("maps/extent") }}' + '/' + layer + '/' + field + '/' + val;

                $.ajax({
                    url: url,
                    type: 'get',
                    data: {
                        "_token": "{{ csrf_token() }}",

                    },
                    success: function (dataResponse) {
                        var extent = ol.proj.transformExtent([parseFloat(dataResponse.xmin), parseFloat(dataResponse.ymin), parseFloat(dataResponse.xmax), parseFloat(dataResponse.ymax)], 'EPSG:4326', 'EPSG:3857');
                        map.getView().fit(extent);

                        if (showMarker) {
                            if (dataResponse.long && dataResponse.lat) {
                                if (!eLayer.markers) {
                                    var markerLayer = new ol.layer.Vector({
                                        // visible: false,
                                        source: new ol.source.Vector()
                                    });

                                    addExtraLayer('markers', 'Markers', markerLayer);
                                    // showExtraLayer('markers');
                                }

                                var markerFeature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse.long), parseFloat(dataResponse.lat)], 'EPSG:4326', 'EPSG:3857'))
                                });

                                var markerStyle = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/pin-green.png'
                                    })
                                });

                                markerFeature.setStyle(markerStyle);

                                eLayer.markers.layer.getSource().addFeature(markerFeature);

                                map.getView().setCenter(ol.proj.transform([parseFloat(dataResponse.long), parseFloat(dataResponse.lat)], 'EPSG:4326', 'EPSG:3857'));
                                map.getView().setZoom(16);
                            }

                            if (dataResponse.long1 && dataResponse.lat1) {
                                if (!eLayer.markers) {
                                    var markerLayer = new ol.layer.Vector({
                                        // visible: false,
                                        source: new ol.source.Vector()
                                    });

                                    addExtraLayer('markers', 'Markers', markerLayer);
                                    // showExtraLayer('markers');
                                }

                                var markerFeature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(dataResponse.long1), parseFloat(dataResponse.lat1)], 'EPSG:4326', 'EPSG:3857'))
                                });

                                var markerStyle = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/pin-purple.png'
                                    })
                                });

                                markerFeature.setStyle(markerStyle);

                                eLayer.markers.layer.getSource().addFeature(markerFeature);
                            }

                            if (dataResponse.geom) {
                                var format = new ol.format.WKT();
                                var feature = format.readFeature(dataResponse.geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });

                                if (feature.getGeometry() instanceof ol.geom.MultiLineString) {
                                    if (!eLayer.markers) {
                                        var markerLayer = new ol.layer.Vector({
                                            // visible: false,
                                            source: new ol.source.Vector()
                                        });

                                        addExtraLayer('markers', 'Markers', markerLayer);
                                        // showExtraLayer('markers');
                                    }

                                    feature.setStyle(new ol.style.Style({
                                        stroke: new ol.style.Stroke({
                                            color: '#ed1f24',
                                            width: 3
                                        }),
                                    }));
                                    eLayer.markers.layer.getSource().addFeature(feature);
                                }
                            }
                        }

                        showLayer(layer);

                        if (callback) {
                            callback();
                        }
                    },
                    error: function (dataResponse) {

                    }
                });
            }

            function extentIntersects(extent1, extent2) {
                return extent1[0] <= extent2[2] && extent1[2] >= extent2[0] &&
                extent1[1] <= extent2[3] && extent1[3] >= extent2[1];
            }

            //WMS URL importing
            var parser = new ol.format.WMSCapabilities();
            var wmsUrl = document.getElementById("wmsURL");
            var mapLayer = document.getElementById("mapLayer");

            const citypolylayers = ['citypolys_layer'];

            let availableLayers = [];

            wmsUrl.addEventListener("click", function () {
            displayAjaxLoader();

            var wmsAddress = document.getElementById("wmsAddress").value;
                const urlObj = new URL(wmsAddress);
                const params = new URLSearchParams(urlObj.search);

                const SERVICE = params.get('service') ||  params.get('SERVICE') ;
                const REQUEST = params.get('request') ||  params.get('REQUEST');
                const VERSION = params.get('version') ||  params.get('VERSION');

                if (!SERVICE || !REQUEST || !VERSION) {
                    Swal.fire({
                                icon: 'error',
                                title: "{{ __('Invalid Layer') }}",
                                text: "{{ __('Enter URL does not match WMS GetCapabilities pattern') }}",
                                    confirmButtonColor: '#d33'
                                });
                    removeAjaxLoader();
                    return;
                }
                $.ajax({
                    url: '/proxy-wms',
                    method: 'GET',
                    data: {
                        url: urlObj.origin + urlObj.pathname,
                        SERVICE: SERVICE,
                        REQUEST: REQUEST,
                        VERSION: VERSION
                    },
                    success: function () {
                        fetch(wmsAddress)
                            .then(response => response.text())
                            .then(text => {
                                $("#wmsModal").modal("hide");
                                const result = parser.read(text);
                                const layers = result.Capability.Layer.Layer;
                                availableLayers = layers.map(layer => layer.Name);
                                wms_gurl = wmsAddress.split("?")[0]; // Base WMS URL
                                const baseWFSUrl = wms_gurl.replace(/\/wms$/i, '/wfs');

                                removeAjaxLoader();
                                $("#getLayerModal").modal();
                                mapLayer.options.length = 0;

                                layers.forEach(layer => {
                                    const option = document.createElement("option");
                                    option.value = option.text = layer.Name;
                                    mapLayer.add(option);
                                });

                                mapLayer.addEventListener("change", function () {
                                    const selectedLayer = mapLayer.value;

                                    const urlParts = wms_gurl.split('/').filter(Boolean);
                                    const workspace_url = urlParts[urlParts.length - 2];

                                    const cityPolyUrl = `${gurl_wms}?service=WFS&version=1.0.0&request=GetFeature&typeName=${workspace}:citypolys_layer&outputFormat=application/json`;
                                    displayAjaxLoader();
                                    fetch(cityPolyUrl)
                                        .then(res => res.json())
                                        .then(cityGeoJSON => {
                                            const cityFeatures = new ol.format.GeoJSON().readFeatures(cityGeoJSON, {
                                                featureProjection: 'EPSG:3857'
                                            });
                                            const cityGeometry = cityFeatures[0].getGeometry();

                                            const selectedLayerUrl = `${baseWFSUrl}?service=WFS&version=${VERSION}&request=GetFeature&typeName=${workspace_url}:${selectedLayer}&outputFormat=application/json`;

                                            fetch(selectedLayerUrl)
                                                .then(res => res.json())
                                                .then(selectedGeoJSON => {
                                                    const selectedFeatures = new ol.format.GeoJSON().readFeatures(selectedGeoJSON, {
                                                        featureProjection: 'EPSG:3857'
                                                    });

                                                    let doesIntersect = selectedFeatures.some(f => {
                                                        return f.getGeometry() && extentIntersects(f.getGeometry().getExtent(), cityGeometry.getExtent());
                                                    });

                                                    if (!doesIntersect) {
                                                        removeAjaxLoader();
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: "{{ __('Invalid Layer') }}",
                                                            text: "{{ __('Selected layer does not intersect with Municipality boundary') }}",
                                                            confirmButtonColor: '#d33'
                                                        });
                                                        return;
                                                    }

                                                    const source = new ol.source.TileWMS({
                                                        url: wms_gurl,
                                                        params: {
                                                            layers: selectedLayer,
                                                            TILED: true
                                                        },
                                                        crossOrigin: "anonymous",
                                                        serverType: "geoserver",
                                                        attributions: 'This is from GetCapabilities'
                                                    });

                                                    const layer = new ol.layer.Tile({
                                                        source: source,
                                                        visible: true
                                                    });
                                                    removeAjaxLoader();
                                                    map.addLayer(layer);
                                                });
                                        })
                                });
                            })
                            .catch(error => {
                                removeAjaxLoader();
                                Swal.fire({
                                    icon: 'error',
                                    title: "{{ __('Invalid Layer') }}",
                                    text: "{{ __('Enter a valid WMS URL.') }}",
                                    confirmButtonText: "{{ __('OK') }}"
                                });
                            });
                    },
                    error: function (xhr) {
                        removeAjaxLoader();
                        Swal.fire({
                            icon: 'error',
                            title:  "{{ __('Proxy Error') }}",
                            text:  "{{ __('Failed to fetch WMS capabilities') }}",
                            confirmButtonColor: '#d33'
                        });
                    }
                });
        });

        function updateMapSize() {
                map.updateSize();
                google.maps.event.trigger(gmap, 'resize');
                onCenterChanged();
                onResolutionChanged();
            }

            function checkNumberIfFloat(value) {
                return Number(value) === value && value % 1 !== 0;
             }

             $(document).on('shown.lte.pushmenu collapsed.lte.pushmenu', function () {
                setTimeout(updateMapSize, 300);
            });

            function setSidebarTabContentHeight() {
                // var height = $('#map-right-sidebar').outerHeight() - $('#map-right-sidebar .nav-tabs').outerHeight();
                var height = window.innerHeight - $('#map-right-sidebar .nav-tabs').offset().top - $('#map-right-sidebar .nav-tabs').outerHeight() - $('footer').outerHeight();
                $('#map-right-sidebar .tab-content').height(height);
            }

            // Fix min-height of content-wrapper on window load
            $(window).on('load', function () {
                $(window).trigger('resize');
                updateMapSize();
            });

            $(window).on('resize', function () {
                setSidebarTabContentHeight();
            });
        });
        const popup = document.getElementById("feature-info-popup");

        let isDragging = false;
        let offsetX = 0;
        let offsetY = 0;

        // Start dragging when the user clicks anywhere on the popup
        popup.addEventListener("mousedown", (event) => {
            if (event.target.id === "feature-info-popup-closer") return; // Prevent dragging from the close button

            isDragging = true;
            offsetX = event.clientX - popup.offsetLeft;
            offsetY = event.clientY - popup.offsetTop;

            document.body.style.userSelect = "none"; // Prevent text selection while dragging
        });

        // Handle drag movement
        document.addEventListener("mousemove", (event) => {
            if (isDragging) {
                popup.style.left = `${event.clientX - offsetX}px`;
                popup.style.top = `${event.clientY - offsetY}px`;
            }
        });

        // Stop dragging when the mouse button is released
        document.addEventListener("mouseup", () => {
            isDragging = false;
            document.body.style.userSelect = "auto"; // Re-enable text selection
        });
        $('#road_code, #road_code_drain, #road_code_watersupply').select2({
            ajax: {
                url: "{{ route('roadlines.get-road-names') }}",
                data: function (params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
            },
            placeholder: 'Road Code - Road Name',
            allowClear: true,
            closeOnSelect: true,
        });

   </script>


@endpush
