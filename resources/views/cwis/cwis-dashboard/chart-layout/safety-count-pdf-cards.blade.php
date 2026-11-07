@if(isset($safetyCountIndicators) && $safetyCountIndicators->isNotEmpty())
<div style="page-break-before: always;">
    <h1>{{ __('Safety') }}</h1>
    <p>{{ __('Country') }}: {{ $countryLabel }} &nbsp; {{ __('Year') }}: {{ $reportingYear }}</p>
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr style="page-break-inside: avoid;">
            @foreach($safetyCountIndicators->sortBy('indicator_code') as $indicator)
                <td style="width: 33.33%; padding: 14px; border: 1px solid #D9D9D9; background: #F4F9F7; text-align: center; vertical-align: middle;">
                    <div style="font-size: 30px; font-weight: bold; color: #29ab87;">{{ \App\Support\CwisValueFormatter::formatCount($safetyCountValues[$indicator->indicator_code] ?? null) }}</div>
                    <p style="font-size: 12px;">{{ __($indicator->label) }}</p>
                </td>
            @endforeach
        </tr>
    </table>
</div>
@endif
