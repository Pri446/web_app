<div class="row cwis-country-accountability">
    @foreach($accountabilityIndicators as $indicator)
        <div class="col-12 col-md-6 col-lg-4 mb-3" data-indicator-code="{{ $indicator->indicator_code }}">
            <div class="card h-100" style="background-color: #F4F9F7; min-height: 300px;">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div style="color: {{ config('cwis_accountability.color') }}; font-weight: bold; font-size: 64px; overflow-wrap: anywhere;">{{ __($data[$indicator->indicator_code] ?? 'No data') }}</div>
                    <p class="mt-4 mb-0">{{ __($indicator->label) }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
