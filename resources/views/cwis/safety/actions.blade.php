<a class="btn btn-info btn-sm" href="{{ route('cwis.safety.show',$row->id) }}" title="{{ __('View') }}" aria-label="{{ __('View submission') }}"><i class="fa fa-eye" aria-hidden="true"></i></a>
@if(in_array($row->status,['draft','submitted'],true))
    @can('Approve CWIS')<a class="btn btn-info btn-sm" href="{{ route('cwis.safety.review',$row->id) }}" title="{{ __('Approve') }}" aria-label="{{ __('Approve submission') }}"><i class="fa fa-check" aria-hidden="true"></i></a>@endcan
@endif
