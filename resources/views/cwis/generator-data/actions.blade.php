<a href="{{ route('cwis.generator-data.show', ['submission' => $row->id, 'country_code' => request('country_code', app(\App\Services\Cwis\CwisIndicatorService::class)->activeCountry())]) }}" class="btn btn-info btn-sm" title="{{ __('View') }}" aria-label="{{ __('View submission') }} {{ $row->id }}"><i class="fa fa-eye" aria-hidden="true"></i></a>
@if(in_array($row->status, ['draft','submitted'], true))
    @can('Approve CWIS')
        <a href="{{ route('cwis.generator-data.review', ['submission' => $row->id]) }}" class="btn btn-info btn-sm" title="{{ __('Approve') }}" aria-label="{{ __('Approve submission') }} {{ $row->id }}"><i class="fa fa-check" aria-hidden="true"></i></a>
    @endcan
@endif
