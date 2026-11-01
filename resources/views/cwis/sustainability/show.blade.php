@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('cwis.generator-data.success-toast')
@include('layouts.components.error-list')
<style>
    #indicator-detail-back, #indicator-detail-back:hover, #indicator-detail-back:focus,
    .cwis-sustainability-action { color: #fff !important; }
    #indicator-input-table, #indicator-input-table th, #indicator-input-table td { border: 1px solid #dee2e6; }
    #cwis-indicator-page { position: relative; min-height: 360px; }
    #cwis-rebuild-loader { position: absolute; inset: 0; z-index: 20; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.95); text-align: center; padding: 24px; }
    #cwis-rebuild-loader[hidden] { display: none; }
</style>
<div id="cwis-indicator-page">
    <div class="card">
        <div class="card-header bg-white d-flex align-items-center flex-wrap">
            <a id="indicator-detail-back" class="btn btn-info text-white mr-3" href="{{ route('cwis.generator-data.index', ['country_code' => $country]) }}">{{ __('Back to List') }}</a>
            <span class="mr-3">{{ __('Year') }}: <strong>{{ $submission->reporting_year }}</strong></span>
            <span class="mr-3">{{ __('Country') }}: <strong>{{ $countryLabel }}</strong></span>
            <span>{{ __('Status') }}: <strong>{{ __(ucfirst($submission->status)) }}</strong></span>
            @if(in_array($submission->status, ['draft', 'submitted'], true))
                @can('Approve CWIS')
                    <a href="{{ route('cwis.sustainability.review', $submission->id) }}" class="btn btn-info cwis-sustainability-action ml-3"><i class="fa fa-check" aria-hidden="true"></i> {{ __('Approve') }}</a>
                @endcan
            @endif
            @if($submission->status === 'approved')
                @can('Add CWIS')
                    <form id="cwis-rebuild-form" method="POST" action="{{ route('cwis.sustainability.calculate', $submission->id) }}" class="ml-3 mb-0"
                        data-confirm-title="{{ __('Rebuild CWIS Results?') }}" data-confirm-button="{{ __('Rebuild') }}" data-cancel-button="{{ __('Cancel') }}"
                        data-confirm-error="{{ __('Unable to open the confirmation. Please try again.') }}"
                        data-confirm="{{ __('Rebuild Sustainability results for this reporting year using this approved submission? Existing results will be backed up. Equity and Safety results will be preserved.') }}">
                        @csrf
                        <button id="sustainability-rebuild-button" type="submit" class="btn btn-info cwis-sustainability-action"><i class="fa fa-refresh" aria-hidden="true"></i> {{ $hasGeneratedResults ? __('Rebuild CWIS Results') : __('Generate CWIS Results') }}</button>
                    </form>
                @endcan
            @endif
        </div>
        <div class="card-body table-responsive">
            <table id="indicator-input-table" class="table table-bordered">
                <thead><tr><th>{{ __('Indicator') }}</th><th>{{ __('Input') }}</th><th>{{ __('Value') }}</th><th>{{ __('Evidence') }}</th></tr></thead>
                <tbody>
                    @if($submission->currency_code)
                        <tr><td></td><td>{{ __('Currency Code') }}</td><td>{{ $submission->currency_code }}</td><td></td></tr>
                    @endif
                    @foreach($sections as $code => $section)
                        @foreach($section['fields'] as $name => $label)
                            @php($value = $financials->$name ?? null)
                            <tr><td>{{ $code }}</td><td>{{ __($label) }}</td><td>{{ $value === null ? __('No data') : $value }}</td><td></td></tr>
                        @endforeach
                    @endforeach
                    @foreach($campaigns as $code => $label)
                        @php($saved = $savedCampaigns->get($code))
                        <tr>
                            <td>{{ $code }}</td><td>{{ __($label) }}</td><td>{{ $saved && $saved->answer !== null ? __($saved->answer) : __('No data') }}</td>
                            <td>
                                @if($saved && $saved->document_path)
                                    @include('cwis.generator-data.saved-evidence', ['documentRoute' => 'cwis.sustainability.document'])
                                @else
                                    {{ __('Not attached') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div id="cwis-rebuild-loader" role="status" aria-live="polite" aria-atomic="true" tabindex="-1" hidden>
        <div>
            <i class="fa fa-spinner fa-spin fa-3x text-info mb-3" aria-hidden="true"></i>
            <h5>{{ __('Rebuilding CWIS Results') }}</h5>
            <p class="mb-0">{{ __('Rebuilding CWIS results may take some time. Please wait.') }}</p>
        </div>
    </div>
</div>
@stop
@push('scripts')
@include('cwis.sustainability.rebuild-script')
@endpush
