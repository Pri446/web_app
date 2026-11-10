{{-- Last Modified Date: 14-04-2024
 Developed By: Innovative Solution Pvt. Ltd. (ISPL)   --}}
<style>
      #map {
        width: 800px;
        height: 400px;
      }
      #olmap {
          border: 1px solid #000000;
          margin-top: 20px;
          height: 500px;
          width: 100%;
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
    color: red;
    margin-left: 5px;
  }
      #map:focus {
        outline: #4A74A8 solid 0.15em;
      }
      /* Fix for Chosen search box */
      .chosen-container .chosen-search input {
          width: 100% !important;
          padding: 5px !important;
      }
      .chosen-container-single .chosen-search {
          display: block !important;
      }
      /* Ensure Chosen matches Bootstrap height */
      .chosen-container-single .chosen-single {
          height: 38px !important;
          padding: 5px 12px !important;
          line-height: 28px !important;
          background: #fff !important;
          border: 1px solid #ced4da !important;
      }
      .select2-container--default .select2-selection--single {
          height: 38px !important;
          border: 1px solid #ced4da !important;
          padding: 4px 8px !important;
      }
      .select2-container--default .select2-selection--single .select2-selection__rendered {
          line-height: 28px !important;
      }
      .select2-container--default .select2-selection--single .select2-selection__arrow {
          height: 36px !important;
      }
    </style>
<link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css" rel="stylesheet">
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
    <div class="form-group row">
        {!! Form::label('unique_reference_id',__('Unique Reference ID'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('unique_reference_id',null,['class' => 'form-control', 'placeholder' => __('Unique Reference ID') ,]) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('type', __('Type'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            <select name="type" id="type" class="form-control select2">
                <option value="">{{ __('Select or Enter Type') }}</option>
                @php
                    $selectedType = old('type', $place->type ?? '');
                @endphp
                @if(!empty($selectedType) && !in_array($selectedType, $types))
                    <option value="{{ $selectedType }}" selected>{{ $selectedType }}</option>
                @endif
                @foreach($types as $type)
                    @if(!empty($type))
                        <option value="{{ $type }}"
                            {{ ($selectedType == $type) ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-group required row">
        {!! Form::label('name',__('Place Name'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('name',null,['class' => 'form-control', 'placeholder' => __('Place Name') ,]) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('ward', __('Ward').' <span class="text-danger">*</span>', ['class' => 'col-sm-3 control-label'], false) !!}
        <div class="col-sm-3">
            @php
                $wards = range(1, 16);
                $wards = array_combine($wards, $wards);
            @endphp
            {!! Form::select('ward', $wards, null, ['class' => 'form-control', 'placeholder' => 'Ward', 'required']) !!}
        </div>
    </div>

    <div class="form-group required row">
        {!! Form::label('geom',__('Location'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-6">
            <a class="skiplink" href="#map">Go to map</a>
            <div id="olmap"></div>
            <div id="popup" class="ol-popup" style="display: none;">
                <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                <div id="popup-content"></div>
            </div>
            <small class="text-muted">Click on the map to select location</small>
        </div>
        <input type="hidden" name="geom" id="geom" value="{{ old('geom', $geom ?? '') }}" />
    </div>
</div>

<div class="card-footer">
    <a href="{{ action('PlacesController@index') }}" class="btn btn-info">{{__('Back to List')}}</a>
    {!! Form::submit(__($submitButtonText), ['class' => 'btn btn-info']) !!}
</div>

@push('scripts')
    <script src="https://openlayers.org/en/v4.6.5/build/ol.js"></script>
    <script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>
    
    <script>
        window.mapConfig = window.mapConfig || {
            centerLng: 85.37004580498977,
            centerLat: 27.643296216592432,
            minZoom: 12,
            maxZoom: 18
        };

        var workspace = '<?php echo Config::get("constants.GEOSERVER_WORKSPACE"); ?>';
        var gurl_wms = "{{ route('gis.wms') }}";
        var gurl_wfs = "{{ route('gis.wfs') }}";
        // URL of GeoServer Legends
        var gurl_legend =
                gurl_wms +
                "?SERVICE=WMS&REQUEST=GetLegendGraphic&VERSION=1.0.0" +
                "&FORMAT=image/png&WIDTH=20&HEIGHT=20" +
                "&BBOX=89.1281,23.502,89.2068,23.5892&LAYER=";

        var WARD_NUMBERS = <?php echo json_encode(array_values($wards)); ?>;

        // Populated once by loadAllWardFeatures(): all ward polygons + the
        // auto-detected property key that holds the ward number.
        var allWardFeatures = null;
        var wardNumberPropertyKey = null;

        // Holds the currently selected ward's geometry/extent (in EPSG:3857) so map
        // clicks can be validated against it. Populated by zoomToWardAndValidate().
        var selectedWardGeometry = null;
        var selectedWardExtent = null;

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
                transition: 0,
            })
        });
        
        var googleLayerHybrid = new ol.layer.Tile({
            visible: false,
            title: "Google Satellite & Roads",
            type: "base",
            source: new ol.source.TileImage({ url: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}' }),
        });
        
        var googleLayerRoadmap = new ol.layer.Tile({
            visible: true,
            title: "Google Road Map",
            type: "base",
            source: new ol.source.TileImage({ url: 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}' }),
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
                doubleClickZoom: false
            }).extend([new ol.interaction.DragPan({kinetic: null})]),
            target: 'olmap',
            controls: ol.control.defaults({ attribution: false }),
            layers: [
                new ol.layer.Group({
                    title: 'Base maps',
                    layers: [
                        googleLayerHybrid, googleLayerRoadmap
                    ]
                }),
                new ol.layer.Group({
                    title: 'Layers',
                    fold: 'open',
                    layers: [
                        roadLineLayer, wardsLayer, buildingsLayer, containmentsLayer, placesLayer
                    ]
                })
            ],
            view: typeof createMapView !== 'undefined' ? createMapView() : new ol.View({
                center: ol.proj.transform([85.37004580498977,27.643296216592432], 'EPSG:4326', 'EPSG:3857'),
                zoom: 13,
                minZoom: 12,
                maxZoom: 18
            })
        });
        
        map.addControl(layerSwitcher);
        
        var eLayer = {};
        
        // Add extra overlay to Extra Overlays Object
        function addExtraLayer(key, name, layer) {
            eLayer[key] = { name: name, layer: layer };
            map.addLayer(layer);
        }

        // Loose equality for ward values: handles number vs string vs
        // zero-padding differences ("05" should match 5).
        function wardValuesMatch(a, b) {
            if (a === null || a === undefined || b === null || b === undefined) return false;
            var na = String(a).trim();
            var nb = String(b).trim();
            if (na !== '' && nb !== '' && !isNaN(na) && !isNaN(nb)) {
                return parseFloat(na) === parseFloat(nb);
            }
            return na === nb;
        }

        function detectWardNumberKey(features, expectedWardNumbers) {
            if (!features || !features.length) return null;

            var keys = Object.keys(features[0].getProperties()).filter(function(k) {
                return k.toLowerCase() !== 'geometry';
            });
            var wardNamedKeys = keys.filter(function(k) { return k.toLowerCase().indexOf('ward') !== -1; });
            var candidates = wardNamedKeys.length ? wardNamedKeys : keys;

            var bestKey = null;
            var bestScore = -1;

            candidates.forEach(function(key) {
                var values = features.map(function(f) { return f.get(key); });
                var matchCount = 0;
                expectedWardNumbers.forEach(function(expected) {
                    if (values.some(function(v) { return wardValuesMatch(v, expected); })) {
                        matchCount++;
                    }
                });
                if (matchCount > bestScore) {
                    bestScore = matchCount;
                    bestKey = key;
                }
            });

            return bestKey;
        }

        // Fetch every ward polygon from GeoServer ONE time (no CQL_FILTER, so
        // there's no attribute name to get wrong) and cache it. Subsequent
        // calls reuse the cache instantly.
        function loadAllWardFeatures(onReady) {
            if (allWardFeatures) {
                onReady();
                return;
            }

            var wfsBase = gurl_wfs;
            var wfsUrl = wfsBase
                + '?service=WFS'
                + '&version=2.0.0'
                + '&request=GetFeature'
                + '&typeName=' + encodeURIComponent('wards_layer')
                + '&outputFormat=application/json'
                + '&srsName=EPSG:4326';

            $.ajax({
                url: wfsUrl,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (!response || !response.features || !response.features.length) {
                        console.error('wards_layer returned zero features. Check GEOSERVER_WORKSPACE/GEOSERVER_URL config and that "wards_layer" is the correct layer name.');
                        allWardFeatures = [];
                        onReady();
                        return;
                    }

                    var geojsonFormat = new ol.format.GeoJSON();
                    allWardFeatures = geojsonFormat.readFeatures(response, {
                        dataProjection: 'EPSG:4326',
                        featureProjection: 'EPSG:3857'
                    });

                    wardNumberPropertyKey = detectWardNumberKey(allWardFeatures, WARD_NUMBERS);

                    if (wardNumberPropertyKey) {
                        console.log('Ward boundary lookup: using attribute "' + wardNumberPropertyKey + '" as the ward number.');
                    } else {
                        console.error('Could not figure out which wards_layer attribute holds the ward number. Available attributes on a sample feature:', allWardFeatures[0].getProperties());
                    }

                    onReady();
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching wards_layer: ' + error);
                    console.error('GeoServer response: ' + xhr.responseText);
                    allWardFeatures = [];
                    onReady();
                }
            });
        }

        // Zoom the map to the selected ward's boundary, draw it as a highlight,
        // and store its geometry so map clicks can be validated against it
        // (so a point can't be dropped outside the chosen ward).
        function zoomToWardAndValidate(wardNumber, shouldFitView) {
            shouldFitView = (shouldFitView !== false);

            // No ward selected: clear any previous boundary/validation state.
            if (!wardNumber) {
                selectedWardGeometry = null;
                selectedWardExtent = null;
                if (eLayer.selected_ward_boundary) {
                    eLayer.selected_ward_boundary.layer.getSource().clear();
                }
                return;
            }

            loadAllWardFeatures(function() {
                var match = wardNumberPropertyKey
                    ? allWardFeatures.filter(function(f) {
                        return wardValuesMatch(f.get(wardNumberPropertyKey), wardNumber);
                    })[0]
                    : null;

                if (!match) {
                    console.error('No wards_layer feature matched Ward ' + wardNumber + '.');
                    selectedWardGeometry = null;
                    selectedWardExtent = null;
                    return;
                }

                selectedWardGeometry = match.getGeometry();
                selectedWardExtent = selectedWardGeometry.getExtent();

                if (shouldFitView) {
                    map.getView().fit(selectedWardExtent, { padding: [50, 50, 50, 50] });
                }

                // Draw/refresh the ward boundary highlight so the user can see the
                // exact area they're allowed to click inside.
                if (!eLayer.selected_ward_boundary) {
                    var wardBoundaryLayer = new ol.layer.Vector({
                        source: new ol.source.Vector(),
                        style: new ol.style.Style({
                            fill: new ol.style.Fill({ color: 'rgba(74, 116, 168, 0.08)' }),
                            stroke: new ol.style.Stroke({ color: '#4A74A8', width: 2, lineDash: [6, 4] })
                        })
                    });
                    addExtraLayer('selected_ward_boundary', 'Selected Ward Boundary', wardBoundaryLayer);
                } else {
                    eLayer.selected_ward_boundary.layer.getSource().clear();
                }
                eLayer.selected_ward_boundary.layer.getSource().addFeature(match);
            });
        }

        // Re-fetch/zoom whenever the Ward dropdown changes, and drop any previously
        // placed point since it may no longer belong to the newly selected ward.
        $('#ward').on('change', function() {
            var wardNumber = $(this).val();

            $('#geom').val('');
            if (eLayer.report_polygon_buffer) {
                eLayer.report_polygon_buffer.layer.getSource().clear();
            }
            if (eLayer.selected_pointcoordinate) {
                eLayer.selected_pointcoordinate.layer.getSource().clear();
            }

            zoomToWardAndValidate(wardNumber, true);
        });
        
        // Create the layer for displaying existing/selected geometry
        if(!eLayer.report_polygon_buffer) {
            var reportPolygonBufferLayer = new ol.layer.Vector({
                source: new ol.source.Vector(),
                style: function(feature) {
                    var geometryType = feature.getGeometry().getType();
                    
                    if (geometryType === 'Point') {
                        return new ol.style.Style({
                            image: new ol.style.Icon({
                                anchor: [0.5, 1],
                                src: '{{ url("/")}}/img/marker-green.png',
                                scale: 1.2
                            })
                        });
                    } else {
                        return new ol.style.Style({
                            fill: new ol.style.Fill({
                                color: 'rgba(255, 99, 132, 0.3)',
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#ff6384',
                                width: 2,
                            })
                        });
                    }
                }
            });
            
            addExtraLayer('report_polygon_buffer', 'Existing Geometry', reportPolygonBufferLayer);
        }
        
        // Handle map click to select location
        map.on('singleclick', function (evt) {
            var wardNumber = $('#ward').val();

            if (!wardNumber) {
                alert('Please select a Ward first, then click on the map to set the location.');
                return;
            }

            // is skipped rather than blocking every click.)
            if (selectedWardGeometry && !selectedWardGeometry.intersectsCoordinate(evt.coordinate)) {
                alert('That point is outside Ward ' + wardNumber + '. Please click within the highlighted ward boundary.');
                return;
            }

            var coordinate = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
            var lat = coordinate[1];
            var lng = coordinate[0];
            
            displayPointByCoordinates(lat, lng);
            
            if(eLayer.report_polygon_buffer) {
                eLayer.report_polygon_buffer.layer.getSource().clear();
            }
            
            var wktPoint = 'POINT(' + lng + ' ' + lat + ')';
            var format = new ol.format.WKT();
            var feature = format.readFeature(wktPoint, {
                dataProjection: 'EPSG:4326',
                featureProjection: 'EPSG:3857'
            });
            
            if(eLayer.report_polygon_buffer && feature) {
                eLayer.report_polygon_buffer.layer.getSource().addFeature(feature);
            }
            
            $('#geom').val(wktPoint);
            console.log('Point selected: ' + wktPoint);
        });
        
        // Display existing geometry from database
        <?php if(isset($geom) && !empty($geom)): ?>
        try {
            var format = new ol.format.WKT();
            var feature = format.readFeature('<?php echo addslashes($geom); ?>', {
                dataProjection: 'EPSG:4326',
                featureProjection: 'EPSG:3857'
            });

            if(feature && eLayer.report_polygon_buffer) {
                eLayer.report_polygon_buffer.layer.getSource().addFeature(feature);
                
                var extent = feature.getGeometry().getExtent();
                map.getView().fit(extent, { padding: [50, 50, 50, 50] });
                
                console.log('Existing geometry loaded successfully');
                
                $('#geom').val('<?php echo addslashes($geom); ?>');
            }
        } catch(e) {
            console.error('Error loading geometry: ' + e);
        }
        <?php endif; ?>
        
        function displayPointByCoordinates(lat, long){
            if(eLayer.selected_pointcoordinate) {
                eLayer.selected_pointcoordinate.layer.getSource().clear();
            }
            else {
                var layer = new ol.layer.Vector({
                    source: new ol.source.Vector()
                });
                addExtraLayer('selected_pointcoordinate', 'Selected Point Coordinate', layer);
            }

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
        
        function setInitialZoom() {
            if (typeof window.mapConfig !== 'undefined' && window.mapConfig.centerLng && window.mapConfig.centerLat) {
                map.getView().setCenter(ol.proj.transform(
                    [window.mapConfig.centerLng, window.mapConfig.centerLat], 
                    'EPSG:4326', 
                    'EPSG:3857'
                ));
                map.getView().setZoom(window.mapConfig.minZoom || 13);
            } else {
                map.getView().setCenter(ol.proj.transform([85.37004580498977, 27.643296216592432], 'EPSG:4326', 'EPSG:3857'));
                map.getView().setZoom(13);
            }
        }
        
        setInitialZoom();
        
        $(document).ready(function(){
            $('#type').select2({
                tags: true,
                placeholder: "{{ __('Select or Enter Type') }}",
                allowClear: true,
                width: '100%'
            });

            if ($(".chosen-select").length) {
                $(".chosen-select").chosen({
                    no_results_text: "No results found!",
                    width: "100%",
                    search_contains: true
                });
            }

            if($('.date').length) {
                $('.date').datetimepicker({
                    format: "YYYY-MM-DD",
                });
            }

            if($('.timepicker').length) {
                $('.timepicker').datetimepicker({
                    format: 'hh:mm A'
                });
            }

            // Prevent browser from trying to navigate to file:/// if a file is dragged onto the page/map
            window.addEventListener('dragover', function(e) { e.preventDefault(); }, false);
            window.addEventListener('drop', function(e) { e.preventDefault(); }, false);

            var initialWard = $('#ward').val();
            if (initialWard) {
                var hasExistingGeom = <?php echo (isset($geom) && !empty($geom)) ? 'true' : 'false'; ?>;
                zoomToWardAndValidate(initialWard, !hasExistingGeom);
            }
        });
    </script>
@endpush
