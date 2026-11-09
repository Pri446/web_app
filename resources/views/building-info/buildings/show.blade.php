@extends('layouts.dashboard')
@section('title', $page_title)

@section('content')
    <div class="card card-info">
        <div class="card-header bg-transparent">

            <a href="{{ action('BuildingInfo\BuildingController@index') }}" class="btn btn-info ">{{ __('Back to List') }}</a>
        </div>

        <div class="card-body">
            {!! Form::open(['class' => 'form-horizontal']) !!}
            <h3 class="mt-4"> {{__('Owner Information')}} </h3>
            <div class="form-group row">

                {!! Form::label('owner_name', __('Owner Name'), ['class' => 'col-sm-2 control-label ']) !!}
                <div class="col-sm-3">
                    {!! Form::text('owner_name', $ownerPii['owner_name'] ?? '', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row ">
                {!! Form::label('nid',__('Owner NID') , ['class' => 'col-sm-2 control-label ']) !!}
                <div class="col-sm-3">
                    {!! Form::text('nid', $ownerPii['nid'] ?? '', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('gender', __('Owner Gender'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('gender', $ownerPii['owner_gender'] ?? '', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('contact_no', __('Owner Contact Number'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('contact_no', $ownerPii['owner_contact'] ?? '', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>



            <h3 class="mt-3"> {{ __('Building Information') }}</h3>

            <div class="form-group row" id="building_associated">
                {!! Form::label('is_main_building', __('Main Building'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('is_main_building', is_null($building->building_associated_to) ? 'Yes' : 'No', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row" id="building_associated">
                {!! Form::label('building_associated_to', __('BIN of Main Building'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('building_associated_to', $building->building_associated_to, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>



            <div class="form-group row">
                {!! Form::label('ward', __('Ward Number'), ['class' => 'col-sm-2 control-label ']) !!}
                <div class="col-sm-3">
                    {!! Form::text('ward', $building->ward, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('road_code', __('Road Code'), ['class' => 'col-sm-2 control-label control-label ']) !!}
                <div class="col-sm-3">
                    {!! Form::text('road_code', $building->road_code, [
                        'class' => 'form-control col-sm-10  font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('house_number', __('House Number'), ['class' => 'col-sm-2 control-label control-label ']) !!}
                <div class="col-sm-3">
                    {!! Form::text('house_number', $building->house_number, [
                        'class' => 'form-control col-sm-10  font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('house_locality', __('House Locality/Address'), [
                    'class' => 'col-sm-2 control-label control-label ',
                ]) !!}
                <div class="col-sm-3">
                    {!! Form::text('house_locality', $building->house_locality, [
                        'class' => 'form-control col-sm-10  font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <!-- Tax ID -->
            <div class="form-group row">
                {!! Form::label('tax_id', __('Tax Code/Holding ID'), ['class' => 'col-sm-2 col-form-label ']) !!}

                <div class="col-sm-3">
                    {!! Form::text('tax_id', $building->tax_code, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <!-- Building Location Information -->



            <!-- Basic Building Structure Information -->
            <div class="form-group row">
                {!! Form::label('structure_type', __('Structure Type'), ['class' => 'col-sm-2 control-label ']) !!}
                <div class="col-sm-3">
                    {!! Form::text('structure_type', $building->structure_type_id ? $building->StructureType->type : '', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('surveyed_date', __('Surveyed Date'), ['class' => 'col-sm-2 control-label ']) !!}
                <div class="col-sm-3">
                    {!! Form::text('surveyed_date', $building->surveyed_date, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('estimated_area',  __('Estimated Area of the Building (m²)'), [

                    'class' => 'col-sm-2 control-label',
                ]) !!}
                <div class="col-sm-3">
                    {!! Form::number('estimated_area', $building->estimated_area, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row" id="construction-year">
                {!! Form::label('construction_year', __('Construction Date'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('construction_year', $building->construction_year, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('floor_count', __('Number of Floors'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::number('floor_count', $building->floor_count, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('functional_use',__('Functional Use of Building'), ['class' => 'col-sm-2 control-label ']) !!}
                <div class="col-sm-3">
                    {!! Form::text('functional_use', $building->functional_use_id ? $building->FunctionalUse->name : '', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('use_category_id', __('Use Category of Building'), ['class' => 'col-sm-2 control-label ']) !!}
                <div class="col-sm-3">
                    @if (!empty($building->use_category_id))
                        {!! Form::text('use_category_id', $building->use_category_id ? $building->useCategory->name : '', [
                            'class' => 'form-control col-sm-10 font-weight-bold',
                            'readonly' => 'readonly',
                        ]) !!}
                    @else
                        {!! Form::text('use_category_id', null, [
                            'class' => 'form-control col-sm-10 font-weight-bold',
                            'readonly' => 'readonly'
                        ]) !!}
                    @endif
                </div>
            </div>


            <div class="form-group row" id="office-business-name">
                {!! Form::label('office_business_name', __('Office or Business Name'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('office_business_name', $building->office_business_name, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <!--  Building Population Information -->
            <div class="form-group row">
                {!! Form::label('household_served', __('Number of Households'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::number('household_served', $building->household_served, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('male_population', __('Male Population'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::number('male_population', $building->male_population, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('female_population', __('Female Population'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::number('female_population', $building->female_population, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('other_population', __('Other Population'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::number('other_population', $building->other_population, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('population_served', __('Population of Building'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::number('population_served', $building->population_served, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>


            <div class="form-group row">
                {!! Form::label('diff_abled_male_pop', __('Differently Abled Male Population'), [
                    'class' => 'col-sm-2 control-label',
                ]) !!}
                <div class="col-sm-3">
                    {!! Form::number('diff_abled_male_pop', $building->diff_abled_male_pop, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('diff_abled_female_pop', __('Differently Abled Female Population'), [
                    'class' => 'col-sm-2 control-label',
                ]) !!}
                <div class="col-sm-3">
                    {!! Form::number('diff_abled_female_pop', $building->diff_abled_female_pop, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('diff_abled_others_pop', __('Differently Abled Other Population'), [
                    'class' => 'col-sm-2 control-label',
                ]) !!}
                <div class="col-sm-3">
                    {!! Form::number('diff_abled_others_pop', $building->diff_abled_others_pop, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <h3 class="mt-3">{{ __('LIC Information') }} </h3>
            <div class="form-group row">
                {!! Form::label('is_lic',__('Is Low Income House'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('is_lic', is_null($building->low_income_hh)
                        ? ''
                        : ($building->low_income_hh === true ? 'Yes' : 'No'), [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('lic_status', __('Located In LIC'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('lic_status', !empty($building->Lic->community_name) ? 'Yes' : 'No', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('lic_id', __('LIC Name'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('lic_id', $building->Lic->community_name ?? '', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>


            <h3 class="mt-3"> {{ __("Water Source Information") }} </h3>
            <div class="form-group row">
                {!! Form::label('water_source', __('Main Drinking Water Source'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('water_source', $building->WaterSource->source ?? '', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('water_customer_id', __('Water Supply Customer ID'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('water_customer_id', $building->water_customer_id, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('watersupply_pipe_code', __('Water Supply Pipe Line Code'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('watersupply_pipe_code', $building->watersupply_pipe_code, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row" id="well-presence">
                {!! Form::label('well_presence_status', __('Well in Premises'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('well_presence_status', is_null($building->well_presence_status)
                        ? ''
                        : ($building->well_presence_status === true ? 'Yes' : 'No'), [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row" id="distance-from-well">
                {!! Form::label('distance_from_well', __('Distance of Well from Closest Containment (m)'), [
                    'class' => 'col-sm-2 control-label',
                ]) !!}
                <div class="col-sm-3">
                    {!! Form::number('distance_from_well', $building->distance_from_well, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <h3 class="mt-3">{{ __("Solid Waste Management Information") }}</h3>
            <div class="form-group row">
                {!! Form::label('swm_customer_id', __('SWM Customer ID'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('swm_customer_id', $building->swm_customer_id, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <h3 class="mt-3">{{ __("Sanitation System Information") }}</h3>

            <div class="form-group row">
                {!! Form::label('toilet_status', __('Presence of Toilet'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('toilet_status', $building->toilet_status == 1 ? 'Yes' : 'No', [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>


            <div class="form-group row">
                {!! Form::label('toilet_count', __('Number of Toilets'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('toilet_count', $building->toilet_count, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('household_with_private_toilet', __('Households with Private Toilet'), [
                    'class' => 'col-sm-2 control-label',
                ]) !!}
                <div class="col-sm-3">
                    {!! Form::text('household_with_private_toilet', $building->household_with_private_toilet, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('population_with_private_toilet', __('Population with Private Toilet'), [
                    'class' => 'col-sm-2 control-label',
                ]) !!}
                <div class="col-sm-3">
                    {!! Form::text('population_with_private_toilet', $building->population_with_private_toilet, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>


            <div class="form-group row">
                {!! Form::label('sanitation_system_id', __('Toilet Connection'), [
                    'class' => 'col-sm-2 control-label ',
                ]) !!}
                <div class="col-sm-3">
                    @if (!empty($building->sanitation_system_id))
                        {!! Form::text('sanitation_system_technology', $building->SanitationSystem->sanitation_system, [
                            'class' => 'form-control col-sm-10 font-weight-bold',
                            'readonly' => 'readonly',
                        ]) !!}
                    @else
                        {!! Form::text('sanitation_system_id', null, [
                            'class' => 'form-control col-sm-10 font-weight-bold',
                            'readonly' => 'readonly',
                        ]) !!}
                    @endif
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('ctpt_name',  __('Community Toilet Name'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    @if (!empty($building->sanitation_system_id) && isset($building->ctPt[0]->name))
                        {!! Form::text('ctpt_name', $building->ctPt[0]->name, [
                            'class' => 'form-control col-sm-10 font-weight-bold',
                            'readonly' => 'readonly',
                        ]) !!}
                    @else
                        {!! Form::text('ctpt_name', null, [
                            'class' => 'form-control col-sm-10 font-weight-bold',
                            'readonly' => 'readonly',
                        ]) !!}
                    @endif
                </div>
            </div>


            <div class="form-group row">
                {!! Form::label('sewer_code', __('Sewer Code'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('sewer_code', $building->sewer_code, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('drain_code', __('Drain Code'), ['class' => 'col-sm-2 control-label']) !!}
                <div class="col-sm-3">
                    {!! Form::text('drain_code', $building->drain_code, [
                        'class' => 'form-control col-sm-10 font-weight-bold',
                        'readonly' => 'readonly',
                    ]) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label('desludging_vehicle_accessible', __('Building Accessible to Desludging Vehicle'), [
                    'class' => 'col-sm-2 control-label',
                ]) !!}
                <div class="col-sm-3">
                    {!! Form::text(
                        'desludging_vehicle_accessible',
                        $building->desludging_vehicle_accessible === null
                            ? ''
                            : ($building->desludging_vehicle_accessible == 1
                                ? 'Yes'
                                : 'No'),
                        [
                            'class' => 'form-control col-sm-10 font-weight-bold',
                            'readonly' => 'readonly',
                        ],
                    ) !!}
                </div>
            </div>
            <div class="form-group row">
                {!! Form::label(
                    'house_image',
                    $imageSrc ? __('House Image') : __('No Building Image'),
                    ['class' => 'col-sm-2 control-label']
                ) !!}

                <div class="col-sm-3">
                    <a href="{{ $imageSrc ?: '#' }}" target="_blank">
                        <button
                            type="button"
                            class="btn btn-info"
                            {{ $imageSrc ? '' : 'disabled' }}>
                            <i class="fa-solid fa-eye"></i> {{ __('View Image') }}
                        </button>
                    </a>
                </div>
            </div>


        </div><!-- /.card-body -->
    </div>
    {!! Form::close() !!}


@endsection
