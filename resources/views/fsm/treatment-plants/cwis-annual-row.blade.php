<div class="cwis-annual-row">
    @if(!empty($annual['id']))
        <input type="hidden" name="cwis_annual[{{ $index }}][id]" value="{{ $annual['id'] }}">
    @endif
    <div class="form-group row">
        <label for="cwis-year-{{ $index }}" class="col-sm-3 control-label">{{ __('Data Year') }}</label>
        <div class="col-sm-3">
        <input id="cwis-year-{{ $index }}" name="cwis_annual[{{ $index }}][data_year]" type="number" min="1900" max="{{ now()->year }}" value="{{ $annual['data_year'] ?? now()->year - 1 }}" aria-label="{{ __('Data Year') }}" class="form-control @error('cwis_annual.'.$index.'.data_year') is-invalid @enderror">
        @error('cwis_annual.'.$index.'.data_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-sm-auto mt-2 mt-sm-0">
            @if($addYearControl ?? false)
                <button type="button" id="cwis-add-year" class="btn btn-info" title="{{ __('Add Data Year') }}" aria-label="{{ __('Add Data Year') }}"><i class="fa fa-plus" aria-hidden="true"></i></button>
            @endif
            @if($removable ?? false)
                <button type="button" class="btn btn-link text-danger cwis-remove-year" title="{{ __('Remove Data Year') }}" aria-label="{{ __('Remove Data Year') }}"><i class="fa fa-trash" aria-hidden="true"></i></button>
            @endif
        </div>
    </div>
    @foreach(\App\Models\Fsm\TreatmentPlantAnnualSustainability::VOLUMES as $column => $label)
        <div class="form-group row">
            <label for="cwis-{{ $index }}-{{ $column }}" class="col-sm-3 control-label">{{ __($label) }}</label>
            <div class="col-sm-3">
                <input id="cwis-{{ $index }}-{{ $column }}" type="number" min="0" step="0.001" name="cwis_annual[{{ $index }}][{{ $column }}]" value="{{ $annual[$column] ?? '' }}" placeholder="{{ __('Enter value') }}" aria-label="{{ __($label) }}" class="form-control @error('cwis_annual.'.$index.'.'.$column) is-invalid @enderror">
                @error('cwis_annual.'.$index.'.'.$column)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    @endforeach
</div>
