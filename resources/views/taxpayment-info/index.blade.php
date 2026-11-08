@extends('layouts.dashboard')
@section('title', __('Property Tax Collection Information Support System'))
@push('style')
<style type="text/css">
.dataTables_filter {
    display: none;
}
</style>
@endpush
@section('content')
@if (!$propertyTaxPiiUnlocked && $canUnlockPropertyTaxPii)
<div class="modal fade" id="property-tax-pii-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <form method="POST" action="{{ route('tax-payment-pii.unlock') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('View Property Tax Owner PII') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Owner names will be available for five minutes.') }}</p>
                <label for="property_tax_pii_password">{{ __('Current Password') }}</label>
                <input id="property_tax_pii_password" name="current_password" type="password"
                    class="form-control @error('property_tax_pii_password') is-invalid @enderror"
                    required maxlength="255" autocomplete="current-password">
                @error('property_tax_pii_password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-info">{{ __('View PII Information') }}</button>
            </div>
        </form>
    </div></div>
</div>
@endif

@if ($canExportPropertyTaxPii)
<div class="modal fade" id="property-tax-pii-export-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <form id="property-tax-pii-export-form" method="POST" action="{{ route('tax-payment-pii.export') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Export Property Tax Owner PII') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    {{ __('The downloaded CSV contains sensitive owner information. Handle it securely and delete it when it is no longer required.') }}
                </div>
                <div class="form-group">
                    <label for="tax_code_file">{{ __('Tax Code List CSV') }}</label>
                    <input id="tax_code_file" type="file" accept=".csv,text/csv" class="form-control-file" required>
                    <input id="tax_code_csv" name="tax_code_csv" type="hidden" value="">
                    <small class="form-text text-muted">
                        {{ __('Upload a CSV containing a tax_code header and one Tax Code per row.') }}
                    </small>
                    <div id="tax-code-file-validation" class="small mt-1" role="status"></div>
                </div>
                <div class="form-group mb-0">
                    <label for="property_tax_export_password">{{ __('Current Password') }}</label>
                    <input id="property_tax_export_password" name="property_tax_export_password"
                        type="password" class="form-control" required maxlength="255"
                        autocomplete="current-password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button id="property-tax-pii-export-submit" type="submit" class="btn btn-danger" disabled>
                    {{ __('Export Owner PII') }}
                </button>
            </div>
        </form>
    </div></div>
</div>
@endif

<div class="card">
    <div class="card-header">
    @can('Import Property Tax Collection From CSV')
      <a href="{{ route('tax-payment.create') }}" class="btn btn-info">{{__('Import from CSV')}} </a>
    @endcan
    @can('Export Property Tax Collection Info')
    <a href="/templates/property-tax-collection-iss-template.csv" download="Property Tax Collection Information Support System-Template.csv" class="btn btn-info">{{__('Download CSV Template')}}</a>
    @endcan
    @can('Export Property Tax Collection Info')
      <a href="{{ route('tax-payment.export') }}" id="export" class="btn btn-info">{{__('Export to CSV')}} </a>
      <a href="{{ route('tax-payment.exportunmatched') }}" id="exportunmatched" class="btn btn-info">{{__('Export Unmatched Records')}}</a>
      @endcan
      @if ($canExportPropertyTaxPii)
      <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#property-tax-pii-export-modal">
          {{ __('Export Owner PII') }}
      </button>
      @endif
      @if ($propertyTaxPiiUnlocked)
      <form id="property-tax-pii-lock-form" method="POST" action="{{ route('tax-payment-pii.lock') }}" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-warning">{{ __('Lock Owner PII') }}</button>
      </form>
      <span class="badge badge-success">{{ __('Owner PII revealed for this list') }}</span>
      @elseif ($canUnlockPropertyTaxPii)
      <button type="button" class="btn btn-info" data-toggle="modal" data-target="#property-tax-pii-modal">
          {{ __('View PII Information') }}
      </button>
      @endif
      <a href="#" class="btn btn-info float-right" id="headingOne" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        {{__('Show Filter')}}
      </a>
    </div><!-- /.box-header -->
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
                                        <label for="code" class="control-label col-md-2"
                                            >{{__('Ward')}}</label>
                                        <div class="col-md-2" >
                                        <select class="form-control" id="ward_select">
                                        <option value="">{{__('Ward')}}</option>
                                        @foreach($wards as $key=>$value)
                                        <option value="{{$key}}">{{$value}}</option>
                                        @endforeach
                                      </select>
                                        </div>
                                        <label for="road_hier_select" class="control-label col-md-2 "
                                            >{{__('Years Due')}}</label>
                                        <div class="col-md-2" >
                                        <select class="form-control" id="dueyear_select">
                                        <option value="">{{__('Years Due')}}</option>
                                          @foreach($dueYears as $key=>$value)
                                          <option value="{{$key}}">{{$value}}</option>
                                          @endforeach
                                    </select>
                                        </div>
                                         <label for="bin" class="control-label col-md-2">{{__('BIN')}}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="bin"
                                                    placeholder="{{__('BIN')}}" 
                                                    oninput = "this.value = this.value.replace(/[^a-zA-Z0-9]/g, ''); "/> <!-- Allow only alphabetic and numeric characters -->
                                            </div> 
                                                                                   
                                    </div>
                                    <div class="form-group row">
                                        <label for="tax_code" class="control-label col-md-2">{{__('Tax Code')}}</label>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="tax_code"
                                                    placeholder="{{__('Tax Code')}}" 
                                                    oninput = "this.value = this.value.replace(/[^a-zA-Z0-9-]/g, ''); "/> <!-- Allow only alphabetic characters, numbers, and the hyphen (-) -->
                                            </div>
                                    </div>
                                    <div class="card-footer text-right">
                                        <button type="submit" class="btn btn-info ">{{__('Filter')}}</button>
                                        <button id="reset-filter" type="reset" class="btn btn-info">{{__('Reset')}}</button>
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
        </div>
        <!--- row !-->
    </div>
    <!--- card body !-->

    <div class="card-body">
        <div style="overflow: auto; width: 100%;">
            <table id="data-table" class="table table-bordered table-striped dtr-inline" width="100%">
                <thead>
                    <tr>
                    <th>{{__('Tax Code')}}</th>
                    <th>{{__('BIN')}}</th>
                    <th>{{__('Owner Name')}}</th>
                    <th>{{__('Years Due')}}</th>
                    <th>{{__('Ward')}}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div><!-- /.box-body -->
</div><!-- /.box -->
@stop

@push('scripts')
<script>
@if ($errors->has('property_tax_pii_password'))
$(function() { $('#property-tax-pii-modal').modal('show'); });
@endif

@if ($canExportPropertyTaxPii)
$(function() {
    var taxPiiCsvValid = false;
    var maxCodes = {{ (int) config('pii.export.max_tax_codes', 5000) }};
    var maxBytes = {{ (int) config('pii.export.max_file_kb', 2048) * 1024 }};

    function setTaxPiiStatus(message, valid) {
        $('#tax-code-file-validation').text(message)
            .toggleClass('text-success', valid)
            .toggleClass('text-danger', !valid);
    }

    function updateTaxPiiExportButton() {
        $('#property-tax-pii-export-submit').prop(
            'disabled',
            !taxPiiCsvValid || !$('#property_tax_export_password').val()
        );
    }

    function validateTaxCodeCsv(text) {
        var lines = text.replace(/^\uFEFF/, '').split(/\r?\n/);
        var headers = (lines.shift() || '').split(',').map(function(value) {
            return value.replace(/^"|"$/g, '').trim().toLowerCase();
        });
        var index = headers.indexOf('tax_code');
        if (index === -1) {
            return { valid: false, message: "{{ __('The CSV must contain a tax_code header.') }}" };
        }

        var codes = {};
        var count = 0;
        for (var row = 0; row < lines.length; row++) {
            if (!lines[row].trim()) continue;
            var code = (lines[row].split(',')[index] || '')
                .replace(/^"|"$/g, '').trim().toUpperCase();
            if (!code || code.length > 100 || !/^[A-Z0-9_-]+$/.test(code)) {
                return {
                    valid: false,
                    message: "{{ __('Invalid Tax Code on CSV row') }}" + ' ' + (row + 2) + '.'
                };
            }
            if (!codes[code]) {
                codes[code] = true;
                count++;
            }
            if (count > maxCodes) {
                return { valid: false, message: "{{ __('The CSV contains too many Tax Codes.') }}" };
            }
        }
        return count
            ? { valid: true, message: count + " {{ __('unique Tax Codes validated.') }}" }
            : { valid: false, message: "{{ __('The CSV does not contain any Tax Codes.') }}" };
    }

    $('#tax_code_file').on('change', function() {
        var file = this.files && this.files[0];
        taxPiiCsvValid = false;
        $('#tax_code_csv').val('');
        updateTaxPiiExportButton();

        if (!file || !/\.csv$/i.test(file.name)) {
            setTaxPiiStatus("{{ __('Select a valid CSV file.') }}", false);
            return;
        }
        if (file.size > maxBytes) {
            setTaxPiiStatus("{{ __('The Tax Code list CSV is too large.') }}", false);
            return;
        }

        var reader = new FileReader();
        reader.onload = function(event) {
            var text = String(event.target.result || '');
            var result = validateTaxCodeCsv(text);
            taxPiiCsvValid = result.valid;
            $('#tax_code_csv').val(result.valid ? text : '');
            setTaxPiiStatus(result.message, result.valid);
            updateTaxPiiExportButton();
        };
        reader.onerror = function() {
            setTaxPiiStatus("{{ __('The Tax Code list CSV could not be read.') }}", false);
        };
        reader.readAsText(file);
    });

    $('#property_tax_export_password').on('input', updateTaxPiiExportButton);

    $('#property-tax-pii-export-form').on('submit', function(event) {
        event.preventDefault();
        var form = $(this);
        if (!taxPiiCsvValid || form.data('submitting')) return;

        form.data('submitting', true);
        $('#property-tax-pii-export-submit').prop('disabled', true).text("{{ __('Exporting...') }}");

        fetch(form.attr('action'), {
            method: 'POST',
            body: new FormData(form[0]),
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json, text/csv', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(response) {
            if (!response.ok) {
                return response.json().catch(function() { return {}; }).then(function(error) {
                    var message = error.message;
                    if (error.errors) {
                        var keys = Object.keys(error.errors);
                        if (keys.length && error.errors[keys[0]].length) message = error.errors[keys[0]][0];
                    }
                    throw new Error(message || "{{ __('The Property Tax owner PII export could not be generated.') }}");
                });
            }
            var disposition = response.headers.get('Content-Disposition') || '';
            var match = disposition.match(/filename\*?=(?:UTF-8''|["'])?([^"';]+)/i);
            var filename = match ? decodeURIComponent(match[1].replace(/["']/g, '')) : 'property-tax-owner-pii.csv';
            return response.blob().then(function(blob) { return { blob: blob, filename: filename }; });
        }).then(function(download) {
            var url = URL.createObjectURL(download.blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = download.filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(function() { URL.revokeObjectURL(url); }, 1000);

            $('#property-tax-pii-export-modal').modal('hide');
            form[0].reset();
            taxPiiCsvValid = false;
            $('#tax_code_csv').val('');
            $('#tax-code-file-validation').text('').removeClass('text-success text-danger');
            Swal.fire({
                title: "{{ __('Owner PII exported successfully') }}",
                text: "{{ __('The CSV was downloaded. Store it securely and delete it when no longer required.') }}",
                icon: 'success'
            });
        }).catch(function(error) {
            Swal.fire({ title: "{{ __('Export failed') }}", text: error.message, icon: 'error' });
        }).finally(function() {
            form.data('submitting', false);
            $('#property-tax-pii-export-submit').text("{{ __('Export Owner PII') }}");
            updateTaxPiiExportButton();
            $('#property_tax_export_password').val('');
        });
    });
});
@endif

$(function() {
    var dataTable = $('#data-table').DataTable({
        bFilter: false,
        processing: true,
        serverSide: true,
        scrollCollapse: true,
        "bStateSave": true,
        "stateDuration": 1800, // In seconds; keep state for half an hour

        ajax: {
          url: '{!! url("tax-payment/data") !!}',
            data: function(d) {
              d.ward_select = $('#ward_select').val();
            d.dueyear_select = $('#dueyear_select').val();
            d.tax_code = $('#tax_code').val();
            d.bin = $('#bin').val();
            }
        },
        columns: [
            { data: 'tax_code', name: 'tax_code' },
            { data: 'bin', name: 'bin' },
            { data: 'owner_name', name: 'owner_name' },
            { data: 'name', name: 'name' },
            { data: 'ward', name: 'ward' }
        ]
    }).on('draw', function() {
      $('.delete').on('click', function(e) {

      var form =  $(this).closest("form");
      event.preventDefault();
      swal({
          title: '{{__(`Are you sure you want to delete this record?`)}}',
          text: '{{__("If you delete this, it will be gone forever.")}}',
          icon: "warning",
          buttons: true,
          dangerMode: true,
      })
      .then((willDelete) => {
        if (willDelete) {
          form.submit();
        }
      })
      });
    });

    var ward_select = '', dueyear_select = '';


    $('#filter-form').on('submit', function(e) {
     
        e.preventDefault();
        dataTable.draw();
        ward_select = $('#ward_select').val();
      dueyear_select = $('#dueyear_select').val();
      tax_code = $('#tax_code').val();
      bin = $('#bin').val();
    });
    filterDataTable(dataTable);
    resetDataTable(dataTable);
    //  $('#data-table_filter input[type=search]').attr('readonly', 'readonly');

    $("#export").on("click", function(e) {
        e.preventDefault();
        var searchData = $('input[type=search]').val();
        var  ward_select = $('#ward_select').val();
        var dueyear_select = $('#dueyear_select').val();
        var tax_code = $('#tax_code').val();
        var bin = $('#bin').val();
        window.location.href = "{!! url('tax-payment/export?searchData=') !!}"+ searchData + 
        "&ward=" + ward_select + 
        "&due_year=" + dueyear_select + 
        "&tax_code=" + tax_code +
        "&bin=" + bin 
    });

    $("#exportunmatched").on("click", function(e) {
        e.preventDefault();
        window.location.href = "{!! url('tax-payment/exportunmatched') !!}";
    });

    @if ($propertyTaxPiiUnlocked && $propertyTaxPiiUnlockSeconds > 0)
    window.setTimeout(function() {
        var form = document.getElementById('property-tax-pii-lock-form');
        if (form) form.submit();
    }, {{ $propertyTaxPiiUnlockSeconds * 1000 }});
    @endif
 


});
</script>

@endpush
