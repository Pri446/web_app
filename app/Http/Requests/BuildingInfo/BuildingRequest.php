<?php

namespace App\Http\Requests\BuildingInfo;

use Illuminate\Foundation\Http\FormRequest;
use Validator, Input, Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;
class BuildingRequest extends FormRequest
{
    /**
     * Determine if the user  authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //owner part
            'owner_name.required' => __('Owner Name is required.'),
            'owner_contact.integer' => __('Owner Contact should be integer value.'),
            'owner_gender.required' => __('Owner Gender is required.'),
            'owner_contact.required' => __('Owner Contact Number is required.'),


            //Building Information
            'main_building.required' => __('The Main Building is required.'),
            'building_associated_to.required_if' => __('The BIN of Main Building is required.'),
            'ward.required' => __('Ward Number is required.'),
            'floor_count.required' => __('Number of Floors is required.'),
            'tax_code.required' => __('Tax Code/Holding ID is required.'),
            'road_code.required' => __('Road Code is required.'),
            'house_number.unique' => __('The House Number is Already Taken.'),
            'main_building.required' => __('The Main Building status required.'),
            'building_associated_to.required_if' => __('BIN of Main Building required.'),
            'ward.required' => __('Ward Number required.'),
            'structure_type_id.required' => __('Structure Type required.'),
            'functional_use_id.required' => __('Functional Use of Building required.'),
            'house_number.unique' => __('The House Number is Taken.'),
            'main_building.required' => __('The Main Building status required.'),
            'building_associated_to.required_if' => __('BIN of Main Building required.'),
            'house_number.unique' => __('The House Number is Taken.'),
            //population Validation
            'diff_abled_male_pop.lte' => __('The Differently Abled Male Population must not exceed the Male Population.'),
            'diff_abled_female_pop.lte' => __('The Differently Abled Female Population must not exceed the Female Population.'),
            'diff_abled_others_pop.lte' => __('The Differently Abled Other Population must not exceed the Other Population.'),

            'construction_year.required' => __('Building Construction Date is required.'),
            //lic
            'low_income_hh.required' => __('Is Low Income House is required.'),

            'lic_id.required_if' => __('LIC Name is required.'),
            //Water Source Information
            'water_source_id.required' => __('Main Drinking Water Source is required.'),
            'watersupply_pipe_code.required_if' => __('Water Supply Pipe Line Code is required.'),

            //Sanitation System Information
            'toilet_status.required' => __('Presence of Toilet is required.'),
            'sewer_code.required_if' => __('Sewer Code is required.'),
            'drain_code.required_if' => __('Drain Code is required.'),
            'ctpt_name.required_if' => __('Community Toilet Name is required.'),

            'size.required_if' => __('Containment Volume (m³) is required. Enter dimensions to auto generate.'),

            'geom.required_if' => __('Building Footprint (KML) is required.'),
            'floor_count.numeric' => __('Number of Floors should be numeric value.'),
            'use_category_id.required_with' => __('Use Category of Building is required.'),

            'depth.numeric' => __('Tank Depth should be numeric value.'),
            'tank_length.numeric' => __('Tank Length should be numeric value.'),
            'tank_width.numeric' => __('Tank Width should be numeric value.'),
            'pit_depth.numeric' => __('Pit Depth should be numeric value.'),
            'pit_diameter.integer' => __('Pit Diameter should be integer value.'),
            'size.numeric' => __('Containment Volume should be numeric value.'),
            'floor_count.min' => __('Number of Floors should be positive and non-zero value.'),
            'household_with_private_toilet.lte' => __('Household with Private Toilet must not exceed the Number of Households.'),
            'population_with_private_toilet.lte' => __('Population with Private Toilet must not exceed the Population of Building.'),
            'toilet_count.min' => __('Number of Toilets should be at least 1.'),
            'toilet_count.integer' => __('Number of Toilets should be integer value.'),
            'toilet_count.required_if' => __('Number of Toilets is required.'),
            'household_served.required_unless' => __('Number of Households is required.'),
            'population_served.required_unless' => __('Population of Building is required.'),
            'household_served.integer' => __('Number of Household should be integer value.'),
            'population_served.integer' => __('Number of Population should be integer value.'),
            'distance_from_well.integer' => __('Distance of containment from well should be integer value.'),
            'distance_from_well.min' => __('Distance of containment from well should be positive value.'),
            'depth.min' => __('Tank depth should be positive value.'),
            'tank_length.min' => __('Tank length should be positive value.'),
            'tank_width.min' => __('Tank width Count should be positive value.'),
            'pit_depth.min' => __('Pit Depth should be positive value.'),
            'pit_diameter.min' => __('Pit Diameter Count should be positive value.'),
            'size.min' => __('Containment Volume should be positive value.'),
            'construction_year' => __('Cant Select Future Value.'),
            'sanitation_system_id.required_if' => __('Toilet Connection is required.'),
            //containment message for validation
            'type_id.required_if' => __('Containment Type is required when Toilet Connection Septic or Pit/Holding.'),
            'defecation_place.required_if' => __('Defecation Place is required.'),
            'build_contain.required_if' => __('BIN of Pre-Connected Building is required.'),
            // 'construction_date.required_if'=>'Containment Construction date  required.',
            'pit_shape.required_if' => __('Pit shape is required.'),
            'house_image.image' => __('The house image must be an image.'),
            'drain_code.exists' => __('Invalid Drain Code. Please select a valid Drain Code.'),

        ];
    }

    public function rules()
    {
        /*
        * API update should use separate API validation rules.
        *
        * This supports:
        * PUT /api/buildings/{bin}
        * POST /api/buildings/{bin} with _method=PUT
        */
        if ($this->is('api/*')) {
            $isApiUpdate =
                $this->isMethod('PUT') ||
                $this->isMethod('PATCH') ||
                strtoupper((string) $this->input('_method')) === 'PUT' ||
                $this->route('bin') ||
                $this->route('building');

            return $isApiUpdate ? $this->apiUpdate() : $this->store();
        }

        return $this->isMethod('POST') ? $this->store() : $this->update();
    }

    public function store()
    {
        Validator::extend(
            'file_extension',
            function ($attribute, $value, $parameters, $validator) {
                if (!in_array($value->getClientOriginalExtension(), $parameters)) {
                    return false;
                } else {
                    return true;
                }
            },
            __('Building Footprint (KML) file must be kml format.') 
        );
        $use_cat = $this->input('use_category_id');
        return [
            // // Owner Infomation
            'owner_name' => 'required',
            'owner_gender' => 'required',
            'owner_contact' => 'integer|min:0|required',
            //Building Information
            'main_building' => 'required',
            'building_associated_to' => 'required_if:main_building,0',
            'ward' => 'required',
            'road_code' => 'required',
            'house_number' => 'nullable|unique:pgsql.building_info.buildings,house_number',
            'tax_code' => 'required',
            'structure_type_id' => 'required',
             //year of building Construction
            'construction_year' => 'required|date|before_or_equal:today',
             //year of building Construction
            'construction_year' => 'required|date|before_or_equal:today',
            'floor_count' => 'required|numeric|min:0.1',
            'functional_use_id' => 'required',
            'use_category_id' => 'required_with:functional_use_id',
            'household_served' => [
                // not required if use cat is Public Toilet or Community Toilet
                'required_unless:use_category_id,34,35',
                'nullable',
                'integer',
                'min:0',
            ],
            'male_population' => 'nullable|integer|min:0',
            'female_population' => 'nullable|integer|min:0',
            'other_population' => 'nullable|integer|min:0',
            'diff_abled_male_pop' => 'nullable|integer|min:0|exclude_if:diff_abled_male_pop,0|lte:male_population',
            'diff_abled_female_pop' => 'nullable|integer|min:0|exclude_if:diff_abled_female_pop,0|lte:female_population',
            'diff_abled_others_pop' => 'nullable|integer|min:0|exclude_if:diff_abled_others_pop,0|lte:other_population',
            'population_served' => [
                // not required if use cat is Public Toilet or Community Toilet
                'required_unless:use_category_id,34,35',
                'nullable',
                'integer',
                'min:0',
            ],
           
            //Lic Information
            'low_income_hh' => 'required',
            // 'lic_status' => 'required_if:low_income_hh,1',
            'lic_id' => 'required_if:lic_status,1',
            //water source Information
            'water_source_id' => 'required',
            'watersupply_pipe_code' => 'required_if:water_source_id,1',
            //sanitation system Information
            'toilet_status' => ['required',
             function ($attribute, $value, $fail) use ($use_cat) {
                if (($use_cat == 34 && $value != true) || ($use_cat == 35 && $value != true) ) {
                    $fail(__("The Toilet Presence must be Yes when Use Category is Public Toilet or Community Toilet"));
                }
            }
            ],
            'toilet_count' => 'exclude_if:toilet_status,0 |required_if:toilet_status,1| integer| min:1',
            'sanitation_system_id' => 'required_if:toilet_status,1',
            'defecation_place' => 'required_if:toilet_status,0',
            'ctpt_name' => 'exclude_if:toilet_status,1 | required_if:defecation_place,9',
            'household_with_private_toilet' => 'nullable |min:0 | lte:household_served',
            'population_with_private_toilet' =>'nullable|min:0| lte:population_served',

            'type_id' => 'exclude_if:toilet_status,0 | required_if:sanitation_system_id,3,4',
            'size' =>  'exclude_if:toilet_status,0 | required_if:sanitation_system_id,3,4',

            'type_id' => 'exclude_if:toilet_status,0 | required_if:sanitation_system_id,3,4',
            'size' =>  'exclude_if:toilet_status,0 | required_if:sanitation_system_id,3,4',
            'depth' => 'numeric|nullable|min:0',
            'tank_length' => 'numeric|nullable|min:0',
            'tank_width' => 'numeric|nullable|min:0',
            'pit_depth' => 'numeric|nullable|min:0',
            'pit_diameter' => 'numeric|nullable|min:0',
            'build_contain' => 'exclude_if:toilet_status,0 | required_if:sanitation_system_id,11',
            //drain and sewer code
            'sewer_code' => 'exclude_if:toilet_status,0 |exclude_if:type_id,2,14 | required_if:sanitation_system_id,1| required_if:type_id,1,13',
           'drain_code' => [
                'exclude_if:toilet_status,0',
                'exclude_if:type_id,1,13',
                'required_if:sanitation_system_id,2',
                'required_if:type_id,2,14',
                'nullable',
                'string',
                Rule::exists('pgsql.utility_info.drains', 'code'),
            ],
            'geom' => 'required_if:kml,null|file_extension:kml|max:1024',
            'house_image' => 'nullable|image|mimes:jpeg,jpg|max:5120', // 5MB = 5120KB

        ];
    }

    public function update()
    {
        $bin = $this->input('building'); 
        $use_cat = $this->input('use_category_id');
        Validator::extend('file_extension', function ($attribute, $value, $parameters, $validator) {
            if (!in_array($value->getClientOriginalExtension(), $parameters)) {
                return false;
            } else {
                return true;
            }
        }, "{{ __('File must be kml format.') }}" );
        return [
            //  compulsory fields
            // Owner Information
            'owner_name' => 'nullable|string',
            'owner_contact' => 'nullable|integer|min:0',
            'owner_gender' => 'nullable|in:Male,Female,Others',
            'nid' => 'nullable|string',
            //Building Information
            'main_building' => 'required',
            // associated bin required only if not main building
            'building_associated_to' => 'required_if:main_building,0',
            'ward' => 'required',
            'road_code' => 'required',
            'house_number' => 'nullable|unique:pgsql.building_info.buildings,bin,' . $bin . ',bin',
            'tax_code' => 'required',
            'structure_type_id' => 'required',
            'use_category_id' => 'required_with:functional_use_id',
            //year of building Construction
            'construction_year' => 'required|date|before_or_equal:today',
            'floor_count' => 'required|numeric|min:0.1',
            'functional_use_id' => 'required',
            'household_served' => [
                // not required if use cat is Public Toilet or Community Toilet
                'required_unless:use_category_id,34,35',
                'integer',
                'nullable',
                'min:0'
            ],
            'population_served' => [
                // not required if use cat is Public Toilet or Community Toilet
                'required_unless:use_category_id,34,35',
                'integer',
                'nullable',
                'min:0'
            ],
            'diff_abled_male_pop' => 'nullable|integer|min:0|exclude_if:diff_abled_male_pop,0|lte:male_population',
            'diff_abled_female_pop' => 'nullable|integer|min:0|exclude_if:diff_abled_female_pop,0|lte:female_population',
            'diff_abled_others_pop' => 'nullable|integer|min:0|exclude_if:diff_abled_others_pop,0|lte:other_population',
            //Lic Information
            'low_income_hh' => 'required',
            'lic_id' => 'required_if:lic_status,1', 
            //water source Information
            'water_source_id' => 'required',
            //Lic Information
            'low_income_hh' => 'required',
            'lic_id' => 'required_if:lic_status,1',
            //water source Information
            'water_source_id' => 'required',
            'watersupply_pipe_code' => 'required_if:water_source_id,1',

            //sanitation system Information
            'toilet_status' => ['required',
            function ($attribute, $value, $fail) use ($use_cat) {
               if (($use_cat == 34 && $value != true) || ($use_cat == 35 && $value != true) ) { 
                   $fail(__("The Toilet Presence must be Yes when Use Category is Public Toilet or Community Toilet"));
               }
           }
           ],
            'toilet_count' => 'exclude_if:toilet_status,0 |required_if:toilet_status,1 | integer | min:1',
            'sanitation_system_id' => 'required_if:toilet_status,1',
            'defecation_place' => 'required_if:toilet_status,0',
            'ctpt_name' => 'exclude_if:toilet_status,1 | required_if:defecation_place,9',
            'household_with_private_toilet' => 'nullable |min:0| lte:household_served',
            'population_with_private_toilet' => 'nullable|min:0| lte:population_served',

            //drain and sewer code
            'sewer_code' => 'exclude_if:toilet_status,0 |required_if:sanitation_system_id,1',
            'drain_code' => 'exclude_if:toilet_status,0 |required_if:sanitation_system_id,2',
            'build_contain' => 'exclude_if:toilet_status,0 |required_if:sanitation_system_id,11',
            'house_image' => 'nullable|image|mimes:jpeg,jpg|max:5120', // 5MB = 5120KB
            'geom' => 'nullable|file_extension:kml|max:1024',
        ];
    }

   public function apiUpdate()
{
    Validator::extend('file_extension', function ($attribute, $value, $parameters, $validator) {
        if (!$value) {
            return true;
        }

        return in_array(
            strtolower($value->getClientOriginalExtension()),
            array_map('strtolower', $parameters)
        );
    }, 'File must be kml format');

    $bin = $this->route('bin')
        ?? $this->route('building')
        ?? $this->input('building')
        ?? $this->input('bin');

    $use_cat = $this->input('use_category_id');

    return [
        // Owner Information
        'owner_name' => 'required',
        'owner_contact' => 'required|integer|min:0',
        'owner_gender' => 'required',

        // Building Information
        'main_building' => 'required',
        'building_associated_to' => 'required_if:main_building,0',

        'ward' => 'required',
        'road_code' => 'required',
        'house_number' => [
            'nullable',
            Rule::unique('pgsql.building_info.buildings', 'house_number')->ignore($bin, 'bin'),
        ],
        'house_locality' => 'nullable',
        'tax_code' => 'required',
        'structure_type_id' => 'required',

        'construction_year' => 'required|date|before_or_equal:today',
        'floor_count' => 'required|numeric|min:0.1',

        'functional_use_id' => 'required',
        'use_category_id' => 'required_with:functional_use_id',

        // Population Information
        'household_served' => [
            'required_unless:use_category_id,34,35',
            'nullable',
            'integer',
            'min:0',
        ],

        'population_served' => [
            'required_unless:use_category_id,34,35',
            'nullable',
            'integer',
            'min:0',
        ],

        'male_population' => 'nullable|integer|min:0',
        'female_population' => 'nullable|integer|min:0',
        'other_population' => 'nullable|integer|min:0',

        'diff_abled_male_pop' => 'nullable|integer|min:0|exclude_if:diff_abled_male_pop,0|lte:male_population',
        'diff_abled_female_pop' => 'nullable|integer|min:0|exclude_if:diff_abled_female_pop,0|lte:female_population',
        'diff_abled_others_pop' => 'nullable|integer|min:0|exclude_if:diff_abled_others_pop,0|lte:other_population',

        // LIC Information
        'low_income_hh' => 'required',
        'lic_status' => 'nullable',
        'lic_id' => 'required_if:lic_status,1',

        // Water Source Information
        'water_source_id' => 'required',
        'watersupply_pipe_code' => 'required_if:water_source_id,1',

        // Sanitation System Information
        'toilet_status' => [
            'required',
            function ($attribute, $value, $fail) use ($use_cat) {
                if (($use_cat == 34 && $value != true) || ($use_cat == 35 && $value != true)) {
                    $fail("The Toilet Presence must be Yes when Use Category is Public Toilet or Community Toilet");
                }
            },
        ],

        'toilet_count' => [
            'exclude_if:toilet_status,0',
            'required_if:toilet_status,1',
            'integer',
            'min:1',
        ],

        'sanitation_system_id' => [
            'exclude_if:toilet_status,0',
            'required_if:toilet_status,1',
        ],

        'defecation_place' => [
            'exclude_if:toilet_status,1',
            'required_if:toilet_status,0',
        ],

        'ctpt_name' => [
            'exclude_if:toilet_status,1',
            'required_if:defecation_place,9',
            'nullable',
            'string',
            'max:255',
        ],

        'household_with_private_toilet' => [
            'nullable',
            'integer',
            'min:0',
            'lte:household_served',
        ],

        'population_with_private_toilet' => [
            'nullable',
            'integer',
            'min:0',
            'lte:population_served',
        ],

        // Containment validation
        'type_id' => [
            'exclude_if:toilet_status,0',
            Rule::requiredIf(function () {
                return in_array((string) $this->input('sanitation_system_id'), ['3', '4'], true);
            }),
            'nullable',
        ],

        'size' => [
            'exclude_if:toilet_status,0',
            Rule::requiredIf(function () {
                return in_array((string) $this->input('sanitation_system_id'), ['3', '4'], true);
            }),
            'nullable',
            'numeric',
            'min:0',
        ],

        'depth' => 'nullable|numeric|min:0',
        'tank_length' => 'nullable|numeric|min:0',
        'tank_width' => 'nullable|numeric|min:0',
        'pit_depth' => 'nullable|numeric|min:0',
        'pit_diameter' => 'nullable|numeric|min:0',

        'build_contain' => [
            'exclude_if:toilet_status,0',
            Rule::requiredIf(function () {
                return (string) $this->input('sanitation_system_id') === '11';
            }),
            'nullable',
        ],

        // Fixed sewer_code: exclude if toilet_status=0 OR type_id=2,14; required if sanitation=1 AND type=1,13
        'sewer_code' => [
            'exclude_if:toilet_status,0',
            'exclude_if:type_id,2,14',
            Rule::requiredIf(function () {
                return (string) $this->input('sanitation_system_id') === '1'
                    && in_array((string) $this->input('type_id'), ['1', '13'], true);
            }),
            'nullable',
            'string',
        ],

        // Fixed drain_code: exclude if toilet_status=0 OR type_id=1,13; required if sanitation=2 AND type=2,14
        'drain_code' => [
            'exclude_if:toilet_status,0',
            'exclude_if:type_id,1,13',
            Rule::requiredIf(function () {
                return (string) $this->input('sanitation_system_id') === '2'
                    && in_array((string) $this->input('type_id'), ['2', '14'], true);
            }),
            Rule::exists('utility_info.drains', 'drain_code'),
            'nullable',
            'string',
        ],

        // KML / geometry
        'kml' => 'nullable',

        'geom' => [
            'nullable',
            'file_extension:kml',
            'max:1024',
        ],

        'house_image' => 'nullable|image|mimes:jpeg,jpg|max:5120',
    ];
}
}
