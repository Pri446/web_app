<!-- Last Modified Date: 21-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)  (© ISPL, 2024) -->
@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('cwis.generator-data.success-toast')
<style>
    #cwis-equity-review-link, #cwis-equity-review-link:visited { color: #0056b3 !important; text-decoration: underline; }
    #cwis-equity-review-link:hover, #cwis-equity-review-link:focus { color: #003d80 !important; }
</style>
@php
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        $lastYear = $currentYear - 1;

@endphp


@if ($hasInvalidValues)
<!-- Bootstrap Modal -->
<div class="modal fade" id="invalidDataModal" tabindex="-1" role="dialog" aria-labelledby="invalidDataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <p>
                    {{__('Some indicators have been calculated as')}} <strong>NaN</strong> {{__('(Not a Number)')}} or <strong>NA</strong> {{__('(Not Available)')}}.
                </p>
                <p>
                    {{__('This happens due to one or more of the following reasons')}}:
                </p>
                <ul class="text-left">
                    <li><strong>NA:</strong>{{__('Occurs when the numerator or denominator is missing')}}.</li>
                    <li><strong>NaN:</strong> {{__('Happens when')}}:
                        <ul>
                            <li>{{__('The denominator is zero')}} {{__('(division by zero)')}}.</li>
                            <li>{{__('Both the numerator and denominator are zero')}} {{__('(undefined result)')}}.</li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endif
@if ($hasInvalidValues)
<script>
    document.addEventListener("DOMContentLoaded", function () {
        $('#invalidDataModal').modal('show');
    });
</script>
@endif
<div class="card card-info">
    @if($resultCountryMismatch)
        <div class="alert alert-info" role="status">{{ __('Saved results for this year belong to another country selection. No values are shown for the selected country until its results are generated.') }}</div>
    @endif
    @if($equityApprovalMissing ?? false)
        <div class="alert alert-warning" role="status">
            {{ __('Equity inputs for :year (:country) must be approved before generating CWIS results.', ['year' => $generationYear, 'country' => $countryLabel]) }}
            @if($equitySource)
                <a id="cwis-equity-review-link" href="{{ route('cwis.generator-data.show', ['submission' => $equitySource->id, 'country_code' => $country]) }}">{{ __('Review Equity Submission') }}</a>
            @endif
        </div>
    @endif
    <div class="card-header bg-white">
        <p class="mb-2">{{ __('Country') }}: <strong>{{ $countryLabel }}</strong></p>
        <form method="GET" action="{{ url('cwis/cwis-df-mne/export-mne-csv')}}" class="form-inline ml-auto">

            @if($show_add_cwis_button)
            <a href="{{ action('Cwis\CwisMneController@createIndex', ['year' => $generationYear, 'placeholder' => __('Enter value in percent.'),'displayText' => __('quantitative') ]) }}"
            class="btn btn-info ml-2" id="addCwisData">{{__('Add CWIS Data')}}</a>
            @else
            @if($pickyear[0] == $currentYear && !Auth::user()->hasRole('Municipality - Executive'))
                {{-- No action needed, nothing will be shown --}}
            @elseif($pickyear[0] < $lastYear && !Auth::user()->hasRole('Municipality - Executive'))
             <a href="{{ action('Cwis\CwisMneController@createIndex', ['year' => $generationYear, 'placeholder' => __('Enter value in percent.'),'displayText' => __('quantitative') ]) }}" class="btn btn-info ml-2" id="addCwisData">{{__('Add CWIS Data')}}</a>
            @elseif($pickyear[0] ==  $lastYear && !Auth::user()->hasRole('Municipality - Executive'))
                {{-- Temporarily enabled for testing before the January release window. --}}
                <a href="{{ action('Cwis\CwisMneController@createIndex', ['year' => $generationYear, 'placeholder' => __('Enter value in percent.'), 'displayText' => __('quantitative')]) }}" class="btn btn-info ml-2" id="addCwisData">{{ __('Add CWIS Data') }}</a>
            @endif
            @can('Export CWIS')
            <button type="submit" id="export" class="btn btn-info" style="margin-left: 1%" >{{__('Export to Excel')}}</button>
            @endcan
            <a href="{{ action('Cwis\CwisMneController@index') }}" class="btn btn-info float-left" style="display: none; margin-left:1%;" id="back">{{__('Back to List')}}</a>
            <div class="form-group float-right text-right ml-auto">
                <label for="year_select">{{__('Year')}}</label>
                <select class="form-control" id="year_select" name="year_select">
                    @foreach($pickyear as $key => $unique)
                    <option value="{{$unique}}" @if($unique ==  $year) selected @endif> {{$unique}} </option>
                    @endforeach
                </select>
            </div>
        @endif
        </form>
    </div><!-- /.card-header -->
@if($show_add_cwis_button)
    @elseif(!Auth::user()->hasRole('Municipality - Executive'))
        @include('errors.list')
        {!! Form::open(['url' => 'cwis/cwis/cwis-df-mne', 'class' => 'form-horizontal']) !!}
            @include('cwis.cwis-df-mne.country-results')
        {!! Form::close() !!}
    @else
        @include('errors.list')
        {!! Form::open(['url' => 'cwis/cwis/cwis-df-mne', 'class' => 'form-horizontal']) !!}
            @include('cwis.cwis-df-mne.country-results')
        {!! Form::close() !!}
    @endif

</div><!-- /.card -->
@stop
@push('scripts')
<script>
    $('[name="year_select"]').change(function(e) {
        // e.preventDefault();
        var year = $(this).val();
        const url = '<?php echo url('');?>'+`/cwis/cwis/cwis-df-mne?year=${year}`;
        window.location.replace(url);
    });
   
</script>
@endpush




