@extends('layouts.dashboard')
@section('title', $page_title)
@push('style')
<style>
    .map {
        width: 100%;
        height: 600px;
    }
    #kml-map {
        border: 1px solid #000000;
    }
    .layer-switcher {
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
@endpush
@section('content')
    <div class="card">
        <div class="card-header">
            <a href="#" class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse"
                data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                Show Filter
            </a>
        </div><!-- /.box-header -->
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

                                            <label for="temp_building_code" class="control-label col-md-2">Temporary Building Code </label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="temp_building_code"
                                                    placeholder="House Number" />
                                            </div>

                                            <label for="date_from" class="control-label col-md-2">Date From</label>
                                            <div class="col-md-2">
                                                <input type="date" class="form-control" placeholder="Date From" id="date_from" onclick = 'this.showPicker();'/>
                                            </div>
                                            <label for="date_to" class="control-label col-md-2">Date To</label>
                                            <div class="col-md-2">
                                                <input type="date" class="form-control" placeholder="Date To"  id="date_to" onclick = 'this.showPicker();'/>
                                            </div>


                                        </div>
                                        <div class="card-footer text-right">
                                            <button type="submit" class="btn btn-info" style="font-family: 'Open Sans', sans-serif;">Filter</button>
                                            <button type="reset" class="btn btn-info reset" style="font-family: 'Open Sans', sans-serif;">Reset</button>
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
            <div style="overflow: auto; width: 100%;" >
                <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%" style="font-family: 'Open Sans', sans-serif;">
                    <thead>
                        <tr>
                            <th>Temporary Building Code</th>
                            <th>Tax Code</th>
                            <th>Survey Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div><!-- /.box-body -->
    </div><!-- /.box -->
    <div class="modal fade" id="kml-previewer" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" >
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">KML Viewer</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><button type="button" class="close" data-dismiss="modal" aria-label="Close">
<span aria-hidden="true">x</span></button>

            </div>
            <div class="modal-body" >
                <div id="kml-map" class="map" style="height: 350px;">

                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src="https://openlayers.org/en/v4.6.5/build/ol.js"></script>
<script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
<link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
<link rel="stylesheet" href="https://unpkg.com/ol-layerswitcher@3.8.3/dist/ol-layerswitcher.css" />
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script>
    $.fn.dataTable.ext.errMode = 'throw';
        $(function() {
            var dataTable = $('#data-table').DataTable({
                bFilter: false,
                processing: true,
                serverSide: true,
                scrollCollapse: true,
                "bStateSave": true,
                "stateDuration": 1800, // In seconds; keep state for half an hour

                ajax: {
                    url: '{!! url('building-info/building-surveys/data') !!}',
                    data: function(d) {
                        d.temp_building_code = $('#temp_building_code').val();
                        d.tax_code = $('#tax_code').val();
                        d.collected_date = $('#collected_date').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                    }
                },
                columns: [{
                        data: 'temp_building_code',
                        name: 'temp_building_code'
                    },
                    {
                        data: 'tax_code',
                        name: 'tax_code',

                    },
                    {
                        data: 'collected_date',
                        name: 'collected_date',

                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ]
            }).on('draw', function() {
                $('.delete').on('click', function(e) {
                    var form = $(this).closest("form");
                    event.preventDefault();
                    deleteAction(form || this);

                });
            });

            var temp_building_code = '',
                tax_code = '';
            collected_date = '';


            $('#filter-form').on('submit', function(e) {

                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();

                if ((date_from !== '') && (date_to === '')) {

                    Swal.fire({
                        title: 'Date To is required',
                        text: "Please Select Date To ",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'close'
                    })

                    return false;
                }
                if ((date_from === '') && (date_to !== '')) {

                    Swal.fire({
                        title: 'Date From is Required',
                        text: "Please Select Date From ",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Close'
                    })

                    return false;
                }
                e.preventDefault();
                dataTable.draw();
                temp_building_code = $('#temp_building_code').val();
                tax_code = $('#tax_code').val();
                date_from = $('#date_from').val();
                date_to = $('#date_to').val();
            });

            $(".reset").on("click", function(e) {
                $('#temp_building_code').val('');
                $('#date_from').val('');
                $('#date_to').val('');
                $('#data-table').dataTable().fnDraw();
            });
           
            $('#headingOne').click(function() {

                if ($(this).text() == 'Hide Filter') {
                    $('#mydiv').slideDown("slow");
                } else if ($(this).text() == 'Show Filter') {
                    $('#mydiv').slideUp("slow");
                }
            });


            $('#kml-previewer').on('shown.bs.modal', function (e) {
    var button = $(e.relatedTarget); // Button that triggered the modal
    var kml = button.data('id'); // Get KML file ID from button's data attribute

    // Create KML source
    var kmlSource = new ol.source.Vector({
        url: '{{ asset('storage/building-survey-kml') }}/' + kml,
        format: new ol.format.KML({
            extractStyles: false,  // Disable KML style extraction to apply custom styles
            extractAttributes: true
        }),
    });

    // Remove existing KML layer if present
    map.getLayers().forEach(function (layer) {
        if (layer.get('name') === 'kml-layer') {
            map.removeLayer(layer);
        }
    });

    // Create KML layer with custom styling
    const kmlLayer = new ol.layer.Vector({
    source: kmlSource,
    style: function(feature) {
        // Apply custom style with only a red border (no fill)
        return new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: '#0000FF',  // Red stroke color
                width: 2  // Stroke width
            })
        });
    }
});


    kmlLayer.set('name', 'kml-layer');
    map.addLayer(kmlLayer);

    // Fit the map view to the KML's extent once it loads
    kmlLayer.once('change', function () {
        var extent = kmlSource.getExtent();
        map.getView().fit(extent, { size: map.getSize() });
    });

    // Ensure the map container resizes correctly
    map.updateSize();
});

// Rest of the code for layers and map initialization remains the same

var workspace = '<?php echo Config::get("constants.GEOSERVER_WORKSPACE"); ?>';
// URL of GeoServer
var gurl = "<?php echo Config::get("constants.GEOSERVER_URL"); ?>/";
var gurl_wms = gurl + 'wms';
var gurl_wfs = gurl + 'wfs';
var authkey = '<?php echo Config::get("constants.AUTH_KEY"); ?>';
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
var sewersLayer = new ol.layer.Image({
    visible: true,
    title: "Sewers",
    source: new ol.source.ImageWMS({
        url: gurl_wms,
        params: {
            'LAYERS': workspace + ':' + 'sewerlines_layer',
            'TILED': true,
            //'STYLES': 'wards_layer_none'
        },
        serverType: 'geoserver',
        //crossOrigin: 'anonymous'
        transition: 0,
    })
});
var roadslayer = new ol.layer.Image({
    visible: true,
    title: "Roads",
    source: new ol.source.ImageWMS({
        url: gurl_wms,
        params: {
            'LAYERS': workspace + ':' + 'roadlines_layer',
            'TILED': true,
            //'STYLES': 'wards_layer_none'
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
                sewersLayer, roadslayer, wardsLayer, containmentsLayer, buildingsLayer,
            ]
        }),
    ],
    view: new ol.View({
        center: ol.proj.transform([85.37004580498977, 27.643296216592432], 'EPSG:4326', 'EPSG:3857'),
        // zoom: 12,
        minZoom: 12.5,
        maxZoom: 25,
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
};

        });
    </script>

@endpush
