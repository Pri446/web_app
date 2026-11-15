@extends('layouts.dashboard')
@inject('piiHistoryPresenter', 'App\Services\PiiHistoryPresenter')
@section('title', $page_title)
@section('content')
<div class="card card-info">
	<div class="card-header bg-transparent">
		<a href="{{ action('BuildingInfo\BuildingController@index') }}" class="btn btn-info">{{ __("Back to List") }}</a>
	</div><!-- /.box-header -->
	<div class="card-body">
		<ul>
			@foreach($building->revisionHistory as $history)
			@if($history->key == 'created_at' && !$history->old_value)
                            @if($history->userResponsible())
                            <li>{{ $history->userResponsible()->name }} created this resource at {{ $history->newValue() }}</li>
                            @endif
                        @else
                            @if($history->userResponsible())
                            @php
                                $isPiiHistoryField = in_array(
                                    (string) $history->key,
                                    $piiHistoryFields ?? [],
                                    true
                                );
                                $isMeaningfulHistoryChange = !$isPiiHistoryField
                                    || $piiHistoryPresenter->hasMeaningfulChange(
                                        $history->old_value,
                                        $history->new_value
                                    );
                                $oldHistoryValue = $isPiiHistoryField
                                    ? $piiHistoryPresenter->present($history->old_value, $piiRevealed ?? false)
                                    : $history->oldValue();
                                $newHistoryValue = $isPiiHistoryField
                                    ? $piiHistoryPresenter->present($history->new_value, $piiRevealed ?? false)
                                    : $history->newValue();
                            @endphp
                            @if(!$isMeaningfulHistoryChange)
                                @continue
                            @endif
                            <li>{{ $history->userResponsible()->name }} changed {{ $history->fieldName() }} from {{ $oldHistoryValue ?? 'null' }} to {{ $newHistoryValue ?? 'null' }} on {{ $history->created_at }}</li>
                            @endif
                        @endif
			@endforeach

                        @foreach($building->Owners->revisionHistory as $history)
			@if($history->key == 'created_at' && !$history->old_value)
                            @if($history->userResponsible())
                            <li>{{ $history->userResponsible()->name }} created this resource at {{ $history->newValue() }}</li>
                            @endif
                        @else
                            @if($history->userResponsible())
                            @php
                                // Owner revisions store encrypted historical
                                // values. Never call oldValue/newValue directly
                                // for protected fields because that would print
                                // ciphertext when the PII mode is locked.
                                $isPiiHistoryField = in_array(
                                    (string) $history->key,
                                    $piiHistoryFields ?? [],
                                    true
                                );
                                $isMeaningfulHistoryChange = !$isPiiHistoryField
                                    || $piiHistoryPresenter->hasMeaningfulChange(
                                        $history->old_value,
                                        $history->new_value
                                    );
                                $oldHistoryValue = $isPiiHistoryField
                                    ? $piiHistoryPresenter->present($history->old_value, $piiRevealed ?? false)
                                    : $history->oldValue();
                                $newHistoryValue = $isPiiHistoryField
                                    ? $piiHistoryPresenter->present($history->new_value, $piiRevealed ?? false)
                                    : $history->newValue();
                            @endphp
                            @if(!$isMeaningfulHistoryChange)
                                @continue
                            @endif
                            <li>{{ $history->userResponsible()->name }} changed {{ $history->fieldName() }} from {{ $oldHistoryValue ?? 'null' }} to {{ $newHistoryValue ?? 'null' }} on {{ $history->created_at }}</li>
                            @endif
                        @endif
			@endforeach
		</ul>
	</div><!-- /.box-body -->
</div><!-- /.box -->
@stop

