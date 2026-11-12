@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('layouts.components.error-list')
@php($isEditing = isset($submission))
@php($isApprovalReview = $approvalReview ?? false)
<style>#indicator-back, #indicator-back:hover, #indicator-back:focus { color: #fff !important; }</style>
<div class="card card-info">
    <form id="indicator-data-form" novalidate class="form-horizontal" method="POST" action="{{ $isApprovalReview ? route('cwis.generator-data.approve', ['submission' => $submission->id]) : ($isEditing ? route('cwis.generator-data.update', ['submission' => $submission->id]) : route('cwis.generator-data.store')) }}" enctype="multipart/form-data">
        @csrf
        @if($isEditing && !$isApprovalReview) @method('PUT') @endif
        @if($isApprovalReview)
            <input type="hidden" name="review_form" value="1">
            <input type="hidden" name="save_mode" value="submitted">
        @endif
        <div class="card-header bg-white">
            <div class="form-inline">
                <a href="{{ action('Cwis\CwisGeneratorDataController@index', ['country_code' => $country]) }}" id="indicator-back" class="btn btn-info">{{ __('Back to List') }}</a>
                <div class="form-group ml-auto">
                    <span class="mr-3">{{ __('Country') }}: <strong>{{ $countryLabel }}</strong></span>
                    <input type="hidden" name="country_code" value="{{ $country }}">
                    <span class="mr-3">{{ __('Reporting Year') }}: <strong>{{ $year }}</strong></span>
                    <span>{{ __('Service Outcome') }}: <strong>{{ __('Equity') }}</strong></span>
                    <input type="hidden" id="reporting-year" name="year" value="{{ $year }}">
                </div>
            </div>
        </div>
        <div class="m-3">
            <p id="indicator-save-preview" class="alert alert-info" role="status" hidden></p>
            @foreach($sections as $section)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><span class="btn btn-link">{{ $section['title'] }}</span></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" width="100%">
                                <thead><tr><th width="55%">{{ __('Indicators') }}</th><th width="12%">{{ __('Outcome') }}</th><th width="33%">{{ __('Value') }}</th></tr></thead>
                                <tbody>
                                    @foreach($section['fields'] as [$id, $name, $code, $label, $hint, $step, $maximum, $required, $comparisons])
                                        @php($inputError = $errors->first('inputs.' . $name) ?: $errors->first($name))
                                        <tr>
                                            <td>
                                                <label for="{{ $id }}" class="font-weight-normal mb-1">{{ __($label) }} @if($required)<span class="text-danger">*</span>@endif</label>
                                                <small class="d-block text-muted">{{ $code }} · {{ __($hint) }}</small>
                                            </td>
                                            <td>{{ __('Equity') }}</td>
                                            <td>
                                                <input type="number" id="{{ $id }}" name="inputs[{{ $name }}]" value="{{ old('inputs.' . $name, $savedInputs[$name] ?? null) }}" class="form-control {{ $inputError ? 'is-invalid' : '' }}" min="0" max="{{ $maximum }}" step="{{ $step }}" data-input-name="{{ $name }}" data-comparisons="{{ json_encode($comparisons) }}" placeholder="{{ __('Enter value') }}" @if($required) required @endif>
                                                @if($inputError)<div class="invalid-feedback">{{ $inputError }}</div>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
            @if(count($policies))
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><span class="btn btn-link">{{ __('Sanitation Worker Policies and Coverage (EQ-6.1 - EQ-6.5)') }}</span></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" width="100%">
                            <thead><tr><th width="55%">{{ __('Indicators') }}</th><th width="12%">{{ __('Outcome') }}</th><th width="33%">{{ __('Value') }}</th></tr></thead>
                            <tbody>
                                @foreach($policies as $index => [$code, $label, $parent, $evidence, $policyKey, $required])
                                    @php($policyError = $errors->first('policies.' . $policyKey) ?: $errors->first($policyKey))
                                    <tr data-policy-row data-parent="{{ $parent }}">
                                        <td>
                                            <label for="policy-{{ $index }}" class="font-weight-normal mb-1">{{ __($label) }}</label>
                                            <small class="d-block text-muted">{{ $code }}</small>
                                        </td>
                                        <td>{{ __('Equity') }}</td>
                                        <td>
                                            <select id="policy-{{ $index }}" name="policies[{{ $policyKey }}]" class="form-control {{ $policyError ? 'is-invalid' : '' }}" data-policy-code="{{ $code }}" aria-describedby="policy-hint-{{ $index }}" @if($required) required @endif>
                                                <option value="">{{ __('Unknown') }}</option>
                                                <option value="Yes" {{ old('policies.' . $policyKey, $savedPolicies[$policyKey] ?? '') === 'Yes' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                                <option value="No" {{ old('policies.' . $policyKey, $savedPolicies[$policyKey] ?? '') === 'No' ? 'selected' : '' }}>{{ __('No') }}</option>
                                            </select>
                                            @if($policyError)<div class="invalid-feedback">{{ $policyError }}</div>@endif
                                            <small id="policy-hint-{{ $index }}" class="d-block text-muted mt-1" data-policy-hint></small>
                                            <div data-evidence-fields hidden>
                                                @if(!empty($savedEvidence[$policyKey]))
                                                    @include('cwis.generator-data.equity-evidence', ['documentId' => $savedEvidence[$policyKey]->id, 'documentName' => $savedEvidence[$policyKey]->original_name])
                                                @endif
                                                <label for="evidence-{{ $index }}" class="font-weight-normal mt-2 mb-1">{{ __('Evidence') }}</label>
                                                <input type="file" id="evidence-{{ $index }}" name="evidence[{{ $policyKey }}]" class="form-control-file" accept="{{ \App\Support\CwisEvidence::UPLOAD_ACCEPT }}" aria-describedby="evidence-hint-{{ $index }}" disabled>
                                                <small id="evidence-hint-{{ $index }}" class="d-block text-muted mt-1">{{ __($evidence) }}. {{ __('The document must support the selected answer.') }}</small>
                                                @error('evidence.' . $policyKey)<div class="text-danger">{{ $message }}</div>@enderror
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <div class="card-footer">
            @if($isApprovalReview)
                <button type="submit" class="btn btn-info">{{ __('Save and Approve') }}</button>
            @else
            <button type="submit" name="save_mode" value="draft" formnovalidate class="btn btn-info mr-1">{{ __('Save Draft') }}</button>
            <button type="submit" name="save_mode" value="submitted" class="btn btn-info">{{ __('Submit for Approval') }}</button>
            @endif
        </div>
    </form>
</div>
@stop
@include('cwis.generator-data.retain-uploads')
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('indicator-data-form');
        function syncPolicies() {
            var selects = Array.from(form.querySelectorAll('[data-policy-code]'));
            form.querySelectorAll('[data-policy-row]').forEach(function (row) {
                var parent = row.dataset.parent;
                var select = row.querySelector('select');
                var evidence = row.querySelector('[data-evidence-fields]');
                var file = row.querySelector('input[type=file]');
                var disabled = false;
                if (parent) {
                    var parentSelect = selects.find(function (candidate) { return candidate.dataset.policyCode === parent; });
                    disabled = !parentSelect || parentSelect.value !== 'Yes';
                    select.disabled = disabled;
                    if (disabled) select.value = '';
                    row.querySelector('[data-policy-hint]').textContent = parentSelect && parentSelect.value === 'No'
                        ? @json(__('Not applicable'))
                        : disabled ? @json(__('Select Yes for the parent indicator first:')) + ' ' + parent
                        : @json(__('Applies when')) + ' ' + parent + ' = Yes';
                }
                var showEvidence = !disabled && select.value === 'Yes';
                evidence.hidden = !showEvidence;
                file.disabled = !showEvidence;
                if (!showEvidence) file.value = '';
            });
        }
        form.querySelectorAll('[data-policy-code]').forEach(function (element) { element.addEventListener('change', syncPolicies); });
        syncPolicies();
    });
</script>
@endpush
