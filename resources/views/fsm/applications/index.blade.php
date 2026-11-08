<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.dashboard')
@section('title', __('Application'))
@push('style')
    <style type="text/css">
        .dataTables_filter {
            display: none;
        }
        /* Added for modal table clarity */
        #tp-details-table th { background-color: #f8f9fa; }
    </style>
@endpush

@section('content')
    @php
        // Full Application PII export is deliberately narrower than the
        // selected-BIN export because it can disclose the complete authorized
        // customer dataset in one operation.
        $canExportAllCustomerPii = $canExportCustomerPii
            && Auth::user()->hasAnyRole([
                'Super Admin',
                'Municipality - Super Admin',
                'Municipality - IT Admin',
            ]);
    @endphp
    @if (!$customerPiiListUnlocked && $canUnlockCustomerPii)
        <div class="modal fade" id="application-customer-pii-modal" tabindex="-1" role="dialog"
            aria-labelledby="application-customer-pii-title" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST" action="{{ route('application-pii.unlock-list') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="application-customer-pii-title">
                                {{ __('Reveal Application Owner PII') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>
                                {{ __('Owner name, gender, and contact will be available for five minutes. The same temporary access also allows automatic owner lookup by BIN on Add Application. Applicant fields are outside this encryption scope.') }}
                            </p>
                            <div class="form-group mb-0">
                                <label for="application_pii_password">{{ __('Current Password') }}</label>
                                <input id="application_pii_password" name="current_password" type="password"
                                    class="form-control @error('application_pii_password') is-invalid @enderror"
                                    required maxlength="255" autocomplete="current-password">
                                @error('application_pii_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-info">{{ __('Reveal for 5 Minutes') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canExportCustomerPii)
        <div class="modal fade" id="application-customer-pii-export-modal" tabindex="-1" role="dialog"
            aria-labelledby="application-customer-pii-export-title" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="application-customer-pii-export-form" method="POST"
                        action="{{ route('application-pii.export') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="application-customer-pii-export-title">
                                {{ __('Export Customer PII') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                {{ __('The downloaded CSV contains sensitive customer information. Handle it securely and delete it when it is no longer required.') }}
                            </div>
                            <div class="form-group">
                                <label class="d-block">{{ __('Export Scope') }}</label>
                                @if ($canExportAllCustomerPii)
                                    <div class="custom-control custom-radio mb-2">
                                        <input id="application_export_mode_all" name="application_export_mode"
                                            type="radio" value="all" class="custom-control-input" required>
                                        <label class="custom-control-label" for="application_export_mode_all">
                                            {{ __('Export all authorized applications') }}
                                        </label>
                                    </div>
                                @endif
                                <div class="custom-control custom-radio">
                                    <input id="application_export_mode_bin_list" name="application_export_mode"
                                        type="radio" value="bin_list" class="custom-control-input" required>
                                    <label class="custom-control-label" for="application_export_mode_bin_list">
                                        {{ __('Export applications from BIN list') }}
                                    </label>
                                </div>
                            </div>
                            @if ($canExportAllCustomerPii)
                                <div id="application-pii-export-all-confirmation" class="alert alert-danger"
                                    style="display: none">
                                    <p class="mb-2">
                                        {{ __('This will export customer PII for every Application record you are authorized to access. No BIN filter will be applied.') }}
                                    </p>
                                    <div class="custom-control custom-checkbox">
                                        <input id="confirm_application_export_all"
                                            name="confirm_application_export_all" type="checkbox" value="1"
                                            class="custom-control-input">
                                        <label class="custom-control-label" for="confirm_application_export_all">
                                            {{ __('I understand and intend to export all authorized Application customer PII.') }}
                                        </label>
                                    </div>
                                </div>
                            @endif
                            <div id="application-pii-bin-list-fields" class="form-group" style="display: none">
                                <label for="application_bin_file">{{ __('BIN List CSV') }}</label>
                                <input id="application_bin_file" type="file" accept=".csv,text/csv"
                                    class="form-control-file">
                                <input id="application_bin_csv" name="application_bin_csv" type="hidden" value="">
                                <small class="form-text text-muted">
                                    {{ __('Upload a CSV containing a bin header and one BIN per row. All authorized Applications matching each BIN will be exported.') }}
                                </small>
                                <div id="application-bin-file-validation" class="small mt-1" role="status"
                                    aria-live="polite"></div>
                            </div>
                            <div class="form-group mb-0">
                                <label for="application_export_password">{{ __('Current Password') }}</label>
                                <input id="application_export_password" name="application_export_password"
                                    type="password" class="form-control" required maxlength="255"
                                    autocomplete="current-password">
                                <small class="form-text text-muted">
                                    {{ __('Re-enter your login password. It is verified but never stored.') }}
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                {{ __('Cancel') }}
                            </button>
                            <button id="application-customer-pii-export-submit" type="submit"
                                class="btn btn-danger" disabled>
                                {{ __('Export Customer PII') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            @if (!empty($createBtnLink) && !empty($createBtnTitle))
                <a href="{{ $createBtnLink }}" class="btn btn-info">{{ $createBtnTitle }}</a>
            @endif
            @if (!empty($exportBtnLink))
                <a href="{{ $exportBtnLink }}" class="btn btn-info" id="export" onclick="exportToCsv(event)" >{{ __('Export to CSV') }}</a>
            @endif
            @if ($canExportCustomerPii)
                <button type="button" class="btn btn-info" data-toggle="modal"
                    data-target="#application-customer-pii-export-modal">
                    {{ __('Export Customer PII') }}
                </button>
            @endif
            @if ($customerPiiListUnlocked)
                <form id="application-pii-lock-form" method="POST"
                    action="{{ route('application-pii.lock') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">{{ __('Lock Owner PII') }}</button>
                </form>
                {{-- Use the same clear, toolbar-sized reveal indicator as the
                    Building module instead of a small status badge. --}}
                <span class="btn btn-success disabled ml-1" role="status"
                    aria-label="{{ __('Owner PII is currently visible') }}">
                    <i class="fas fa-eye mr-1" aria-hidden="true"></i>
                    {{ __('Owner PII is visible') }}
                </span>
            @elseif ($canUnlockCustomerPii)
                <button type="button" class="btn btn-info" data-toggle="modal"
                    data-target="#application-customer-pii-modal">
                    {{ __('View Owner PII Information') }}
                </button>
            @endif
            <div class="float-right">
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#treatmentPlantModal"
                    data-loader-ignore id="btnShowTP">
                    {{ __('Treatment Plant Capacity') }}
                </button>
                <a class="btn btn-info" id="headingOne" type="button" data-toggle="collapse"
                    data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    {{ __('Show Filter') }}
                </a>
            </div>
            @if (!empty($reportBtnLink))
                <a class="btn btn-info" data-toggle="collapse" data-target="#collapseFilterPdf" aria-expanded="false"
                    aria-controls="collapseFilterPdf">{{ __('Generate Report') }}</a>
                <div class="card-body">
                    <div class="col-12">
                        <div id="collapseFilterPdf" class="accordion-collapse collapse" aria-labelledby="headingOne"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <div class="form-group row required">
                                    <label for="bin_text" class="control-label col-md-2">{{ __('Month') }}</label>
                                    <div class="col-md-2">
                                        <select class="form-control row" id="month_select" name="month" {{ !empty($application_months) ? '' : 'disabled' }}>
                                            @foreach($application_months as $unique)
                                                <option value="{{ $unique->date1 }}">
                                                    {{ date('F', mktime(0, 0, 0, $unique->date1, 10)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <label for="bin_text" class="control-label col-md-2">{{ __('Year') }}</label>
                                    <div class="col-md-2">
                                        <select class="form-control row" id="year_select" name="year" {{ !empty($application_years) ? '' : 'disabled' }}>
                                            @foreach($application_years as $unique)
                                                <option value="{{ $unique->date1 }}">{{ $unique->date1 }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <a class="btn btn-info pdf" id="pdf">{{ __('Export to PDF') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <form class="form-horizontal" id="filter-form">
                                        @foreach ($filterFormFields as $formFieldGroup)
                                            <div class="form-group row">
                                                @foreach ($formFieldGroup as $formField)
                                                    @if ($formField->inputId === 'customer_name' && !$customerPiiListUnlocked)
                                                        @continue
                                                    @endif
                                                    {!! Form::label($formField->labelFor, $formField->label, ['class' => $formField->labelClass]) !!}
                                                    <div class="col-md-2">
                                                        @if ($formField->inputType === 'text')
                                                            {!! Form::text($formField->inputId, $formField->inputValue, ['class' => $formField->inputClass, 'placeholder' => $formField->placeholder, 'autocomplete' => $formField->autoComplete]) !!}
                                                        @elseif ($formField->inputType === 'date')
                                                            {!! Form::date($formField->inputId, $formField->inputValue, ['class' => $formField->inputClass, 'onclick' => 'this.showPicker();']) !!}
                                                        @elseif ($formField->inputType === 'number')
                                                            {!! Form::number($formField->inputId, $formField->inputValue, ['class' => $formField->inputClass, 'placeholder' => $formField->placeholder]) !!}
                                                        @elseif ($formField->inputType === 'select')
                                                            {!! Form::select($formField->inputId, $formField->selectValues, $formField->selectedValue, ['class' => $formField->inputClass, 'placeholder' => $formField->placeholder]) !!}
                                                        @elseif ($formField->inputType === 'label')
                                                            {!! Form::label($formField->inputId, $formField->labelValue, ['class' => $formField->inputClass]) !!}
                                                        @elseif ($formField->inputType === 'multiple-select')
                                                            {!! Form::select($formField->inputId, $formField->selectValues, $formField->selectedValue, ['class' => $formField->inputClass, 'disabled' => $formField->disabled]) !!}
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                        <div class="card-footer text-right">
                                            <button type="submit" class="btn btn-info">{{ __('Filter') }}</button>
                                            <button id="reset-filter" type="button" class="btn btn-info">{{ __('Reset') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div style="overflow: auto; width: 100%;">
                <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('BIN') }}</th>
                            <th>{{ __('House Number') }}</th>
                            <th>{{ __('Containment ID') }}</th>
                            <th>{{ __('Street Code') }}</th>
                            <th>{{ __('Ward Number') }}</th>
                            <th>{{ __('Applicant Name') }}</th>
                            <th>{{ __('Applicant Contact') }}</th>
                            <th>{{ __('Service Provider Name') }}</th>
                            <th>{{ __('Application Date') }}</th>
                            <th>{{ __('Proposed Emptying Date') }}</th>
                            <th>{{ __('Emptying Status') }}</th>
                            <th>{{ __('Sludge Collection Status') }}</th>
                            <th>{{ __('Feedback Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- TREATMENT PLANT MODAL -->
    <div class="modal fade" id="treatmentPlantModal" tabindex="-1" role="dialog" aria-labelledby="tpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="tpModalLabel">{{ __('Treatment Plant Capacity') }}</h5>
                    <small id="currentDate"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="tp-details-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th>{{ __('Capacity') }}</th>
                            </tr>
                        </thead>
                        <tbody id="tp-body">
                            <tr>
                                <td colspan="4" class="text-center">{{ __('Click button to load data...') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>


@stop

@push('scripts')
    <script>
        @if ($errors->has('application_pii_password'))
            $(function() {
                $('#application-customer-pii-modal').modal('show');
            });
        @endif

        @if ($canExportCustomerPii)
            $(function() {
                var applicationPiiCsvIsValid = false;
                var applicationPiiMaxBins = {{ (int) config('pii.export.max_bins', 5000) }};
                var applicationPiiMaxFileBytes = {{ (int) config('pii.export.max_file_kb', 2048) * 1024 }};

                function showApplicationPiiCsvStatus(message, valid) {
                    $('#application-bin-file-validation')
                        .text(message)
                        .toggleClass('text-success', valid)
                        .toggleClass('text-danger', !valid);
                }

                function updateApplicationPiiExportButton() {
                    var exportMode = $('input[name="application_export_mode"]:checked').val();
                    var hasPassword = $('#application_export_password').val().length > 0;
                    var scopeIsReady = exportMode === 'all'
                        ? $('#confirm_application_export_all').is(':checked')
                        : exportMode === 'bin_list' && applicationPiiCsvIsValid;

                    $('#application-customer-pii-export-submit')
                        .prop('disabled', !scopeIsReady || !hasPassword);
                }

                function resetApplicationPiiExportMode() {
                    applicationPiiCsvIsValid = false;
                    $('#application_bin_file').val('');
                    $('#application_bin_csv').val('');
                    $('#confirm_application_export_all').prop('checked', false);
                    $('#application-pii-bin-list-fields, #application-pii-export-all-confirmation').hide();
                    $('#application-bin-file-validation').text('')
                        .removeClass('text-success text-danger');
                    updateApplicationPiiExportButton();
                }

                function parseApplicationPiiCsvLine(line) {
                    var values = [];
                    var value = '';
                    var quoted = false;

                    for (var index = 0; index < line.length; index++) {
                        var character = line.charAt(index);

                        if (character === '"') {
                            if (quoted && line.charAt(index + 1) === '"') {
                                value += '"';
                                index++;
                            } else {
                                quoted = !quoted;
                            }
                        } else if (character === ',' && !quoted) {
                            values.push(value);
                            value = '';
                        } else {
                            value += character;
                        }
                    }

                    values.push(value);
                    return values;
                }

                function validateApplicationPiiCsv(csvText) {
                    var lines = csvText.replace(/^\uFEFF/, '').split(/\r?\n/);
                    var header = parseApplicationPiiCsvLine(lines.shift() || '')
                        .map(function(value) { return value.trim().toLowerCase(); });
                    var binIndex = header.indexOf('bin');

                    if (binIndex === -1) {
                        return {
                            valid: false,
                            message: "{{ __('The CSV must contain a bin header.') }}"
                        };
                    }

                    var uniqueBins = {};
                    var binCount = 0;

                    for (var rowIndex = 0; rowIndex < lines.length; rowIndex++) {
                        if (lines[rowIndex].trim() === '') {
                            continue;
                        }

                        var row = parseApplicationPiiCsvLine(lines[rowIndex]);
                        var bin = (row[binIndex] || '').trim().toUpperCase();

                        if (!/^[A-Z0-9_-]{1,100}$/.test(bin)) {
                            return {
                                valid: false,
                                message: "{{ __('Invalid BIN on CSV row') }}" + ' ' + (rowIndex + 2) + '.'
                            };
                        }

                        if (!uniqueBins[bin]) {
                            uniqueBins[bin] = true;
                            binCount++;
                        }

                        if (binCount > applicationPiiMaxBins) {
                            return {
                                valid: false,
                                message: "{{ __('The CSV contains too many unique BIN values.') }}"
                            };
                        }
                    }

                    if (binCount === 0) {
                        return {
                            valid: false,
                            message: "{{ __('The CSV does not contain any BIN values.') }}"
                        };
                    }

                    return {
                        valid: true,
                        message: binCount + " {{ __('unique BIN values validated.') }}"
                    };
                }

                $('input[name="application_export_mode"]').on('change', function() {
                    var exportMode = $(this).val();

                    // Clear stale data whenever the user changes scope so an
                    // invalid/old BIN list can never influence a full export.
                    applicationPiiCsvIsValid = false;
                    $('#application_bin_file').val('');
                    $('#application_bin_csv').val('');
                    $('#confirm_application_export_all').prop('checked', false);
                    $('#application-bin-file-validation').text('')
                        .removeClass('text-success text-danger');
                    $('#application-pii-bin-list-fields').toggle(exportMode === 'bin_list');
                    $('#application-pii-export-all-confirmation').toggle(exportMode === 'all');
                    updateApplicationPiiExportButton();
                });

                $('#application_bin_file').on('change', function() {
                    var file = this.files && this.files.length ? this.files[0] : null;

                    applicationPiiCsvIsValid = false;
                    $('#application_bin_csv').val('');
                    updateApplicationPiiExportButton();

                    if (!file) {
                        showApplicationPiiCsvStatus("{{ __('Select a CSV file.') }}", false);
                        return;
                    }

                    if (!/\.csv$/i.test(file.name)) {
                        showApplicationPiiCsvStatus("{{ __('The BIN list must be a CSV file.') }}", false);
                        return;
                    }

                    if (file.size > applicationPiiMaxFileBytes) {
                        showApplicationPiiCsvStatus("{{ __('The BIN list CSV is too large.') }}", false);
                        return;
                    }

                    var reader = new FileReader();
                    reader.onload = function(event) {
                        var csvText = String(event.target.result || '');
                        var result = validateApplicationPiiCsv(csvText);

                        applicationPiiCsvIsValid = result.valid;
                        $('#application_bin_csv').val(result.valid ? csvText : '');
                        showApplicationPiiCsvStatus(result.message, result.valid);
                        updateApplicationPiiExportButton();
                    };
                    reader.onerror = function() {
                        showApplicationPiiCsvStatus(
                            "{{ __('The BIN list CSV could not be read.') }}",
                            false
                        );
                        updateApplicationPiiExportButton();
                    };
                    reader.readAsText(file);
                });

                $('#application_export_password, #confirm_application_export_all').on(
                    'input change',
                    updateApplicationPiiExportButton
                );

                $('#application-customer-pii-export-form').on('submit', function(event) {
                    event.preventDefault();
                    var form = $(this);
                    var exportMode = $('input[name="application_export_mode"]:checked').val();
                    var scopeIsReady = exportMode === 'all'
                        ? $('#confirm_application_export_all').is(':checked')
                        : exportMode === 'bin_list' && applicationPiiCsvIsValid;

                    if (!scopeIsReady || form.data('submitting')) {
                        return;
                    }

                    form.data('submitting', true);
                    $('#application-customer-pii-export-submit')
                        .prop('disabled', true)
                        .text("{{ __('Exporting...') }}");

                    fetch(form.attr('action'), {
                        method: 'POST',
                        body: new FormData(form[0]),
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json, text/csv',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(function(response) {
                        if (!response.ok) {
                            return response.json().catch(function() {
                                return {
                                    message: "{{ __('The Application customer PII export could not be generated.') }}"
                                };
                            }).then(function(error) {
                                var validationMessage = error.message;

                                if (error.errors) {
                                    var keys = Object.keys(error.errors);
                                    if (keys.length && error.errors[keys[0]].length) {
                                        validationMessage = error.errors[keys[0]][0];
                                    }
                                }

                                throw new Error(validationMessage ||
                                    "{{ __('The Application customer PII export could not be generated.') }}");
                            });
                        }

                        var disposition = response.headers.get('Content-Disposition') || '';
                        var filenameMatch = disposition.match(/filename\*?=(?:UTF-8''|["'])?([^"';]+)/i);
                        var filename = filenameMatch
                            ? decodeURIComponent(filenameMatch[1].replace(/["']/g, ''))
                            : 'application-customer-pii-export.csv';

                        return response.blob().then(function(blob) {
                            return { blob: blob, filename: filename };
                        });
                    }).then(function(download) {
                        var downloadUrl = window.URL.createObjectURL(download.blob);
                        var link = document.createElement('a');

                        link.href = downloadUrl;
                        link.download = download.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.setTimeout(function() {
                            window.URL.revokeObjectURL(downloadUrl);
                        }, 1000);

                        $('#application-customer-pii-export-modal').modal('hide');
                        form[0].reset();
                        form.data('submitting', false);
                        resetApplicationPiiExportMode();
                        $('#application-customer-pii-export-submit')
                            .text("{{ __('Export Customer PII') }}");
                        updateApplicationPiiExportButton();

                        Swal.fire({
                            title: "{{ __('Customer PII exported successfully') }}",
                            text: "{{ __('The CSV was downloaded. Store it securely and delete it when it is no longer required.') }}",
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        });
                    }).catch(function(error) {
                        form.data('submitting', false);
                        $('#application-customer-pii-export-submit')
                            .text("{{ __('Export Customer PII') }}");
                        updateApplicationPiiExportButton();

                        Swal.fire({
                            title: "{{ __('Export failed') }}",
                            text: error.message,
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    });
                });
            });
        @endif

        $(document).ready(function () {

            $('#btnShowTP').on('click', function() {
            $.ajax({
                url: '{{ route("application.treatment-plant") }}',
                method: 'GET',
                beforeSend: function() {
                    $('#tp-body').html('<tr><td colspan="3" class="text-center">Loading...</td></tr>');
                },
                success: function(response) {
                    if(response.date_display) {
                        $('#currentDate').html(response.date_display);
                    } else if(response.date) {
                        const dateObj = new Date(response.date);
                        const options = { month: 'long', day: 'numeric' };
                        $('#currentDate').html(dateObj.toLocaleDateString(undefined, options));
                    }

                    var html = '';
                    if(response.plants && response.plants.length > 0) {
                        $.each(response.plants, function(index, plant) {
                            html += '<tr>' +
                                    '<td>' + (plant.name ?? "N/A") + '</td>' +
                                    '<td>' + (plant.location ?? "N/A") + '</td>' +
                                    '<td>' + (plant.remaining_capacity ?? "0") + '</td>' + // The subtracted value
                                    '</tr>';
                        });
                    } else {
                        html = '<tr><td colspan="3" class="text-center">No active plants found.</td></tr>';
                    }
                    $('#tp-body').html(html);
                },
                error: function() {
                    $('#tp-body').html('<tr><td colspan="3" class="text-center text-danger">Error fetching data.</td></tr>');
                }
            });
        });

            // 2. Service Provider Dropdown AJAX
            var serviceProviderId = {{ Auth::user()->service_provider_id ?? 'null' }};
            var spUrl = serviceProviderId ? '{!! url("fsm/service-provider") !!}/' + serviceProviderId : '{!! url("fsm/service-provider") !!}/0';

            $.ajax({
                url: spUrl,
                method: 'GET',
                success: function (response) {
                    $('#service_provider_id').empty().append('<option value="">{{ __("Service Provider Name") }}</option>');
                    $.each(response, function (id, name) {
                        $('#service_provider_id').append('<option value="' + id + '">' + name + '</option>');
                    });
                }
            });

            // 3. DataTable Initialization
            var dataTable = $('#data-table').DataTable({
                bFilter: false,
                processing: true,
                serverSide: true,
                stateSave: true,
                ajax: {
                    url: '{!! route('application.get-data') !!}',
                    data: function(d) {
                        d.bin = $('#bin').val();
                        d.house_address = $('#house_address').val();
                        d.applicant_name = $('#applicant_name').val();
                        @if ($customerPiiListUnlocked)
                            d.customer_name = $('#customer_name').val();
                        @endif
                        d.ward = $('#ward').val();
                        d.application_id = $('#application_id').val();
                        d.emptying_status = $('#emptying_status').val();
                        d.sludge_collection_status = $('#sludge_collection_status').val();
                        d.feedback_status = $('#feedback_status').val();
                        d.road_code = $('#road_code').val();
                        d.proposed_emptying_date = $('#proposed_emptying_date').val();
                        d.service_provider_id = $('#service_provider_id').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                    },
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'bin', name: 'bin' },
                    { data: 'house_address', name: 'house_address' },
                    { data: 'containment_id', name: 'containment_id' },
                    { data: 'road_code', name: 'road_code' },
                    { data: 'ward', name: 'ward' },
                    { data: 'applicant_name', name: 'applicant_name' },
                    { data: 'applicant_contact', name: 'applicant_contact' },
                    { data: 'service_provider_id', name: 'service_provider_id' },
                    {
                        data: 'application_date',
                        name: 'application_date',
                        render: function(data) { return data ? moment(data).format("dddd, MMMM Do YYYY") : ''; }
                    },
                    { data: 'proposed_emptying_date', name: 'proposed_emptying_date' },
                    { data: 'emptying_status', name: 'emptying_status' },
                    { data: 'sludge_collection_status', name: 'sludge_collection_status' },
                    { data: 'feedback_status', name: 'feedback_status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[0, 'desc']]
            });

            // DataTable action rows are rendered dynamically, so use a
            // delegated handler that remains active after every redraw.
            $(document).on('click', '#data-table .delete', function(e) {
                e.preventDefault();
                deleteAction($(this).closest('form')[0]);
            });

            // 4. Form Actions (Filter & Reset)
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();

                if ((date_from !== '' && date_to === '') || (date_from === '' && date_to !== '')) {
                    Swal.fire({ icon: 'warning', title: 'Required', text: 'Both Date From and Date To are required for range filtering.' });
                    return false;
                }
                if (date_from !== '' && date_to !== '' && date_to < date_from) {
                    Swal.fire({ icon: 'warning', title: 'Invalid Range', text: 'Date To cannot be before Date From.' });
                    return false;
                }
                dataTable.draw();
            });

            $('#reset-filter').on('click', function() {
                $('#filter-form')[0].reset();
                $('#service_provider_id, #road_code').val(null).trigger('change');
                dataTable.draw();
            });

            // 5. Select2 for Road Code
            @if ($customerPiiListUnlocked && $customerPiiUnlockSeconds > 0)
                window.setTimeout(function() {
                    var lockForm = document.getElementById('application-pii-lock-form');
                    if (lockForm) {
                        lockForm.submit();
                    }
                }, {{ $customerPiiUnlockSeconds * 1000 }});
            @endif
            $('#road_code').prepend('<option selected=""></option>').select2({
                ajax: {
                    url: "{{ route('roadlines.get-road-names') }}",
                    data: function(params) { return { search: params.term, page: params.page || 1 }; },
                },
                placeholder:'{{ __('Street Name / Street Code') }}',
                allowClear: true,
                width: '100%'
            });

            // 6. PDF Export Logic
            $('#pdf').click(function() {
                var year_sel = localStorage.getItem('year_select') || $('#year_select').val();
                var month_sel = localStorage.getItem('month_select') || $('#month_select').val();
                if(!year_sel || !month_sel) return;
                window.globalLoader.download(
                    `application/pdf/${year_sel}/${month_sel}/monthly-report`,
                    'Monthly Report.pdf',
                    this
                ).catch(function () {});
            });

        });
    </script>
@endpush
