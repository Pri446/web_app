@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
<div class="card card-info">
    @include('errors.list')
    {!! Form::open(['id' => 'export_form', 'class' => 'form-horizontal']) !!}

        <div class="card-body">
            <div class="form-group row">
                {!! Form::label('ward_overlay', __('Layers'),['class' => 'col-md-3 control-label']) !!}
                <div class="col-md-3">
                    {!! Form::select('ward_overlay',
                    [
                        'containments_layer' => 'Containments',
                        'buildings_layer' => 'Buildings',
                        'buildings_tax_status_layer' => 'Property Tax Collection ISS Status',
                        'buildings_water_payment_status_layer' => 'Water Supply ISS Status',
                        'treatmentplants_layer' => 'Treatment Plants',
                        'roadlines_layer' => 'Road Network',
                        'sewerlines_layer' => 'Sewer Network',
                        'drains_layer' => 'Drain Network',
                        'watersupply_network_layer' => 'Water Supply Network',
                        'places_layer' => 'Places',
                        'waterbodys_layer'=>'Water Bodies',
                        'landuses_layer'=>'Land Use',
                        'grids_layer'=>'Summarized Grids',
                        'sanitation_system_layer'=>'Sanitation System',
                        'toilets_layer'=>'Toilets PT/CT',
                        'waterborne_hotspots_layer' => 'Waterborne Hotspots',
                        'water_samples_layer' => 'Water Samples',
                        'low_income_communities_layer' => 'Low Income Community',
                        'buildings_swm_payment_status_layer' =>'Solid Waste ISS',
                    ],
                    null,
                    ['id' => 'ward_overlay', 'multiple' => true, 'style' => 'width: 100%', 'class' => 'custom-ul'])
                    !!}
                </div>
            </div>
             {{--<div class="form-group row">
                <label class="col-md-3 control-label">
                    Wards
                </label>
                <div class="col-md-3">
                    <input type="checkbox" name="inlineRadioOptions" id="checkboxforwards" value="option1">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 control-label">
                    Taxzones
                </label>
                <div class="col-md-3">
                    <input type="checkbox" name="inlineRadioOptions" id="radiofortaxzones" value="option2">
                </div>
            </div>
            --}}

            <div class="form-group wardssection row" style="xdisplay: none">
                {!! Form::label('ward', __('Select Wards'),['class' => 'col-md-3 control-label']) !!}
                <div class="col-md-3">
    {!! Form::select('ward', $wards, null, ['id' => 'ward', 'multiple' => true, 'style' => 'width: 100%','class' => 'custom-ul']) !!}


                </div>
            </div>
{{--            Hidden Tax zone for now--}}
{{--            <div class="form-group taxzonesection" style="display: none">--}}
{{--                {!! Form::label('taxzone','Tax Zone',['class' => 'col-sm-3 control-label']) !!}--}}
{{--                <div class="col-sm-3">--}}
{{--                    {!! Form::select('taxzone',--}}
{{--                    $taxzoneids,--}}
{{--                    null,--}}
{{--                    ['id' => 'taxzone', 'multiple' => true, 'style' => 'width: 100%'])--}}
{{--                    !!}--}}
{{--                </div>--}}
{{--            </div>--}}
            <div class="form-group row">
                {!! Form::label('export_format', __('Export Format'),['class' => 'col-md-3 control-label']) !!}
                <div class="col-md-3">
                    {!! Form::select('export_format',
                    [
                        'kml' => 'KML File',
                        'shp' => 'Shape File'
                    ],
                    null,
                    ['class' => 'form-control', 'placeholder' => __('Export Format')])
                    !!}
                </div>
            </div>
        </div><!-- /.card-body -->
        <div class="card-footer">
            {!! Form::submit(__('Download'), ['class' => 'btn btn-info']) !!}
        </div><!-- /.card-footer -->
    {!! Form::close() !!}
</div><!-- /.card -->

@stop
@push('scripts')

<script type="text/javascript">
$(document).ready(function(){
    $('#ward').multipleSelect({
        filter: true,
        placeholder: "{{ __('Select Wards')}}"
    });
    $('#ward_overlay').multipleSelect({
        filter: true,
        placeholder: "{{__('Layers')}}"
    });


    $('#checkboxforwards').click(function(){
        if($(this).is(":checked")){
            // $('.taxzonesection').css('display', 'none');
            $('.wardssection').css('display', 'flex');
            $('#ward').multipleSelect('uncheckAll');
        }
        else if($(this).is(":not(:checked)")){
            $('.wardssection').css('display', 'none');
            $('#ward').multipleSelect('uncheckAll');
        }
    });

    // $('#radiofortaxzones').click(function(){
    //     $('.wardssection').css('display', 'none');
    //     // $('.taxzonesection').css('display', 'block');
    //     // $('#taxzone').multipleSelect('uncheckAll');
    //
    // });

    $('#export_form').on('submit', function() {
        var workspace = '<?php echo Config::get("constants.GEOSERVER_WORKSPACE"); ?>';
        var gurl = "{{ url('/gis/') }}";
        var gurl_wfs = gurl + 'wfs';

        var selectedWards = $('#ward').val();
        // var selectedTaxzone = $('#taxzone').val();
        var selectedLayers = $('#ward_overlay').val();
        var selectedFormat = $('#export_format').val();

        if(checkValidation(selectedWards, selectedLayers, selectedFormat))
        {
            var filters = [];

            $.each(selectedWards, function(index, value){
                filters.push('ward=' + value);
            });

            var layers = [];
            $.each(selectedLayers, function(index, value) {
                layers.push(workspace + ':' + value);
            });

            var outputFormat = selectedFormat == 'kml' ? 'KML' : 'SHAPE-ZIP';

            var exportLink = gurl_wfs + "?request=GetFeature&service=WFS&version=1.0.0&typeName=" + layers.join(',') + "&CQL_FILTER=INTERSECTS(geom, collectGeometries(queryCollection('" + workspace + ":wards_layer', 'geom', '" + filters.join(' OR ') + "')))AND deleted_at IS NULL" +
    "&outputFormat=" + outputFormat;

            if(outputFormat == 'SHAPE-ZIP') {
                exportLink += '&format_options=filename:' + 'export_imis_' + moment().format('YYYYMMDD_HHmmss') + '.zip';
            } else {
                exportLink += '&format_options=filename:' + 'export_imis_' + moment().format('YYYYMMDD_HHmmss') + '.kml';
            }
            window.open(exportLink);

        }
        return false;

    });

    function checkValidation(selectedWards, selectedLayers, selectedFormat){

            if ( !selectedWards || selectedWards.length < 1 || !selectedLayers || selectedLayers.length < 1 || !selectedFormat) {
                Swal.fire({
                    title: `Please select layers, wards and export format!`,
                    icon: "warning",
                    button: "Close",
                    className: "custom-swal",
                })
                return false;
            }


    return true;
    }
});
</script>
@endpush

