<style>
    .map {
        width: 100%;
        height:400px;
    }
</style>
<style>
    #kml-map {
        border: 1px solid #000000;
    }

    .layer-switcher{
        top: 0 !important;
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

    #kml-map:focus {
        outline: #4A74A8 solid 0.15em;
    }
</style>
<link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
<link rel="stylesheet" href="https://unpkg.com/ol-layerswitcher@3.8.3/dist/ol-layerswitcher.css"/>
<div class="modal fade" id="hotspot-viewer" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">{{__('Hotspot Viewer')}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
<span aria-hidden="true">×</span>
</button>
                
            </div>
            <div class="modal-body">
                <div id="containment-map" class="map"></div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script src="https://openlayers.org/en/v4.6.5/build/ol.js"></script>
<!-- Layer Switcher -->
<script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>

<script>
    $(document).ready(function () {
        $('#hotspot-viewer').on('shown.bs.modal', function () {
            map.updateSize();
        })
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
        var googleLayerHybrid = new ol.layer.Tile({
            visible: false,
            title: "Google Satellite & Roads",
            type: "base",
            source: new ol.source.TileImage({url: 'http://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'}),
        });
        var googleLayerRoadmap = new ol.layer.Tile({
            title: "Google Road Map",
            type: "base",
            source: new ol.source.TileImage({url: 'http://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'}),
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
            target: 'containment-map',
            controls: ol.control.defaults({attribution: false}),
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
                        wardsLayer, buildingsLayer, containmentsLayer
                    ]
                })
            ],
            view: new ol.View({
                center: ol.proj.transform([{{$long}},{{$lat}}], 'EPSG:4326', 'EPSG:3857'),
                // zoom: 12,
                minZoom: 12,
                maxZoom: 19,
                extent: ol.proj.transformExtent([85.32348539192756, 27.58711426558866, 85.44082675863419, 27.684646263435823], 'EPSG:4326', 'EPSG:3857')
            })
        });
        map.addControl(layerSwitcher);
        var eLayer = {};

        // Add extra overlay to Extra Overlays Object
        function addExtraLayer(key, name, layer) {
            // adding as property of Extra Overlays Object
            eLayer[key] = {name: name, layer: layer};

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
            var format = new ol.format.WKT();
            var feature = format.readFeature('<?php echo $geom; ?>', {
                dataProjection: 'EPSG:4326',
                featureProjection: 'EPSG:3857'
            });

            eLayer.report_polygon_buffer.layer.getSource().addFeature(feature);
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


        setInitialZoom();

        function setInitialZoom() {
            map.getView().setCenter(ol.proj.transform([{{$long}},{{$lat}}], 'EPSG:4326', 'EPSG:3857'));
            map.getView().setZoom(16);
        }

        $(document).ready(function () {


            $('#getpointbycoordinates_control').click(function (e) {
                e.preventDefault();
                disableAllControls();
                $('.map-control').removeClass('map-control-active');
                currentControl = '';

                $('#coordinate_search_modal').modal('show');
            });


            function displayAjaxLoader() {
                if ($('.ajax-modal').length == 0) {
                    $('body').append('<div class="ajax-modal"><div class="ajax-modal-content"><div class="loader"></div></div></div>');
                }
            }

            function displayAjaxError() {
                displayAjaxErrorModal('An error occurred');
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

            function removeAjaxLoader() {
                $('.ajax-modal').remove();
            }


            function handleZoomToExtent(layer, field, val, showMarker, callback) {
                var url = '{{ url("getExtentWard") }}' + '/' + layer + '/' + field + '/' + val;
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function (data) {
                        var extent = ol.proj.transformExtent([parseFloat(data.xmin), parseFloat(data.ymin), parseFloat(data.xmax), parseFloat(data.ymax)], 'EPSG:4326', 'EPSG:3857');
                        map.getView().fit(extent);

                        if (showMarker) {
                            if (data.long && data.lat) {
                                if (!eLayer.markers) {
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

                            if (data.long1 && data.lat1) {
                                if (!eLayer.markers) {
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

                            if (data.geom) {
                                var format = new ol.format.WKT();
                                var feature = format.readFeature(data.geom, {
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

                        if (callback) {
                            callback();
                        }
                    },
                    error: function (data) {

                    }
                });
            }
        });
    });

</script>
@endpush
