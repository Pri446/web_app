<?php

namespace App\Http\Requests\BuildingInfo;

use Illuminate\Foundation\Http\FormRequest;

class BuildingMobileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Owner Information
            'owner_name' => ['required', 'string', 'max:255'],
            'nid' => ['nullable', 'string', 'max:20'],
            'owner_gender' => ['required', 'in:Male,Female'],
            'owner_contact' => ['required', 'string', 'max:15'],

            // Building Information
            'ward' => ['required', 'integer'],
            'road_code' => ['required', 'string', 'max:50'],
            'house_number' => ['required', 'string', 'max:50'],
            'construction_year' => ['nullable', 'date', 'before_or_equal:today'],
            'structure_type_id' => ['required', 'integer'],
            'floor_count' => ['nullable', 'integer', 'min:1', 'max:200'],

            // Sanitation Information
            'sanitation_system_id' => ['required', 'integer', 'in:3,4'],

            // Containment Information
            'type_id' => ['required', 'integer'],
            'size' => ['required', 'numeric', 'min:0'],
            'construction_date' => ['nullable', 'date', 'before_or_equal:today'],

            // Geometry
            'lat' => ['required', 'string'],
            'lng' => ['required', 'string'],

            //Applicationupdate 
            'id' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_name.required' => 'Owner name is required.',
            'owner_gender.required' => 'Owner gender is required.',
            'owner_gender.in' => 'Owner gender must be Male or Female.',
            'owner_contact.required' => 'Owner contact number is required.',

            'ward.required' => 'Ward is required.',
            'ward.integer' => 'Ward must be a number.',

            'road_code.required' => 'Road code is required.',
            'road_code.string' => 'Road code must be text.',

            'house_number.required' => 'House number is required.',

            'construction_year.required' => 'Construction year is required.',
            'construction_year.integer' => 'Construction year must be a number.',
            'construction_year.digits' => 'Construction year must be 4 digits.',
            'construction_year.lte' => 'Construction year cannot be in the future.',

            'structure_type_id.required' => 'Structure type is required.',
            'floor_count.integer' => 'Number of floors must be a whole number.',

            'sanitation_system_id.required' => 'Sanitation system is required.',
            'sanitation_system_id.in' => 'Sanitation system must be either 3 or 4.',

            'type_id.required' => 'Containment type is required.',
            'size.required' => 'Containment size is required.',
            'size.numeric' => 'Containment size must be numeric.',

            'construction_year.date' => 'Construction date must be a valid date.',
            'construction_year.before_or_equal' => 'Construction date cannot be in the future.',
            'lat.required' => 'Latitude is required.',
            'lng.required' => 'Longitude is required.',
            'id.required' => 'Application ID is required.',
        ];
    }
}
