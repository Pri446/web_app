<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@push('style')
<style type="text/css">
.chosen-container .chosen-results {
    max-height: 150px !important;
}
</style>
@endpush
    <div class="card-body">
      
        @if(!empty($treatment_plant_id))
        <div class="form-group row required">
            {!! Form::label('treatment_plant_id',__('Treatment Plant Name'),['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-3">
                {!! Form::select('treatment_plant_id', $treatmentPlants, $treatment_plant_id, ['class' => 'form-control chosen-select', 'placeholder' => '--- Choose treatment plant ---','disabled'=>'true']) !!}
                {!! Form::text('treatment_plant_id', $treatment_plant_id, ['class' => 'form-control','hidden'=>'hidden']) !!}
                {!! Form::text('service_provider_id', $service_provider_id, ['class' => 'form-control','hidden'=>'hidden']) !!}
            </div>
        </div>
        @else
        <div class="form-group row required">
            {!! Form::label('treatment_plant_id',__('Treatment Plant Name'),['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-3">
                {!! Form::select('treatment_plant_id', $treatmentPlants, null, ['class' => 'form-control chosen-select', 'placeholder' => '--- Choose treatment plant ---']) !!}
            </div>
        </div>
        @endif
        <div class="form-group row required">
        {!! Form::label('application_id',__('Application ID'),['class' => 'col-sm-3 control-label']) !!}
        @if($sludgeCollection)
        <div class="col-sm-3">
            {!! Form::text('application_id',null,['class' => 'form-control datepicker ', 'placeholder' => __('Application ID'),'readonly']) !!}
        </div>
        @elseif($application_id)
        <div class="col-sm-3">
            {!! Form::text('application_id',$application_id,['class' => 'form-control datepicker ', 'placeholder' => __('Application ID'),'readonly']) !!}
        </div>
        @else
        <div class="col-sm-3">
            {!! Form::select('application_id',$applications, null,['class' => 'form-control datepicker', 'placeholder' => __('Application ID')]) !!}
        </div>
        @endif
    </div>
    @if(!empty($volume_of_sludge))
        <div class="form-group row required">
        {!! Form::label('volume_of_sludge',__('Sludge Volume (m³)') ,['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('volume_of_sludge',$volume_of_sludge,['class' => 'form-control ','readonly']) !!}
        </div>
        </div>
    @endif
    <div class="form-group row required">
        {!! Form::label('date',__('Date'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
       
            {!! Form::date('date',$sludgeCollection->date??null,['class' => 'form-control date', 'id' => 'sludge_collection_date', 'onclick' => 'this.showPicker()']) !!}
        </div>
    </div>
    
    <div class="form-group row required">
        {!! Form::label('entry_time',__('Entry Time'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::time('entry_time',$entry_time,['class' => 'form-control timepicker', 'placeholder' => 'Time']) !!}
        </div>
    </div>
    <div class="form-group row required">
        {!! Form::label('exit_time',__('Exit Time'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::time('exit_time',$exit_time,['class' => 'form-control timepicker', 'placeholder' => 'Time']) !!}
        </div>
    </div>
        <div class="form-group row required">
        {!! Form::label('tipping_fee_receipt_no', __('Tipping Fee Receipt No'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('tipping_fee_receipt_no', $sludgeCollection->tipping_fee_receipt_no??null,['class' => 'col-sm form-control', 'placeholder' => 'Tipping Fee Receipt No']) !!}
        </div>
    </div>
    
    <div class="form-group row required">
        {!! Form::label('tipping_fee_amount', __('Tipping Fee Amount'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('tipping_fee_amount', $sludgeCollection->tipping_fee_amount??null,['class' => 'col-sm form-control', 'placeholder' => 'Tipping Fee Amount', 'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '');"]) !!}
        </div>
    </div>
  @if(!empty($service_provider_id))
        <div class="form-group row required">
            <div class="col-sm-3">
                {!! Form::text('service_provider_id', $service_provider_id, ['class' => 'form-control','hidden'=>'hidden']) !!}
            </div>
        </div>
    @endif

    @if(!empty($vacutug_id))
        <div class="form-group row required">
            <div class="col-sm-3">
                {!! Form::text('desludging_vehicle_id', $vacutug_id, ['class' => 'form-control','hidden'=>'hidden']) !!}
            </div>
        </div>
    @endif

</div><!-- /.card-body -->
<div class="card-footer">
    <a href="{{ action('Fsm\ApplicationController@index') }}" class="btn btn-info">{{__('Back to List')}}</a>
    {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}
</div><!-- /.card-footer -->

