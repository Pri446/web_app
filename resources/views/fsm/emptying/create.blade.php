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
var anfBuildingMapLookupUrl = @json(route('buildings.map-lookup'));
var anfCreateContainmentUrl = @json(action('Fsm\ContainmentController@createContainment', ['__BUILDING_PUBLIC_ID__']));

$('#locateBuildingModal').on('shown.bs.modal', function () {
    if (window.anfMap) {
        window.anfMap.updateSize();
        return;
    }

    var workspace = '{{ Config::get("constants.GEOSERVER_WORKSPACE") }}';
    var gurl_wms = "{{ route('gis.wms') }}";
    var gurl_wfs = "{{ route('gis.wfs') }}";

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
    + '&typeName=' + encodeURIComponent(workspace + ':buildings_layer')
    + '&outputFormat=application/json'
    + '&propertyName=' + encodeURIComponent(
        'bin,ward,house_number,road_code,structure_type_name,floor_count,building_sanitation_system,geom'
    )
    + '&CQL_FILTER=' + encodeURIComponent(
        'INTERSECTS(geom,POINT(' + lng + ' ' + lat + '))'
    );

    $.getJSON(wfsUrl, function (data) {
        if (data.features && data.features.length > 0) {
            var props = data.features[0].properties;
            var bin = props.bin;

            if (!bin) {
                alert('{{ __('Unable to identify the selected building.') }}');
                return;
            }

            $.ajax({
                url: anfBuildingMapLookupUrl,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    bin: bin,
                    application_id: {{ $application_id }}
                }
            }).done(function (res) {
                props.bin = res.building_bin;
                props.public_id = res.building_public_id;
                props.owner_pii_locked = res.owner_pii_locked;
                props.owner_name = res.owner_name;
                props.owner_gender = res.owner_gender;
                showAnfPopup(props, res);
            }).fail(function (xhr) {
                console.error('Unable to load the selected building:', xhr.responseText);
                alert('{{ __('Unable to load the selected building. Please try again.') }}');
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
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showAnfPopup(building, containmentRes) {
    var html = '';
    var hasContainments = false;
    var containmentId = null;

    if (containmentRes) {
        if (containmentRes.containments && Array.isArray(containmentRes.containments) && containmentRes.containments.length > 0) {
            hasContainments = true;
            containmentId = containmentRes.containments[0].public_id;
        }
    }

    var applicationId = '{{ $application_id }}';
    var buildingPublicId = building.public_id;
    var ownerDisplay = '********';

    if (building.owner_pii_locked === false) {
        var ownerName = escapeHtml(building.owner_name || '-');
        var ownerGender = escapeHtml(building.owner_gender || '');
        ownerDisplay = ownerName + (ownerGender ? ' (' + ownerGender + ')' : '');
    }

    var addContainmentUrl = anfCreateContainmentUrl.replace(
        '__BUILDING_PUBLIC_ID__',
        encodeURIComponent(buildingPublicId)
    );

    if (!hasContainments) {
        html = `<div class="p-3">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                <h6 class="font-weight-bold mb-0">🏠 {{__('Building Info')}}</h6>
                <button type="button" class="close ml-2" onclick="$('#anf-popup').hide()"><span>&times;</span></button>
            </div>
            <p class="mb-1 small"><strong>{{__('Owner')}}:</strong> ${ownerDisplay}</p>
            <p class="mb-1 small"><strong>{{__('Ward')}}:</strong> ${escapeHtml(building.ward ?? '-')} &nbsp;|&nbsp; <strong>{{__('House No')}}:</strong> ${escapeHtml(building.house_number ?? '-')}</p>
            <p class="mb-1 small"><strong>{{__('Road Code')}}:</strong> ${escapeHtml(building.road_code ?? '-')}</p>
            <p class="mb-1 small"><strong>{{__('Structure')}}:</strong> ${escapeHtml(building.structure_type_name ?? '-')} &nbsp;|&nbsp; <strong>{{__('Floors')}}:</strong> ${escapeHtml(building.floor_count ?? '-')}</p>
            <p class="mb-1 small"><strong>{{__('Sanitation')}}:</strong> ${escapeHtml(building.building_sanitation_system ?? '-')}</p>
            <p class="mb-2 small"><strong>{{__('BIN')}}:</strong> <span class="badge badge-secondary">${escapeHtml(building.bin ?? '-')}</span></p>
            <div class="alert alert-warning py-1 small mb-2">⚠ {{__('No containment linked to this building')}}.</div>
            <a href="${addContainmentUrl}?application_id=${encodeURIComponent(applicationId)}" class="btn btn-warning btn-sm btn-block">
                + {{__('Add Containment')}}
            </a>
        </div>`;
    } else {
        html = `<div class="p-3">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                <h6 class="font-weight-bold mb-0">🏠 {{__('Building Info')}}</h6>
                <button type="button" class="close ml-2" onclick="$('#anf-popup').hide()"><span>&times;</span></button>
            </div>
            <p class="mb-1 small"><strong>{{__('Owner')}}:</strong> ${ownerDisplay}</p>
            <p class="mb-1 small"><strong>{{__('Ward')}}:</strong> ${escapeHtml(building.ward ?? '-')} &nbsp;|&nbsp; <strong>{{__('House No')}}:</strong> ${escapeHtml(building.house_number ?? '-')}</p>
            <p class="mb-1 small"><strong>{{__('Road Code')}}:</strong> ${escapeHtml(building.road_code ?? '-')}</p>
            <p class="mb-1 small"><strong>{{__('Structure')}}:</strong> ${escapeHtml(building.structure_type_name ?? '-')} &nbsp;|&nbsp; <strong>{{__('Floors')}}:</strong> ${escapeHtml(building.floor_count ?? '-')}</p>
            <p class="mb-1 small"><strong>{{__('Sanitation')}}:</strong> ${escapeHtml(building.building_sanitation_system ?? '-')}</p>
            <p class="mb-2 small"><strong>{{__('BIN')}}:</strong> <span class="badge badge-secondary">${escapeHtml(building.bin ?? '-')}</span></p>
            <h6 class="font-weight-bold border-bottom pb-1 mb-2">🚽 {{__('Containments')}}</h6>
            <div class="mb-2 small" id="anf-containments-table-wrapper">${containmentRes.popContentsHtml}</div>
            <button id="anf-select-building-btn" class="btn btn-success btn-sm btn-block"
                onclick="selectAnfBuilding('${buildingPublicId}', '${containmentId}')">
                ✔ {{__('Select This Building')}}
            </button>
        </div>`;
    }
    $('#anf-popup').html(html).show();

    if (hasContainments) {
        $('#anf-containments-table-wrapper table tbody tr:first').addClass('table-primary');
        $('#anf-containments-table-wrapper table tbody tr').css('cursor', 'pointer').on('click', function () {
            $('#anf-containments-table-wrapper table tbody tr').removeClass('table-primary');
            $(this).addClass('table-primary');
            var containmentPublicId = $(this).attr('data-containment-public-id');
            if (containmentPublicId) {
                $('#anf-select-building-btn').attr('onclick', "selectAnfBuilding('" + buildingPublicId + "', '" + containmentPublicId + "')");
            }
        });
    }
}
function selectAnfBuilding(buildingPublicId, containmentPublicId) {
    var applicationId = {{ $application_id }};

    if (!containmentPublicId) {
        alert('{{ __('Please select a containment.') }}');
        return;
    }

    $.ajax({
        url: '{{ route("application.resolve-anf", $application_id) }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            _method: 'PATCH',
            bin: buildingPublicId,
            containment_id: containmentPublicId
        },
        success: function (res) {
            if (res.status) {
                $('#anf-popup').hide();
                $('#locateBuildingModal').modal('hide');

                toastr.success('{{ __('Building and containment linked successfully. Resolving ANF') }}...', 'Success', {
                    timeOut: 2000,
                    onHidden: function () {
                        window.location.reload();
                    }
                });
            } else {
                alert(res.message || 'Error saving building selection.');
            }
        },
        error: function (xhr) {
            console.error('Failed to resolve ANF:', xhr.responseText);
            var msg = 'Error saving building selection. Please try again.';
            try {
                var json = JSON.parse(xhr.responseText);
                if (json.message) msg = json.message;
            } catch (e) {}
            alert(msg);
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

    function handleImagePreview(input, previewId, hintId, feedbackId) {
        var preview = document.getElementById(previewId);
        var hint = document.getElementById(hintId);
        var feedback = document.getElementById(feedbackId);
        var file = input.files && input.files[0];

        function resetToDefault() {
            if (!preview) return;
            var defaultSrc = preview.getAttribute('data-default-src');
            if (defaultSrc) {
                preview.src = defaultSrc;
                preview.classList.remove('d-none');
            } else {
                preview.src = '#';
                preview.classList.add('d-none');
            }
        }

        function setHintStatus(hintElement, status) {
            if (!hintElement) return;
            if (status === 'success') {
                hintElement.classList.remove('text-muted', 'text-danger');
                hintElement.classList.add('text-success');
                hintElement.style.color = '#28a745';
            } else if (status === 'error') {
                hintElement.classList.remove('text-muted', 'text-success');
                hintElement.classList.add('text-danger');
                hintElement.style.color = '#dc3545';
            } else {
                hintElement.classList.remove('text-success', 'text-danger');
                hintElement.classList.add('text-muted');
                hintElement.style.color = '';
            }
        }

        if (!file) {
            resetToDefault();
            if (feedback) feedback.textContent = '';
            setHintStatus(hint, 'default');
            return;
        }

        var extensionIsValid = /\.(jpe?g)$/i.test(file.name);
        var mimeIsValid = !file.type || file.type === 'image/jpeg' || file.type === 'image/jpg';

        if (!extensionIsValid || !mimeIsValid) {
            input.value = '';
            var label = input.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = 'Choose file';
            }
            resetToDefault();
            setHintStatus(hint, 'error');
            if (feedback) {
                feedback.textContent = 'Only JPG or JPEG images are allowed.';
                feedback.className = 'file-feedback mt-1 text-danger';
            }
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            input.value = '';
            var label = input.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = 'Choose file';
            }
            resetToDefault();
            setHintStatus(hint, 'error');
            if (feedback) {
                feedback.textContent = 'Image size should not be more than 5MB.';
                feedback.className = 'file-feedback mt-1 text-danger';
            }
            return;
        }

        if (preview._previewUrl) {
            URL.revokeObjectURL(preview._previewUrl);
        }
        preview._previewUrl = URL.createObjectURL(file);
        preview.src = preview._previewUrl;
        preview.classList.remove('d-none');

        setHintStatus(hint, 'success');
        if (feedback) {
            feedback.textContent = '';
            feedback.className = 'file-feedback mt-1 text-success';
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
    });
</script>

<script>
function toggleEmptyingFinalTripFields() {
    const tripCountField = document.querySelector('[name="trip_count"]:not([type="hidden"])');
    const currentTripField = document.querySelector('[name="trip_no"]:not([type="hidden"])');
    const finalTripFieldIds = ['receipt_number', 'total_cost', 'house_image', 'receipt_image'];

    if (!tripCountField || !currentTripField) return;

    const tripValue = field => {
        const rawValue = String(field.value || '').trim();
        const value = Number.parseInt(rawValue, 10);

        return Number.isInteger(value) && value > 0 ? value : null;
    };

    const numberOfTrips = tripValue(tripCountField);
    const currentTripNumber = tripValue(currentTripField);
    const isFinalTrip = numberOfTrips !== null && numberOfTrips === currentTripNumber;

    finalTripFieldIds.forEach(id => {
        const field = document.getElementById(id);
        const wrapper = document.getElementById(`${id}_form_group`);
        if (!field || !wrapper) return;

        wrapper.hidden = false;
        wrapper.classList.remove('d-none');
        wrapper.style.setProperty('display', isFinalTrip ? 'flex' : 'none', 'important');
        field.disabled = !isFinalTrip;
        field.required = isFinalTrip;
    });
}

function initializeFinalTripFields() {
    toggleEmptyingFinalTripFields();

    document.addEventListener('input', event => {
        if (event.target.matches('[name="trip_count"], [name="trip_no"]')) {
            toggleEmptyingFinalTripFields();
        }
    });

    document.addEventListener('change', event => {
        if (event.target.matches('[name="trip_count"], [name="trip_no"]')) {
            toggleEmptyingFinalTripFields();
        }
    });

    window.setTimeout(toggleEmptyingFinalTripFields, 0);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFinalTripFields);
} else {
    initializeFinalTripFields();
}
</script>

<script src="{{ asset('js/emptying-image-upload.js') }}?v={{ filemtime(public_path('js/emptying-image-upload.js')) }}"></script>

@endpush
