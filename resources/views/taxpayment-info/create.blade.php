@extends('layouts.dashboard')
@section('title', __('Import Property Tax Collection Information Support System'))
@section('content')
@include('layouts.components.error-list')
@include('layouts.components.success-alert')
@include('layouts.components.error-alert')
<div class="card card-info">

	{!! Form::open(['url' => 'tax-payment','files'=>true, 'class' => 'form-horizontal']) !!}

        <div class="card-body">
            <div class="alert alert-info">
                <p>{{ __('Import replaces the current tax payment dataset. If any row fails, previous data is preserved.') }}</p>
                <p>{{ __('Tax Code must exactly match an existing building\'s Tax Code/Holding ID to appear on the map. A tax import does not create buildings, geometry or Building owner records. Temporary building codes and BINs are separate identifiers.') }}</p>
                <p>{{ __('Use YYYY-MM-DD or M/D/YYYY for Last Payment Date. Owner names are hidden until Property Tax Owner PII is unlocked.') }}</p>
                <a href="{{ asset('templates/property-tax-collection-iss-template.csv') }}" download>{{ __('Download import template') }}</a>
            </div>
            <div class="form-group row">
                {!! Form::label(__('Upload Property Tax Collection Information Support System File'),null,['class' => 'col-sm-3 control-label', 'style'=>'padding-top:3px;']) !!}
                <div class="col-sm-3">
                    {!! Form::file('csvfile') !!}
                </div>
            </div>
        </div><!-- /.card-body -->
        <div class="card-footer">
            <a href="{{ route('tax-payment.index') }}" class="btn btn-info">{{ __('Back to List') }}</a>
            {!! Form::submit(__('Upload'), ['class' => 'btn btn-info']) !!}
        </div><!-- /.card-footer -->
    {!! Form::close() !!}

</div><!-- /.card -->
@stop





