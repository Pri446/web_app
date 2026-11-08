@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
    <div class="card card-info">
        <div class="card-header with-border">
            <h3 class="card-title">{{ __('House Number') }} : {{ $building->bin }}</h3>
        </div><!-- /.card-header -->
        @include('layouts.components.error-list')
        {!! Form::model($building, [
            'method' => 'PATCH',
            'action' => ['BuildingInfo\BuildingController@update', $building->public_id],
            'files' => true,

            'class' => 'form-horizontal',
        ]) !!}

        @include('building-info.buildings.partial-form', ['submitButtomText' => 'Update'])
        {!! Form::close() !!}
    </div><!-- /.card -->

    @if ($piiUnlocked)
        <form id="pii-lock-form" method="POST" action="{{ route('owner-pii.lock') }}" class="d-none">
            @csrf
        </form>
    @endif
@stop



@push('scripts')
    <script>
    $(document).ready(function() {
        @if ($piiUnlocked && $piiUnlockSeconds > 0)
            window.setTimeout(function() {
                var lockForm = document.getElementById('pii-lock-form');

                if (lockForm) {
                    lockForm.submit();
                }
            }, {{ $piiUnlockSeconds * 1000 }});
        @endif

        // Trigger event handlers when page loads
        handleMainBuildingChange();
        handleLowIncomeChange();
        handleLicStatusChange();
        maindrinkingWaterSource();
        wellpresenceStatus();
        handleToiletPresenceChange();
        handleToiletConnectionChange();
        defecationStatus();
        hideoffice();
        munPublicDrinkingWater();
        onLoadPopReadOnly();
        setTimeout(showHideCode, 0);
        showContainmentDimensionsOnReload();
        handleCTPTUseCat();
        $(function() {
            var dataTable = $('#data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{!! url("fsm/containments/building/$building->public_id/containmentData") !!}',
                    data: function(d) {

                    }
                },
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'size',
                        name: 'size'
                    },
                    {
                        data: 'location',
                        name: 'location'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }



                ]
            }).on('draw', function() {
                $('.delete').on('click', function(e) {

                    var form = $(this).closest("form");
                    event.preventDefault();
                    Swal.fire({
                        title: '{{ __('Are you sure?') }}',
                        text: "{!! __('You won\'t be able to revert this!') !!}",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '{{ __('Yes, delete it!') }}',
                        cancelButtonText: '{{ __('Cancel') }}',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    })
                });
            });
        });
        // Check if a div with the alert class exists, if yes then clear local storage
        let selectedAssociatedValue = null;
        let selectedAssociatedText = null;
        let selectedRoadCode = null;
        let selectedWaterCode = null;
        let selectedSewerCode = null;
        let selectedDrainCode = null;
        let selectedBINText = null;
        let selectedBINValue = null;
        if ($('.alert.alert-danger.alert-dismissible').length == 0) {
                localStorage.removeItem("selectedAssociatedValue");
                localStorage.removeItem("selectedAssociatedText");
                localStorage.removeItem("selectedRoadCode");
                localStorage.removeItem("selectedWaterCode");
                localStorage.removeItem("selectedSewerCode");
                localStorage.removeItem("selectedDrainCode");
                localStorage.removeItem("selectedBINText");
                localStorage.removeItem("selectedBINValue");
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
            if(selectedRoadCode)
            {
                var roadCode = selectedRoadCode.split(" - ")[0];
            }
        }
        //script to  make dropdowns searchable

         optionHtmlBIN = selectedAssociatedValue
                ? `<option value=${selectedAssociatedValue} selected="${selectedAssociatedText}">${selectedAssociatedText}</option>`
                : '<option selected={{ $building->building_associated_to }}">{{ $building->building_associated_to }}</option>'
        $('#building_associated_to').prepend(
                ).select2({
            ajax: {
                url: "{{ route('building.get-house-numbers-all') }}",
                data: function(params) {
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
        : '<option selected="{{ $building->road_code }}">{{ $building->road_code }}</option>';
        $('#road_code').prepend(optionHtmlRoadCode).select2({
            ajax: {
                url: "{{ route('roadlines.get-road-names') }}",
                data: function(params) {
                    return {
                        search: params.term,
                        // ward: $('#ward').val(),
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
            :         '<option selected=""{{ $building->watersupply_pipe_code }}"">{{ $building->watersupply_pipe_code }}</option>';
        $('#watersupply_pipe_code').prepend(optionHtmlWaterCode).select2({
            ajax: {
                url: "{{ route('watersupply.get-watersupply-code') }}",
                data: function(params) {
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
        : '<option selected="{{ $building->sewer_code }}">{{ $building->sewer_code }}</option>';
        $('#sewer_code').prepend(optionHtmlSewerCode).select2({
                ajax: {
                    url: "{{ route('sewerlines.get-sewer-names') }}",
                    data: function(params) {
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
            : '<option selected="{{ $building->drain_code }}">{{ $building->drain_code }}</option>';
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
        : '<option selected="{{ $building->build_contain }}">{{ $building->build_contain }}</option>';
        var excluded_bin = "{{ $building->bin }}";
        $('#build_contain').prepend(optionHtmlBIN).select2({
        ajax: {
            url: "{{ route('building.get-house-numbers-containments') }}",
            data: function(params) {
                return {
                    search: params.term,
                    exclude_bin: excluded_bin,
                    page: params.page || 1
                };
            },
        },
        placeholder: '{{__('BIN of Pre-Connected Building')}}',
        allowClear: true,
        closeOnSelect: true,
        width: '85%',
        });


        //show use category only when functional use is filled

        function handleMainBuildingChange() {
            if ($("#main_building :selected").text() == "Yes") {
                $('#building_associated').hide();
            } else if ($("#main_building :selected").text() == "No") {
                $('#building_associated').show();
            }
        }


        // Logic for lic_status field
        function handleLowIncomeChange() {
            if ($("#lic_status select").val() == "1") {
                $('#lic_status').show();

            } else {

                $('#lic_id').hide();
            }
        }

        function handleLicStatusChange() {
            // Check the initial value of lic_status on page load
            if ($("#lic_status :selected").val() == "1") {
                $('#lic_id').show();
            } else {
                $('#lic_id').hide();
            }
            // Add the change event listener
            $('#lic_status').on('change', function() {
                if ($("#lic_status :selected").val() == "1") {
                    $('#lic_id').show();
                } else {
                    $('#lic_id').hide();
                }
            });
        }



        function maindrinkingWaterSource() {
            if ($("#water-id :selected").val() == "1") {
                $('#water-customer-id').show();
                $('#water-pipe-id').show();
            } else {
                $('#water-customer-id').hide();
                $('#water-pipe-id').hide();
            }
        }

        function wellpresenceStatus() {
            if ($("#well-presence select").val() == "1") {

                $('#distance-from-well').show();
            } else {
                $('#distance-from-well').hide();
            }
        }

        function handleToiletPresenceChange() {
            if ($("#toilet-presence :selected").text() == "Yes") {
                $('#toilet-info').show();
                if ($("#use_category_id :selected").text() == "Community Toilet" ||  $("#use_category_id :selected").text() == "Public Toilet") {
                $('#shared-toilet-popn').hide();
                $('#shared-toilet').hide();
                }
                else{
                    $('#shared-toilet-popn').show();
                    $('#shared-toilet').show();
                }
                $('#toilet-connection').show();
                $('#defecation-place').hide();
                $('#ctpt-toilet').hide();
            } else {
                $('#vacutug-accessible').hide();
                $('#defecation-place').show();
                $('#toilet-info').hide();
                $('#shared-toilet').hide();
                $('#toilet-connection').hide();
                $('#shared-toilet-popn').hide();
                $('#containment-info').hide();
                $('#containment-id').hide();
                $('#drain-code').hide();
                $('#sewer-code').hide();
            }
        }

        function handleToiletConnectionChange() {

            if ($("#toilet-connection :selected").text() === "Septic Tank" || $("#toilet-connection :selected").text() ===
                "Pit/ Holding Tank") {
                $('#containment-info').show();
                $('#containment-id').hide();
                $('#drain-code').hide();
                $('#sewer-code').hide();
                $('#vacutug-accessible').show();
            } else if ($("#toilet-connection :selected").text() === "Shared Containment") {
                $('#containment-id').show();
                $('#containment-info').hide();
                $('#drain-code').hide();
                $('#sewer-code').hide();
                $('#vacutug-accessible').hide();
            } else if ($("#toilet-connection :selected").text() === "Drain Network") {
                $('#drain-code').show();
                $('#containment-id').hide();
                $('#containment-info').hide();
                $('#sewer-code').hide();
                $('#vacutug-accessible').hide();
            } else if ($("#toilet-connection :selected").text() === "Sewer Network") {
                $('#drain-code').hide();
                $('#containment-id').hide();
                $('#containment-info').hide();
                $('#sewer-code').show();
                $('#vacutug-accessible').hide();
            } else {
                $('#containment-id').hide();
                $('#containment-info').hide();
                $('#drain-code').hide();
                $('#sewer-code').hide();
                $('#vacutug-accessible').hide();
            }
        }

        function defecationStatus() {
            if ($("#defecation-place select").val() == 9) {
                $('#ctpt-toilet').show();
            } else {
                $('#ctpt-toilet').hide();
            }
        }

        // Bind change event to trigger event handlers
        $('#main_building').on('change', handleMainBuildingChange);
        $('#low_income_hh select').on('change', handleLowIncomeChange);
        $('#lic_status select').on('change', handleLicStatusChange);
        $('#well-presence select').on('change', wellpresenceStatus);
        $('#water-id select').on('change', maindrinkingWaterSource);
        $('#toilet-presence').on('change', handleToiletPresenceChange); // Bind the change event to the function
        $('#toilet-connection').on('change', handleToiletConnectionChange); // Bind the change event to the function
        $('#defecation-place').on('change', defecationStatus); // Bind the change event to the function
        $('#functional-use').on('change', hideoffice);

        /*
        script to dynamically display child dropdown values according to value selected in parent dropdown
        */
        var usecatgs = JSON.parse('{!! $usecatgsJson !!}');
        $(document).ready(function() {

            $('#functional_use_id').change(function() {
                var html = '<option value="">Use Category of Building</option>';

                var functional_use = $(this).val();
                if (functional_use) {
                    $.each(usecatgs[functional_use], function(key, value) {
                        html += '<option value="' + key + '">' + value + '</option>';
                    });
                    if (functional_use == 1) {
                        $('#office-business').hide();
                    } else {
                        $('#office-business').show();
                    }
                }

                $('#use_category_id').html(html);
            });
        });


        // for dynamic dropdown for containment type acc to toilet connection

        $('#toilet-connection select').on('change', function() {
            var selectedText = $(this).find('option:selected').text();

            if (selectedText == "Septic Tank") {
                $.ajax({
                    url: "{{ route('building.get-containment-septic') }}",
                    method: "GET",
                    data: {
                        sanitation_system_id: 3
                    },
                    success: function(response) {
                        var containmentSelect = $('#containment-type select');
                        containmentSelect.empty();
                        containmentSelect.prepend('<option selected="">Containment Type</option>')
                        $.each(response, function(index, option) {
                            containmentSelect.append($('<option>').text(option.type).attr(
                                'value', option.id));
                        });
                    },

                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            } else if (selectedText == "Pit/ Holding Tank") {
                $.ajax({
                    url: "{{ route('building.get-containment-septic') }}",
                    method: "GET",
                    data: {
                        sanitation_system_id: 4
                    },
                    success: function(response) {
                        var containmentSelect = $('#containment-type select');
                        containmentSelect.empty();
                        containmentSelect.prepend('<option selected="">Containment Type</option>')
                        $.each(response, function(index, option) {
                            containmentSelect.append($('<option>').text(option.type).attr(
                                'value', option.id));
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            } else {
                $('#containment-type select').empty();
            }
        });



        $('#functional_use_id').on('change', function() {
            if ($("#functional_use_id :selected").text() == "Community Toilet" || $("#functional_use_id :selected")
                .text() == "Public Toilet") {
                $('#population-info').hide();
                $('#family-count').hide();
                $('#male-population').hide();
                $('#female-population').hide();
                $('#other-population').hide();
                $('#male-diff-population').hide();
                $('#female-diff-population').hide();
                $('#other-diff-population').hide();
            } else {
                $('#male-population').show();
                $('#female-population').show();
                $('#other-population').show();
                $('#population-info').show();
                $('#family-count').show();
                $('#male-diff-population').show();
                $('#female-diff-population').show();
                $('#other-diff-population').show();
            }
        });
        // To set toilet status == 1 and show related fields(also applied in backend part (Building Structure Service))
        $('#functional_use_id').on('change', function() {
            var selectedText = $("#functional_use_id option:selected").text();
            if (selectedText == "Community/Public Toilet") {
                $('#toilet_status').val('1'); // Set toilet_status to 'Yes'
                $('#toilet-info').show();
                $('#shared-toilet').show();
                $('#toilet-connection').show();
                $('#shared-toilet-popn').show();
                $('#defecation-place').hide();
                $('#ctpt-toilet').hide();
            }
        });

        $('#water-id').on('change', function () {
        if ($("#water-id :selected").text() == "Municipal/Public Water Supply") {
            $('#water-customer-id').show();
            $('#water-pipe-id').show();
        } else {
            $('#water-customer-id').hide();
            $('#water-pipe-id').hide();
        }
        });
        //show use category only when functional use is filled

        var functionalUseId = $('#functional_use_id').val();
        if (functionalUseId) {
            $('#use-category').show();
        } else {
            $('#use-category').hide();
        }

        $('#functional_use_id').on('change', function() {
            var functionalUseId = $(this).val();
            if (functionalUseId) {
                $('#use-category').show();
            } else {
                $('#use-category').hide();
            }
        });



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
        // show drain or sewer code if containment type has drain or sewer code
        function showHideCode()
        {
            if({{$sewer_status}} == true)
            {
                $('#sewer_code').show();
            }
            if({{$drain_status}} == true)
            {
                $('#drain_code').show();
            }
        }



    });
    var usecatgs = JSON.parse('{!! $usecatgsJson !!}');
    // use category handled on initial load
    var html = '<option value="">Use Categories of Building</option>';
        var functional_use = $('#functional_use_id').val();
        if (functional_use) {
            $.each(usecatgs[functional_use], function(key, value) {
                if(key == {{$building->use_category_id}})
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
