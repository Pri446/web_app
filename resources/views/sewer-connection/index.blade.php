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
<div class="card" style="font-family: 'Open Sans', sans-serif;">
<div class="card-header">
    <a class="btn btn-info float-right" id="toggleFilter" type="button" data-toggle="collapse" data-target="#filterSection" aria-expanded="false" aria-controls="filterSection">
    {{ __('Show Filter')}}    
    </a>
</div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="collapse" id="filterSection">
                    <form class="form-horizontal" id="filter-form">
                        <div class="form-group row">
                            <label for="sewer_code" class="col-md-2 col-form-label" style=" font-family: 'Open Sans', sans-serif;">{{ __('Sewer Code')}}</label>
                            <div class="col-md-2">
                                <input type="text" class="form-control" id="sewer_code" placeholder="{{ __('Sewer Code')}}" oninput="validateAlphanumeric(this)"/>
                            </div>
                            <label for="bin" class="col-md-2 col-form-label" style=" font-family: 'Open Sans', sans-serif;">{{ __('BIN')}}</label>
                            <div class="col-md-2">
                                <input type="text" class="form-control" id="bin" placeholder="{{ __('BIN') }}" oninput="validateAlphanumeric(this)"/>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-info" style="font-family: 'Open Sans', sans-serif;">{{ __('Filter')}}</button>
                            <button type="reset" class="btn btn-info reset" style="font-family: 'Open Sans', sans-serif;">{{ __('Reset')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- /.card-body -->
    <div class="card-body">
        <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
            <thead>
                <tr>
                   
                    <th style=" font-family: 'Open Sans', sans-serif;">{{ __('BIN')}}</th>
                    <th style=" font-family: 'Open Sans', sans-serif;">{{ __('Sewer Code')}}</th>
                    <th style=" font-family: 'Open Sans', sans-serif;">{{ __('Actions')}}</th>
                </tr>
            </thead>
        </table>
    </div><!-- /.card-body -->
</div><!-- /.card -->
@include('sewer-connection.approve')
<!-- Map Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">"{{__('Sewer Connection')}}"</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="kml-map" class="map"></div>
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
        processing: true,
        bFilter: false,
        serverSide: true,
        ajax: {
            url: '{!! url("sewerconnection/sewerconnection/data") !!}',
            data: function(d) {
                d.sewer_code = $('#sewer_code').val();
                d.bin = $('#bin').val();
            }
        },
        columns: [
           
            { data: 'bin', name: 'bin' },
            { data: 'sewer_code', name: 'sewer_code' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']]
    }).on('draw', function() {
        $('.delete').on('click', function(e) {
            var form = $(this).closest("form");
            event.preventDefault();
            Swal.fire({
               title: "{{__('Are you sure?')}}",
                    text: "{!! __('You won\'t be able to revert this!') !!}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Yes, delete it!') }}",
                    cancelButtonText: '{{ __('Cancel') }}',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            })
        });
    });

    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        dataTable.draw();
    });

    $(".reset").on("click", function(e) {
        $('#sewer_code').val('');
        $('#bin').val('');
        dataTable.draw();
        localStorage.removeItem('DataTables_' + window.location.pathname);
    });

    $('#toggleFilter').click(function () {
    const isExpanded = $(this).attr('aria-expanded') === 'true';
    
    if (isExpanded) {
        $(this).text("{{ __('Show Filter') }}");
        $(this).attr('aria-expanded', 'false');
    } else {
        $(this).text("{{ __('Hide Filter') }}");
        $(this).attr('aria-expanded', 'true');
    }
    
    $('#filterSection').collapse('toggle');
});

    $('#exampleModal').on('shown.bs.modal', function(e) {
        var button = $(e.relatedTarget);
        var kml = button.data('id');
        var kml2 = button.data('sewer-code');
        var url = '/sewerconnection/sewerconnection/datageom/' + kml;
        var url2 = '/sewerconnection/sewerconnection/geomsewer/' + kml2;
        
        $.when(
            $.get(url),
            $.get(url2)
        ).done(function(response1, response2) {
            var format = new ol.format.WKT();
            if (response1[0].wkt_geom) {
                var feature = format.readFeature(response1[0].wkt_geom, {
                    dataProjection: 'EPSG:4326',
                    featureProjection: 'EPSG:3857'
                });

                var buildingLayer = new ol.layer.Vector({
                    source: new ol.source.Vector(),
                    style: new ol.style.Style({
                        stroke: new ol.style.Stroke({ color: '#0000FF', width: 4 }),
                        text: new ol.style.Text({
                            font: '12px Calibri,sans-serif',
                            fill: new ol.style.Fill({ color: '#000' }),
                            stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
                            text: 'BIN: ' + kml
                        })
                    })
                });

                buildingLayer.getSource().addFeature(feature);
                addExtraLayer('building_layer', 'Building Layer', buildingLayer);
                map.getView().fit(feature.getGeometry().getExtent(), map.getSize());
            } else {
                swal({ title: "Warning!", text: "Building not found", icon: "warning" })
                    .then(() => { $('#exampleModal').modal('hide'); });
            }

            if (response2[0].wkt_geom) {
                var sewerFeature = format.readFeature(response2[0].wkt_geom, {
                    dataProjection: 'EPSG:4326',
                    featureProjection: 'EPSG:3857'
                });

                var sewerLayer = new ol.layer.Vector({
                    source: new ol.source.Vector(),
                    style: new ol.style.Style({
                        stroke: new ol.style.Stroke({ color: '#FF00FF', width: 3 }),
                        text: new ol.style.Text({
                            font: '12px Calibri,sans-serif',
                            fill: new ol.style.Fill({ color: '#000' }),
                            stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
                            text: 'Sewer Code: ' + kml2
                        })
                    })
                });

                sewerLayer.getSource().addFeature(sewerFeature);
                addExtraLayer('sewer_layer', 'Sewer Layer', sewerLayer);
            } else {
                swal({ title: "Warning!", text: "{{__('Sewer information not found.')}}", icon: "warning" })
                    .then(() => { $('#exampleModal').modal('hide'); });
            }
        }).fail(function() {
            swal({ title: "Error!", text: "{{__('An error occurred while fetching data.')}}", icon: "error" })
                .then(() => { $('#exampleModal').modal('hide'); });
        });

        if (!mapInitialized) {
            initializeMap();
            mapInitialized = true;
        }
    });

    var mapInitialized = false;
  var map;

    function initializeMap() {
        var workspace = '<?php echo Config::get("constants.GEOSERVER_WORKSPACE"); ?>';
        var gurl = "{{ url('/gis/') }}";
        var gurl_wms = gurl + 'wms';

        var googleLayerRoadmap = new ol.layer.Tile({
            title: "Google Road Map",
            type: "base",
            source: new ol.source.TileImage({
                url: "https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}"
            }),
            visible: false
        });

        var googleLayerSatellite = new ol.layer.Tile({
            title: "Google Satellite",
            type: "base",
            source: new ol.source.TileImage({
                url: "https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}"
            }),
            visible: false
        });

        var osmLayer = new ol.layer.Tile({
            title: 'Open Street Map',
            type: 'base',
            source: new ol.source.OSM(),
            visible: true
        });

        var baseLayerGroup = new ol.layer.Group({
            title: 'Base Maps',
            layers: [googleLayerRoadmap, googleLayerSatellite, osmLayer]
        });

        map = new ol.Map({
            target: "kml-map",
            layers: [baseLayerGroup],
            view: new ol.View({
                center: ol.proj.transform([85.37004580498977, 27.643296216592432], 'EPSG:4326', 'EPSG:3857'),
                // zoom: 12,
                minZoom: 18,
                maxZoom: 25,
                extent: ol.proj.transformExtent([85.32348539192756, 27.58711426558866, 85.44082675863419, 27.684646263435823], 'EPSG:4326', 'EPSG:3857')
            })
        });

        var layerSwitcher = new ol.control.LayerSwitcher({});
        map.addControl(layerSwitcher);
    }

    function addExtraLayer(layerId, layerTitle, layerInstance) {
        var existingLayer = map.getLayers().getArray().find(layer => layer.get('id') === layerId);
        if (existingLayer) {
            map.removeLayer(existingLayer);
        }
        layerInstance.set('id', layerId);
        layerInstance.set('title', layerTitle);
        map.addLayer(layerInstance);
    }
});
</script>
@endpush
