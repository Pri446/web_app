@extends('layouts.dashboard')
@push('style')
    <style type="text/css">
        .dataTables_filter {
            display: none;
        }
    </style>
@endpush
@section('title', $page_title)
@section('content')
    @include('layouts.components.error-list')
    @include('layouts.components.success-alert')
    @include('layouts.components.error-alert')
    <div class="card">

        <div class="card-body">
            {!! Form::open([
                'method' => 'POST',
                'action' => ['Fsm\CwisSettingController@store'],
                'class' => 'form-horizontal',
                'id' => 'editForm',
            ]) !!}
            <div class="form-group row">
                <div class="col-sm-3" style="color:grey">
                    <small><i class="fa-regular fa-clock"></i> {{__('Last Updated')}}: {{ $updated ?? '' }}</small>
                </div>
            </div>
            
            <div class="form-group row">
                {!! Form::label('active_country', __('Country'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::select('active_country', array_map('__', $countries), old('active_country', $data['active_country'] ?? 'ALL'), ['class' => 'form-control', 'required' => true]) !!}
                </div>
            </div>
            @foreach ($parameters as $key => $parameter)
                <div class="form-group row">
                    {!! Form::label(
                        $key,
                        __($parameter['label']),
                        ['class' => 'col-sm-3 control-label' ],
                    ) !!}
                    <div class="col-sm-3">
                        {!! Form::number($key, old($key, $data[$key] ?? null), [
                            'class' => 'form-control',
                            'required' => true,
                            'min' => $parameter['min'],
                            'max' => $parameter['max'],
                            'step' => 'any',
                        ]) !!}
                    </div>
                </div>
            @endforeach
            @foreach($decisions as $key => $decision)
                <div class="form-group row">
                    <label for="{{ $key }}" class="col-sm-3 control-label">{{ __($decision['label']) }}</label>
                    <div class="col-sm-3"><select id="{{ $key }}" name="{{ $key }}" class="form-control">
                        @foreach($decision['options'] as $value => $label)<option value="{{ $value }}" @if(old($key,$data[$key] ?? ($decision['default'] ?? 'pending')) === $value) selected @endif>{{ __($label) }}</option>@endforeach
                    </select></div>
                </div>
            @endforeach
            <h6 class="mt-4 mb-3">{{ __('Legacy Parameters (Not Used by Revised Formulas)') }}</h6>
            @foreach ([
                'fs_generation_from_containment_not_connected_to_sewer_lpcd' => 'FS Generation From Containment Not Connected to Sewer (LPCD)',
                'fs_generation_from_permeable_or_unlined_pit_lpcd' => 'FS Generation From Permeable/Unlined Pit (LPCD)',
                'greywater_conversion_factor_connected_to_sewer' => 'Greywater Conversion Factor Connected to Sewer (%)',
            ] as $key => $label)
                <div class="form-group row">
                    <label for="legacy-{{ $key }}" class="col-sm-3 control-label">{{ __($label) }}</label>
                    <div class="col-sm-3">
                        <input id="legacy-{{ $key }}" class="form-control" value="{{ $data[$key] ?? '' }}" readonly data-legacy-setting>
                    </div>
                </div>
            @endforeach
        </div><!-- /.box-body -->
        <div class="card-footer">
             @can('Edit CWIS')
            <span id="editButton" class="btn btn-info">{{__('Edit')}}</span>
            <button type="submit" id="saveButton" class="btn btn-info" style="display: none;">{{__('Save')}}</button>
            @endcan
        </div><!-- /.box-footer -->
    {!! Form::close() !!}
    </div>
@stop
@push('scripts')
<script>
    $(document).ready(function() {
        // Function to toggle readonly attribute
        function toggleReadOnly(readonly) {
            $('#editForm input:not([type=hidden]):not([data-legacy-setting])').prop('readonly', readonly);
            $('#editForm select').prop('disabled', readonly);
        }

        // Initially set form fields as read-only
        toggleReadOnly(true);

        // Edit button click event
        $('#editButton').click(function() {
            toggleReadOnly(false);
            $('#editButton').hide();
            $('#saveButton').show();

            // Hide "Last Updated" element
            $('.col-sm-3 small').hide();
        });

        // Check for errors and update buttons accordingly
        var hasErrors = $('.alert-danger').length > 0;

        if (hasErrors) {
            toggleReadOnly(false);
            $('#editButton').hide();
            $('#saveButton').show();
        } else {
            $('#saveButton').hide();
            $('#editButton').show();
        }
    });
</script>

@endpush
