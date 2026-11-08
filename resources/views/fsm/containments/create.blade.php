@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')

@include('layouts.components.error-list')
<!-- /.card-footer -->
<div class="card card-info">
	<!-- /.card-header -->

    {{-- @include('errors.list') --}}
    {!! Form::open([ 'action' => ['Fsm\ContainmentController@storeContainment', $id],'files' => true, 'class' => 'form-horizontal']) !!}
		@if(request()->has('application_id'))
			<input type="hidden" name="application_id" value="{{ request()->get('application_id') }}">
		@endif
		@include('fsm.containments.partial-form', ['submitButtomText' => 'Save'])
	<div class="card-footer">

    <a href="{{ action('BuildingInfo\BuildingController@index') }}" class="btn btn-info">{{ __('Back to List') }}</a>
    {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}





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
     $('#containment-type').on('change', function () {

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

    $('#containment-type').on('change', function () {

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


    $('#containment-type, #pit-shape').on('change', function () {

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
    }
    else {
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
function onloadDynamicContainmentType() {
    $('#containment-info').show();
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
    }
    else {
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
}


$('#sewer_code').prepend('<option selected=""></option>').select2({
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

$('#drain_code').prepend('<option selected=""></option>').select2({
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
</script>
@endpush
