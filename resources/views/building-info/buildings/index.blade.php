@extends('layouts.dashboard')
@section('title', $page_title)


@section('content')
    @php
        $piiExportUser = auth()->user();
        $canExportOwnerPii = $piiExportUser
            && $piiExportUser->can('List Building Structures')
            && $piiExportUser->can('View Owner PII')
            && $piiExportUser->can('Unlock Owner PII')
            && $piiExportUser->can('Export Owner PII');
        // Full-dataset export is deliberately narrower than selected-BIN
        // export because Owner/Building rows do not contain municipality_id.
        $canExportAllOwnerPii = $canExportOwnerPii
            && $piiExportUser->hasAnyRole([
                'Super Admin',
                'Municipality - Super Admin',
                'Municipality - IT Admin',
            ]);
    @endphp

    @if (!$piiListUnlocked && $canUnlockOwnerPiiList)
        <div class="modal fade" id="reveal-owner-pii-list-modal" tabindex="-1" role="dialog"
            aria-labelledby="reveal-owner-pii-list-title" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="pii-list-unlock-form" method="POST" action="{{ route('owner-pii.unlock-list') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="reveal-owner-pii-list-title">
                                {{ __('Reveal All Owner PII') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>
                                {{ __('This will reveal owner names in the building data table.') }}
                            </p>
                            <p class="mb-0">
                                {{ __('Access lasts five minutes and applies to the list and Building Details pages. Users with building-edit permission may also edit owner PII. Access will be recorded in the security audit log.') }}
                            </p>
                            <div class="form-group mt-3 mb-0">
                                <label for="current_password">
                                    {{ __('Current Password') }}
                                </label>
                                <input id="current_password" name="current_password" type="password"
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    required maxlength="255" autocomplete="current-password"
                                    aria-describedby="current-password-help">
                                <small id="current-password-help" class="form-text text-muted">
                                    {{ __('Re-enter your login password to confirm access. The password is not stored.') }}
                                </small>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                {{ __('Cancel') }}
                            </button>
                            <button id="pii-list-unlock-submit" type="submit" class="btn btn-info">
                                {{ __('Reveal for 5 Minutes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canExportOwnerPii)
        <div class="modal fade" id="export-owner-pii-modal" tabindex="-1" role="dialog"
            aria-labelledby="export-owner-pii-title" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="owner-pii-export-form" method="POST"
                        action="{{ route('owner-pii.export') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="export-owner-pii-title">
                                {{ __('Export Owner PII') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal"
                                aria-label="{{ __('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                {{ __('The downloaded CSV contains sensitive owner information. Handle it securely and delete it when it is no longer required.') }}
                            </div>
                            <div class="form-group">
                                <label class="d-block">{{ __('Export Scope') }}</label>
                                @if ($canExportAllOwnerPii)
                                    <div class="custom-control custom-radio mb-2">
                                        <input id="owner_pii_export_mode_all" name="export_mode" type="radio"
                                            value="all" class="custom-control-input" required>
                                        <label class="custom-control-label" for="owner_pii_export_mode_all">
                                            {{ __('Export all authorized owners') }}
                                        </label>
                                    </div>
                                @endif
                                <div class="custom-control custom-radio">
                                    <input id="owner_pii_export_mode_bin_list" name="export_mode" type="radio"
                                        value="bin_list" class="custom-control-input" required>
                                    <label class="custom-control-label" for="owner_pii_export_mode_bin_list">
                                        {{ __('Export owners from BIN list') }}
                                    </label>
                                </div>
                                @error('export_mode')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            @if ($canExportAllOwnerPii)
                                <div id="owner-pii-export-all-confirmation" class="alert alert-danger" style="display: none">
                                    <p class="mb-2">
                                        {{ __('This will export every active owner record available in this municipality installation. No BIN filter will be applied.') }}
                                    </p>
                                    <div class="custom-control custom-checkbox">
                                        <input id="confirm_export_all" name="confirm_export_all" type="checkbox"
                                            value="1" class="custom-control-input">
                                        <label class="custom-control-label" for="confirm_export_all">
                                            {{ __('I understand and intend to export all owner PII.') }}
                                        </label>
                                    </div>
                                    @error('confirm_export_all')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                            <div id="owner-pii-bin-list-fields" class="form-group" style="display: none">
                                <label for="bin_file">{{ __('BIN List CSV') }}</label>
                                <input id="bin_file" type="file" accept=".csv,text/csv"
                                    class="form-control-file @if ($errors->has('bin_file') || $errors->has('bin_csv')) is-invalid @endif">
                                <input id="bin_csv" name="bin_csv" type="hidden" value="">
                                <small class="form-text text-muted">
                                    {{ __('Upload a CSV containing a bin header and one BIN per row.') }}
                                </small>
                                <div id="bin-file-validation" class="small mt-1" role="status"
                                    aria-live="polite"></div>
                                @error('bin_file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('bin_csv')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-0">
                                <label for="export_password">{{ __('Current Password') }}</label>
                                <input id="export_password" name="export_password" type="password"
                                    class="form-control @error('export_password') is-invalid @enderror"
                                    required maxlength="255" autocomplete="current-password">
                                <small class="form-text text-muted">
                                    {{ __('Re-enter your login password. It is verified but never stored.') }}
                                </small>
                                @error('export_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                {{ __('Cancel') }}
                            </button>
                            <button id="owner-pii-export-submit" type="submit" class="btn btn-danger" disabled>
                                {{ __('Export Owner PII') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="containmentsModal" tabindex="-1" role="dialog" aria-labelledby="containmentsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="containmentsModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            @can('Add Building Structure')
                <a href="{{ action('BuildingInfo\BuildingController@create') }}"
                    class="btn btn-info">{{ __('Add Building') }}</a>
            @endcan
            @can('Export Building Structures')
                <a href="{{ action('BuildingInfo\BuildingController@export') }}" id="export"
                    class="btn btn-info">{{ __('Export to CSV') }}</a>
            @endcan
            @can('Export Building Structures')
                <a href="#" id="export-shp" class="btn btn-info">{{ __('Export to Shape File') }}</a>
            @endcan
            @can('Export Building Structures')
                <a href="#" id="export-kml" class="btn btn-info">{{ __('Export to KML') }}</a>
            @endcan
            @if ($canExportOwnerPii)
                <button type="button" class="btn btn-info" data-toggle="modal"
                    data-target="#export-owner-pii-modal">
                    {{ __('Export Owner PII') }}
                </button>
            @endif

            @if ($piiListUnlocked)
                <form id="pii-list-lock-form" method="POST" action="{{ route('owner-pii.lock') }}"
                    class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        {{ __('Lock Owner PII') }}
                    </button>
                </form>
                {{-- Present reveal state as a full toolbar-sized status instead
                    of a small badge that can be missed beside the action buttons. --}}
                <span class="btn btn-success disabled ml-1" role="status"
                    aria-label="{{ __('Owner PII is currently visible') }}">
                    <i class="fas fa-eye mr-1" aria-hidden="true"></i>
                    {{ __('Owner PII is visible') }}
                </span>
            @elseif ($canUnlockOwnerPiiList)
                <button type="button" class="btn btn-info" data-toggle="modal"
                    data-target="#reveal-owner-pii-list-modal">
                    {{ __('View PII Information') }}
                </button>
            @endif

            <a href="#" class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse"
                data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                {{ __('Show Filter') }}
            </a>
        </div><!-- /.card-header -->
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <form class="form-horizontal" id="filter-form">
                                        <div class="form-group row">
                                            <label for="bin_text" class="control-label col-md-2">{{ __('BIN') }}
                                            </label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="bin_text"
                                                    placeholder="{{ __('BIN') }}"
                                                    oninput = "this.value = this.value.replace(/[^a-zA-Z0-9]/g, ''); " />
                                                <!-- Allow only alphabetic and numeric characters -->
                                            </div>
                                            <label for="structype_select" class="control-label col-md-2">
                                                {{ __('Structure Type') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="structype_select">
                                                    <option value="">{{ __('Structure Type') }}</option>
                                                    @foreach ($structure_type as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label for="ward_select"
                                                class="control-label col-md-2">{{ __('Ward Number') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="ward_select">
                                                    <option value="">{{ __('Ward Number') }}</option>
                                                    @foreach ($ward as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="functional_use_select"
                                                class="control-label col-md-2">{{ __('Functional Use') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="functional_use_select">
                                                    <option value="">{{ __('Functional Use') }}</option>
                                                    @foreach ($functional_use as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label for="use_category_select"
                                                class="control-label col-md-2">{{ __('Use Category') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="use_category_select">
                                                    <option value="">{{ __('Use Category') }}</option>
                                                </select>
                                            </div>
                                            {{-- Road Code remains visible in the middle of the
                                                filter grid even while the PII-only owner-name
                                                filter is unavailable. --}}
                                            <label for="road_code"
                                                class="control-label col-md-2">{{ __('Road Code') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="road_code">
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="toilet"
                                                class="control-label col-md-2">{{ __('Presence of Toilet') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="toilet">
                                                    <option value="">{{ __('Presence of Toilet') }}</option>
                                                    <option value="Yes">Yes</option>
                                                    <option value="No">No</option>
                                                </select>
                                            </div>

                                            <label for="sanitation_system_id"
                                                class="control-label col-md-2">{{ __('Sanitation Systems') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="sanitation_system_id">
                                                    <option value="">{{ __('Sanitation Systems') }}</option>
                                                    @foreach ($sanitation_systems as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label for="watersourc"
                                                class="control-label col-md-2">{{ __('Main Drinking Water Source') }}</label>

                                            <div class="col-md-2">
                                                <select class="form-control" id="watersourc">
                                                    <option value="">{{ __('Main Drinking Water Source') }}</option>
                                                    @foreach ($water_sources as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="well_prese"
                                                class="control-label col-md-2">{{ __('Well in Premises') }}</label>
                                            <div class="col-md-2">
                                                <select class="form-control" id="well_prese">
                                                    <option value="">{{ __('Well in Premises') }}</option>
                                                    <option value="Yes">Yes</option>
                                                    <option value="No">No</option>
                                                </select>
                                            </div>
                                            <label for="floor_count"
                                                class="control-label col-md-2">{{ __('Number of Floors') }}
                                            </label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="floor_count"
                                                    placeholder="{{ __('Number of Floors') }}" />
                                            </div>
                                            <label for="house_number"
                                                class="control-label col-md-2">{{ __('House Number') }}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="house_number"
                                                    placeholder="{{ __('House Number') }}
                                                    oninput = "this.value=this.value.replace(/[^a-zA-Z0-9-]/g, ''
                                                    ); " />
                                                    <!-- Allow only alphabetic characters, numbers, and the hyphen (-) -->
                                                </div>
                                            </div>


                                            <div class="form-group row">
                                                <label for="date_from" class="control-label col-md-2"> {{ __('Construction Date From') }}</label>
                                                <div class="col-md-2">
                                                    <input type="date" class="form-control" id="date_from"
                                                        placeholder=" {{ __('Construction Date From') }}"
                                                        onclick = 'this.showPicker();' />
                                                </div>
                                                <label for="date_to" class="control-label col-md-2">{{ __('Construction Date To') }}</label>

                                                <div class="col-md-2">
                                                    <input type="date" class="form-control" id="date_to"
                                                        placeholder="{{ __('Construction Date To') }}" onclick = 'this.showPicker();' />
                                                </div>
                                                {{-- Owner Name is last because it exists only
                                                    during an authorized PII reveal. Hiding it no
                                                    longer leaves a gap between normal filters. --}}
                                                @if ($piiListUnlocked)
                                                    <label for="owner_name"
                                                        class="control-label col-md-2">{{ __('Owner Name') }}</label>
                                                    <div class="col-md-2">
                                                        <input type="text" class="form-control" id="owner_name"
                                                            maxlength="100" autocomplete="off"
                                                            placeholder="{{ __('Owner Name') }}" />
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="card-footer text-right">
                                                <button type="submit" class="btn btn-info">{{ __('Filter') }}</button>
                                                <button type="reset" id="reset-filter" class="btn btn-info">{{ __('Reset') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                    <!--- accordion body!-->
                                </div>
                                <!--- collapseOne!-->
                            </div>
                            <!--- accordion item!-->
                        </div>
                        <!--- accordion !-->
                    </div>
                    <!---col!-->
                </div>
                <!--- row !-->
            </div>
            <!--- card body !-->
            <div class="card-body">

                <div style="overflow: auto; width: 100%;">

                    <table id="data-table" class="table table-bordered table-striped dataTable dtr-inline" width="100%">
                        <thead>
                            <tr>
                                <th>{{ __('BIN') }}</th>
                                <th>{{ __('House Number') }}</th>
                                <th>{{ __('Road Code') }}</th>
                                <th>{{ __('Ward Number') }}</th>
                                <th>{{ __('Structure Type') }}</th>
                                <th>{{ __('Number of Floors') }}</th>
                                <th>{{ __('Presence of Toilet') }}</th>
                                <th>{{ __('Sanitation System') }}</th>
                                <th>{{ __('Owner Name') }}</th>
                                <th>{{ __('Actions') }}</th>

                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div> <!-- /.card -->

@stop


@push('scripts')
    <script>
        $.fn.dataTable.ext.errMode = 'throw';
        $(function() {

            @if ($errors->has('current_password'))
                $('#reveal-owner-pii-list-modal').modal('show');
            @endif

            @if ($errors->has('export_mode') || $errors->has('confirm_export_all') || $errors->has('bin_file') || $errors->has('bin_csv') || $errors->has('export_password'))
                $('#export-owner-pii-modal').modal('show');
            @endif

            var ownerPiiCsvIsValid = false;
            var ownerPiiMaxFileBytes = {{ max(1, (int) config('pii.export.max_file_kb', 2048)) * 1024 }};
            var ownerPiiMaxBins = {{ max(1, (int) config('pii.export.max_bins', 5000)) }};

            function updateOwnerPiiExportButton() {
                var exportMode = $('input[name="export_mode"]:checked').val();
                var passwordPresent = $('#export_password').val().length > 0;
                var scopeIsReady = exportMode === 'all'
                    ? $('#confirm_export_all').is(':checked')
                    : exportMode === 'bin_list' && ownerPiiCsvIsValid;

                // The button requires an explicit scope. A missing/invalid CSV
                // cannot fall through to a full export, and full export also
                // requires its separate acknowledgement checkbox.
                $('#owner-pii-export-submit').prop(
                    'disabled',
                    !scopeIsReady || !passwordPresent
                );
            }

            function resetOwnerPiiExportMode() {
                ownerPiiCsvIsValid = false;
                $('#bin_file').val('');
                $('#bin_csv').val('');
                $('#confirm_export_all').prop('checked', false);
                $('#owner-pii-bin-list-fields, #owner-pii-export-all-confirmation').hide();
                $('#bin-file-validation').text('')
                    .removeClass('text-success text-danger');
                updateOwnerPiiExportButton();
            }

            $('input[name="export_mode"]').on('change', function() {
                var exportMode = $(this).val();

                // Switching modes clears all previous CSV/confirmation state.
                // This prevents stale hidden CSV content from being submitted
                // with an intentional full export (or vice versa).
                ownerPiiCsvIsValid = false;
                $('#bin_file').val('');
                $('#bin_csv').val('');
                $('#confirm_export_all').prop('checked', false);
                $('#bin-file-validation').text('')
                    .removeClass('text-success text-danger');
                $('#owner-pii-bin-list-fields').toggle(exportMode === 'bin_list');
                $('#owner-pii-export-all-confirmation').toggle(exportMode === 'all');
                updateOwnerPiiExportButton();
            });

            function showOwnerPiiCsvStatus(message, valid) {
                $('#bin-file-validation')
                    .toggleClass('text-success', valid)
                    .toggleClass('text-danger', !valid)
                    .text(message);
            }

            function parseOwnerPiiCsvLine(line) {
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

            function validateOwnerPiiCsv(csvText) {
                var lines = csvText.replace(/^\uFEFF/, '').split(/\r?\n/);
                var header = parseOwnerPiiCsvLine(lines.shift() || '')
                    .map(function(value) { return value.trim().toLowerCase(); });
                var binIndex = header.indexOf('bin');

                if (binIndex === -1) {
                    return { valid: false, message: "{{ __('The CSV must contain a bin header.') }}" };
                }

                var uniqueBins = {};
                var binCount = 0;

                for (var rowIndex = 0; rowIndex < lines.length; rowIndex++) {
                    if (lines[rowIndex].trim() === '') {
                        continue;
                    }

                    var row = parseOwnerPiiCsvLine(lines[rowIndex]);
                    var bin = (row[binIndex] || '').trim().toUpperCase();

                    if (!bin || bin.length > 100 || !/^[A-Z0-9_-]+$/.test(bin)) {
                        return {
                            valid: false,
                            message: "{{ __('Invalid BIN on CSV row') }}" + ' ' + (rowIndex + 2) + '.'
                        };
                    }

                    if (!uniqueBins[bin]) {
                        uniqueBins[bin] = true;
                        binCount++;
                    }

                    if (binCount > ownerPiiMaxBins) {
                        return {
                            valid: false,
                            message: "{{ __('The CSV contains too many unique BIN values.') }}"
                        };
                    }
                }

                if (binCount === 0) {
                    return { valid: false, message: "{{ __('The CSV does not contain any BIN values.') }}" };
                }

                return {
                    valid: true,
                    message: binCount + " {{ __('unique BIN values validated.') }}"
                };
            }

            $('#bin_file').on('change', function() {
                var file = this.files && this.files.length ? this.files[0] : null;
                var reader;

                ownerPiiCsvIsValid = false;
                $('#bin_csv').val('');
                updateOwnerPiiExportButton();

                if (!file) {
                    showOwnerPiiCsvStatus("{{ __('Select a CSV file.') }}", false);
                    return;
                }

                if (!/\.csv$/i.test(file.name)) {
                    showOwnerPiiCsvStatus("{{ __('The BIN list must be a CSV file.') }}", false);
                    return;
                }

                if (file.size > ownerPiiMaxFileBytes) {
                    showOwnerPiiCsvStatus("{{ __('The BIN list CSV is too large.') }}", false);
                    return;
                }

                reader = new FileReader();
                reader.onload = function(event) {
                    var csvText = String(event.target.result || '');
                    var result = validateOwnerPiiCsv(csvText);

                    ownerPiiCsvIsValid = result.valid;
                    $('#bin_csv').val(result.valid ? csvText : '');
                    showOwnerPiiCsvStatus(result.message, result.valid);
                    updateOwnerPiiExportButton();
                };
                reader.onerror = function() {
                    showOwnerPiiCsvStatus("{{ __('The BIN list CSV could not be read.') }}", false);
                    updateOwnerPiiExportButton();
                };
                reader.readAsText(file);
            });

            $('#export_password, #confirm_export_all').on(
                'input change',
                updateOwnerPiiExportButton
            );

            $('#owner-pii-export-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var exportMode = $('input[name="export_mode"]:checked').val();
                var scopeIsReady = exportMode === 'all'
                    ? $('#confirm_export_all').is(':checked')
                    : exportMode === 'bin_list' && ownerPiiCsvIsValid;

                if (!scopeIsReady || form.data('submitting')) {
                    return;
                }

                form.data('submitting', true);
                $('#owner-pii-export-submit')
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
                            return { message: "{{ __('The owner PII export could not be generated.') }}" };
                        }).then(function(error) {
                            throw new Error(error.message || "{{ __('The owner PII export could not be generated.') }}");
                        });
                    }

                    var disposition = response.headers.get('Content-Disposition') || '';
                    var filenameMatch = disposition.match(/filename\*?=(?:UTF-8''|["'])?([^"';]+)/i);
                    var filename = filenameMatch
                        ? decodeURIComponent(filenameMatch[1].replace(/["']/g, ''))
                        : 'owner-pii-export.csv';

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

                    $('#export-owner-pii-modal').modal('hide');
                    form[0].reset();
                    form.data('submitting', false);
                    resetOwnerPiiExportMode();
                    $('#owner-pii-export-submit')
                        .text("{{ __('Export Owner PII') }}");
                    updateOwnerPiiExportButton();

                    Swal.fire({
                        title: "{{ __('Owner PII exported successfully') }}",
                        text: "{{ __('The CSV was downloaded. Store it securely and delete it when it is no longer required.') }}",
                        icon: 'success',
                        confirmButtonColor: '#3085d6'
                    });
                }).catch(function(error) {
                    form.data('submitting', false);
                    $('#owner-pii-export-submit')
                        .text("{{ __('Export Owner PII') }}");
                    updateOwnerPiiExportButton();

                    Swal.fire({
                        title: "{{ __('Export failed') }}",
                        text: error.message,
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                });
            });

            $('#pii-list-unlock-form').on('submit', function(e) {
                var form = $(this);

                if (form.data('submitting')) {
                    e.preventDefault();
                    return;
                }

                form.data('submitting', true);
                $('#pii-list-unlock-submit')
                    .prop('disabled', true)
                    .text("{{ __('Revealing...') }}");
            });

            var bin = '';
            var house_number = '';
            var structype = '';
            var ward = '';
            var functional_use = '';
            var roadcd = '';
            var sanitation_system_id = '';
            var floor_count = '';

            var dataTable = $('#data-table').DataTable({

                bFilter: false,
                processing: true,
                serverSide: true,
                scrollCollapse: true,
                "bStateSave": true,
                "stateDuration": 1800, // In seconds; keep state for half an hour
                ajax: {
                    url: '{!! url('building-info/buildings/data') !!}',
                    data: function(d) {
                        d.bin = $('#bin_text').val();
                        d.house_number = $('#house_number').val();
                        d.structype = $('#structype_select').val();
                        d.ward = $('#ward_select').val();
                        d.functional_use = $('#functional_use_select').val();
                        d.roadcd = $('#road_code').val();
                        d.toilet = $('#toilet').val();
                        d.defecation = $('#defecation').val();
                        d.toiletconn = $('#toiletconn').val();
                        d.watersourc = $('#watersourc').val();
                        d.well_prese = $('#well_prese').val();
                        @if ($piiListUnlocked)
                            d.ownername = $('#owner_name').val();
                        @endif
                        d.sanitation_system_id = $('#sanitation_system_id').val();
                        d.floor_count = $('#floor_count').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                        d.use_category_select = $('#use_category_select').val();



                    }
                },
                columns: [{
                        data: 'bin',
                        name: 'bin'
                    },
                    {
                        data: 'house_number',
                        name: 'house_number'
                    },
                    {
                        data: 'road_code',
                        name: 'road_code'
                    },
                    {
                        data: 'ward',
                        name: 'ward'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'floor_count',
                        name: 'floor_count'
                    },
                    {
                        data: 'toilet_status',
                        name: 'toilet_status'
                    },
                    {
                        data: 'sanitation_system_id',
                        name: 'sanitation_system_id'
                    },
                    {
                        data: 'owner_name',
                        name: 'owner_name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ]
            }).on('draw', function() {
                $('.delete').on('click', function(e) {
                var form = $(this).closest("form");
                event.preventDefault();
                Swal.fire({
                    title: "{{__('Are you sure?')}}",
                    text: "{!! __('You won\'t be able to revert this!') !!}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Yes, delete it!') }}",
                    cancelButtonText: '{{ __('Cancel') }}',
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                })
            });
        });

            @if ($piiListUnlocked && $piiListUnlockSeconds > 0)
                window.setTimeout(function() {
                    var lockForm = document.getElementById('pii-list-lock-form');

                    if (lockForm) {
                        lockForm.submit();
                    }
                }, {{ $piiListUnlockSeconds * 1000 }});
            @endif



            $(".sidebar-toggle").on("click", function() {
                dataTable.columns.adjust().draw(false);
            });


            var bin = '',
                house_number = '',
                structype = '',
                ward = '',
                roadcd = '';
            var toilet = '';
            var defecation = '';
            var toiletconn = '';
            var watersourc = '';
            var well_prese = '';
            var sanitation_system_id = '';
            var floor_count = '';
            var date_from = '';
            var date_to = '';

            $('#filter-form').on('submit', function(e) {

                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();

                if ((date_from !== '') && (date_to === '')) {

                    Swal.fire({
                        title: "{{ __('Construction Date To is required') }}",
                        text: "{{ __('Please Select Construction Date From') }}",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Close') }}"
                    });

                    return false;
                }
                if ((date_from === '') && (date_to !== '')) {

                    Swal.fire({
                        title: "{{ __('Construction Date From is Required') }}",
                        text: "{{ __('Please Select Construction Date To') }}",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Close') }}"
                    })

                    return false;
                }
                e.preventDefault();
                dataTable.draw();
                bin = $('#bin_text').val();
                house_number = $('#house_number').val();
                structype = $('#structype_select').val();
                ward = $('#ward_select').val();
                functional_use = $('#functional_use_select').val();
                roadcd = $('#road_code').val();
                toilet = $('#toilet').val();
                watersourc = $('#watersourc').val();
                well_prese = $('#well_prese').val();
                sanitation_system_id = $('#sanitation_system_id').val();
                floor_count = $('#floor_count').val();
                date_from = $('#date_from').val();
                date_to = $('#date_to').val();
                use_category_select = $('#use_category_select').val();
                date_from = $('#date_from').val();
                date_to = $('#date_to').val();

                //save filter data in local storage
            });

            $('#road_code').prepend('<option selected=""></option>').select2({
                ajax: {
                    url: "{{ route('roadlines.get-road-names') }}",
                    data: function(params) {
                        return {
                            search: params.term,
                            // ward: $('#ward').val(),
                            page: params.page || 1
                        };
                    },
                },
                placeholder: "{{ __('Road Code') }}",
                allowClear: true,
                closeOnSelect: true,
                width: '100%'
            });
            resetDataTable(dataTable);



            $("#export").on("click", function(e) {
                e.preventDefault();

                var searchData = $('input[type=search]').val();
                var bin = $('#bin_text').val();
                var house_number = $('#house_number').val();
                var structype = $('#structype_select').val();
                var ward = $('#ward_select').val();
                var roadcd = $('#road_code').val();
                var toilet = $('#toilet').val();
                var watersourc = $('#watersourc').val();
                var well_prese = $('#well_prese').val();
                var functional_use = $('#functional_use_select').val();
                var sanitation_system_id = $('#sanitation_system_id').val();
                var floor_count = $('#floor_count').val();
                var use_category_select = $('#use_category_select').val();
                var date_from = $('#date_from').val();
                var date_to = $('#date_to').val();
                window.location.href = "{!! url('building-info/buildings/export?searchData=') !!}" +
                    searchData +
                    "&bin=" + bin +
                    "&house_number=" + house_number +
                    "&structype=" + structype +
                    "&ward=" + ward +
                    "&roadcd=" + roadcd +
                    "&toilet=" + toilet +
                    "&watersourc=" + watersourc +
                    "&well_prese=" + well_prese +
                    "&functional_use=" + functional_use +
                    "&sanitation_system_id=" + sanitation_system_id +
                    "&floor_count=" + floor_count +
                    "&use_category_select=" + use_category_select +
                    "&date_from=" + date_from +
                    "&date_to=" + date_to;
            });

            var buildingNonPiiExportProperties = [
                'bin',
                'building_associated_to',
                'ward',
                'road_code',
                'house_number',
                'house_locality',
                'tax_code',
                'structure_type_name',
                'surveyed_date',
                'construction_year',
                'floor_count',
                'functional_use_name',
                'use_category_name',
                'office_business_name',
                'household_served',
                'male_population',
                'female_population',
                'other_population',
                'population_served',
                'diff_abled_male_pop',
                'diff_abled_female_pop',
                'diff_abled_others_pop',
                'lic_community',
                'water_source_name',
                'water_customer_id',
                'watersupply_pipe_code',
                'well_presence_status',
                'distance_from_well',
                'swm_customer_id',
                'toilet_status',
                'toilet_count',
                'household_with_private_toilet',
                'population_with_private_toilet',
                'building_sanitation_system',
                'sewer_code',
                'drain_code',
                'desludging_vehicle_accessible',
                'estimated_area',
                'toilet_name',
                'verification_status',
                'geom'
            ].join(',');

            $("#export-shp").on("click", function(e) {
                e.preventDefault();
                var cql_param = getCQLParams();
                window.location.href =
                    "{{ Config::get('constants.GEOSERVER_URL') }}wfs?service=WFS&version=1.0.0&request=GetFeature&authkey={{ Config::get('constants.AUTH_KEY') }}&typeName={{ Config::get('constants.GEOSERVER_WORKSPACE') }}:buildings_layer+&CQL_FILTER=" +
                    cql_param + "&PROPERTYNAME=" + encodeURIComponent(buildingNonPiiExportProperties) +
                    "&outputFormat=SHAPE-ZIP&format_options=filename:Buildings.zip";
            });
            $("#export-kml").on("click", function(e) {
                e.preventDefault();
                var cql_param = getCQLParams();
                window.location.href =
                    "{{ Config::get('constants.GEOSERVER_URL') }}wfs?service=WFS&version=1.0.0&request=GetFeature&authkey={{ Config::get('constants.AUTH_KEY') }}&typeName={{ Config::get('constants.GEOSERVER_WORKSPACE') }}:buildings_layer+&CQL_FILTER=" +
                    cql_param + "&PROPERTYNAME=" + encodeURIComponent(buildingNonPiiExportProperties) +
                    "&outputFormat=KML&format_options=filename:Buildings.kml";

            });

            function getCQLParams() {
                bin = $('#bin_text').val();
                structype = $('#structype_select').val();
                ward = $('#ward_select').val();
                functional_use = $('#functional_use_select').val();
                use_category_select = $('#functional_use_select').val();
                roadcd = $('#road_code').val();
                toilet = $('#toilet').val();
                defecation = $('#defecation').val();
                toiletconn = $('#toiletconn').val();
                watersourc = $('#watersourc').val();
                well_prese = $('#well_prese').val();
                sanitation_system_id = $('#sanitation_system_id').val();
                floor_count = $('#floor_count').val();
                house_number = $('#house_number').val();
                date_from = $('#date_from').val();
                date_to = $('#date_to').val();

                var cql_param = "deleted_at IS NULL";
                if (bin) {
                    cql_param += " AND bin ILIKE '%" + bin + "%'";
                }
                if (structype) {
                    cql_param += " AND structure_type_id = '" + structype + "'";
                }
                if (ward) {
                    cql_param += " AND ward = '" + ward + "'";
                }
                if (functional_use) {
                    cql_param += " AND functional_use_id = '" + functional_use + "'";
                }
                if (roadcd) {
                    cql_param += " AND road_code = '" + roadcd + "'";
                }

                if (toilet) {
                    cql_param += " AND toilet_status = '" + toilet + "'";
                }

                if (toiletconn) {
                    cql_param += " AND sanitation_system_type_id = '" + toiletconn + "'";
                }
                if (watersourc) {
                    cql_param += " AND water_source_id = '" + watersourc + "'";
                }
                if (well_prese) {
                    cql_param += " AND well_presence_status = '" + well_prese + "'";
                }
                if (sanitation_system_id) {
                    cql_param += " AND sanitation_system_id = '" + sanitation_system_id + "'";
                }
                if (floor_count) {
                    cql_param += " AND strConcat(floor_count,'')  ILIKE '" + floor_count + "%'";
                }
                if (house_number) {
                    cql_param += " AND house_number ILIKE '%" + house_number + "%'";
                }
                if (use_category_select) {
                    cql_param += " AND use_category_id = '" + use_category_select + "'";
                }
                if (date_from && date_to) {
                    cql_param += " AND construction_year BETWEEN '" + date_from.trim() + "' AND '" + date_to
                        .trim() + "'";
                }
                return encodeURI(cql_param);
            }
        });

        $(document).on('click', '.containment', function() {
            var modalHeader = $('#containmentsModal .modal-header');
            var modalBody = $('#containmentsModal .modal-body');
            var binId = $(this).data('id');
            $.ajax({
                url: 'buildings/' + binId + '/listContainments',
                type: 'GET',
                success: function(data) {
                    modalHeader.find('.modal-title').text(data.title);
                    modalBody.html(data.popContentsHtml);

                },
            });
        });


        document.getElementById('toilet').addEventListener('change', function() {
            const toiletPresence = this.value;
            const sanitationSelect = document.getElementById('sanitation_system_id');

            // Clear existing options
            sanitationSelect.innerHTML = '<option value="">Sanitation Systems</option>';

            if (toiletPresence === "Yes") {
                // Add options for IDs 1 to 8 and 11
                @foreach ($sanitation_systems as $key => $value)
                    @if (($key >= 1 && $key <= 8) || $key == 11)
                        sanitationSelect.innerHTML +=
                            `<option value="{{ $key }}">{{ $value }}</option>`;
                    @endif
                @endforeach
            } else if (toiletPresence === "No") {
                // Add remaining options (IDs not in 1 to 8 and 11)
                @foreach ($sanitation_systems as $key => $value)
                    @if (($key < 1 || $key > 8) && $key != 11)
                        sanitationSelect.innerHTML +=
                            `<option value="{{ $key }}">{{ $value }}</option>`;
                    @endif
                @endforeach
            }
        });




        $('#functional_use_select').on('change', function() {
            var functionalUseId = $(this).val();

            // Generate the URL correctly with the parameter
            var url = '{!! route('functionaluse.getusecat', ':functionalUseId') !!}'.replace(':functionalUseId', functionalUseId);

            $('#use_category_select').empty().append('<option value="">Use Category</option>');

            if (functionalUseId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $.each(data, function(id, name) {
                            // Populate the dropdown with options
                            $('#use_category_select').append('<option value="' + id + '">' +
                                name + '</option>');
                        });
                    },
                    error: function() {
                        alert('Error fetching use categories.');
                    }
                });
            }
        });

        // Add change event for the second dropdown to show input dynamically
        $('#use_category_select').on('change', function() {
            var selectedOption = $(this).val();

            // Remove any previously added input field
            $('#dynamic-input').remove();

            // Check the condition and add input field if needed
            if (selectedOption) {
                $('<div id="dynamic-input" class="col-md-2">' +

                    '</div>').insertAfter('#use_category_select');
            }
        });
    </script>
@endpush
