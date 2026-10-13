{{-- Last Modified Date: 14-04-2024
 Developed By: Innovative Solution Pvt. Ltd. (ISPL)   --}}
<style>
      #map {
        width: 800px;
        height: 400px; /* 100% of the viewport height - navbar height */
      }
      #olmap {
          border: 1px solid #000000;
          margin-top: 20px;
      }
      a.skiplink {
        position: absolute;
        clip: rect(1px, 1px, 1px, 1px);
        padding: 0;
        border: 0;
        height: 1px;
        width: 1px;
        overflow: hidden;
      }
      a.skiplink:focus {
        clip: auto;
        height: auto;
        width: auto;
        background-color: #fff;
        padding: 0.3em;
      }
      .required-sign {
    color: red; /* Change the color to your desired color */
    margin-left: 5px; /* Adjust the margin to control spacing */
  }
      #map:focus {
        outline: #4A74A8 solid 0.15em;
      }
    </style>
<link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">

<link rel="stylesheet" href="https://unpkg.com/ol-layerswitcher@3.8.3/dist/ol-layerswitcher.css" />
<style>
    .layer-switcher{
        top: 0.5em;
    }
    .layer-switcher button{
        width: 25px;
        height: 25px;
        background-position: unset;
        background-size: contain;
    }
</style>

<div class="card-body">
    <div class="form-group required row">
        {!! Form::label('hotspot_location',__('Hotspot Location'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('hotspot_location',null,['class' => 'form-control', 'placeholder' => __('Hotspot Location') ,'onclick' => 'this.showPicker();',]) !!}
        </div>
    </div>
    <div class="form-group required row">
        {!! Form::label('date',__('Date'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
             {!! Form::date('date', null, ['class' => 'form-control date','id'=>'date','onclick' => 'this.showPicker();', 'autocomplete' => 'off','placeholder' => 'Date']) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('disease',__('Infected Disease'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::select('disease', $enumValues, null, ['class' => 'form-control chosen-select', 'placeholder' => __('Choose Infected Disease')]) !!}
        </div>
    </div>
    <h4 class="required">{{__('No. of Cases')}}<span class="required-sign">*</span></h4>


    <div class="form-group  row">
        {!! Form::label('male_cases',__('Male'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::number('male_cases',null,['class' => 'form-control', 'placeholder' => __('Male') ,'oninput' => "this.value = this.value.replace(/[^0-9.]/g, ''); "]) !!}
        </div>
    </div>

    <div class="form-group  row">
        {!! Form::label('female_cases',__('Female'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::number('female_cases',null,['class' => 'form-control', 'placeholder' => __('Female') ,'oninput' => "this.value = this.value.replace(/[^0-9.]/g, ''); "]) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('other_cases',__('Other'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::number('other_cases',null,['class' => 'form-control', 'placeholder' => __('Other') ,'oninput' => "this.value = this.value.replace(/[^0-9.]/g, ''); "]) !!}
        </div>
    </div>

    <h4>{{__('No. of Fatalities')}}</h4>
    <div class="form-group  row">
        {!! Form::label('male_fatalities',__('Male'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::number('male_fatalities',null,['class' => 'form-control', 'placeholder' => __('Male') ,'oninput' => "this.value = this.value.replace(/[^0-9.]/g, ''); "]) !!}
        </div>
    </div>
    <div class="form-group  row">
        {!! Form::label('female_fatalities',__('Female'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::number('female_fatalities',null,['class' => 'form-control', 'placeholder' => __('Female') ,'oninput' => "this.value = this.value.replace(/[^0-9.]/g, ''); "]) !!}
        </div>
    </div>

    <div class="form-group  row">
        {!! Form::label('other_fatalities',__('Other'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::number('other_fatalities',null,['class' => 'form-control', 'placeholder' => __('Other') ,'oninput' => "this.value = this.value.replace(/[^0-9.]/g, ''); "]) !!}
        </div>
    </div>

    <div class="form-group required row">
            {!! Form::label('geom',__('Hotspot Area'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-6">
            <a class="skiplink" href="#map">Go to map</a>
            <!--<div id="map" class="map" tabindex="0">-->
                <div id="olmap"></div>
            <div id="popup" class="ol-popup" style="display: none;">
                <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                <div id="popup-content"></div>
            </div>

            </div>
            <input type="hidden" name="geom" id="geom" value="{{ @$geom }}" />
        </div>

        <div class="form-group row">
            {!! Form::label('notes',__('Notes'),['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-3">
                {!! Form::textarea('notes',null,['class' => 'form-control', 'placeholder' => __('Notes')]) !!}
            </div>
        </div>
    </div>



      </div>
<div class="card-footer">
    <a href="{{ action('PublicHealth\HotspotController@index') }}" class="btn btn-info">{{__('Back to List')}}</a>
    {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}
</div><!-- /.box-footer -->
<!--</div> /.content-wrapper -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css" rel="stylesheet">

<!-- Include Chosen JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>
<script>
    $(document).ready(function() {
        // Get the current date
        var currentDate = new Date();

        // Set the maximum date for the date input field
        $('#date').attr('max', formatDate(currentDate));

        // Function to format a date in YYYY-MM-DD format (the format expected by the date input)
        function formatDate(date) {
            var day = String(date.getDate()).padStart(2, '0');
            var month = String(date.getMonth() + 1).padStart(2, '0'); // Month is zero-based
            var year = date.getFullYear();
            return year + '-' + month + '-' + day;
        }
    });
</script>

@push('scripts')

    <script src="https://openlayers.org/en/v4.6.5/build/ol.js"></script>
    <script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
    <script>

        var workspace = '<?php echo Config::get("constants.GEOSERVER_WORKSPACE"); ?>';
        // URL of GeoServer
        var gurl = "{{ url('/gis/') }}";
        var gurl_wms = gurl + 'wms';
        var gurl_wfs = gurl + 'wfs';
        // URL of GeoServer Legends
        var gurl_legend = gurl_wms + "?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&BBOX=89.1281,23.502, 89.2068,23.5892&LAYER=";
        var buildingsLayer = new ol.layer.Image({
            visible: false,
            title: "Buildings",
            source: new ol.source.ImageWMS({
                url: gurl_wms,
                params: {
                    'LAYERS': workspace + ':' + 'buildings_layer',
                    'TILED': true,
                },
                serverType: 'geoserver',
                //crossOrigin: 'anonymous'
                transition: 0,
            })
        });
        var containmentsLayer = new ol.layer.Image({
            visible: false,
            title: "Containments",
            source: new ol.source.ImageWMS({
                url: gurl_wms,
                params: {
                    'LAYERS': workspace + ':' + 'containments_layer',
                    'TILED': true,
                },
                serverType: 'geoserver',
                //crossOrigin: 'anonymous'
                transition: 0,
            })
        });
        var wardsLayer = new ol.layer.Tile({
            visible: true,
            title: "Wards",
            source: new ol.source.TileWMS({
                url: gurl_wms,
                params: {
                    'LAYERS': workspace + ':' + 'wards_layer',
                    'TILED': true,
                    'STYLES': 'wards_layer_none'

                },
                serverType: 'geoserver',
                //crossOrigin: 'anonymous'
                transition: 0,
            })
        });
        var roadLineLayer = new ol.layer.Image({
            visible: false,
            title: "Road",
            source: new ol.source.ImageWMS({
                url: gurl_wms,
                params: {
                    'LAYERS': workspace + ':' + 'roadlines_layer',
                    'TILED': true,
                },
                serverType: 'geoserver',
//crossOrigin: 'anonymous'
                transition: 0,
            })
        });
        var placesLayer = new ol.layer.Image({
            visible: false,
            title: "Places",
            source: new ol.source.ImageWMS({
                url: gurl_wms,
                params: {
                    'LAYERS': workspace + ':' + 'places_layer',
                    'TILED': true,

                },
                serverType: 'geoserver',
//crossOrigin: 'anonymous'
                transition: 0,
            })
        });
        var waterbodysLayer = new ol.layer.Image({
            visible: false,
            title: "Waterbodys",
            source: new ol.source.ImageWMS({
                url: gurl_wms,
                params: {
                    'LAYERS': workspace + ':' + 'waterbodys_layer',
                    'TILED': true,

                },
                serverType: 'geoserver',
//crossOrigin: 'anonymous'
                transition: 0,
            })
        });
        var googleLayerHybrid =new ol.layer.Tile({
            visible:false,
            title: "Google Satellite & Roads",
            type: "base",
            source: new ol.source.TileImage({ url: 'http://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}' }),
        });
        var googleLayerRoadmap=new ol.layer.Tile({
            title: "Google Road Map",
            type: "base",
            source: new ol.source.TileImage({ url: 'http://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}' }),
        });

        var layerSwitcher = new LayerSwitcher({
            startActive: true,
            reverse: true,
            groupSelectStyle: 'group'
        });
        var map = new ol.Map({
            interactions: ol.interaction.defaults({
                altShiftDragRotate: false,
                dragPan: false,
                rotate: false,
                // mouseWheelZoom: false,
                doubleClickZoom: false
            }).extend([new ol.interaction.DragPan({kinetic: null})]),
            target: 'olmap',
            controls: ol.control.defaults({ attribution: false }),
            layers: [
                new ol.layer.Group({
                    title: 'Base maps',
                    layers: [
                        googleLayerHybrid,googleLayerRoadmap
                    ]
                }),
                new ol.layer.Group({
                    title: 'Layers',
                    fold: 'open',
                    layers: [
                        roadLineLayer,wardsLayer,buildingsLayer,containmentsLayer,placesLayer,waterbodysLayer
                    ]
                })
            ],
            view: new ol.View({
                center: ol.proj.transform([85.37004580498977,27.643296216592432], 'EPSG:4326', 'EPSG:3857'),
                // zoom: 12,
                minZoom: 12.5,
                maxZoom: 19,
                extent: ol.proj.transformExtent([85.32348539192756,27.58711426558866,85.44082675863419, 27.684646263435823 ], 'EPSG:4326', 'EPSG:3857')
            })
        });
        map.addControl(layerSwitcher);
        var eLayer = {};
        // Add extra overlay to Extra Overlays Object
        function addExtraLayer(key, name, layer) {
            // adding as property of Extra Overlays Object
            eLayer[key] = { name: name, layer: layer };

            // Adding layer to OpenLayers Map
            map.addLayer(layer);

        }
        if(!eLayer.report_polygon_buffer) {
            var reportPolygonBufferLayer = new ol.layer.Vector({

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
        draw = new ol.interaction.Draw({
            source: eLayer.report_polygon_buffer.layer.getSource(),
            type: 'Polygon'
        });
        map.addInteraction(draw);
        draw.on('drawstart', function(evt){
            eLayer.report_polygon_buffer.layer.getSource().clear();
        });
        draw.on('drawend', function(evt){
            var format = new ol.format.WKT();
            var geom = format.writeGeometry(evt.feature.getGeometry().clone().transform('EPSG:3857', 'EPSG:4326'));
            $('#geom').val(geom);
        });
        <?php if(@$geom) { ?>
        var format = new ol.format.WKT();
        var feature = format.readFeature('<?php echo $geom; ?>', {
            dataProjection: 'EPSG:4326',
            featureProjection: 'EPSG:3857'
        });

        eLayer.report_polygon_buffer.layer.getSource().addFeature(feature);

        <?php } ?>
        setInitialZoom();

        function setInitialZoom() {
            @if(isset($hotspotIdentification) && $lat && $long)
            map.getView().setCenter(ol.proj.transform([<?php echo $long;?>, <?php echo $lat;?>], 'EPSG:4326', 'EPSG:3857'));
            map.getView().setZoom(14);
            @else
            map.getView().setCenter(ol.proj.transform([85.38334613018505,27.634613503939818], 'EPSG:4326', 'EPSG:3857'));
            map.getView().setZoom(12);
            @endif
        }
        $(document).ready(function(){

            @isset($hotspotIdentification->date)
                $('.date').datepicker().datepicker('setDate',moment("{{$hotspotIdentification->date}}").format('YYYY/MM/DD'));
            @endisset

            $('.chosen-select').chosen();

            $('#getpointbycoordinates_control').click(function(e){
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';

                $('#coordinate_search_modal').modal('show');
            });

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
            popupCloser.onclick = function() {
                popupOverlay.setPosition(undefined);
                popupCloser.blur();
                return false;
            };

            map.on('singleclick', function (evt) {
                //map.on('singleclick', displayCoordinateInformation);
            });



            function displayAjaxLoader() {
                if($('.ajax-modal').length == 0) {
                    $('body').append('<div class="ajax-modal"><div class="ajax-modal-content"><div class="loader"></div></div></div>');
                }
            }

            function displayAjaxError() {
                displayAjaxErrorModal('An error occurred');
            }

            function displayAjaxErrorModal(message) {
                if($('.ajax-modal').length > 0) {
                    var html = '<div class="ajax-modal-message">';
                    html += '<span>' + message + '</span>';
                    html += '<a href="#" class="ajax-modal-close-btn"><i class="fa fa-times"></i></a>';
                    html += '</div>';

                    $('.ajax-modal-content').html(html);
                }
            }
            function removeAjaxLoader() {
                $('.ajax-modal').remove();
            }

            // Display information about coordinate
            function displayCoordinateInformation(evt) {
                var coordinate = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
                var html = '';
                html += '<div style="padding:10px;">';
                html += '<form class="form-inline" id="feature_info_form">';
                html += ' <div class="form-group">';
                html += '<div class="input-group">';
                html += '<select class="form-control" id="ward_select" name="ward_select">';
                html += ' <option value="">Select a ward</option>';
                html += ' <option value="1">1</option>';
                html += ' <option value="2">2</option>';
                html += ' <option value="3">3</option>';
                html += ' <option value="4">4</option>';
                html += ' <option value="5">5</option>';
                html += ' <option value="6">6</option>';
                html += ' <option value="7">7</option>';
                html += ' <option value="8">8</option>';
                html += ' <option value="9">9</option>';
                html += ' <option value="10">10</option>';
                html += ' </select>';
                html += ' <span class="input-group-btn">';
                html += ' <button type="submit" class="btn btn-default">';
                html += ' <i class="fa fa-search"></i>';
                html += ' </button>';
                html += ' </span>';
                html += ' </div>';
                html += ' </div>';
                html += ' <input type="hidden" id="feature_info_long" value="" />';
                html += ' <input type="hidden" id="feature_info_lat" value="" />';
                html += ' </form></div>';
                popupContent.innerHTML = html;
                popupOverlay.setPosition(evt.coordinate);
            }


            $(document).on('change','#ward',function(){
                var ward =  this.value;
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
                var url = '{{ url("hotspot-identifications/ward-center-coordinates") }}';
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
                                stroke: new ol.style.Stroke({color: '#00bfff',
                                    width: 3
                                })
                            })
                        );
                        eLayer.searchResultMarkers.layer.getSource().addFeature(feature);
                        handleZoomToExtent('wards_layer', 'ward', data.ward, false, function(){
                            removeAjaxLoader();
                        });

                    },
                    error: function(data) {
                        displayAjaxError();
                    }
                });
            });

            function handleZoomToExtent(layer, field, val, showMarker, callback) {
                var url = '{{ url("getExtent") }}' + '/' + layer + '/' + field + '/' + val;
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function(data){
                        var extent = ol.proj.transformExtent([parseFloat(data.xmin), parseFloat(data.ymin), parseFloat(data.xmax), parseFloat(data.ymax)], 'EPSG:4326', 'EPSG:3857');
                        map.getView().fit(extent);

                        if(showMarker) {

                            if(data.geom) {
                                var format = new ol.format.WKT();
                                var feature = format.readFeature(data.geom, {
                                    dataProjection: 'EPSG:4326',
                                    featureProjection: 'EPSG:3857'
                                });

                                if(feature.getGeometry() instanceof ol.geom.MultiLineString) {
                                    if(!eLayer.markers) {
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

                        //showLayer(layer);

                        if(callback) {
                            callback();
                        }
                    },
                    error: function(data) {

                    }
                });
            }
        });
    </script>
@endpush

