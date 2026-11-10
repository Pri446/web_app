<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
<div class="card-body">
    @if(!$employeeInfos)
        @if(!Auth::user()->service_provider_id)
            <div class="form-group row required">
                {!! Form::label('service_provider_id', __('Service Provider Name'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::select('service_provider_id', $service_provider_id, null, ['class' => 'form-control', 'required' => true, 'placeholder' => __('Service Provider Name')]) !!}
                </div>
            </div>
        @else
            <div class="form-group row required">
                {!! Form::label('service_provider_id', __('Service Provider Name'), ['class' => 'col-sm-3 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::select('service_providers', $service_providers, $service_provider_id, ['class' => 'form-control', 'disabled' => 'true', 'placeholder' => __('Service Provider Name')]) !!}
                    {!! Form::hidden('service_provider_id', $service_provider_id) !!}
                </div>
            </div>
        @endif
    @else
        <div class="form-group row required">
            {!! Form::label('service_provider_id', __('Service Provider Name'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-3">
                {!! Form::select('service_provider_id', $service_provider_id, null, ['class' => 'form-control', 'required' => true, 'placeholder' => __('Service Provider Name')]) !!}
            </div>
        </div>
    @endif

    <div class="form-group row required">
        {!! Form::label('name', __('Employee Name'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Employee Name')]) !!}
        </div>
    </div>
    
    <div class="form-group row required">
        {!! Form::label('gender', __('Employee Gender'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::select('gender', ['Male' => 'Male', 'Female' => 'Female', 'Others' => 'Others'], null, ['class' => 'form-control', 'placeholder' => __('Employee Gender')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('contact_number', __('Employee Contact Number'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('contact_number', null, ['class' => 'form-control', 'placeholder' => __('Employee Contact Number'), 'oninput' => "validateOwnerContactInput(this)"]) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('dob', __('Date of Birth'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::date('dob', null, ['class' => 'form-control date', 'id' => 'dob', 'placeholder' => __('Date of birth'), 'onclick' => 'this.showPicker();']) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('address', __('Address'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('address', null, ['class' => 'form-control', 'placeholder' => __('Address')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('employee_type', __('Designation'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::select('employee_type', ['Management' => 'Management', 'Driver' => 'Driver', 'Cleaner/Emptier' => 'Cleaner/Emptier'], null, ['class' => 'form-control', 'id' => 'employee_type', 'placeholder' => __('Designation')]) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('year_of_experience', __('Working Experience (Years)'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('year_of_experience', null, ['class' => 'form-control', 'placeholder' => __('Working Experience (Years)'), 'oninput' => "validateOwnerContactInput(this)"]) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('wage', __('Monthly Remuneration'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('wage', null, ['class' => 'form-control', 'placeholder' => __('Monthly Remuneration'), 'oninput' => "validateOwnerContactInput(this)"]) !!}
        </div>
    </div>

    <div id="license_number" class="form-group row required" style="display: none;">
        {!! Form::label('license_number', __('Driving License Number'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('license_number', null, ['class' => 'form-control', 'placeholder' => __('Driving License Number')]) !!}
        </div>
    </div>

    <div id="license_issue_date" class="form-group row required" style="display: none;">
        {!! Form::label('license_issue_date', __('License Issue Date'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::date('license_issue_date', null, ['class' => 'form-control', 'placeholder' => __('License Issue Date'), 'onclick' => 'this.showPicker();']) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('training_status', __('Training Received (if any)'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::text('training_status', null, ['class' => 'form-control', 'placeholder' => __('Training Received')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('status', __('Status'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-3">
            {!! Form::select('status', $status, null, ['class' => 'form-control chosen-select', 'id' => 'status', 'placeholder' => __('Status')]) !!}
        </div>
    </div>

    @if(isset($start))
        <div class="form-group row required">
            {!! Form::label('employment_start', __('Job Start Date'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-3">
                {!! Form::date('employment_start', isset($start) ? $start : null, ['class' => 'form-control date', 'onclick' => 'this.showPicker();', 'placeholder' => __('Job Start Date')]) !!}
            </div>
        </div>
    @else
        <div class="form-group row required">
            {!! Form::label('employment_start', __('Job Start Date'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-3">
                {!! Form::date('employment_start', null, ['class' => 'form-control date', 'onclick' => 'this.showPicker();', 'placeholder' => __('Job Start Date')]) !!}
            </div>
        </div>
    @endif

    @if(isset($end))
        <div id="employment" class="form-group row required" style="display: none;">
            {!! Form::label('employment_end', __('Job End Date'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-3">
                {!! Form::date('employment_end', isset($end) ? $end : null, ['class' => 'form-control date', 'placeholder' => __('Job End Date'), 'onclick' => 'this.showPicker();']) !!}
            </div>
        </div>
    @else
        <div id="employment" class="form-group row required" style="display: none;">
            {!! Form::label('employment_end', __('Job End Date'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-3">
                {!! Form::date('employment_end', null, ['class' => 'form-control date', 'placeholder' => __('Job End Date'), 'onclick' => 'this.showPicker();']) !!}
            </div>
        </div>
    @endif
</div>

<div class="card-footer">
    <a href="{{ action('Fsm\EmployeeInfoController@index') }}" class="btn btn-info">{{ __('Back to List') }}</a>
    {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}
</div><!-- /.card-footer -->

@push('scripts')
    <script>

$(document).ready(function() {
    $('#employee_type').on('change', function() {
        var selectedValue = $(this).val();
        if (selectedValue === 'Driver') {
            $('#license_number').show();
            $('#license_issue_date').show();
        } else {
            $('#license_number').hide();
            $('.license_number').val('');

            $('#license_issue_date').hide();
            $('.license_issue_date').val('');
        }
    }).trigger('change');

    $('#status').on('change', function() {
        var selectedValue = $(this).val();
        if (selectedValue === '0') {
            $('#employment').show();

        } else {
            $('#employment_end').val('');
            $('#employment').hide();


        }
    }).trigger('change');

    $('.date').focus(function(){
        $(this).blur();


        });

        });

    </script>
@endpush
