<!-- Preview KML already stored in building database -->

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
<link rel="stylesheet" href="{{asset('css/app.css')}}">
<div class="modal fade" id="kml-previewer" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">KML Viewer</h4>
            </div>
            <div class="modal-body">
                <div id="kml-map" class="map"></div>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
<script src="https://openlayers.org/en/v4.6.5/build/ol.js"></script>
<!-- Layer Switcher -->
<script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
<script src="{{asset('js/app.js')}}"></script>


<script>
    $(document).ready(function () {
        $('#kml-previewer').on('shown.bs.modal', function (e) {
            var button = $(e.relatedTarget) // Button that triggered the modal
            var kml = button.data('id');
            kmlSource = new ol.source.Vector({
                url: '{{asset('storage/building-survey-kml')}}/'+kml,
                format: new ol.format.KML(
                    {
                        extractStyles: false,
                        extractAttributes: false,
                    }
                ),
            });
            map.getLayers().forEach(function (layer) {
                if (layer.get("name")==="kml-layer"){
                    map.removeLayer(layer);
                }
            });
            const kmlLayer = new ol.layer.Vector({
                source: kmlSource,
            });
            kmlLayer.set("name","kml-layer");
            map.addLayer(kmlLayer);
            kmlLayer.once("change", function(e){
                var extent = kmlSource.getExtent();
                map.getView().fit(extent);
            });
            map.updateSize();
        })
        var kmlSource;
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
                    'LAYERS': workspace + ':' + 'buildings',
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
                    'LAYERS': workspace + ':' + 'containment',
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
            target: 'kml-map',
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
                }),
            ],
            view: new ol.View({
                center: ol.proj.transform([85.37004580498977, 27.643296216592432], 'EPSG:4326', 'EPSG:3857'),
                // zoom: 12,
                minZoom: 12.5,
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

        setInitialZoom();

        function setInitialZoom() {
            map.getView().setCenter(ol.proj.transform([85.38334613018505, 27.634613503939818], 'EPSG:4326', 'EPSG:3857'));
            map.getView().setZoom(12);
        }
    });


</script>
