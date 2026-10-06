<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
<style>
      #map {
        width: 100px;
        height: 200px; /* 100% of the viewport height - navbar height */
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
      #map:focus {
        outline: #4A74A8 solid 0.15em;
      }
      #olmap{
          width: 800px;
      }
    </style>
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
    <div class="form-group row required">
        {!! Form::label('name', __('Name'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Name')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('location', __('Location'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('location', null, ['class' => 'form-control', 'placeholder' => __('Location')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('type', __('Treatment Plant Type'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::select('type', $tpType, null, ['class' => 'form-control chosen-select', 'placeholder' => __('Treatment Plant Type')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('capacity_per_day', __('Capacity Per Day (m³)'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('capacity_per_day', null, [
                'class' => 'form-control',
                'placeholder' => __('Capacity Per Day (m³)'),
                'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '');",
            ]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('caretaker_name', __('Caretaker Name'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('caretaker_name', null, ['class' => 'form-control', 'placeholder' => __('Caretaker Name')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('caretaker_gender', __('Caretaker Gender'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::select('caretaker_gender', [
                "Male" => 'Male',
                "Female" => 'Female',
                "Others" => 'Others'
            ], null, ['class' => 'form-control', 'placeholder' => __('Caretaker Gender')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('caretaker_number', __('Caretaker Number'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('caretaker_number', null, [
                'class' => 'form-control',
                'placeholder' => __('Caretaker Number'),
                'oninput' => "validateOwnerContactInput(this)",
            ]) !!}
        </div>
    </div>





    <div class="form-group row required">
        {!! Form::label('status',__('Status'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::select('status', $status, null, ['class' => 'form-control chosen-select', 'placeholder' => __('Status')]) !!}
        </div>
    </div>

    @if(!isset($treatmentPlant))
    <div class="form-group row required">
        {!! Form::label('', __('Click to set Latitude and Longitude'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-6">
            <div id="olmap" xstyle="position: absolute; top: 0; left: 0; right: 0; bottom:0;"></div>
        </div>
    </div>
@endif
    @if(!isset($treatmentPlant))
    <div class="form-group row required" style="display: none;">
        {!! Form::label('longitude', null, ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::hidden('longitude', null, ['class' => 'form-control', 'placeholder' => 'Longitude']) !!}
        </div>
    </div>
    <div class="form-group row required" style="display: none;">
        {!! Form::label('latitude', null, ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::hidden('latitude', null, ['class' => 'form-control', 'placeholder' => 'Latitude']) !!}
        </div>
    </div>
@endif


    @if(!$treatmentPlant)

	<div class="form-group row">
		{!! Form::label('create_user',__('Create User?'),['class' => 'col-sm-3 control-label']) !!}
		<div class="col-sm-3">
        <input type="checkbox"
       name="create_user"
       id="create_user"
       class="create_user"
       value="on"
       {{ old('create_user') ? 'checked' : '' }}>

		</div>
	</div>
	 <div id="user-password">
    <div class="form-group row required">
        {!! Form::label('email',__("Email"),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('email',null,['class' => 'form-control', 'placeholder' => __('Email')]) !!}
        </div>
    </div>
    <div class="form-group row ">
    {!! Form::label('password', __('Password'), ['class' => 'col-sm-3 control-label']) !!}
    <div class="col-sm-3">
        <!-- Password Input -->
        <input type="password"
        class="form-control"
        name="password"
        id="password"
        placeholder="{{ __('Password') }}">


        <!-- Error/Requirement Message Container -->
        <div id="password-error" class="mt-1" style="display: none; color: red;">
            <ul style="margin-bottom: 0; padding-left: 1rem;">
                <li id="char-count">{{__('The Password must be at least 8 characters.')}}</li>
                <li id="uppercase-lowercase">{{__('The Password must contain at least one uppercase and one lowercase letter.')}}</li>
                <li id="symbol">{{__('The Password must contain at least one symbol.')}}</li>
                <li id="number">{{__('The Password must contain at least one number.')}}</li>
            </ul>
        </div>
    </div>
</div>
<div class="form-group row">
    {!! Form::label('password_confirmation', __('Confirm Password'), ['class' => 'col-sm-3 control-label']) !!}
    <div class="col-sm-3">
        <input type="password"
               class="form-control"
               name="password_confirmation"
               id="password_confirmation"
               placeholder="{{__('Confirm Password')}}">

        <!-- Confirm Password Requirements -->
        <div id="confirm-password-error" class="mt-1" style="display: none; color: red;">
            <ul style="margin-bottom: 0; padding-left: 1rem;">
                <li id="confirm-char-count">{{__('The Password must be at least 8 characters.')}}</li>
                <li id="confirm-uppercase-lowercase">{{__('The Password must contain at least one uppercase and one lowercase letter.')}}</li>
                <li id="confirm-symbol">{{__('The Password must contain at least one symbol.')}}</li>
                <li id="confirm-number">{{__('The Password must contain at least one number.')}}</li>
                <li id="confirm-match">{{__('Passwords must match.')}}</li>
            </ul>
        </div>
    </div>
</div>
	</div>
	@endif
</div><!-- /.box-body -->
<div class="card-footer">
    <a href="{{ action('Fsm\TreatmentPlantController@index') }}" class="btn btn-info">{{ __('Back to List') }}</a>
    {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}
</div><!-- /.box-footer -->

@push('scripts')
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
        var wardsLayer = new ol.layer.Image({
            visible: true,
            title: "Wards",
            source: new ol.source.ImageWMS({
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
        var roadLineLayer = new ol.layer.Image({
            visible: false,
            title: "Roads",
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
                        roadLineLayer,wardsLayer,buildingsLayer,containmentsLayer,placesLayer
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
        //            if(!eLayer.report_polygon_buffer) {
        //                var reportPolygonBufferLayer = new ol.layer.Vector({
        //
        //                    source: new ol.source.Vector(),
        //                    style: new ol.style.Style({
        //                    fill: new ol.style.Fill({
        //                      color: 'rgba(255, 255, 255, 0.2)',
        //                    }),
        //                    stroke: new ol.style.Stroke({
        //                      color: '#ffcc33',
        //                      width: 2,
        //                    }),
        //                    image: new ol.style.CircleStyle({
        //                      radius: 7,
        //                      fill: new ol.style.Fill({
        //                        color: '#ffcc33',
        //                      }),
        //                    }),
        //                  }),
        //                });
        //
        //
        //                addExtraLayer('report_polygon_buffer', 'Report Polygon Buffer', reportPolygonBufferLayer);
        //            }
        map.on('singleclick', function (evt) {
            var coordinate = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
            displayPointByCoordinates(coordinate[1], coordinate[0]);
            $('#latitude').val(coordinate[1]);
            $('#longitude').val(coordinate[0]);
        });
        <?php if(@$geom) { ?>
        var format = new ol.format.WKT();
        var feature = format.readFeature('<?php echo $geom; ?>', {
            dataProjection: 'EPSG:4326',
            featureProjection: 'EPSG:3857'
        });

        eLayer.report_polygon_buffer.layer.getSource().addFeature(feature);

        <?php } ?>
        function displayPointByCoordinates(lat, long){
            if(eLayer.selected_pointcoordinate) {
                eLayer.selected_pointcoordinate.layer.getSource().clear();
            }
            else {
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
                    src: '{{ url("/")}}/img/marker-green.png'
                })
            });

            feature.setStyle(style);

            eLayer.selected_pointcoordinate.layer.getSource().addFeature(feature);

            map.getView().setCenter(ol.proj.transform([parseFloat(long), parseFloat(lat)], 'EPSG:4326', 'EPSG:3857'));
        }
        setInitialZoom();

        function setInitialZoom() {
            map.getView().setCenter(ol.proj.transform([85.38334613018505,27.634613503939818], 'EPSG:4326', 'EPSG:3857'));
            map.getView().setZoom(12);
        }
        $(document).ready(function(){
            $(document).ready(function () {
    // Check if the create_user checkbox is checked on page load
    if ($('#create_user').is(":checked")) {
        $('#user-password').show();
    } else {
        $('#user-password').hide();
    }
});


    $('#create_user').on('change', function () {
        if ($('#create_user').is(":checked")) {

            $('#user-password').show();
        }
        else {
            $('#user-password').hide();

        }
    });


            $('.date').datetimepicker({
                format: "YYYY-MM-DD",
            });

            $('.timepicker').datetimepicker({
                format: 'hh:mm A'
            });

            $('.chosen-select').chosen();

            $('#getpointbycoordinates_control').click(function(e){
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';

                $('#coordinate_search_modal').modal('show');
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






            function handleZoomToExtent(layer, field, val, showMarker, callback) {
                var url = '{{ url("getExtentWard") }}' + '/' + layer + '/' + field + '/' + val;
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function(data){
                        var extent = ol.proj.transformExtent([parseFloat(data.xmin), parseFloat(data.ymin), parseFloat(data.xmax), parseFloat(data.ymax)], 'EPSG:4326', 'EPSG:3857');
                        map.getView().fit(extent);

                        if(showMarker) {
                            if(data.long && data.lat){
                                if(!eLayer.markers) {
                                    var markerLayer = new ol.layer.Vector({
                                        // visible: false,
                                        source: new ol.source.Vector()
                                    });

                                    addExtraLayer('markers', 'Markers', markerLayer);
                                    // showExtraLayer('markers');
                                }

                                var markerFeature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(data.long), parseFloat(data.lat)], 'EPSG:4326', 'EPSG:3857'))
                                });

                                var markerStyle = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: '{{ url("/")}}/img/pin-green.png'
                                    })
                                });

                                markerFeature.setStyle(markerStyle);

                                eLayer.markers.layer.getSource().addFeature(markerFeature);

                                map.getView().setCenter(ol.proj.transform([parseFloat(data.long), parseFloat(data.lat)], 'EPSG:4326', 'EPSG:3857'));
                                map.getView().setZoom(16);
                            }

                            if(data.long1 && data.lat1){
                                if(!eLayer.markers) {
                                    var markerLayer = new ol.layer.Vector({
                                        // visible: false,
                                        source: new ol.source.Vector()
                                    });

                                    addExtraLayer('markers', 'Markers', markerLayer);
                                    // showExtraLayer('markers');
                                }

                                var markerFeature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(data.long1), parseFloat(data.lat1)], 'EPSG:4326', 'EPSG:3857'))
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
