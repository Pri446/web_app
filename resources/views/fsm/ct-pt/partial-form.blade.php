<!-- Last Modified Date: 19-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
<div class="card-body">
<div class="form-group row required">
        {!! Form::label('type', __('Toilet Type'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-4">
            {!! Form::select('type', ['Community Toilet' => 'Community Toilet', 'Public Toilet' => 'Public Toilet'], null, [
                'class' => 'form-control type',
                'placeholder' => __('Toilet Type'),
            ]) !!}
        </div>
    </div>
    <div class="form-group row ">
        {!! Form::label('name', __('Toilet Name'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-4">
            {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Toilet Name')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('ward', __('Ward Number'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-4">
            {!! Form::select('ward', $ward, null, ['class' => 'form-control', 'placeholder' => __('Ward Number')]) !!}
        </div>
    </div>

    <div class="form-group row ">
            {!! Form::label('location_name', __('Location'), ['class' => 'col-sm-3 control-label ']) !!}
            <div class=col-sm-4>
                {!! Form::text('location_name', null, ['class' => 'form-control', 'placeholder' => __('Location')]) !!}
            </div>
        </div>
        <div class="form-group row required" id="bin" >
            {!! Form::label('bin', __('House Number / BIN'), ['class' => 'col-sm-3 control-label ']) !!}
            <div class=col-sm-4>
                {!! Form::select('bin', $bin, null, [
                    'class' => 'form-control col-sm-10 bin',
                    'placeholder' => __('House Number / BIN'),
                    'data-lookup-url' => route('building.get-ctpt-house-numbers', [], false),
                    'data-placeholder' => __('House Number / BIN'),
                    'data-load-error' => __('Unable to load buildings. Please try again.'),
                ]) !!}
            </div>
        </div>

        <div class="form-group row " id="access_frm_nearest_road">
        {!! Form::label('access_frm_nearest_road', __('Distance from Nearest Road (m)'), [
            'class' => 'col-sm-3 control-label',
        ]) !!}
        <div class=col-sm-4>
            {!! Form::text('access_frm_nearest_road', null, [
                'class' => 'form-control access_frm_nearest_road',
                'placeholder' => __('Distance from Nearest Road (m)'), 'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'); this.value = this.value < 0 || this.value.startsWith('-') ? '' : this.value;",
            ]) !!}
        </div>
    </div>

    <div class="form-group row required " id="status">
        {!! Form::label('status',__('Status'),['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select('status', $operational, null, ['class' => 'form-control chosen-select ', 'placeholder' => __('Status')]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('caretaker_name', __('Caretaker Name'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::text('caretaker_name', null, ['class' => 'form-control', 'placeholder' => __('Caretaker Name')]) !!}
        </div>
    </div>
    <div class="form-group row required" id="caretaker_gender">
        {!! Form::label('caretaker_gender',__('Caretaker Gender'),['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select('caretaker_gender', ['Male' => 'Male', 'Female' => 'Female' , 'Others' => 'Others'], null, ['class' => 'form-control caretaker_gender', 'placeholder' => __('Caretaker Gender')]) !!}
        </div>
    </div>
    <div class="form-group row required">
        {!! Form::label('caretaker_contact_number', __('Caretaker Contact'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::text('caretaker_contact_number', null, [
                'class' => 'form-control',
                'placeholder' => __('Caretaker Contact'), 'oninput' => "validateOwnerContactInput(this)",
            ]) !!}
        </div>
    </div>

    
    <div class="form-group row required">
        {!! Form::label('owner', __('Owning Institution'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select('owner', ['Community Based Organization' => 'Community Based Organization','Government' => 'Government','Municipality' => 'Municipality','NGO' => 'NGO', 'Private' => 'Private','Public Private Partnership' => 'Public Private Partnership', 'Self Help Group' => 'Self Help Group'], null, [
                'class' => 'form-control' , 'id' => 'owning_institution',
                'placeholder' => __('Owning Institution'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id="name_of_owning_institution" style="display:none">
    {!! Form::label('owning_institution_name', __('Name of Owning Institution'), ['class' => 'col-sm-3 control-label']) !!}
    <div class="col-sm-4">
        {!! Form::text('owning_institution_name', null, ['class' => 'form-control', 'id'=>'name_of_owning_institution', 'placeholder' => __('Name of Owning Institution')]) !!}
    </div>
</div>

    <div class="form-group row required">
        {!! Form::label('operator_or_maintainer', __('Operate and Maintained by'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select(
                'operator_or_maintainer',
                ['Community Based Organization' => 'Community Based Organization','Government' => 'Government','Municipality' => 'Municipality','NGO' => 'NGO', 'Private' => 'Private','Public Private Partnership' => 'Public Private Partnership', 'Self Help Group' => 'Self Help Group'],
                null,
                ['class' => 'form-control', 'id' => 'operator_or_maintainer','placeholder' => __('Operate and Maintained by')],
            ) !!}
        </div>
    </div>

    <div class="form-group row " id="name_operator_or_maintainer" style="display:none">
            {!! Form::label('operator_or_maintainer_name', __('Name of Operate and Maintained by'), ['class' => 'col-sm-3 control-label ']) !!}
            <div class=col-sm-4>
                {!! Form::text('operator_or_maintainer_name', null, ['class' => 'form-control','id' => 'name_operator_or_maintainer', 'placeholder' => __('Name of Operate and Maintained by')]) !!}
            </div>
        </div>

    <div class="form-group row ">
        {!! Form::label('total_no_of_toilets', __('Total Number of Seats'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
        {!! Form::number('total_no_of_toilets', null, [
            'class' => 'form-control total_no_of_toilets',
            'placeholder' => __('Total Number of Seats'),
            'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')" 
        ]) !!}


        </div>
    </div>

    <div class="form-group row ">
        {!! Form::label('total_no_of_urinals', __('Total Number of Urinals'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::number('total_no_of_urinals', null, ['class' => 'form-control total_no_of_urinals', 'placeholder' => __('Total Number of Urinals'),'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"]) !!}
        </div>
    </div>
    
    <div class="form-group row " id="male_or_female_facility">
        {!! Form::label('male_or_female_facility', __('Separate Facility for Male and Female'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select('male_or_female_facility', [true => 'Yes', false => 'No'], null, [
                'class' => 'form-control male_or_female_facility',
                'placeholder' => __('Separate Facility for Male and Female'),
            ]) !!}
        </div>
    </div>
    <div class="form-group row " id="male_seats">
        {!! Form::label('male_seats', __('No. of Seats for Male Users'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::text('male_seats', null, ['class' => 'form-control male_seats', 'placeholder' => __('No. of Seats for Male Users'),'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"]) !!}
        </div>
    </div>

    <div class="form-group row " id="female_seats">
        {!! Form::label('female_seats', __('No. of Seats for Female Users'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::text('female_seats', null, ['class' => 'form-control female_seats', 'placeholder' => __('No. of Seats for Female Users'),'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"]) !!}
        </div>
    </div>

    <div class="form-group row " id="handicap_facility">
      {!! Form::label('handicap_facility'  , __('Separate Facility for People with Disability'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select('handicap_facility',  [true => 'Yes', false => 'No'], null, ['class' => 'form-control chosen-select handicap_facility', 'placeholder' =>  __("Separate Facility for People with Disability")]) !!}
        </div>
    </div>

    <div class="form-group row " id="pwd_seats">
        {!! Form::label('pwd_seats', __('No. of Seats for People with Disability'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::text('pwd_seats', null, ['class' => 'form-control pwd_seats', 'placeholder' => __('No. of Seats for People with Disability'),'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"]) !!}
        </div>
    </div>
  
    <div class="form-group row " id="children_facility">
        {!! Form::label('children_facility',__('Separate Facility for Children'),['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>                    
            {!! Form::select('children_facility',  [true => 'Yes', false => 'No'], null, ['class' => 'form-control chosen-select children_facility', 'placeholder' => __('Separate Facility for Children')]) !!}
        </div>
    </div>
   
    <div class="form-group row ">
        {!! Form::label('indicative_sign',__('Presence of Indicative Sign'),['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select('indicative_sign', [true => 'Yes', false => 'No'], null, ['class' => 'form-control chosen-select', 'placeholder' => __('Presence of Indicative Sign')]) !!}
        </div>
    </div>
  
    <div class="form-group row " id="sanitary_supplies_disposal_facility">
        {!! Form::label('sanitary_supplies_disposal_facility',__('Sanitary Supplies and Disposal Facilities'),['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select('sanitary_supplies_disposal_facility',  [true => 'Yes', false => 'No'], null, ['class' => 'form-control chosen-select sanitary_supplies_disposal_facility', 'placeholder' => __('Sanitary Supplies and Disposal Facilities')]) !!}
        </div>
    </div>

    <div class="form-group row " id="separate_facility_with_universal_design">
        {!! Form::label('separate_facility_with_universal_design',__('Adherence with Universal Design Principles'),['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select('separate_facility_with_universal_design',  [true => 'Yes', false => 'No'], null, ['class' => 'form-control chosen-select ','placeholder' => __('Adherence with Universal Design Principles')]) !!}
        </div>
    </div>

    <div class="form-group row " id="fee_collected">
        {!! Form::label('fee_collected',__('Uses Fee Collection'),['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::select('fee_collected', [true => 'Yes', false => 'No'], null, ['class' => 'form-control chosen-select fee_collected', 'placeholder' => __('Uses Fee Collection')]) !!}
        </div>
    </div>

    <div class="form-group row " id="amount_fee_collected" style = 'display:none'>
        {!! Form::label('amount_of_fee_collected', __('Uses Fee Rate'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>
            {!! Form::text('amount_of_fee_collected', null, ['id'=> 'amount_of_fee_collected','class' => 'form-control', 'placeholder' => __('Uses Fee Rate'), 'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'); this.value = this.value < 0 || this.value.startsWith('-') ? '' : this.value;",]) !!}
        </div>
    </div>
    
    <div class="form-group row " id="frequency_of_fee_collected" style = 'display:none'>
   
        {!! Form::label('frequency_of_fee_collected', __('Frequency of Fee Collection'), ['class' => 'col-sm-3 control-label']) !!}
        <div class=col-sm-4>                            
            {!! Form::select('frequency_of_fee_collected', [], null, ['class' => 'form-control ', 'placeholder' => __('Frequency of Fee Collection'), 'id' => 'frequency_select']) !!}
        </div>                                                                                                        
    </div>  


</div><!-- /.box-body -->
<div class="card-footer">
    <a href="{{ action('Fsm\CtptController@index') }}" class="btn btn-info">{{__('Back to List')}}</a>
    {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}
</div><!-- /.box-footer -->
@push('scripts')
   
<script>
 


    //change in Separate facility for male and female 
    $('.male_or_female_facility').on('change', function() {
        var selectedValue = $(this).val();
        if (selectedValue === '1') {
            $('#male_seats, #female_seats').show();
        }
        else{
            $('#male_seats, #female_seats').hide().val('');
        }
    }).trigger('change');  

    //change in Separate facility for PWD
    $('.handicap_facility').on('change', function() {
        var selectedValue = $(this).val();
        if (selectedValue === '1') {
            $('#pwd_seats').show();   
        }
        else{
            $('#pwd_seats').hide().val('');
        }
    }).trigger('change');


    $('#type').on('change', function() {
    var selectedValue = $(this).val();

    // Hide/show fee collected elements based on the selected value
    $('.fee_collected').on('change', function() {
        var value = $(this).val();

        if (value === '0') {
            $('#frequency_of_fee_collected, #amount_fee_collected').hide().val('');
        } 

        if (value === '1' && selectedValue === 'Community Toilet') {
            $('#amount_fee_collected').show();
            $('#frequency_of_fee_collected').show();

            $('#frequency_select').html('<option value="">Frequency of Fee Collection</option><option value="Weekly">Weekly</option><option value="Half Monthly">Half Monthly</option><option value="Monthly">Monthly</option><option value="Quartely">Quartely</option><option value="Yearly">Yearly</option>');

            // Save the selected frequency value in localStorage
            var selectedFrequency = $('#frequency_select').val();

            @if(isset($ctpt) && $ctpt->exists())
                var selectedFrequency = {!! json_encode($ctpt->frequency_of_fee_collected) !!};
                $('#frequency_select').val(selectedFrequency);
            @endif

        }

        if (value === '1' && selectedValue === 'Public Toilet') {
            $('#amount_fee_collected').show();
            $('#frequency_of_fee_collected').show();

            $('#frequency_select').html('<option value="">Frequency of Fee Collection</option><option value="Per Use">Per Use</option>');

            // Save the selected frequency value in localStorage
            var selectedFrequency = $('#frequency_select').val();

            @if(isset($ctpt) && $ctpt->exists())
                var selectedFrequency = {!! json_encode($ctpt->frequency_of_fee_collected) !!};
                $('#frequency_select').val(selectedFrequency);
            @endif
        }
    }).trigger('change');
}).trigger('change');




        // When owning institution is other than municipality
        $('#owning_institution').on('change', function() {
            var selectedValue = $(this).val();
            if (selectedValue === 'Municipality' || selectedValue == '') {
                $('#name_of_owning_institution').hide().val('');
            } 
            else{
                $('#name_of_owning_institution').show();
            }
        }).trigger('change');


        // When operate & maintained by is other than municipality
        $('#operator_or_maintainer').on('change', function() {
            var selectedValue = $(this).val();
            if (selectedValue === 'Municipality' || selectedValue == '') {
                $('#name_operator_or_maintainer').hide().val('');
            } 
            else{
                $('#name_operator_or_maintainer').show();
            }
        }).trigger('change');

    </script>
@endpush
