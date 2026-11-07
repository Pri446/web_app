@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('layouts.components.error-list')
<style>
.safety-page { position: relative; }
.safety-page .btn-info { color: #fff !important; }
.safety-page table { min-width: 650px; }
#cwis-rebuild-loader { position: absolute; inset: 0; z-index: 20; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.95); text-align: center; padding: 24px; }
#cwis-rebuild-loader[hidden] { display: none; }
</style>
<div class="safety-page card card-info">
    <div class="card-header bg-white d-flex flex-wrap align-items-center">
        <a class="btn btn-info mr-3" href="{{ route('cwis.generator-data.index', ['country_code' => $country]) }}">{{ __('Back to List') }}</a>
        <span class="mr-3">{{ __('Country') }}: <strong>{{ $countryLabel }}</strong></span>
        <span class="mr-3">{{ __('Reporting Year') }}: <strong>{{ $year }}</strong></span>
        <span class="mr-3">{{ __('Service Outcome') }}: <strong>{{ __('Safety') }}</strong></span>
        @if($submission)<span class="mr-3">{{ __('Status') }}: <strong>{{ __(ucfirst($submission->status)) }}</strong></span>@endif
        @if($readOnly && $submission && in_array($submission->status, ['draft', 'submitted']))
            @can('Approve CWIS')<a class="btn btn-info" href="{{ route('cwis.safety.review', $submission->id) }}"><i class="fa fa-check" aria-hidden="true"></i> {{ __('Approve') }}</a>@endcan
        @endif
        @if($readOnly && $submission && $submission->status === 'approved')
            @can('Add CWIS')
            <form id="cwis-rebuild-form" method="POST" action="{{ route('cwis.safety.calculate', $submission->id) }}" class="mb-0"
                data-confirm-title="{{ __('Rebuild CWIS Results') }}" data-confirm="{{ __('Rebuild saved CWIS results for this reporting year using this approved submission? Existing results will be backed up.') }}"
                data-confirm-button="{{ __('Continue') }}" data-cancel-button="{{ __('Cancel') }}" data-confirm-error="{{ __('Unable to open confirmation. Please try again.') }}">
                @csrf<button type="submit" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> {{ $hasGeneratedResults ? __('Rebuild CWIS Results') : __('Generate CWIS Results') }}</button>
            </form>
            @endcan
        @endif
    </div>
    <form class="safety-form form-horizontal" method="POST" novalidate enctype="multipart/form-data"
        action="{{ $approvalReview ? route('cwis.safety.approve', $submission->id) : ($submission ? route('cwis.safety.update', $submission->id) : route('cwis.safety.store')) }}">
        @csrf
        @if($submission && !$approvalReview) @method('PUT') @endif
        @if($approvalReview)<input type="hidden" name="save_mode" value="submitted">@endif
        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="country_code" value="{{ $country }}">
        <fieldset @if($readOnly) disabled @endif class="m-3">
        @if($toilets->isNotEmpty() || $hasPublicSpaces)
        <h4>{{ __('Quantitative') }}</h4>
        @endif
        @if($toilets->isNotEmpty())
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><span class="btn btn-link">{{ __('Toilet Waiting Times (SF-3d / SF-4c)') }}</span></h5></div>
            <div class="card-body table-responsive">
                <table class="table table-bordered mb-0"><thead><tr><th>{{ __('Toilet') }}</th><th>{{ __('Waiting Time (minutes)') }}</th><th>{{ __('Number of Users') }}</th><th>{{ __('Evidence') }}</th></tr></thead><tbody>
                @foreach($toilets as $toilet)
                    @php($measurement = $savedWaiting->get($toilet->id))
                    <tr><td>{{ $toilet->name }}<small class="d-block text-muted">{{ $toilet->type }}</small></td>
                        <td><input aria-label="{{ $toilet->name }} {{ __('Waiting Time') }}" class="form-control" type="number" step="0.001" name="waiting_times[{{ $toilet->id }}][minutes]" value="{{ old('waiting_times.'.$toilet->id.'.minutes', $measurement->waiting_minutes ?? '') }}">
                        @error('waiting_times.'.$toilet->id.'.minutes')<div class="text-danger">{{ $message }}</div>@enderror</td>
                        <td><input aria-label="{{ $toilet->name }} {{ __('Number of Users') }}" class="form-control" type="number" step="1" name="waiting_times[{{ $toilet->id }}][users]" value="{{ old('waiting_times.'.$toilet->id.'.users', $measurement->number_users ?? '') }}">
                        @error('waiting_times.'.$toilet->id.'.users')<div class="text-danger">{{ $message }}</div>@enderror</td>
                        <td>@include('cwis.safety.evidence', ['evidenceModel' => $measurement, 'kind' => 'waiting'])
                        @unless($readOnly)<input aria-label="{{ $toilet->name }} {{ __('Evidence') }}" type="file" class="form-control-file" name="waiting_evidence[{{ $toilet->id }}]" accept="{{ \App\Support\CwisEvidence::UPLOAD_ACCEPT }}">@endunless
                        @error('waiting_evidence.'.$toilet->id)<div class="text-danger">{{ $message }}</div>@enderror</td>
                    </tr>
                @endforeach
                </tbody></table>
            </div>
        </div>
        @endif
        @if($hasPublicSpaces)
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><span class="btn btn-link">{{ __('Public-Space Toilet Coverage (SF-4)') }}</span></h5></div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>{{ __('Public Space') }}</th><th>{{ __('Public Toilet') }}</th>@unless($readOnly)<th></th>@endunless</tr></thead>
                <tbody id="public-spaces">
                @foreach(old('public_spaces', $publicSpaces->map(function ($row) { return ['name' => $row->name, 'toilet_id' => $row->toilet_id]; })->all()) as $index => $space)
                    @include('cwis.safety.public-space-row')
                @endforeach
                </tbody></table></div>
                @unless($readOnly)<button type="button" id="add-public-space" class="btn btn-info" title="{{ __('Add Public Space') }}"><i class="fa fa-plus" aria-hidden="true"></i><span class="sr-only">{{ __('Add Public Space') }}</span></button>@endunless
                <label class="d-block mt-3"><input type="checkbox" name="public_spaces_complete" value="1" {{ old('public_spaces_complete', $submission->public_spaces_complete ?? false) ? 'checked' : '' }}> {{ __('Complete public-space inventory') }}</label>
                @error('public_spaces_complete')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        </div>
        @endif
        @if($groups)<h4>{{ __('Qualitative') }}</h4>@endif
        @foreach($groups as $title => $questions)
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><span class="btn btn-link">{{ __($title) }}</span></h5></div>
            <div class="card-body table-responsive"><table class="table table-bordered mb-0"><thead><tr><th width="55%">{{ __('Indicators') }}</th><th width="12%">{{ __('Outcome') }}</th><th width="33%">{{ __('Value') }}</th></tr></thead><tbody>
            @foreach($questions as $code => $label)
                @php($key = str_replace('.', '_', $code))
                @php($saved = $savedAnswers->get($code))
                @php($answer = old('answers.'.$key, $saved->answer ?? ''))
                @php($parent = config('cwis_safety_d22.parents')[$code] ?? '')
                <tr><td><label class="font-weight-normal" for="answer-{{ $key }}">{{ __($label) }}</label><small class="d-block text-muted">{{ $code }}</small></td><td>{{ __('Safety') }}</td><td>
                    <select id="answer-{{ $key }}" name="answers[{{ $key }}]" class="form-control safety-answer" data-parent="{{ $parent }}" data-evidence="evidence-{{ $key }}">
                        @foreach(['' => __('Unknown'), 'Yes' => __('Yes'), 'No' => __('No')] as $value => $text)<option value="{{ $value }}" {{ $answer === $value ? 'selected' : '' }}>{{ $text }}</option>@endforeach
                    </select>
                    @error('answers.'.$key)<div class="text-danger">{{ $message }}</div>@enderror
                    <small class="text-muted safety-dependency" hidden>{{ __('Not applicable unless the parent indicator is Yes.') }}</small>
                    <div id="evidence-{{ $key }}" class="mt-2" @if($answer !== 'Yes') hidden @endif>
                        @include('cwis.safety.evidence', ['evidenceModel' => $saved, 'kind' => 'policy'])
                        @unless($readOnly)<label for="file-{{ $key }}">{{ $code === 'SF-12' ? __('Effluent Standard Evidence') : __('Evidence') }}</label><input id="file-{{ $key }}" type="file" class="form-control-file" name="evidence[{{ $key }}]" accept="{{ \App\Support\CwisEvidence::UPLOAD_ACCEPT }}">@endunless
                        @error('evidence.'.$key)<div class="text-danger">{{ $message }}</div>@enderror
                        @if($code === 'SF-12')
                            <div class="mt-2">@include('cwis.safety.evidence', ['evidenceModel' => $saved, 'kind' => 'biosolids'])</div>
                            @unless($readOnly)<label for="biosolids-{{ $key }}">{{ __('Biosolids Standard Evidence') }}</label><input id="biosolids-{{ $key }}" type="file" class="form-control-file" name="biosolids_evidence[{{ $key }}]" accept="{{ \App\Support\CwisEvidence::UPLOAD_ACCEPT }}">@endunless
                            @error('biosolids_evidence.'.$key)<div class="text-danger">{{ $message }}</div>@enderror
                        @endif
                    </div>
                </td></tr>
            @endforeach
            </tbody></table></div>
        </div>
        @endforeach
        </fieldset>
        @unless($readOnly)
        <div class="card-footer">
            @if($approvalReview)<button type="submit" class="btn btn-info">{{ __('Save and Approve') }}</button>
            @else<button type="submit" name="save_mode" value="draft" class="btn btn-info">{{ __('Save as Draft') }}</button> <button type="submit" name="save_mode" value="submitted" class="btn btn-info">{{ __('Submit for Approval') }}</button>@endif
        </div>
        @endunless
    </form>
    <div id="cwis-rebuild-loader" role="status" aria-live="polite" tabindex="-1" hidden><div><i class="fa fa-spinner fa-spin fa-2x" aria-hidden="true"></i><h4 class="mt-3">{{ __('Rebuilding CWIS Results') }}</h4><p>{{ __('Rebuilding CWIS results may take some time. Please wait.') }}</p></div></div>
</div>
@if($hasPublicSpaces && !$readOnly)
<template id="public-space-template">@include('cwis.safety.public-space-row', ['index' => '__INDEX__', 'space' => []])</template>
@endif
@stop
@include('cwis.generator-data.retain-uploads')
@push('scripts')
@include('cwis.sustainability.rebuild-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var readOnly = @json($readOnly);
    function refreshAnswers() {
        document.querySelectorAll('.safety-answer').forEach(function (select) {
            var parent = select.dataset.parent ? document.getElementById('answer-' + select.dataset.parent.replace(/\./g, '_')) : null;
            var applies = !parent || parent.value === 'Yes';
            select.disabled = readOnly || !applies;
            if (!applies && !readOnly) select.value = '';
            select.parentElement.querySelector('.safety-dependency').hidden = applies;
            var evidence = document.getElementById(select.dataset.evidence);
            evidence.hidden = !applies || select.value !== 'Yes';
            evidence.querySelectorAll('input[type=file]').forEach(function (input) { input.disabled = evidence.hidden || readOnly; });
        });
    }
    document.querySelectorAll('.safety-answer').forEach(function (select) { select.addEventListener('change', refreshAnswers); });
    refreshAnswers();
    var add = document.getElementById('add-public-space');
    var rows = document.getElementById('public-spaces');
    var nextIndex = rows ? rows.children.length : 0;
    if (add) add.addEventListener('click', function () {
        rows.insertAdjacentHTML('beforeend', document.getElementById('public-space-template').innerHTML.replace(/__INDEX__/g, nextIndex++));
    });
    if (rows && !readOnly) rows.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-public-space');
        if (button) button.closest('tr').remove();
    });
});
</script>
@endpush
