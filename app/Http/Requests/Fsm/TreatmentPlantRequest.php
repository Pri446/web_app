<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)
namespace App\Http\Requests\Fsm;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\Rules\Password;

class TreatmentPlantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        'name.required' => __('The Name is required.'),
        'email.required' => __('The Email is required.'),
        'email.email' => __('Please enter a valid email address.'),
        'email.unique' => __('The Email has already been taken.'),
        'location.required' => __('The Location is required.'),
        'geom.required' => __('The geom is required.'),
        'name.regex' => __('The Name may only contain letters and spaces.'),
        'latitude.numeric' => __('The Latitude must be numeric.'),
        'longitude.numeric' => __('The Longitude must be numeric.'),
        'capacity_per_day.numeric' => __('The Capacity Per Day (m³) must be numeric.'),
        'capacity_per_day.required' => __('The Capacity Per Day (m³) is required.'),
        'caretaker_name.required' => __('The Caretaker Name is required.'),
        'caretaker_name.regex' => __('The Caretaker Name may only contain letters and spaces.'),
        'caretaker_number.required' => __('The Caretaker Number is required.'),
        'caretaker_number.integer' => __('The Caretaker Number must be an integer.'),
        'type.required' => __('The Treatment Plant Type is required.'),
        'status.required' => __('The Status is required.'),
        'password.required_if' => __('The Password is required when create user is on.'),
        'email.required_if' => __('The Email field is required when create user is on.'),
        'caretaker_number.min' => __('The caretaker number must be at least 1'),
        'password.uncompromised' => __('The given password has appeared in a data leak. Please choose a different password.'),
    ];
}


    public function rules()
    {
        $rules = ($this->isMethod('POST') ? $this->store() : $this->update());

        // Check if the geom field exists in the request
        if ($this->isMethod('POST')) {
            if (!$this->filled('latitude') || !$this->filled('longitude')) {
                $rules['geom'] = 'required'; // Make geom required if lat or long are missing
            } else {
                $rules['geom'] = 'nullable'; // Allow geom to be nullable if lat and long are provided
            }
        } else {
            // For update, ensure geom is nullable if not present in the request
            $rules['geom'] = 'nullable';
        }

        return $rules;
    }



    /**
     * Store validation rules.
     */
    public function store()
    {
        $rules = [
            'name' => 'required',
            'location' => 'required',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity_per_day' => 'required|numeric|min:0.01',
            'caretaker_number' => 'required|integer|min:1',
            'caretaker_name' => 'required',
            'type' => 'required',
            'email' => [
                'required_if:create_user,on',
                'nullable',
                'string',
                'email',
                'max:255',
                'unique:pgsql.auth.users',
                'regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix'
            ],
            'status' => 'required',
            'password' => ['exclude_unless:create_user,on', 'required_if:create_user,on', 'nullable', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(), 'confirmed'],
            // Removing the geom required validation and relying on custom validation
            'geom' => 'nullable|latitude_longitude', // Custom rule checks if both latitude and longitude are present
        ];

        return $rules;
    }


    /**
     * Update validation rules.
     */
    public function update()
    {
        return [
            'name' => 'required',
            'location' => 'required',
            'capacity_per_day' => 'required|numeric|min:0.01',
            'caretaker_number' => 'required|integer|min:1',
            'type' => 'required',
            'caretaker_name' => 'nullable',
            'status' => 'required',
        ];
    }

    /**
     * Add custom validation rule for latitude and longitude.
     */
    public function withValidator($validator)
    {
        $validator->addExtension('latitude_longitude', function ($attribute, $value, $parameters, $validator) {
            // Check if both latitude and longitude are missing
            if (!$this->filled('latitude') || !$this->filled('longitude')) {
                return false; // Invalid if either of the two is missing
            }

            return true;
        });
    }

}
