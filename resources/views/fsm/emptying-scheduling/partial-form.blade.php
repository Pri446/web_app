<div class="card-body">
    <div class="form-group row required">
        {!! Form::label('application_id', __('Application ID'),['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('id',null,['class' => 'form-control', 'placeholder' => 'Application ID', 'readonly']) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('proposed_emptying_date', __('Proposed Emptying Date'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::date('proposed_emptying_date',  null, ['class'=>'form-control', 'min' => date('Y-m-d'), 'id' => 'proposed_emptying_date', 'onclick' => 'this.showPicker();']) !!}
        </div>
    </div>

    <div class="card-footer">
        <a href="{{ action('Fsm\ApplicationController@index') }}" class="btn btn-info">{{ __('Back to List')}}</a>
        {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}
    </div>
</div>
