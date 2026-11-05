@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('cwis.generator-data.success-toast')
@if(isset($errors))
    @include('layouts.components.error-list')
@endif
<style>
    #indicator-detail-back,
    #indicator-detail-back:visited,
    #indicator-detail-back:hover,
    #indicator-detail-back:focus,
    #indicator-detail-back:active {
        color: #fff !important;
    }
    .cwis-equity-action { color: #fff !important; }
    #indicator-detail-approve,
    #indicator-detail-approve:visited,
    #indicator-detail-approve:hover,
    #indicator-detail-approve:focus,
    #indicator-detail-approve:active,
    #indicator-detail-approve i {
        color: #fff !important;
    }
    #indicator-input-table, #indicator-input-table th, #indicator-input-table td {
        border: 1px solid #dee2e6;
    }
    #cwis-indicator-page { position: relative; min-height: 360px; }
    #cwis-rebuild-loader {
        position: absolute;
        inset: 0;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.95);
        text-align: center;
        padding: 24px;
    }
    #cwis-rebuild-loader[hidden] { display: none; }
</style>
<div id="cwis-indicator-page">
<div class="card">
    <div class="card-header bg-white d-flex align-items-center flex-wrap">
        <a id="indicator-detail-back" class="btn btn-info text-white mr-3" href="{{ route('cwis.generator-data.index', ['country_code' => $country]) }}">{{ __('Back to List') }}</a>
        <span class="mr-3">{{ __('Year') }}: <strong>{{ $submission->reporting_year }}</strong></span>
        <span class="mr-3">{{ __('Country') }}: <strong>{{ $countryLabel }}</strong></span>
        <span>{{ __('Status') }}: <strong>{{ __(ucfirst($submission->status)) }}</strong></span>
        @if($canManageCountry && in_array($submission->status, ['draft','submitted'], true))
            @can('Approve CWIS')
                <a id="indicator-detail-approve" href="{{ route('cwis.generator-data.review', ['submission' => $submission->id]) }}" class="btn btn-info cwis-equity-action ml-3"><i class="fa fa-check" aria-hidden="true"></i> {{ __('Approve') }}</a>
            @endcan
        @endif
        @if($canManageCountry && $submission->status === 'approved')
            @can('Add CWIS')
                <form id="cwis-rebuild-form" method="POST" action="{{ route('cwis.generator-data.calculate', ['submission' => $submission->id]) }}" class="ml-3 mb-0" data-confirm-title="{{ __('Rebuild CWIS Results?') }}" data-confirm-button="{{ __('Rebuild') }}" data-cancel-button="{{ __('Cancel') }}" data-confirm-error="{{ __('Unable to open the confirmation. Please try again.') }}" @if($hasGeneratedResults) data-confirm="{{ __('Rebuild saved CWIS results for this reporting year using this approved submission? Existing results will be backed up.') }}" @endif>
                    @csrf
                    <button type="submit" class="btn btn-info cwis-equity-action"><i class="fa fa-refresh" aria-hidden="true"></i> {{ $hasGeneratedResults ? __('Rebuild CWIS Results') : __('Generate CWIS Results') }}</button>
                </form>
            @endcan
        @endif
    </div>
    <div class="card-body table-responsive">
        <table id="indicator-input-table" class="table table-bordered">
            <thead><tr><th>{{ __('Indicator') }}</th><th>{{ __('Input') }}</th><th>{{ __('Value') }}</th><th>{{ __('Evidence') }}</th></tr></thead>
            <tbody>
                @foreach($fields as $field)
                    @php
                        $source = $sourceRows[$field->storage_table] ?? null;
                        $value = $source->{$field->storage_column} ?? null;
                        $documentId = $field->document_column ? ($source->{$field->document_column} ?? null) : null;
                        $fieldErrors = isset($errors) ? $errors->get($field->input_name) : [];
                    @endphp
                    <tr @if($fieldErrors) class="table-danger" @endif>
                        <td>{{ $field->indicator_code }}</td>
                        <td>{{ __($field->label) }}
                            @foreach($fieldErrors as $fieldError)
                                <div class="text-danger mt-1" role="alert">{{ $fieldError }}</div>
                            @endforeach
                        </td>
                        <td>{{ $value === null ? __('No data') : ($field->kind === 'policy' ? ($value ? __('Yes') : __('No')) : $value) }}</td>
                        <td>
                            @if($field->kind === 'policy' && $documentId)
                                @include('cwis.generator-data.equity-evidence', ['documentName' => $evidenceNames[$documentId] ?? __('Attached Evidence')])
                            @elseif($field->kind === 'policy')
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('cwis-rebuild-form');
        if (!form) return;
        var loader = document.getElementById('cwis-rebuild-loader');
        var button = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (form.dataset.busy === 'true' || form.dataset.confirming === 'true') return;
            form.dataset.confirming = 'true';
            button.disabled = true;
            try {
                if (form.dataset.confirm) {
                    var confirmation = await window.Swal.fire({
                        title: form.dataset.confirmTitle,
                        text: form.dataset.confirm,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: form.dataset.confirmButton,
                        cancelButtonText: form.dataset.cancelButton,
                        reverseButtons: true
                    });
                    if (!confirmation.isConfirmed) return;
                }
                form.dataset.busy = 'true';
                form.setAttribute('aria-busy', 'true');
                button.disabled = true;
                loader.hidden = false;
                loader.focus();
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () { HTMLFormElement.prototype.submit.call(form); });
                });
            } catch (error) {
                if (window.toastr) window.toastr.error(form.dataset.confirmError);
            } finally {
                delete form.dataset.confirming;
                if (form.dataset.busy !== 'true') button.disabled = false;
            }
        });
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            loader.hidden = true;
            button.disabled = false;
            delete form.dataset.busy;
            delete form.dataset.confirming;
            form.removeAttribute('aria-busy');
        });
    });
</script>
@endpush
