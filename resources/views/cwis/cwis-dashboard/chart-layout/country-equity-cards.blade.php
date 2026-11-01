<div class="row cwis-country-equity">
    @foreach($equityIndicators as $indicator)
        <div class="col-12 col-md-6 col-lg-4 mb-3" data-indicator-code="{{ $indicator->indicator_code }}">
            <div class="card h-100" style="background-color: #F4F9F7; min-height: 300px;">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div style="color: #6CB4EE; font-weight: bold; font-size: 64px; overflow-wrap: anywhere;">{{ $data[$indicator->indicator_code] ?? __('No data') }}</div>
                    <p class="mt-4 mb-0">{{ __($indicator->label) }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
