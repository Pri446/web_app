<a class="btn btn-info btn-sm" href="{{ route('cwis.accountability.show', ['submission' => $row->id]) }}" title="{{ __('View') }}" aria-label="{{ __('View submission') }} {{ $row->id }}"><i class="fa fa-eye" aria-hidden="true"></i></a>
@if(in_array($row->status, ['draft', 'submitted'], true))
    @can('Approve CWIS')
        <a class="btn btn-info btn-sm" href="{{ route('cwis.accountability.review', ['submission' => $row->id]) }}" title="{{ __('Approve') }}" aria-label="{{ __('Approve submission') }} {{ $row->id }}"><i class="fa fa-check" aria-hidden="true"></i></a>
    @endcan
@endif
