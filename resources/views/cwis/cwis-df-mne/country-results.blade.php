<div class="panel-group m-3" id="accordion" role="tablist" aria-multiselectable="true">
    @foreach($countryIndicators->groupBy('outcome') as $outcome => $indicators)
        @php($outcomeLabel = $outcome === 'rpm' ? __('Resource Planning and Management') : __(ucfirst($outcome)))
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><button type="button" class="btn btn-link" data-toggle="collapse" data-target="#cwis-outcome-{{ $loop->index }}" aria-expanded="true">{{ $outcomeLabel }}</button></h5>
            </div>
            <div id="cwis-outcome-{{ $loop->index }}" class="collapse show">
                <div class="card-body table-responsive">
                    <table class="table table-bordered" width="100%">
                        <thead><tr><th width="60%">{{ __('Indicators') }}</th><th width="15%">{{ __('Outcome') }}</th><th width="25%">{{ __('Value') }}</th></tr></thead>
                        <tbody>
                        @foreach($indicators as $indicator)
                            <tr data-indicator-code="{{ $indicator->indicator_code }}">
                                <td>{{ __($indicator->label) }}</td><td>{{ $outcomeLabel }}</td>
                                <td><input type="text" class="form-control" value="{{ $data[$indicator->indicator_code] ?? __('No data') }}" aria-label="{{ __($indicator->label) }}" readonly></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>
