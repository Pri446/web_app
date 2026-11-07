@if($safetyD22Indicators->isNotEmpty())
<div style="page-break-before:always;">
    <h1>{{ __('Safety') }}</h1><p>{{ __('Country') }}: {{ $countryLabel }} &nbsp; {{ __('Year') }}: {{ $reportingYear }}</p>
    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
    @foreach($safetyD22Indicators->values()->chunk(3) as $row)
        <tr style="page-break-inside:avoid;">
        @foreach($row as $indicator)
            @php($value = $safetyD22Values[$indicator->indicator_code] ?? null)
            @php($numeric = is_numeric($value) && is_finite((float)$value))
            <td style="width:33.33%; padding:14px; border:1px solid #D9D9D9; background:#F4F9F7; text-align:center;">
                <div style="font-size:28px; font-weight:bold; color:#29ab87;">{{ $numeric ? \App\Support\CwisValueFormatter::format($value) : __(in_array($value,['Yes','No'],true) ? $value : ($value === 'NA' ? 'Not applicable' : 'No data')) }}</div>
                @if($numeric)<small>{{ __(config('cwis_safety_d22.indicators')[$indicator->indicator_code]['unit']) }}</small>@endif
                <p style="font-size:12px;">{{ __($indicator->label) }}</p>
            </td>
        @endforeach
        @for($empty=$row->count();$empty<3;$empty++)<td style="width:33.33%;"></td>@endfor
        </tr>
    @endforeach
    </table>
</div>
@endif
