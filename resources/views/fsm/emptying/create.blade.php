@extends('layouts.dashboard')
@section('title', __('Add Emptying Service Details'))

@section('content')

{{-- Modal: must be at TOP LEVEL, outside everything else --}}
@if($is_anf)
<div class="modal fade" id="locateBuildingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📍 {{ __('Locate Building') }}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            {{-- position:relative is required for the popup overlay --}}
            <div class="modal-body" style="padding: 0; position: relative;">
                <div id="anf-map" style="width: 100%; height: 500px;"></div>
                {{-- Popup div sits on top of the map --}}
                <div id="anf-popup" style="display:none; position:absolute; bottom:10px; left:10px;
                    background:white; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.3);
                    min-width:260px; max-width:320px; z-index:1000;">
                </div>
            </div>
            <div class="modal-footer">
                <span id="anf-selected-info" class="text-success mr-auto" style="display:none;"></span>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card card-info">
    <div class="card-header">
        @if($is_anf)
            <div class="card-tools">
                <button type="button" class="btn btn-warning btn-sm"
                    data-toggle="modal" data-target="#locateBuildingModal">
                    📍 {{ __('Locate Building') }}
                </button>
            </div>
        @endif
    </div>
    @include('layouts.components.error-list')
    @include('layouts.components.success-alert')
    @include('layouts.components.error-alert')
    <div class="card-body">
        {!! Form::open(['url' => route('emptying.store'), 'class' => 'form-horizontal', 'files' => true]) !!}
        @if($is_anf)
            <input type="hidden" name="anf_bin" id="anf_bin">
            <input type="hidden" name="anf_containment_id" id="anf_containment_id">
        @endif
        @include('layouts.partial-form', ["submitButtonText" => __('Save')])
        {!! Form::close() !!}
    </div>
</div>

@endsection

@push('scripts')
@if($is_anf)
<link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
<link rel="stylesheet" href="https://unpkg.com/ol-layerswitcher@3.8.3/dist/ol-layerswitcher.css"/>
<script src="{{ asset('/js/ol.js') }}"></script>
<script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
<script>
$('#locateBuildingModal').on('shown.bs.modal', function () {
    if (window.anfMap) {
        window.anfMap.updateSize();
        return;
    }

    var workspace = '{{ Config::get("constants.GEOSERVER_WORKSPACE") }}';
    var gurl      = '{{ Config::get("constants.GEOSERVER_URL") }}/';
    var gurl_wms  = gurl + 'wms';
    var gurl_wfs  = gurl + 'wfs';
    var authkey   = '{{ Config::get("constants.AUTH_KEY") }}';

    var centerLng    = 85.32;
var centerLat    = 27.68;
var extentMinLng = 85.22;
var extentMinLat = 27.60;
var extentMaxLng = 85.42;
var extentMaxLat = 27.78;

    var buildingsLayer = new ol.layer.Image({
        visible: true, title: "Buildings",
        source: new ol.source.ImageWMS({
            url: gurl_wms,
            params: { 'LAYERS': workspace + ':buildings_layer', 'TILED': true },
            serverType: 'geoserver', transition: 0,
        })
    });
    var containmentsLayer = new ol.layer.Image({
        visible: true, title: "Containments",
        source: new ol.source.ImageWMS({
            url: gurl_wms,
            params: { 'LAYERS': workspace + ':containments_layer', 'TILED': true },
            serverType: 'geoserver', transition: 0,
        })
    });
    var wardsLayer = new ol.layer.Image({
        visible: true, title: "Wards",
        source: new ol.source.ImageWMS({
            url: gurl_wms,
            params: { 'LAYERS': workspace + ':wards_layer', 'TILED': true, 'STYLES': 'wards_layer_none' },
            serverType: 'geoserver', transition: 0,
        })
    });
    var googleLayerHybrid = new ol.layer.Tile({
        visible: false, title: "Google Satellite & Roads", type: "base",
        source: new ol.source.TileImage({ url: 'http://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}' }),
    });
    var googleLayerRoadmap = new ol.layer.Tile({
        title: "Google Road Map", type: "base",
        source: new ol.source.TileImage({ url: 'http://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}' }),
    });
    var roadLineLayer = new ol.layer.Image({
        visible: false, title: "Roads",
        source: new ol.source.ImageWMS({
            url: gurl_wms,
            params: { 'LAYERS': workspace + ':roadlines_layer', 'TILED': true },
            serverType: 'geoserver', transition: 0,
        })
    });

    var layerSwitcher = new LayerSwitcher({ startActive: true, reverse: true, groupSelectStyle: 'group' });

    window.anfMap = new ol.Map({
        interactions: ol.interaction.defaults({
            altShiftDragRotate: false, dragPan: false, rotate: false, doubleClickZoom: false
        }).extend([new ol.interaction.DragPan({ kinetic: null })]),
        target: 'anf-map',
        controls: ol.control.defaults({ attribution: false }),
        layers: [
            new ol.layer.Group({ title: 'Base maps', layers: [googleLayerHybrid, googleLayerRoadmap] }),
            new ol.layer.Group({ title: 'Layers', fold: 'open', layers: [roadLineLayer, wardsLayer, buildingsLayer, containmentsLayer] })
        ],
        view: new ol.View({
            minZoom: 12.5, maxZoom: 19,
            extent: ol.proj.transformExtent(
                [extentMinLng, extentMinLat, extentMaxLng, extentMaxLat],
                'EPSG:4326', 'EPSG:3857'
            )
        })
    });

    window.anfMap.addControl(layerSwitcher);
    window.anfMap.updateSize();
    window.anfMap.getView().setCenter(ol.proj.transform([centerLng, centerLat], 'EPSG:4326', 'EPSG:3857'));
    window.anfMap.getView().setZoom(14);

 window.anfMap.on('singleclick', function (evt) {
    var coordinate = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
    var lng = coordinate[0];
    var lat = coordinate[1];

    var wfsUrl = gurl_wfs
        + '?service=WFS&version=1.0.0&request=GetFeature'
        + '&typeName=' + workspace + ':buildings_layer'
        + '&outputFormat=application/json'
        + '&CQL_FILTER=INTERSECTS(geom,POINT(' + lng + ' ' + lat + '))'
        + '&authkey=' + authkey;

    $.getJSON(wfsUrl, function (data) {
        if (data.features && data.features.length > 0) {
            var props = data.features[0].properties;
            var bin = props.bin;
            $.getJSON('{{ url("/building-info/buildings") }}/' + bin + '/listContainments', function (res) {
                 console.log('listContainments response:', res);
                showAnfPopup(props, res);
            }).fail(function () {
                showAnfPopup(props, null);
            });
        } else {
            // No building — show popup with option to add
            showNoBuilding(lat, lng);
        }
    }).fail(function (xhr) {
        console.error('Building query failed:', xhr.responseText);
        alert('Error querying map. Please try again.');
    });
});
});
function showNoBuilding(lat, lng) {
    var applicationId = '{{ $application_id }}';

    var createUrl = '{{ url("/building-info/buildings/create") }}'
        + '?lat=' + lat
        + '&lng=' + lng
        + '&application_id=' + applicationId;

    var html = `<div class="p-3">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
            <h6 class="font-weight-bold mb-0">📍 {{__('No Building Found')}}</h6>
            <button type="button" class="close ml-2" onclick="$('#anf-popup').hide()"><span>&times;</span></button>
        </div>
        <div class="alert alert-warning py-1 small mb-2">
            ⚠ {{__('No building found at this location.')}}
        </div>
        <p class="small mb-2">
            <strong>{{__('Coordinates')}}:</strong><br>
            Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}
        </p>
        <a href="${createUrl}" class="btn btn-warning btn-sm btn-block">
            + {{__('Add New Building Here')}}
        </a>
        <button class="btn btn-secondary btn-sm btn-block mt-1" onclick="$('#anf-popup').hide()">
            {{__('Cancel')}}
        </button>
    </div>`;

    $('#anf-popup').html(html).show();
}
function showAnfPopup(building, containmentRes) {
    var html = '';
    var hasContainments = containmentRes && containmentRes.popContentsHtml &&
                          containmentRes.popContentsHtml !== 'No Containment Found.';

    // Extract containment ID from the HTML (first <td> content)
    var containmentId = null;
    if (hasContainments) {
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = containmentRes.popContentsHtml;
        var firstTd = tempDiv.querySelector('tbody tr td:first-child');
        containmentId = firstTd ? firstTd.textContent.trim() : null;
    }

  if (!hasContainments) {
    const currentUrl = new URL(window.location.href);

    let applicationId = currentUrl.searchParams.get('application_id');

    if (!applicationId) {
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        applicationId = pathParts[pathParts.length - 1];
    }
    console.log('applicationId:', applicationId);

    html = `<div class="p-3">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
            <h6 class="font-weight-bold mb-0">🏠 {{__('Building Info')}}</h6>
            <button type="button" class="close ml-2" onclick="$('#anf-popup').hide()"><span>&times;</span></button>
        </div>
        <p class="mb-1 small"><strong>{{__('Owner')}}:</strong> ${building.owner_name ?? '-'} (${building.owner_gender ?? '-'})</p>
        <p class="mb-1 small"><strong>{{__('Ward')}}:</strong> ${building.ward ?? '-'} &nbsp;|&nbsp; <strong>{{__('House No')}}:</strong> ${building.house_number ?? '-'}</p>
        <p class="mb-1 small"><strong>{{__('Road Code')}}:</strong> ${building.road_code ?? '-'}</p>
        <p class="mb-1 small"><strong>{{__('Structure')}}:</strong> ${building.structure_type_name ?? '-'} &nbsp;|&nbsp; <strong>{{__('Floors')}}:</strong> ${building.floor_count ?? '-'}</p>
        <p class="mb-1 small"><strong>{{__('Sanitation')}}:</strong> ${building.building_sanitation_system ?? '-'}</p>
        <p class="mb-2 small"><strong>{{__('BIN')}}:</strong> <span class="badge badge-secondary">${building.bin ?? '-'}</span></p>
        <div class="alert alert-warning py-1 small mb-2">⚠ {{__('No containment linked to this building')}}.</div>
        <a href="/fsm/containments/${building.bin}/create?application_id=${applicationId}" class="btn btn-warning btn-sm btn-block">
            + {{__('Add Containment')}}
        </a>
    </div>`;
} else {
    html = `<div class="p-3">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
            <h6 class="font-weight-bold mb-0">🏠 {{__('Building Info')}}</h6>
            <button type="button" class="close ml-2" onclick="$('#anf-popup').hide()"><span>&times;</span></button>
        </div>
        <p class="mb-1 small"><strong>{{__('Owner')}}:</strong> ${building.owner_name ?? '-'} (${building.owner_gender ?? '-'})</p>
        <p class="mb-1 small"><strong>{{__('Ward')}}:</strong> ${building.ward ?? '-'} &nbsp;|&nbsp; <strong>{{__('House No')}}:</strong> ${building.house_number ?? '-'}</p>
        <p class="mb-1 small"><strong>{{__('Road Code')}}:</strong> ${building.road_code ?? '-'}</p>
        <p class="mb-1 small"><strong>{{__('Structure')}}:</strong> ${building.structure_type_name ?? '-'} &nbsp;|&nbsp; <strong>{{__('Floors')}}:</strong> ${building.floor_count ?? '-'}</p>
        <p class="mb-1 small"><strong>{{__('Sanitation')}}:</strong> ${building.building_sanitation_system ?? '-'}</p>
        <p class="mb-2 small"><strong>{{__('BIN')}}:</strong> <span class="badge badge-secondary">${building.bin ?? '-'}</span></p>
        <h6 class="font-weight-bold border-bottom pb-1 mb-2">🚽 {{__('Containments')}}</h6>
        <div class="mb-2 small">${containmentRes.popContentsHtml}</div>
        <button class="btn btn-success btn-sm btn-block"
            onclick="selectAnfBuilding('${building.bin}', '${containmentId}')">
            ✔ {{__('Select This Building')}}
        </button>
    </div>`;
}
    $('#anf-popup').html(html).show();
}
function selectAnfBuilding(bin, containmentId) {
    var applicationId = {{ $application_id }};

    $.ajax({
        url: '{{ route("application.resolve-anf", $application_id) }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            _method: 'PATCH',
            bin: bin,
            containment_id: containmentId ?? ''
        },
        success: function (res) {
            if (res.status) {
                $('#anf-popup').hide();
                $('#locateBuildingModal').modal('hide');

                toastr.success('{{ __('Building and containment linked successfully. Resolving ANF') }}...', 'Success', {
                    timeOut: 2000,
                    onHidden: function () {
                        window.location.reload();s
                    }
                });
            }
        },
        error: function (xhr) {
            console.error('Failed to resolve ANF:', xhr.responseText);
            alert('Error saving building selection. Please try again.');
        }
    });
}
</script>
@endif

<script>
    function autoFillDetails() {
        $(document).ready(function() {
            if ($("input[name='autofill']:checked").val() === 'on') {
                $("input[name='applicants_name']").val($("input[name=customer_name]").val());
                $("#applicant_gender").val($("#customer_gender").val());
                $("input[name='applicants_contact']").val($("input[name=contact_no]").val());
                $("input[name='applicants_name']").attr('disabled','disabled');
                $("#applicant_gender").attr('disabled','disabled');
                $("input[name='applicants_contact']").attr('disabled','disabled');
            } else {
                $("input[name='applicants_name']").val('');
                $("#applicant_gender").val('');
                $("input[name='applicants_contact']").val('');
                $("input[name='applicants_name']").removeAttr('disabled');
                $("#applicant_gender").removeAttr('disabled');
                $("input[name='applicants_contact']").removeAttr('disabled');
            }
        });
    }

    function emptyAutoFields() {
        $('#road_code').val('');
        $('#containment_code').val('');
        $('#ward').val('');
        $('#customer_name').val('');
        $('#customer_gender').val('');
        $('#contact_no').val('');
        $("input[name='applicants_name']").val('');
        $("#applicant_gender").val('');
        $("input[name='applicants_contact']").val('');
        $("input[name='applicants_name']").removeAttr('disabled');
        $("#applicant_gender").removeAttr('disabled');
        $("input[name='applicants_contact']").removeAttr('disabled');
        $("input[name='autofill']").prop('checked', false);
    }

    function onAddressChange() {
        emptyAutoFields();
        if($('#address').find(":selected").text() === 'Address Not Found'){
            $('#building-if-address').hide();
            $("#building-if-address :input").each(function () { $(this).attr("disabled",true); });
            $('#building-if-not-address').show();
            $("#building-if-not-address :input").each(function () { $(this).attr("disabled",false); });
            $("input[type='submit']").removeAttr('disabled');
        } else {
            $('#building-if-not-address').hide();
            $("#building-if-not-address :input").each(function () { $(this).attr("disabled",true); });
            $('#building-if-address').show();
            $("#building-if-address :input").each(function () { $(this).attr("disabled",false); });
            $.ajax({
                url: "{{ route('application.get-building-details') }}",
                data: { "address" : $('#address').val() },
                success: function (res) {
                    if (res.status === true){
                        let containments = '';
                        res.containments.forEach(function (containment) { containments+=containment.id + ' '; })
                        $('#customer_name').val(res.customer_name);
                        $('#customer_gender').val(res.customer_gender);
                        $('#contact_no').val(res.customer_contact);
                        $('#road_code').val(res.road);
                        $('#containment_code').val(containments);
                        $('#ward').val(res.ward);
                        $("input[type='submit']").removeAttr('disabled');
                    } else if (res.status === false) {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: "There is an ongoing application for this address!" });
                        emptyAutoFields();
                        $("input[type='submit']").attr('disabled','disabled');
                    } else {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: "Error!" });
                        emptyAutoFields();
                    }
                },
                error: function (err) {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: err.responseJSON.error });
                    emptyAutoFields();
                    $("input[type='submit']").attr('disabled','disabled');
                }
            });
        }
    }

    $(document).ready(function() {
        $('#proposed_emptying_date').daterangepicker({
            minDate: moment(), singleDatePicker: true, autoUpdateInput: false,
        });
        $('#proposed_emptying_date').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY'));
        });
        $('#proposed_emptying_date').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });
        $('#address').prepend('<option selected=""></option>').append('<option value="-">Address Not Found</option>').select2({
            placeholder: 'Address',
            matcher: function(params, data) {
                if (data.id === "-") { return data; }
                else { return $.fn.select2.defaults.defaults.matcher.apply(this, arguments); }
            },
            closeOnSelect: true, width: '100%'
        });
        if ('{{ old('address') }}' !== ''){
            $('#address').select2().val('{{ old('address') }}').trigger('change');
            onAddressChange();
        }
        $('#address').on('change', onAddressChange);
        $('#house_image').on('change', function() {
            validateFileSize(document.querySelector('#house_image'),'fileSizeHintImg','5');
        });
        $('#receipt_image').on('change', function() {
            validateFileSize(document.querySelector('#receipt_image'),'fileSizeRintImg','5');
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tripCountField =
        document.querySelector('#trip_count:not([type="hidden"])') ||
        document.querySelector('input[name="trip_count"]:not([type="hidden"])') ||
        document.getElementById('trip_count');
    const tripNoField =
        document.querySelector('#tr ip_no:not([type="hidden"])') ||
        document.querySelector('input[name="trip_no"]:not([type="hidden"])') ||
        document.getElementById('trip_no');
    const receiptFieldIds = ['receipt_number', 'total_cost', 'house_image', 'receipt_image'];

    function getWrapper(el) {
        return el?.closest('.form-group, .form-field, .mb-3, .row') || el?.parentElement || el;
    }
    function showNode(node, show) {
        if (!node) return;
        node.style.display = show ? '' : 'none';
    }
    function clearFieldValue(field) {
        if (!field) return;
        if (['text', 'number'].includes(field.type) || field.tagName === 'TEXTAREA') field.value = '';
        if (field.type === 'file') {
            field.value = '';
            const label = field.closest('.custom-file')?.querySelector('.custom-file-label');
            if (label) label.textContent = 'Choose file';
        }
    }
    function getCurrentTripCount() {
        const raw = tripCountField?.value?.trim() ?? document.getElementById('trip_count_hidden')?.value?.trim() ?? '';
        const val = parseInt(raw, 10);
        return Number.isFinite(val) ? val : null;
    }
    function getCurrentTripNo() {
        const raw = tripNoField?.value?.trim() ?? document.getElementById('trip_no_hidden')?.value?.trim() ?? '';
        const val = parseInt(raw, 10);
        return Number.isFinite(val) ? val : 0;
    }
    function toggleReceiptFields() {
        const tripCount = getCurrentTripCount();
        const emptyingTripNo = getCurrentTripNo();
        const show = tripCount !== null && tripCount === emptyingTripNo;
        receiptFieldIds.forEach(id => {
            const field = document.getElementById(id);
            const wrapper = getWrapper(field);
            showNode(wrapper || field, show);
            if (!show) clearFieldValue(field);
        });
    }
    toggleReceiptFields();
    ['input', 'change', 'keyup', 'blur'].forEach(evt => {
        tripCountField.addEventListener(evt, toggleReceiptFields);
        if (tripNoField) tripNoField.addEventListener(evt, toggleReceiptFields);
    });
});
</script>

@endpush
