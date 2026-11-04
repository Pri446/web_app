@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
<style>
    #data-table, #data-table th, #data-table td {
        border: 1px solid #dee2e6;
    }
    #data-table .btn { color: #fff !important; }
</style>
@include('layouts.components.error-list')
@include('cwis.generator-data.success-toast')
<div class="card">
    <div class="card-header">
        <button type="button" id="add-indicator-data" class="btn btn-info" data-toggle="modal" data-target="#add-indicator-modal" @if(empty($inputOutcomes) || $availableInputYears->isEmpty()) disabled @endif>{{ __('Add Indicator Data') }}</button>
        <span class="ml-3">{{ __('Country') }}: <strong>{{ $countryLabel }}</strong></span>
        <span class="ml-3">{{ __('Year') }}: <strong id="generator-selected-year">{{ $year ?? __('All Years') }}</strong></span>
        <button type="button" class="btn btn-info float-right" data-toggle="collapse" data-target="#generator-data-filter" aria-expanded="false" aria-controls="generator-data-filter">{{ __('Show Filter') }}</button>
    </div>
    <div class="card-body collapse" id="generator-data-filter">
        <div class="row">
            <div class="col-12">
                                <form class="form-horizontal" id="filter-form">
                                    <div class="form-group row">
                                        <label for="generator-data-year" class="col-md-2 col-form-label">{{ __('Year') }}</label>
                                        <div class="col-md-2">
                                            <select name="year" id="generator-data-year" class="form-control">
                                                <option value="" {{ $year === null ? 'selected' : '' }}>{{ __('All Years') }}</option>
                                                @foreach($years as $availableYear)
                                                    <option value="{{ $availableYear }}" {{ $availableYear == $year ? 'selected' : '' }}>{{ $availableYear }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label for="generator-data-outcome" class="col-md-2 col-form-label">{{ __('Service Outcome') }}</label>
                                        <div class="col-md-2">
                                            <select name="service_outcome" id="generator-data-outcome" class="form-control">
                                                <option value="">{{ __('All Service Outcomes') }}</option>
                                                @foreach($serviceOutcomes as $value => $label)
                                                    <option value="{{ $value }}" {{ ($filters['service_outcome'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-info">{{ __('Filter') }}</button>
                                        <button type="button" id="reset-filter" class="btn btn-info">{{ __('Reset') }}</button>
                                    </div>
                                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if(session('cwis_input_notice') || empty($inputOutcomes))
            <div class="alert alert-info" role="status">{{ session('cwis_input_notice') ?? __('No indicator input form is available for the selected country. Its mapped indicators use existing IMIS data.') }}</div>
        @endif
        @if(!empty($inputOutcomes) && $availableInputYears->isEmpty())
            <div class="alert alert-info" role="status">{{ __('Indicator data already exists for every available reporting year.') }}</div>
        @endif
        <p id="generator-data-action-preview" class="alert alert-info" role="status" hidden></p>
        <div style="overflow: auto; width: 100%;">
            <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
                <thead>
                    <tr>
                        <th>{{ __('Year') }}</th>
                        <th>{{ __('Service Outcome') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Updated At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="add-indicator-modal" tabindex="-1" role="dialog" aria-labelledby="add-indicator-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="add-indicator-form" class="modal-content" method="GET" action="{{ action('Cwis\CwisGeneratorDataController@create') }}">
            <input type="hidden" name="country_code" value="{{ $country }}">
            <div class="modal-header">
                <h5 class="modal-title" id="add-indicator-title">{{ __('Add Indicator Data') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <fieldset class="form-group">
                    <legend class="col-form-label font-weight-bold pt-0">{{ __('Service Outcome') }}</legend>
                    <div class="d-flex flex-wrap" id="add-indicator-outcomes">
                        @foreach($inputOutcomes as $value => $label)
                            <div class="custom-control custom-radio mr-4 mb-2">
                                <input type="radio" id="add-outcome-{{ $value }}" name="service_outcome" value="{{ $value }}" class="custom-control-input" required>
                                <label class="custom-control-label" for="add-outcome-{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
                <div class="form-group" id="add-indicator-year-group" hidden>
                    <label for="add-indicator-year">{{ __('Year') }}</label>
                    <select id="add-indicator-year" name="year" class="form-control" required disabled></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-info" disabled>{{ __('Continue') }}</button>
            </div>
        </form>
    </div>
</div>
@stop
@push('scripts')
<script>
    $(function () {
        var inputYearsByOutcome = @json($inputYearsByOutcome);
        function refreshInputYears() {
            var outcome = $('#add-indicator-outcomes input:checked').val();
            var select = document.getElementById('add-indicator-year');
            document.getElementById('add-indicator-year-group').hidden = !outcome;
            select.replaceChildren();
            var years = (inputYearsByOutcome[outcome] || []).slice().sort(function (a, b) { return b - a; });
            years.forEach(function (year) {
                select.add(new Option(year, year));
            });
            if (!years.length) {
                select.add(new Option(@json(__('No reporting years available')), ''));
            }
            select.disabled = years.length === 0;
            $('#add-indicator-form button[type="submit"]').prop('disabled', years.length === 0);
        }
        $('#add-indicator-outcomes input').on('change', refreshInputYears);
        var dataTable = $('#data-table').DataTable({
            bFilter: false,
            processing: true,
            serverSide: true,
            scrollCollapse: true,
            ajax: {
                url: @json(action('Cwis\CwisGeneratorDataController@getData')),
                error: function (xhr) {
                    if (xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.country_changed) {
                        window.location.replace(xhr.responseJSON.redirect);
                        return;
                    }
                    var notice = document.getElementById('generator-data-action-preview');
                    notice.textContent = @json(__('Unable to load indicator data. Please refresh the page.'));
                    notice.hidden = false;
                },
                data: function (d) {
                    d.year = $('#generator-data-year').val();
                    d.country_code = @json($country);
                    d.service_outcome = $('#generator-data-outcome').val();
                }
            },
            columns: [
                { data: 'year', name: 'year' },
                { data: 'service_outcome', name: 'service_outcome', orderable: false, searchable: false },
                { data: 'status_label', name: 'status' },
                { data: 'updated_at', name: 'updated_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc'], [3, 'desc']]
        });
        $('#filter-form').on('submit', function (e) {
            e.preventDefault();
            $('#generator-data-year').trigger('change');
        });
        $('#generator-data-year, #generator-data-outcome').on('change', function () {
            var url = new URL(window.location.href);
            var selectedYear = $('#generator-data-year').val();
            if (selectedYear) url.searchParams.set('year', selectedYear);
            else url.searchParams.delete('year');
            document.getElementById('generator-selected-year').textContent = selectedYear || @json(__('All Years'));
            var outcome = $('#generator-data-outcome').val();
            if (outcome) url.searchParams.set('service_outcome', outcome);
            else url.searchParams.delete('service_outcome');
            window.history.replaceState({}, '', url);
            dataTable.ajax.reload();
        });
        $('#reset-filter').on('click', function () {
            $('#generator-data-year').val('');
            $('#generator-data-outcome').val('');
            $('#generator-data-year').trigger('change');
        });
        $('#add-indicator-modal').on('show.bs.modal', function () {
            $('#add-indicator-outcomes input').prop('checked', false);
            refreshInputYears();
        });
        $('#data-table').on('click', '[data-preview-action]', function () {
            var notice = document.getElementById('generator-data-action-preview');
            notice.textContent = this.getAttribute('aria-label') + ' — ' + @json(__('Preview only. No data has been changed.'));
            notice.hidden = false;
        });
    });
</script>
@endpush
