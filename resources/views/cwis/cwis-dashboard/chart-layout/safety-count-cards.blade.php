@php($countIndicators = $safetyIndicators->whereIn('indicator_code', ['SF-0a', 'SF-0b', 'SF-0c'])->sortBy('indicator_code'))
@if($countIndicators->isNotEmpty())
@once
<style>
    .cwis-safety-counts {
        display: grid;
        grid-template-columns: repeat(var(--cwis-count-columns, 3), minmax(0, 1fr));
        gap: 24px;
        flex: 0 0 100%;
        width: 100%;
        min-width: 0;
        margin-bottom: 24px;
    }
    .cwis-safety-counts .cwis-safety-count { min-width: 0; }
    .cwis-safety-counts .cwis-safety-count-value {
        color: #29ab87;
        font-weight: bold;
        font-size: 64px;
        overflow-wrap: anywhere;
        min-height: 96px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1.2;
    }
    .cwis-safety-counts p { min-height: 48px; }
    @media (max-width: 991px) {
        .cwis-safety-counts { grid-template-columns: minmax(0, 1fr); }
        .cwis-safety-counts .cwis-safety-count-value { font-size: 48px; }
    }
</style>
@endonce
<div class="cwis-safety-counts" style="--cwis-count-columns: {{ $countIndicators->count() }};">
    @foreach($countIndicators as $indicator)
        <div class="cwis-safety-count" data-indicator-code="{{ $indicator->indicator_code }}">
            <div class="card h-100" style="background-color: #F4F9F7; min-height: 300px;">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div class="cwis-safety-count-value">{{ \App\Support\CwisValueFormatter::formatCount($data[$indicator->indicator_code] ?? null) }}</div>
                    <p class="mt-4 mb-0">{{ __($indicator->label) }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif
