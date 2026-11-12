@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('layouts.components.error-list')
<style>.sustainability-form .btn-info { color: #fff !important; }</style>
<div class="card card-info">
<form class="sustainability-form form-horizontal" method="POST" novalidate enctype="multipart/form-data" action="{{ $approvalReview ? route('cwis.sustainability.approve', $submission->id) : ($submission ? route('cwis.sustainability.update', $submission->id) : route('cwis.sustainability.store')) }}">
    @csrf
    @if($submission && !$approvalReview) @method('PUT') @endif
    @if($approvalReview)<input type="hidden" name="save_mode" value="submitted">@endif
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="country_code" value="{{ $country }}">
    <div class="card-header bg-white">
        <div class="form-inline">
        <a class="btn btn-info" href="{{ route('cwis.generator-data.index', ['country_code' => $country]) }}">{{ __('Back to List') }}</a>
        <div class="form-group ml-auto">
            <span class="mr-3">{{ __('Country') }}: <strong>{{ $countryLabel }}</strong></span>
            <span class="mr-3">{{ __('Reporting Year') }}: <strong>{{ $year }}</strong></span>
            <span>{{ __('Service Outcome') }}: <strong>{{ __('Sustainability') }}</strong></span>
            @if($submission)<span class="ml-3">{{ __('Status') }}: <strong>{{ __(ucfirst($submission->status)) }}</strong></span>@endif
        </div>
        @if($readOnly && in_array($submission->status, ['draft', 'submitted'], true))
            @can('Approve CWIS')
                <a class="btn btn-info ml-3" href="{{ route('cwis.sustainability.review', $submission->id) }}"><i class="fa fa-check" aria-hidden="true"></i> {{ __('Approve') }}</a>
            @endcan
        @endif
        </div>
    </div>
    <fieldset @if($readOnly) disabled @endif>
    <div class="m-3">
    @if(count($sections))
        <h4>{{ __('Quantitative') }}</h4>
        <div class="form-group row">
            <label class="col-sm-4 col-form-label" for="sustainability-currency">{{ __('Currency Code') }}</label>
            <div class="col-sm-4">
                <input id="sustainability-currency" class="form-control @error('currency_code') is-invalid @enderror" name="currency_code" maxlength="3" value="{{ old('currency_code', $submission->currency_code ?? '') }}">
                @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    @endif
    @foreach($sections as $code => $section)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><span class="btn btn-link">{{ __($section['title']) }} ({{ $code }})</span></h5>
            </div>
            <div class="card-body">
            <div class="table-responsive">
            <table class="table table-bordered mb-0" width="100%">
                <thead><tr><th width="55%">{{ __('Indicators') }}</th><th width="12%">{{ __('Outcome') }}</th><th width="33%">{{ __('Value') }}</th></tr></thead>
                <tbody>
                @foreach($section['fields'] as $name => $label)
                    <tr><td><label for="ss-{{ $name }}" class="font-weight-normal mb-1">{{ __($label) }}</label><small class="d-block text-muted">{{ $code }}</small></td><td>{{ __('Sustainability') }}</td><td>
                        <input id="ss-{{ $name }}" class="form-control @error('inputs.'.$name) is-invalid @enderror" type="number" min="0" step="0.01" name="inputs[{{ $name }}]" placeholder="{{ __('Enter value') }}" value="{{ old('inputs.'.$name, $financials->$name ?? '') }}">
                        @error('inputs.'.$name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </td></tr>
                @endforeach
                </tbody>
            </table>
            </div>
            </div>
        </div>
    @endforeach
    @if(count($campaigns))
        <h4>{{ __('Qualitative') }}</h4>
        <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><span class="btn btn-link">{{ __('Sanitation and Hygiene Campaigns') }} (SS-4.1 - SS-4.1d)</span></h5>
        </div>
        <div class="card-body">
        <div class="table-responsive">
        <table class="table table-bordered mb-0" width="100%">
            <thead><tr><th width="55%">{{ __('Indicators') }}</th><th width="12%">{{ __('Outcome') }}</th><th width="33%">{{ __('Value') }}</th></tr></thead>
            <tbody>
            @foreach($campaigns as $code => $label)
                @php($key = str_replace('.', '_', $code))
                @php($saved = $savedCampaigns->get($code))
                @php($answer = old('campaigns.'.$key, $saved->answer ?? ''))
                <tr><td><label for="campaign-{{ $key }}" class="font-weight-normal mb-1">{{ __($label) }}</label><small class="d-block text-muted">{{ $code }}</small></td><td>{{ __('Sustainability') }}</td><td>
                    <select id="campaign-{{ $key }}" class="form-control campaign-answer @error('campaigns.'.$key) is-invalid @enderror" name="campaigns[{{ $key }}]" data-evidence="evidence-{{ $key }}">
                        @foreach(['' => __('Unknown'), 'Yes' => __('Yes'), 'No' => __('No')] as $value => $text)
                            <option value="{{ $value }}" {{ $answer === $value ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('campaigns.'.$key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div id="evidence-{{ $key }}" class="mt-2" @if($answer !== 'Yes') hidden @endif>
                        @include('cwis.generator-data.saved-evidence', ['documentRoute' => 'cwis.sustainability.document'])
                        <label for="file-{{ $key }}">{{ $saved && $saved->document_path ? __('Replace Evidence (Optional)') : __('Evidence') }}</label>
                        <input id="file-{{ $key }}" type="file" class="form-control-file @error('evidence.'.$key) is-invalid @enderror" name="evidence[{{ $key }}]" accept="{{ \App\Support\CwisEvidence::UPLOAD_ACCEPT }}" @if($answer !== 'Yes') disabled @endif>
                        @error('evidence.'.$key)<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                </td></tr>
            @endforeach
            </tbody>
        </table>
        </div>
        </div>
        </div>
    @endif
    </div>
    </fieldset>
    @if(!$readOnly)
    <div class="card-footer">
        @if($approvalReview)
            <button type="submit" class="btn btn-info">{{ __('Save and Approve') }}</button>
        @else
            <button type="submit" name="save_mode" value="draft" class="btn btn-info">{{ __('Save as Draft') }}</button>
            <button type="submit" name="save_mode" value="submitted" class="btn btn-info">{{ __('Submit for Approval') }}</button>
        @endif
    </div>
    @endif
</form>
</div>
@stop
@include('cwis.generator-data.retain-uploads')
@push('scripts')
<script>
document.querySelectorAll('.campaign-answer').forEach(function (select) {
    select.addEventListener('change', function () {
        var evidence = document.getElementById(select.dataset.evidence);
        evidence.hidden = select.value !== 'Yes';
        var input = evidence.querySelector('input');
        input.disabled = evidence.hidden;
        if (evidence.hidden) input.value = '';
    });
});
</script>
@endpush
