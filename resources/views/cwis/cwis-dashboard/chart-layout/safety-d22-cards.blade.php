@php($newSafety = $safetyIndicators->whereIn('indicator_code', array_keys(config('cwis_safety_d22.indicators'))))
@if($newSafety->isNotEmpty())
@once
<style>
.cwis-safety-d22 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:24px; width:100%; margin:24px 0; }
.cwis-safety-d22 .card { min-width:0; min-height:300px; background:#F4F9F7; margin:0; }
.cwis-safety-d22 .safety-d22-value { color:#29ab87; font-size:48px; font-weight:bold; line-height:1.2; overflow-wrap:anywhere; }
.cwis-safety-d22 .chart { width:180px; height:160px; margin:0 auto; position:relative; }
.cwis-safety-d22 .chart__value { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; pointer-events:none; }
.cwis-safety-d22 .chart__value p { font-size:24px; margin:0; }
@media(max-width:991px) { .cwis-safety-d22 { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:600px) { .cwis-safety-d22 { grid-template-columns:minmax(0,1fr); } }
</style>
@endonce
<div class="cwis-safety-d22">
@foreach($newSafety as $indicator)
    @php($definition = config('cwis_safety_d22.indicators')[$indicator->indicator_code])
    @php($value = $data[$indicator->indicator_code] ?? null)
    @php($numeric = is_numeric($value) && is_finite((float)$value))
    @php($chartId = 'safety-d22-'.$safetyChartPrefix.'-'.str_replace('-', '', $indicator->indicator_code))
    <div class="card" data-indicator-code="{{ $indicator->indicator_code }}"><div class="card-body d-flex flex-column justify-content-center text-center">
        @if($numeric && $definition['unit'] === '%')
            <div class="chart" id="{{ $chartId }}Container"><figure class="chart__figure"><canvas id="{{ $chartId }}Canvas" width="180" height="160" role="img" aria-label="{{ __($indicator->label) }}"></canvas></figure></div>
            <script>document.addEventListener('DOMContentLoaded', function () { createDoughnutChart(@json((float)$value),'#29ab87',@json($chartId.'Canvas'),@json($chartId.'Container')); });</script>
        @else
            <div class="safety-d22-value">{{ $numeric ? \App\Support\CwisValueFormatter::format($value) : __(in_array($value,['Yes','No'],true) ? $value : ($value === 'NA' ? 'Not applicable' : 'No data')) }}</div>
            @if($numeric)<small class="text-muted mt-2">{{ __($definition['unit']) }}</small>@endif
        @endif
        <p class="mt-4 mb-0">{{ __($indicator->label) }}</p>
    </div></div>
@endforeach
</div>
@endif
