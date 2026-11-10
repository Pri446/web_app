<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
<div class="col-sm-12 col-md-8 col-lg-8">
    <div class="card">
        <div class="card-header">
            __('Customer Details')
        </div>
        <div class="card-body">
            <div class="form-group row">
                {!! Form::label('address',__('Address'),['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::select('address',['asd'],null) !!}
                    @else
                    {!! Form::label(null,$application->address,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('customer_name',__("Customer's Name"),['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::text('customer_name',null,['class' => 'form-control', 'placeholder' => "Customer's Name"]) !!}
                    @else
                        {!! Form::label(null,$application->applicant_name,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('customer_gender',"Customer's Gender",['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::select('customer_gender',["M"=>"M","F"=>"F","O"=>"O"],null,['class' => 'form-control', 'placeholder' => "Customer's Gender", 'id'=>'customer_gender']) !!}
                    @else
                        {!! Form::label(null,$application->applicant_gender,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('contact_no','Contact',['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::number('contact_no',null,['class' => 'form-control', 'placeholder' => 'Contact']) !!}
                    @else
                        {!! Form::label(null,$application->applicant_contact,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            __('Applicant Details')
            @if(empty($application))
            <div class="clearfix float-right">
                <div class="icheck-primary d-inline">
                    <input type="checkbox" name="autofill" id="autofill" onclick="autoFillDetails()">
                    <label for="autofill">
                        Same as Customer
                    </label>
                </div>
            </div>
            @endif
        </div>
        <div class="card-body">
            <div class="form-group row">
                {!! Form::label('applicants_name',"Applicant's Name",['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::text('applicants_name',null,['class' => 'form-control', 'placeholder' => "Applicant's Name"]) !!}
                    @else
                        {!! Form::label(null,$application->applicants_name,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('applicant_gender',"Applicant's Gender",['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::select('applicant_gender',["M"=>"M","F"=>"F","O"=>"O"],null,['class' => 'form-control', 'placeholder' => "Applicant's Gender",'id'=>'applicant_gender']) !!}
                    @else
                        {!! Form::label(null,$application->applicant_gender,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('applicants_contact',"Applicant's Contact",['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::number('applicants_contact',null,['class' => 'form-control', 'placeholder' => "Applicant's Contact"]) !!}
                    @else
                        {!! Form::label(null,$application->applicants_contact,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div id="building-if-address" style="display: none" class="card">
        <div class="card-header">
            __('Building Details')
        </div>
        <div class="card-body">
            <div class="form-group row">
                {!! Form::label('road_code','Road',['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::select('road_code',[],null,['class' => 'form-control', 'placeholder' => 'Road']) !!}
                    @else
                        {!! Form::label(null,$application->road_code,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('containment_code','Containment',['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::select('containment_code',[],null,['class' => 'form-control', 'placeholder' => 'Containment']) !!}
                    @else
                        {!! Form::label(null,$application->containment_code,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('ward','Ward',['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::text('ward',null,['class' => 'form-control', 'placeholder' => 'Capacity']) !!}
                    @else
                        {!! Form::label(null,$application->ward,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('proposed_emptying_date','Proposed Emptying Date',['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::number('proposed_emptying_date',null,['class' => 'form-control', 'placeholder' => 'Proposed Emptying Date']) !!}
                    @else
                        {!! Form::label(null,$application->proposed_emptying_date,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div id="building-if-not-address" style="display: none" class="card">
        <div class="card-header">
            __('Building Details')
        </div>
        <div class="card-body">
            <div class="form-group row">
                {!! Form::label('road_code','Nearest Road',['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::select('road_code',[],null,['class' => 'form-control', 'placeholder' => 'Nearest Road']) !!}
                    @else
                        {!! Form::label(null,$application->road_code,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('ward','Ward',['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::text('ward',null,['class' => 'form-control', 'placeholder' => 'Capacity']) !!}
                    @else
                        {!! Form::label(null,$application->ward,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('landmark','Nearest Landmark',['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::text('landmark',null,['class' => 'form-control', 'placeholder' => 'Nearest Landmark']) !!}
                    @else
                        {!! Form::label(null,$application->landmark,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('proposed_emptying_date','Proposed Emptying Date',['class' => 'col-sm-6 control-label']) !!}
                <div class="col-sm-6">
                    @if(empty($application))
                    {!! Form::number('proposed_emptying_date',null,['class' => 'form-control', 'placeholder' => 'Proposed Emptying Date']) !!}
                    @else
                        {!! Form::label(null,$application->proposed_emptying_date,['class' => 'form-control']) !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    {!! Form::submit($submitButtonText, ['class' => 'btn btn-info']) !!}
</div>


