@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')

    @include('layouts.components.error-list')
    <div class="card card-info">
        {!! Form::open([
            'url' => 'building-info/buildings',
            'id' => 'prevent-multiple-submits',
            'files' => true,

        ]) !!}
        @include('building-info.buildings.partial-form', ['submitButtomText' => __('Save')])
        {!! Form::close() !!}
    </div><!-- /.card -->
@stop



@push('scripts')
    <script>

        $(document).ready(function() {

            // Check if a div with the alert class exists, if yes then clear local storage
            let selectedAssociatedValue = null;
            let selectedAssociatedText = null;
            let selectedRoadCode = null;
            let selectedWaterCode = null;
            let selectedSewerCode = null;
            let selectedDrainCode = null;
            let selectedBINText = null;
            let selectedBINValue = null;
            let containmentTypeText = null;
            let containmentTypeValue = null;
            let selectedUseCategory = null;


            if ($('.alert.alert-danger.alert-dismissible').length == 0) {
                localStorage.removeItem("selectedAssociatedValue");
                localStorage.removeItem("selectedAssociatedText");
                localStorage.removeItem("selectedRoadCode");
                localStorage.removeItem("selectedWaterCode");
                localStorage.removeItem("selectedSewerCode");
                localStorage.removeItem("selectedDrainCode");
                localStorage.removeItem("selectedBINText");
                localStorage.removeItem("selectedBINValue");
                localStorage.removeItem("containmentTypeText");
                localStorage.removeItem("containmentTypeValue");
                localStorage.removeItem("selectedUseCategory");
            }
            else{
                selectedAssociatedValue = localStorage.getItem("selectedAssociatedValue");
                selectedAssociatedText = localStorage.getItem("selectedAssociatedText");
                selectedRoadCode = localStorage.getItem("selectedRoadCode");
                selectedWaterCode = localStorage.getItem("selectedWaterCode");
                selectedSewerCode = localStorage.getItem("selectedSewerCode");
                selectedDrainCode = localStorage.getItem("selectedDrainCode");
                selectedBINText = localStorage.getItem("selectedBINText");
                selectedBINValue = localStorage.getItem("selectedBINValue");
                containmentTypeText = localStorage.getItem("containmentTypeText");
                containmentTypeValue = localStorage.getItem("containmentTypeValue");
                selectedUseCategory = localStorage.getItem("selectedUseCategory");
                if(selectedRoadCode)
                {
                    var roadCode = selectedRoadCode.split(" - ")[0];
                }
            }

            dynamicBuildingForm();
            handleMainBuildingChange();
            handleLowIncomeChange();
            handleLicStatusChange();
            maindrinkingWaterSource();
            wellpresenceStatus();
            handleToiletPresenceChange(); // Ensure the function runs on page load
            handleToiletConnectionChange();
            defecationStatus()
            hideoffice();
            munPublicDrinkingWater();
            handleContainmentTypeChange();
            showContainmentDimensionsOnReload();
          // searchable dropdown for building_associated_to
          optionHtmlBIN = selectedAssociatedValue
                ? `<option value=${selectedAssociatedValue} selected="${selectedAssociatedText}">${selectedAssociatedText}</option>`
                : `<option selected=""></option>`;
          $('#building_associated_to').prepend(optionHtmlBIN).select2({
            ajax: {
                url: "{{ route('building.get-house-numbers-all') }}",
                data: function (params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
            },
            placeholder: '{{__('BIN of Main Building')}}',
            allowClear: true,
            closeOnSelect: true,
            width: '85%',
            });

            optionHtmlRoadCode = selectedRoadCode
                ? `<option value=${roadCode} selected=${selectedRoadCode}>${selectedRoadCode}</option>`
                : `<option selected=""></option>`;
            $('#road_code').prepend(optionHtmlRoadCode).select2({
                ajax: {
                    url: "{{ route('roadlines.get-road-names') }}",
                    data: function (params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                },
                placeholder: '{{__('Road Code - Road Name')}}',
                allowClear: true,
                closeOnSelect: true,
                width: '85%',
                });

            optionHtmlWaterCode = selectedWaterCode
                ? `<option selected=${selectedWaterCode}>${selectedWaterCode}</option>`
                : `<option selected=""></option>`;
            $('#watersupply_pipe_code').prepend(optionHtmlWaterCode).select2({
                ajax: {
                    url: "{{ route('watersupply.get-watersupply-code') }}",
                    data: function (params) {
                        return {
                            search: params.term,
                            // ward: $('#ward').val(),
                            page: params.page || 1
                        };
                    },
                },
                placeholder: '{{__('Water Supply Pipe Line Code')}}',
                allowClear: true,
                closeOnSelect: true,
                width: '85%',
            });

            optionHtmlSewerCode = selectedSewerCode
                ? `<option selected=${selectedSewerCode}>${selectedSewerCode}</option>`
                : `<option selected=""></option>`;
            $('#sewer_code').prepend(optionHtmlSewerCode).select2({
                ajax: {
                    url: "{{ route('sewerlines.get-sewer-names') }}",
                    data: function (params) {
                        return {
                            search: params.term,
                            // ward: $('#ward').val(),
                            page: params.page || 1
                        };
                    },
                },
                placeholder: '{{__('Sewer Code')}}',
                allowClear: true,
                closeOnSelect: true,
                width: '85%',
            });

            optionHtmlDrainCode = selectedDrainCode
                ? `<option selected=${selectedDrainCode}>${selectedDrainCode}</option>`
                : `<option selected=""></option>`;

            $('#drain_code').prepend(optionHtmlDrainCode).select2({
                ajax: {
                    url: "{{ route('drains.get-drain-names') }}",
                    data: function (params) {
                        return {
                            search: params.term,
                            // ward: $('#ward').val(),
                            page: params.page || 1
                        };
                    },
                },
                placeholder: '{{__('Drain Code')}}',
                allowClear: true,
                closeOnSelect: true,
                width: '85%',
            });


            optionHtmlBIN = selectedBINValue
                ? `<option value=${selectedBINValue} selected=${selectedBINText}>${selectedBINText}</option>`
                : `<option selected=""></option>`;
            $('#build_contain').prepend(optionHtmlBIN).select2({
                ajax: {
                    url: "{{ route('building.get-house-numbers-containments') }}",
                    data: function (params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                },
                placeholder: '{{__('BIN of Pre-Connected Building')}}',
                allowClear: true,
                closeOnSelect: true,
                width: '85%',
            });
        });
        /*
          script to dynamically display child dropdown values according to value selected in parent dropdown
          */
        var usecatgs = {{ Illuminate\Support\Js::from(json_decode($usecatgsJson)) }};
        $(document).on('ready', function() {
            @if ($errors->any())
                @if (old('functional_use'))
                    $('#functional_use_id').change();

                    @if (old('use_category_id'))
                        $('#use_category_id').val('{{ old('use_category_id') }}');
                    @endif
                @endif
            @endif
        });

        $('#toilet-connection select').on('change', function() {
            var selectedText = $(this).find('option:selected').text();
            var sanitationId;
            if (selectedText === "Septic Tank") {
                sanitationId = 3;
            } else if (selectedText === "Pit/ Holding Tank") {
                sanitationId = 4;
            } else {
                $('#containment-type select').empty();
                return;
            }
            $.ajax({
                url: "{{ route('building.get-containment-septic') }}",
                method: "GET",
                data: {
                    sanitation_system_id: sanitationId
                },
                success: function(response) {
                    var containmentSelect = $('#containment-type select');
                    containmentSelect.empty();
                    containmentSelect.append('<option selected value=" ">{{__('Containment Type')}}</option>');
                    $.each(response, function(index, option) {
                        containmentSelect.append($('<option>').text(option.type).attr('value',
                            option.id));
                    });
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);

                }
            });
        });


        //To calculate volume of cylinder
        document.addEventListener('DOMContentLoaded', function() {
            var diameterField = document.querySelector('input[name="pit_diameter"]');
            var depthField = document.querySelector('input[name="pit_depth"]');
            var volumeField = document.querySelector('input[name="size"]');
            var calculateVolume = function() {
                var diameter = parseFloat(diameterField.value) || 0;
                var depth = parseFloat(depthField.value) || 0;

                // Calculate radius
                var radius = diameter / 2;
                // Calculate volume
                var volume = Math.PI * Math.pow(radius, 2) * depth;
                // Update the volume field with the calculated value
                volumeField.value = volume.toFixed(2); // Round to 2 decimal places
                if ( diameterField.value >= 1 || depthField.value >= 1 ) {
                    volumeField.readOnly = true; // Set as readonly to prevent editing
                }
                else
                {
                    volumeField.readOnly = false; // Set as not readonly
                }

            };
            // Add event listeners to trigger volume calculation on input change
            diameterField.addEventListener('input', calculateVolume);
            depthField.addEventListener('input', calculateVolume);
        });


    //To calculate volume of recatangle
    document.addEventListener('DOMContentLoaded', function() {
        var lengthField = document.querySelector('input[name="tank_length"]');
        var widthField = document.querySelector('input[name="tank_width"]');
        var depthField = document.querySelector('input[name="depth"]');
        var volumeField = document.querySelector('input[name="size"]');

        var calculateVolume = function() {
            var length = parseFloat(lengthField.value) || 0;
            var width = parseFloat(widthField.value) || 0;
            var depth = parseFloat(depthField.value) || 0;

            // Calculate volume
            var volume = length * width * depth;

            // Update the volume field with the calculated value
            volumeField.value = volume.toFixed(2); // Round to 2 decimal places

            // Make the 'size' field readonly after the value is calculated
            if (lengthField.value >= 1 || widthField.value >= 1 || depthField.value >= 1 ) {
                volumeField.readOnly = true; // Set as readonly to prevent editing
            }
            else
            {
                volumeField.readOnly = false; // Set as not readonly

            }
        };

            // Add event listeners to trigger volume calculation on input change
            lengthField.addEventListener('input', calculateVolume);
            widthField.addEventListener('input', calculateVolume);
            depthField.addEventListener('input', calculateVolume);
    });



        // document.addEventListener('DOMContentLoaded', function() {
        //     var populationFields = document.querySelectorAll(
        //         'input[name="male_population"], input[name="female_population"], input[name="other_population"]'
        //     );
        //     populationFields.forEach(function(field) {
        //         field.addEventListener('input', function() {
        //             var malePopulation = parseInt(document.querySelector(
        //                 'input[name="male_population"]').value) || 0;
        //             var femalePopulation = parseInt(document.querySelector(
        //                 'input[name="female_population"]').value) || 0;
        //             var otherPopulation = parseInt(document.querySelector(
        //                 'input[name="other_population"]').value) || 0;
        //             var totalPopulation = malePopulation + femalePopulation + otherPopulation;
        //             document.querySelector('input[name="population_served"]').value =
        //                 totalPopulation;
        //         });
        //     });
        // });

        // Using local storage to save input values of building_associated_to
        $('#building_associated_to').on('change', function() {
        var selectedAssociatedValue = $(this).find('option:selected').attr('value');
        var selectedAssociatedText = $(this).find('option:selected').text();
        localStorage.setItem("selectedAssociatedValue", selectedAssociatedValue);
        localStorage.setItem("selectedAssociatedText", selectedAssociatedText);
        });

        // Using local storage to save input values of road_code
        $('.road_code').on('change', function() {
        var selectedRoadCode = $(this).find('option:selected').text();
        localStorage.setItem("selectedRoadCode", selectedRoadCode);
        });

        // Using local storage to save input values of watersupply_pipe_code
        $('#watersupply_pipe_code').on('change', function() {
        var selectedWaterCode = $(this).find('option:selected').text();
        localStorage.setItem("selectedWaterCode", selectedWaterCode);
        });

        // Using local storage to save input values of sewer_code
        $('.sewer_code').on('change', function() {
        var selectedSewerCode = $(this).find('option:selected').text();
        localStorage.setItem("selectedSewerCode", selectedSewerCode);
        });

        // Using local storage to save input values of drain_code
        $('#drain_code').on('change', function() {
        var selectedDrainCode = $(this).find('option:selected').text();
        localStorage.setItem("selectedDrainCode", selectedDrainCode);
        });

        // Using local storage to save input values of build_contain
        $('#build_contain').on('change', function() {
        var selectedBINValue = $(this).find('option:selected').attr('value');
        var selectedBINText = $(this).find('option:selected').text();
        localStorage.setItem("selectedBINValue", selectedBINValue);
        localStorage.setItem("selectedBINText", selectedBINText);
        });

        // Using local storage to save input values of build_contain
        $('#type_id').on('change', function() {
        var containmentTypeValue = $(this).find('option:selected').attr('value');
        var containmentTypeText = $(this).find('option:selected').text();
        localStorage.setItem("containmentTypeValue", containmentTypeValue);
        localStorage.setItem("containmentTypeText", containmentTypeText);
        });

        // Using local storage to save input values of use_category_id
        $('#use_category_id').on('change', function() {
        var selectedUseCategory = $(this).find('option:selected').attr('value');
        localStorage.setItem("selectedUseCategory", selectedUseCategory);
        });

        // containment type on reload change
        var selectedText = $("#toilet-connection :selected").text();
        var sanitationId;
        containmentTypeText = localStorage.getItem("containmentTypeText");
        containmentTypeValue = localStorage.getItem("containmentTypeValue");

        optionHtmlContainmentType = containmentTypeText
            ? `<option value=${containmentTypeValue} selected=${containmentTypeText}>${containmentTypeText}</option>`
            : `<option selected="">Containment Type</option>`;

            if (selectedText === "Septic Tank") {
            sanitationId = 3;
        } else if (selectedText === "Pit/ Holding Tank") {
            sanitationId = 4;
        } else {
            $('#containment-type select').empty();
        }
        $.ajax({
            url: "{{ route('building.get-containment-septic') }}",
            method: "GET",
            data: {
                sanitation_system_id: sanitationId
            },
            success: function(response) {
                var containmentSelect = $('#containment-type select');
                containmentSelect.empty();
                containmentSelect.append(optionHtmlContainmentType);
                $.each(response, function(index, option) {
                    containmentSelect.append($('<option>').text(option.type).attr('value',
                        option.id));
                });
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);

            }
        });
    var usecatgs = {{ Illuminate\Support\Js::from(json_decode($usecatgsJson)) }};
    // use category handled on initial load
    var html = '<option value="">' + _('Use Category of Building') + '</option>';
        var functional_use = $('#functional_use_id').val();
        if (functional_use) {
            selectedUseCategory = localStorage.getItem("selectedUseCategory");
            $.each(usecatgs[functional_use], function(key, value) {
                if(key == selectedUseCategory)
            {
                html += '<option value="' + key + '" selected="selected">' + value + '</option>';
            }
            else
            {
                html += '<option value="' + key + '">' + value + '</option>';
            }
            });
            if (functional_use == 1) {
                $('#office-business').hide();
            } else {
                $('#office-business').show();
            }
        }
        $('#use_category_id').html(html);

</script>

@endpush
