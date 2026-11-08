<div class="card-body" style="font-family: 'Open Sans', sans-serif;">

    <!-- preview building footprint if building is being approved via Building Survey- Approve -->
    @if (!empty($buildingSurvey))
        <div class="form-group row">
            {!! Form::label('', __('Preview Building Footprint'), [
                'class' => 'col-sm-3 control-label',
                'style' => 'font-family: "Open Sans", sans-serif;',
            ]) !!}

            <div class="col-sm-5">
                <a title="' . __("Preview Building Location") . '" data-toggle="modal" data-target="#kml-previewer"
                    data-id="{{ $buildingSurvey->kml }}" class="btn btn-info btn-xs"><i class="fa fa-eye"></i></a>
            </div>
        </div>
        @include('building-info.building-surveys.kmlPreviewModal')
    @endif

    <h3 class="mt-4"> {{ __('Owner Information') }}</h3>
    @php($editingOwner = isset($ownerPii))

    @if ($editingOwner)
        @if ($piiUnlocked)
            <div class="alert alert-success col-sm-8">
                {{ __('Owner PII is temporarily unlocked. You may view and edit it for five minutes.') }}
                <button type="button" class="btn btn-sm btn-warning ml-2"
                    onclick="document.getElementById('pii-lock-form').submit();">
                    {{ __('Lock Owner PII') }}
                </button>
            </div>
        @else
            <div class="alert alert-info col-sm-8">
                {{ __('Owner information is masked and locked. Activate View PII Information from the Buildings page to view or edit it.') }}
            </div>
        @endif
    @endif

    <!-- Building Owner Information -->
    @if (!$editingOwner || $piiUnlocked)
        <div class="form-group row {{ $editingOwner ? '' : 'required' }}">
            {!! Form::label('owner_name', __('Owner Name'), ['class' => 'col-sm-3 control-label ']) !!}
            <div class="col-sm-5">
                {!! Form::text('owner_name', null, [
                    'class' => 'form-control col-sm-10',
                    'placeholder' => __('Owner Name'),
                    'autocomplete' => 'off',
                ]) !!}
            </div>
        </div>

        <div class="form-group row">
            {!! Form::label('nid', __('Owner NID'), ['class' => 'col-sm-3 control-label ']) !!}
            <div class="col-sm-5">
                {!! Form::text('nid', null, [
                    'class' => 'form-control col-sm-10',
                    'placeholder' => __('Owner NID'),
                    'autocomplete' => 'off',
                ]) !!}
            </div>
        </div>

        <div class="form-group row {{ $editingOwner ? '' : 'required' }}">
            {!! Form::label('owner_gender', __('Owner Gender'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-5">
                {!! Form::select('owner_gender', ['Male' => 'Male', 'Female' => 'Female', 'Others' => 'Others'], null, [
                    'class' => 'form-control col-sm-10',
                    'placeholder' => __('Owner Gender'),
                    'autocomplete' => 'off',
                ]) !!}
            </div>
        </div>

        <div class="form-group row {{ $editingOwner ? '' : 'required' }}">
            {!! Form::label('owner_contact', __('Owner Contact Number'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-5">
                {!! Form::text('owner_contact', null, [
                    'class' => 'form-control col-sm-10',
                    'placeholder' => __('Owner Contact Number'),
                    'autocomplete' => 'off',
                    'oninput' => 'validateOwnerContactInput(this)',
                ]) !!}
            </div>
        </div>
    @else
        @foreach ([
            'owner_name' => __('Owner Name'),
            'nid' => __('Owner NID'),
            'owner_gender' => __('Owner Gender'),
            'owner_contact' => __('Owner Contact Number'),
        ] as $field => $label)
            <div class="form-group row">
                <label class="col-sm-3 control-label">{{ $label }}</label>
                <div class="col-sm-5">
                    <input type="text" class="form-control col-sm-10"
                        value="********" disabled>
                </div>
            </div>
        @endforeach
    @endif

    <h3 class="mt-3">{{ __('Building Information') }}  </h3>

    @if (empty($building))
        <div class="form-group row required">
    @else
        <div class="form-group row">
    @endif
        {!! Form::label('geom', __('Building Footprint (KML File)'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            @if (!empty($buildingSurvey))
                {!! Form::text('kml_display', $buildingSurvey->kml, [
                    'class' => 'form-control col-sm-10',
                    'readonly' => true,
                ]) !!}
                {!! Form::hidden('kml', $buildingSurvey->kml) !!}
                {!! Form::hidden('survey_id', $buildingSurvey->id) !!}
                <small class="form-text text-success">
                    {{ __('The approved survey KML will be used.') }}
                </small>
            @else
                {!! Form::hidden('kml', null) !!}
                {!! Form::file('geom', [
                    'class' => 'form-control col-sm-10',
                    'accept' => '.kml,application/vnd.google-earth.kml+xml,application/xml,text/xml',
                    'aria-describedby' => 'fileSizeHintKML kmlFileFeedback',
                    'required' => empty($building),
                ]) !!}
                <small class="form-text text-muted" id="fileSizeHintKML">
                    {{ __('Select a valid polygon KML file. Maximum size: 1 MB.') }}
                </small>
                <div id="kmlFileFeedback" class="file-feedback mt-1" role="status" aria-live="polite"></div>
            @endif
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('house_image', __('House Image'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::file('house_image', [
                'class' => 'form-control col-sm-10',
                'accept' => '.jpg,.jpeg,image/jpeg',
                'aria-describedby' => 'fileSizeHintImg imageFileFeedback',
            ]) !!}
            <small class="form-text text-muted" id="fileSizeHintImg">
                {{ __('Image (JPG, JPEG) size should not be more than 5MB.') }}
            </small>
            <div id="imageFileFeedback" class="file-feedback mt-1" role="status" aria-live="polite"></div>
            <img
                id="houseImagePreview"
                class="img-thumbnail mt-2 d-none"
                style="max-width: 320px; max-height: 240px; object-fit: contain;"
                alt="{{ __('Selected house image preview') }}"
            >
        </div>
    </div>

    <!-- Main Building Identifier -->
    <div class="form-group row required" id="main-building">
        {!! Form::label('main_building', __('Main Building'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('main_building', [true => 'Yes', false => 'No'], null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Main Building'),
            ]) !!}
        </div>
    </div>

    <!-- Associated Main Building House Number -->
    <div class="form-group row required" id="building_associated" style="display: none;">
        {!! Form::label('building_associated_to', __('BIN of Main Building'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('building_associated_to', $buildingBin, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('BIN of Main Building'),
                'style' => 'width:100%',
            ]) !!}
        </div>
    </div>

    <!-- Building Location Information -->
    <div class="form-group row required">
        {!! Form::label('ward', __('Ward Number'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('ward', $ward, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Ward Number'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('road_code', __('Road Code'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('road_code', $road_code, null, [
                'class' => 'form-control col-sm-10 road_code',
                'placeholder' => __('Road Code'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('house_number', __('House Number'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::text('house_number', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('House Number'),
                'autocomplete' => 'off',
            ]) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('house_locality', __('House Locality/Address'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::text('house_locality', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('House Locality/Address'),
                'autocomplete' => 'off',
            ]) !!}
        </div>
    </div>

    <!-- Tax ID -->
    <div class="form-group row required">
        {!! Form::label('tax_code', __('Tax Code/Holding ID'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::text('tax_code', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Tax Code/Holding ID'),
                'autocomplete' => 'off',
            ]) !!}
        </div>
    </div>

    <!-- Basic Building Structure Information -->
    <div class="form-group row required">
        {!! Form::label('structure_type_id', __('Structure Type'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('structure_type_id', $structure_type, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Structure Type'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row">
        {!! Form::label('surveyed_date', __('Surveyed Date'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            @if (empty($buildingSurvey))
                {!! Form::date('surveyed_date', null, [
                    'class' => 'form-control date col-sm-10',
                    'autocomplete' => 'off',
                    'id' => 'surveyed_date',
                    'max' => now()->format('Y-m-d'),
                    'onclick' => 'this.showPicker();', // Trigger date picker when clicked
                ]) !!}
            @else
                {!! Form::date('surveyed_date', $buildingSurvey->collected_date, [
                    'class' => 'form-control date col-sm-10',
                    'autocomplete' => 'off',
                    'id' => 'surveyed_date',
                    'max' => now()->format('Y-m-d'),
                    'readonly' => 'readonly',
                    'onclick' => 'this.showPicker();',
                ]) !!}
            @endif
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('construction_year', __('Construction Date'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::date('construction_year', null, [
                'class' => 'form-control date col-sm-10',
                'autocomplete' => 'off',
                'id' => 'construction_year',
                'max' => now()->format('Y-m-d'),
                'onclick' => 'this.showPicker();',
            ]) !!}
        </div>
    </div>

    <div class="form-group row required">
        {!! Form::label('floor_count', __('Number of Floors'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::text('floor_count', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Number of Floors'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '');",
            ]) !!}
        </div>
    </div>

    <!-- Building Function Use Classification -->
    <div class="form-group row required" id="functional-use">
        {!! Form::label('functional_use_id', __('Functional Use of Building'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('functional_use_id', $functional_use, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Functional Use of Building'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row required" id="use-category">
        {!! Form::label('use_category_id', __('Use Category of Building'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('use_category_id', $use_category_id, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Use Category of Building'),
                'id' => 'use_category_id', // Add an ID for JavaScript targeting
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id="office-business">
        {!! Form::label('office_business_name', __('Office or Business Name'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::text('office_business_name', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Office or Business Name'),
                'autocomplete' => 'off',
            ]) !!}
        </div>
    </div>

    <!-- Building Population Information - Number of Households -->
    <div class="form-group row required" id="family-count">
        {!! Form::label('household_served', __('Number of Households'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::number('household_served', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Number of Households'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value;",
            ]) !!}
        </div>
    </div>




    <!-- Additional Population Fields (Optional) -->
    <div class="form-group row" id="male-population">
        {!! Form::label('male_population', __('Male Population'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::number('male_population', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Male Population'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id="female-population">
        {!! Form::label('female_population', __('Female Population'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::number('female_population', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Female Population'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id="other-population">
        {!! Form::label('other_population', __('Other Population'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::number('other_population', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Other Population'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

    <div class="form-group row required" id="population-info">
        {!! Form::label('population_served', __('Population of Building'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::number('population_served', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Population of Building'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id='male-diff-population'>
        {!! Form::label('diff_abled_male_pop', __('Differently Abled Male Population'), [
            'class' => 'col-sm-3 control-label',
        ]) !!}
        <div class="col-sm-5">
            {!! Form::number('diff_abled_male_pop', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Differently Abled Male Population'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id='female-diff-population'>
        {!! Form::label('diff_abled_female_pop', __('Differently Abled Female Population'), [
            'class' => 'col-sm-3 control-label',
        ]) !!}
        <div class="col-sm-5">
            {!! Form::number('diff_abled_female_pop', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Differently Abled Female Population'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id='other-diff-population'>
        {!! Form::label('diff_abled_others_pop', __('Differently Abled Other Population'), [
            'class' => 'col-sm-3 control-label',
        ]) !!}
        <div class="col-sm-5">
            {!! Form::number('diff_abled_others_pop', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Differently Abled Other Population'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

  <!-- Building Population Information - Population of Building -->


    <h3 class="mt-3"> {{ __("LIC Information") }}</h3>



    {{-- lic information --}}
    <div class="form-group row required" id="low_income_hh">
        {!! Form::label('low_income_hh', __('Is Low Income House'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('low_income_hh', [true => 'Yes', false => 'No'], null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Is Low Income House'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id="lic-status">
        {!! Form::label('lic_status', __('Located In LIC'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('lic_status', [true => 'Yes', false => 'No'], null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Located In LIC'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row required" style="display:none" id="lic_id">
        {!! Form::label('lic_id', __('LIC Name'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('lic_id', $licNames, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('LIC Name'),
            ]) !!}
        </div>
    </div>


    <h3 class="mt-3"> {{ __('Water Source Information') }}</h3>

    <!-- Water Source Information & Water Supply Customer ID -->
    <div id="water-id">
        <div class="form-group row required">
            {!! Form::label('water_source_id', __('Main Drinking Water Source'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-5">
                {!! Form::select('water_source_id', $water_source, null, [
                    'class' => 'form-control col-sm-10',
                    'placeholder' => __('Main Drinking Water Source'),
                ]) !!}
            </div>
        </div>
    </div>

    <div id="water-customer-id" style="display: none;">
        <div class="form-group row">
            {!! Form::label('water_customer_id', __('Water Supply Customer ID'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-5">
                {!! Form::text('water_customer_id', null, [
                    'class' => 'form-control col-sm-10',
                    'placeholder' => __('Water Supply Customer ID'),
                    'autocomplete' => 'off',
                ]) !!}
            </div>
        </div>
    </div>

    <div class="form-group row required" id="water-pipe-id" style="display: none;">
        {!! Form::label('watersupply_pipe_code', __('Water Supply Pipe Line Code'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('watersupply_pipe_code', $waterSupply, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Water Supply Pipe Line Code'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id="well-presence">
        {!! Form::label('well_presence_status', __('Well in Premises'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('well_presence_status', [true => 'Yes', false => 'No'], null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Well in Premises'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id="distance-from-well" style="display: none;">
        {!! Form::label('distance_from_well', __('Distance of Well from Closest Containment (m)'), [
            'class' => 'col-sm-3 control-label',
        ]) !!}
        <div class="col-sm-5">
            {!! Form::number('distance_from_well', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Distance of Well from Closest Containment (m)'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>


    <h3 class="mt-3"> {{ __('Solid Waste Management Information') }} </h3>

    <div class="form-group row">
        {!! Form::label('swm_customer_id', __('SWM Customer ID'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::text('swm_customer_id', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('SWM Customer ID'),
                'autocomplete' => 'off',
            ]) !!}
        </div>
    </div>
    <h3 class="mt-3">{{ __('Sanitation System Information') }} </h3>

    <div class="form-group row required" id="toilet-presence">
        {!! Form::label('toilet_status', __('Presence of Toilet'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('toilet_status', [true => 'Yes', false => 'No'], null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Presence of Toilet'),
                'id' => 'toilet_status',
            ]) !!}
        </div>
    </div>

    <div class="form-group row required" id="defecation-place" style="display: none">
        {!! Form::label('defecation_place', __('Defecation Place'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('defecation_place', $defecationPlace, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Defecation Place'),
            ]) !!}
        </div>
    </div>

    {{-- Only show when sanitation system technology is communal --}}
    <div id="ctpt-toilet" style="display:none;">
        <div class="form-group row required">
            {!! Form::label('ctpt_name', __('Community Toilet Name'), ['class' => 'col-sm-3 control-label']) !!}
            <div class="col-sm-5">
                {!! Form::select('ctpt_name', $capitalizedctpt, null, [
                    'class' => 'form-control col-sm-10',
                    'placeholder' => __('Community Toilet Name'),
                ]) !!}
            </div>
        </div>
    </div>

    {{-- Show these options when toilet presence is Yes --}}
    <div class="form-group row required" id="toilet-info" style="display: none">
        {!! Form::label('toilet_count', __('Number of Toilets'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::number('toilet_count', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Number of Toilets'),
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id="shared-toilet" style="display:none;">
        {!! Form::label('household_with_private_toilet', __('Households with Private Toilet'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::number('household_with_private_toilet', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Households with Private Toilet'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

    <div class="form-group row" id="shared-toilet-popn" style="display:none;">
        {!! Form::label('population_with_private_toilet', __('Population with Private Toilet'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::number('population_with_private_toilet', null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Population with Private Toilet'),
                'autocomplete' => 'off',
                'oninput' => "this.value = this.value < 0 ? '' : this.value",
            ]) !!}
        </div>
    </div>

    <div class="form-group row required" id="toilet-connection" style="display: none">
        {!! Form::label('sanitation_system_id', __('Toilet Connection'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('sanitation_system_id', $toiletConnection, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Toilet Connection'),
            ]) !!}
        </div>
    </div>

    {{-- Hide containment ID if containment data is being edited --}}
    <div class="form-group row required" id="containment-id" style="display:none">
        {!! Form::label('build_contain', __('BIN of Pre-Connected Building'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('build_contain', $bin, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('BIN of Pre-Connected Building'),
            ]) !!}
        </div>
    </div>

    <div class="form-group row" style="display:none;" id="vacutug-accessible">
        {!! Form::label('desludging_vehicle_accessible', __('Building Accessible to Desludging Vehicle'), ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-5">
            {!! Form::select('desludging_vehicle_accessible', [true => 'Yes', false => 'No'], null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Building Accessible to Desludging Vehicle'),
            ]) !!}
        </div>
    </div>




    @if (empty($building))
        <!-- Containment information tab -->
        @include('fsm.containments.partial-form')
    @endif
    <!--  show if toilet connection is Sewer Network -->
    <div class="form-group row required" id="sewer-code" style="display:none">
        {!! Form::label('sewer_code', __('Sewer Code'), ['class' => 'col-sm-3 control-label  ']) !!}
        <div class="col-sm-5">
            {!! Form::select('sewer_code', $sewer_code, null, [
                'class' => 'form-control col-sm-10 sewer_code',
                'placeholder' => __('Sewer Code'),
            ]) !!}
        </div>
    </div>

    <!--  show if toilet connection is Drain Network -->
    <div class="form-group row required" style="display:none" id="drain-code">
        {!! Form::label('drain_code', __('Drain Code'), ['class' => 'col-sm-3 control-label  ']) !!}
        <div class="col-sm-5">
            {!! Form::select('drain_code', $drain_code, null, [
                'class' => 'form-control col-sm-10',
                'placeholder' => __('Drain Code'),
            ]) !!}
        </div>
    </div>
</div><!-- /.card-body -->

<div class="card-footer">
    <a href="{{ action('BuildingInfo\BuildingController@index') }}" class="btn btn-info">{{ __("Back to List")}}</a>
    {!! Form::submit(__('Save'), [
        'class' => 'btn btn-info prevent-multiple-submits',
        'id' => 'prevent-multiple-submits',
    ]) !!}
</div><!-- /.card-footer -->
@if (!empty($building))
    <div class="card">
        <h2 class="ml-4 mt-3">{{ __('Containment Information') }} </h2>
        <div class="card-header">
            <a href="{{ action('Fsm\ContainmentController@createContainment', [$building->public_id]) }}"
                class="btn btn-info">{{__('Add Containment to Building')}}</a>
        </div>
        <div class="card-body">
            @include('fsm.containments.list-containments')
        </div>
    </div>
@endif

@push('scripts')
    <script src="{{ asset('js/building-file-upload.js') }}?v={{ filemtime(public_path('js/building-file-upload.js')) }}"></script>
@endpush
<!-- Last Modified Date: 011-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
{{-- <script>
    $(document).ready(function() {
        $('.road_code').on('change', function() {
            var selectedRoadCode = $(this).find('option:selected').text();
            var codePart = selectedRoadCode.split(" - ")[0];
            if (codePart) {
                $.ajax({
                    url: '{{ route('buildings.check-house') }}',
                    type: 'GET',
                    data: {
                        road_code: codePart
                    },
                    success: function(response) {
                        var houseNumberSuffix = (response.count + 1).toString().padStart(4, '0');
                        var newHouseNumberValue = codePart + ' - ' + houseNumberSuffix;
                        $('input[name="house_number"]').val(newHouseNumberValue);
                    },
                    error: function(xhr) {
                        console.log('Error:', xhr);
                    }
                });
            }
        });
    });
</script> --}}
