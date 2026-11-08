@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')



    <!-- /.card-footer -->
    <div class="card card-info">
        <div class="card-header with-border">
            <h3 class="card-title">{{ __("Containment ID") }}: {{ $containment->id }}</h3>
        </div><!-- /.card-header -->
        <div class="" style="margin-top: 2px">
            {!! Form::model($containment, [
                'method' => 'PATCH',
                'action' => ['Fsm\ContainmentController@update', $containment->public_id],
                'files' => true,
                'class' => 'form-horizontal',
            ]) !!}
        </div><!-- /.card -->
        @include('layouts.components.error-list')
        @include('fsm.containments.partial-form', ['submitButtomText' => 'Update'])
        <div class="card-footer">

            <a href="{{ action('Fsm\ContainmentController@index') }}" class="btn btn-info">{{ __("Back to List") }}</a>
            {!! Form::submit(__('Save'), [
                'class' => 'btn btn-info prevent-multiple-submits',
                'id' => 'prevent-multiple-submits',
            ]) !!}





        </div>
        {!! Form::close() !!}
    </div><!-- /.card -->
@stop

@push('scripts')
    <script>
        onloadDynamicContainmentType();
        handleSizeReadOnlyChange();
        handleSizeReadOnlyOnLoad();
        showContainmentDimensionsOnReload();
        let selectedSewerCode = null;
        let selectedDrainCode = null;
        // Check if a div with the alert class exists, if yes then clear local storage
        if ($('.alert.alert-danger.alert-dismissible').length == 0) {
            localStorage.removeItem("selectedSewerCode");
            localStorage.removeItem("selectedDrainCode");
        }
        else{

            selectedSewerCode = localStorage.getItem("selectedSewerCode");
            selectedDrainCode = localStorage.getItem("selectedDrainCode");
        }
        $('#containment-type').on('change', function() {

            var selectedText = $("#containment-type option:selected").text();
            var showOptions = [
                "Septic Tank connected to Drain Network",
                "Lined Pit connected to Drain Network"
            ];
            if (showOptions.includes(selectedText)) {
                $('#drain-code').show();
            } else {
                $('#drain-code').hide();
            }
        });

        $('#containment-type').on('change', function() {

            var selectedText = $("#containment-type option:selected").text();
            var showOptions = [
                "Septic Tank connected to Sewer Network",
                "Lined Pit connected to Sewer Network"
            ];
            if (showOptions.includes(selectedText)) {
                $('#sewer-code').show();
            } else {
                $('#sewer-code').hide();
            }
        });

        optionHtmlSewerCode = selectedSewerCode
        ? `<option selected=${selectedSewerCode}>${selectedSewerCode}</option>`
        : '<option selected="{{ $containment_building->sewer_code }}">{{ $containment_building->sewer_code }}</option>';
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
            : '<option selected="{{ $containment_building->drain_code }}">{{ $containment_building->drain_code }}</option>';
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


        $('#containment-type, #pit-shape').on('change', function() {

            var selectedText = $("#containment-type option:selected").text();
            var showOptions = [
                "Double Pit",
                "Permeable/ Unlined Pit",
                "Lined Pit connected to a Soak Pit",
                "Lined Pit connected to Water Body",
                "Lined Pit connected to Open Ground",
                "Lined Pit connected to Sewer Network",
                "Lined Pit connected to Drain Network",
                "Lined Pit without Outlet",
                "Lined Pit with Unknown Outlet Connection",
                "Lined Pit with Impermeable Walls and Open Bottom"
            ];
            if (showOptions.includes(selectedText)) {
                $('#pit-shape').show();
                $('#tank-depth').hide();
                $('#tank-width').hide();
                $('#tank-length').hide();
                $('#septic-tank').hide();
            } else {
                $('#tank-length').show();
                $('#septic-tank').show();
                $('#pit-shape').hide();
            }

            // Check if the selected text is in the array of showOptions and if the pit shape is "Cylindrical"
            if (showOptions.includes(selectedText) && ($("#pit-shape :selected").text() ==
                    "Cylindrical")) {
                $('#pit-depth').show();
                $('#pit-size').show();
                $('#tank-depth').hide();
                $('#tank-width').hide();
                $('#tank-length').hide();
            } else {
                $('#pit-size').hide();
                $('#pit-depth').hide();
                $('#tank-depth').show();
                $('#tank-width').show();
                $('#tank-length').show();

            }


            if (!showOptions.includes(selectedText) || $("#pit-shape :selected").text() !== "Rectangular") {
                $('#tank-length').show();
            } else {
                $('#tank-length').hide();
            }

            if ($("#pit-shape :selected").text() == "Cylindrical") {
                $('#tank-length').hide();
            } else {
                $('#tank-length').show();
            }
            if (!showOptions.includes(selectedText)) {
                $('#tank-length').show();
            }

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


    </script>
@endpush
