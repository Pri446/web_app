@php
    $piiUnlocked = auth()->check() && (
        app(\App\Services\PiiAccessService::class)->isUnlocked(auth()->user(), 'view') ||
        app(\App\Services\PiiAccessService::class)->isUnlocked(auth()->user(), 'export')
    );
    $encryptionService = app(\App\Services\PiiEncryptionService::class);
    $formatPii = function($val) use ($piiUnlocked, $encryptionService) {
        if ($val === null || $val === '') {
            return '';
        }
        if (!$piiUnlocked) {
            return '********';
        }
        try {
            return $encryptionService->decrypt($val);
        } catch (\Throwable $e) {
            return '********';
        }
    };
@endphp
<table>
    <thead>
    <tr>
        <th align="right" width="20"><h1><strong>{{__('BIN')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Owner Name')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Owner NID')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Owner Gender')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Owner Contact Number')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('BIN of Main Building')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Ward')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Road Code')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('House Number')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('House Locality/Address')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Tax Code/Holding ID')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Structure Type')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Surveyed Date')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Construction Date')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Number of Floors')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Functional Use of Building')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Use Category of Building')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Office or Business Name')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Number of Households')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Male Population')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Female Population')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Other Population')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Population of Building')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Differently Abled Male Population')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Differently Abled Female Population')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Differently Abled Other Population')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Is Low Income House')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('LIC Name')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Main Drinking Water Source')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Water Supply Customer ID')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Water Supply Pipe Line Code')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Well in Premises')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Distance of Well from Closest Containment (m)')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('SWM Customer ID')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Presence of Toilet')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Number of Toilets')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Households with Private Toilet')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Population with Private Toilet')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Toilet Connection')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Sewer Code')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Drain Code')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Building Accessible to Desludging Vehicle')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Estimated Area of the Building (m²)')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Community Toilet Name')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Verification Status')}}</strong></h1></th>
    </tr>
    </thead>
    <tbody>
    @foreach($buildingResults as $building)
        <tr>
            <td>{{ $building->bin }}</td>
            <td>{{ $formatPii($building->owner_name ?? ($building->Owners->owner_name ?? null)) }}</td>
            <td>{{ $formatPii($building->nid ?? ($building->Owners->nid ?? null)) }}</td>
            <td>{{ $formatPii($building->owner_gender ?? ($building->Owners->owner_gender ?? null)) }}</td>
            <td>{{ $formatPii($building->owner_contact ?? ($building->Owners->owner_contact ?? null)) }}</td>
            <td>{{ $building->building_associated_to }}</td>
            <td>{{ $building->ward }}</td>
            <td>{{ $building->road_code }}</td>
            <td>{{ $building->house_number }}</td>
            <td>{{ $building->house_locality }}</td>
            <td>{{ $building->tax_code }}</td>
            <td>{{ $building->structure_type }}</td>
            <td>{{ $building->surveyed_date }}</td>
            <td>{{ $building->construction_year }}</td>
            <td>{{ $building->floor_count }}</td>
            <td>{{ $building->functional_use_id }}</td>
            <td>{{ $building->use_category_id }}</td>
            <td>{{ $building->office_business_name }}</td>
            <td>{{ $building->household_served }}</td>
            <td>{{ $building->male_population }}</td>
            <td>{{ $building->female_population }}</td>
            <td>{{ $building->other_population }}</td>
            <td>{{ $building->population_served }}</td>
            <td>{{ $building->diff_abled_male_pop }}</td> 
            <td>{{ $building->diff_abled_female_pop }}</td>
            <td>{{ $building->diff_abled_others_pop }}</td>
            <td>{{ is_null($building->low_income_hh)
            ? ''
            : ($building->low_income_hh === true ? 'Yes' : 'No') }}</td>
            <td>{{ $building->community_name }}</td>
            <td>{{ $building->water_source }}</td>
            <td>{{ $building->water_customer_id }}</td>
            <td>{{ $building->watersupply_pipe_code }}</td>
            <td>{{ is_null($building->well_presence_status)
            ? ''
            : ($building->well_presence_status === true ? 'Yes' : 'No') }}</td>
            <td>{{ $building->distance_from_well }}</td>
            <td>{{ $building->swm_customer_id }}</td> 
            <td>{{ $building->toilet_status ? 'Yes' : 'No' }}</td>
            <td>{{ $building->toilet_count }}</td>
            <td>{{ $building->household_with_private_toilet }}</td>
            <td>{{ $building->population_with_private_toilet }}</td>
            <td>{{ $building->sanitation_system }}</td>
            <td>{{ $building->sewer_code }}</td>
            <td>{{ $building->drain_code }}</td>
            <td>{{ $building->desludging_vehicle_accessible === true ? 'Yes' : 'No' }}</td>
            <td>{{ $building->estimated_area }}</td>
            <td>{{ $building->toilet_name }}</td>
            <td>{{ $building->verification_status ? 'Yes' : 'No'}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
