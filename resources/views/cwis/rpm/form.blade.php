@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('layouts.components.error-list')
<style>.rpm-form .btn-info { color: #fff !important; }</style>
<div class="card card-info">
<form class="rpm-form form-horizontal" method="POST" novalidate enctype="multipart/form-data" action="{{ $approvalReview ? route('cwis.rpm.approve', $submission->id) : ($submission ? route('cwis.rpm.update', $submission->id) : route('cwis.rpm.store')) }}">
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
                <span>{{ __('Service Outcome') }}: <strong>{{ __('Resource Planning and Management') }}</strong></span>
                @if($submission)<span class="ml-3">{{ __('Status') }}: <strong>{{ __(ucfirst($submission->status)) }}</strong></span>@endif
            </div>
        </div>
    </div>
    <div class="m-3">
        <h4>{{ __('Qualitative') }}</h4>
        @foreach($groups as $title => $questions)
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><span class="btn btn-link">{{ __($title) }}</span></h5></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered mb-0" width="100%">
                    <thead><tr><th width="55%">{{ __('Indicators') }}</th><th width="12%">{{ __('Outcome') }}</th><th width="33%">{{ __('Value') }}</th></tr></thead>
                    <tbody>
                    @foreach($questions as $code => $label)
                        @php($key = str_replace('.', '_', $code))
                        @php($saved = $savedAnswers->get($code))
                        @php($answer = old('answers.'.$key, $saved->answer ?? ''))
                        <tr><td><label for="answer-{{ $key }}" class="font-weight-normal mb-1">{{ __($label) }}</label><small class="d-block text-muted">{{ $code }}</small></td><td>{{ __('Resource Planning and Management') }}</td><td>
                            <select id="answer-{{ $key }}" class="form-control rpm-answer @error('answers.'.$key) is-invalid @enderror" name="answers[{{ $key }}]" data-evidence="evidence-{{ $key }}">
                                @foreach(['' => __('Unknown'), 'Yes' => __('Yes'), 'No' => __('No')] as $value => $text)
                                    <option value="{{ $value }}" {{ $answer === $value ? 'selected' : '' }}>{{ $text }}</option>
                                @endforeach
                            </select>
                            @error('answers.'.$key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="evidence-{{ $key }}" class="mt-2" @if($answer !== 'Yes') hidden @endif>
                                @include('cwis.generator-data.saved-evidence', ['documentRoute' => 'cwis.rpm.document'])
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
        @endforeach
    </div>
    <div class="card-footer">
        @if($approvalReview)
            <button type="submit" class="btn btn-info">{{ __('Save and Approve') }}</button>
        @else
            <button type="submit" name="save_mode" value="draft" class="btn btn-info">{{ __('Save as Draft') }}</button>
            <button type="submit" name="save_mode" value="submitted" class="btn btn-info">{{ __('Submit for Approval') }}</button>
        @endif
    </div>
</form>
</div>
@stop
@include('cwis.generator-data.retain-uploads')
@push('scripts')
<script>
document.querySelectorAll('.rpm-answer').forEach(function (select) {
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
