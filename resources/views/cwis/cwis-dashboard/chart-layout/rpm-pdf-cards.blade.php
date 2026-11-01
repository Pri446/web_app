@if(isset($rpmIndicators) && $rpmIndicators->isNotEmpty())
<div style="page-break-before: always;">
    <h1>{{ __('Resource Planning and Management') }}</h1>
    <p>{{ __('Country') }}: {{ $countryLabel }} &nbsp; {{ __('Year') }}: {{ $reportingYear }}</p>
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
        @foreach($rpmIndicators->values()->chunk(3) as $row)
            <tr style="page-break-inside: avoid;">
                @foreach($row as $indicator)
                    <td style="width: 33.33%; padding: 14px; border: 1px solid #D9D9D9; background: #F4F9F7; text-align: center; vertical-align: middle;">
                        <div style="font-size: 30px; font-weight: bold; color: {{ config('cwis_rpm.color') }};">{{ __($rpmValues[$indicator->indicator_code] ?? 'No data') }}</div>
                        <p style="font-size: 12px;">{{ __($indicator->label) }}</p>
                    </td>
                @endforeach
                @for($empty = $row->count(); $empty < 3; $empty++)<td style="width: 33.33%;"></td>@endfor
            </tr>
        @endforeach
    </table>
</div>
@endif
