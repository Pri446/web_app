<!-- Last Modified Date: 19-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('layouts.components.error-list')
@include('layouts.components.success-alert')
@include('layouts.components.error-alert')
<div class="card card-info">
	{!! Form::open(['url' => 'fsm/ctpt', 'class' => 'form-horizontal']) !!}
		@include('fsm/ct-pt.partial-form', ['submitButtomText' => __('Save')])
	{!! Form::close() !!}
</div><!-- /.card -->
@stop
@push('scripts')
    <script src="{{ asset('js/ctpt-building-lookup.js') }}?v={{ filemtime(public_path('js/ctpt-building-lookup.js')) }}"></script>
@endpush
