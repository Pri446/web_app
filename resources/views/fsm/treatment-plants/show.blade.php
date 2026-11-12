<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
<div class="card card-info">
    <div class="card-header bg-transparent">
        <a href="{{ action('Fsm\TreatmentPlantController@index') }}" class="btn btn-info">{{ __('Back to List') }}</a>
    </div><!-- /.card-header -->
    <div class="form-horizontal">
        <div class="card-body">
            <div class="form-group row">
                {!! Form::label('name', __('Name'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::label(null, $treatmentPlant->name, ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('location', __('Location'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::label(null, $treatmentPlant->location, ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('type', __('Treatment Plant Type'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::label(null, $type, ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('capacity_per_day', __('Capacity Per Day (m³)'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::label(null, $treatmentPlant->capacity_per_day, ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('caretaker_name', __('Caretaker Name'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::label(null, $treatmentPlant->caretaker_name, ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('caretaker_gender', __('Caretaker Gender'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::label(null, $treatmentPlant->caretaker_gender, ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('caretaker_number', __('Caretaker Number'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::label(null, $treatmentPlant->caretaker_number, ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('status', __('Status'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::label(null, $status, ['class' => 'form-control']) !!}
                </div>
            </div>
            @php($annualDetails = $treatmentPlant->annualSustainability->isEmpty() ? collect([null]) : $treatmentPlant->annualSustainability)
            @foreach($annualDetails as $annual)
                <div class="form-group row">
                    <label class="col-sm-3 control-label">{{ __('Data Year') }}</label>
                    <div class="col-sm-3"><span class="form-control">{{ $annual->data_year ?? __('No data') }}</span></div>
                </div>
                @foreach(\App\Models\Fsm\TreatmentPlantAnnualSustainability::VOLUMES as $column => $label)
                    <div class="form-group row">
                        <label class="col-sm-3 control-label">{{ __($label) }}</label>
                        <div class="col-sm-3"><span class="form-control">{{ $annual->$column ?? __('No data') }}</span></div>
                    </div>
                @endforeach
            @endforeach
        </div><!-- /.box-body -->
    </div>
</div><!-- /.box -->

@stop

