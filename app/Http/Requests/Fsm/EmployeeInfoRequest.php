<?php
//Last Modified Date: 14-04-2024
//Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Http\Requests\Fsm;

use App\Models\Fsm\EmployeeInfo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeeInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected $id;

    public function __construct(Request $request)
    {
        $request->isMethod('POST')?$this->id = null:$this->id = (integer) $request->route()->employee_info->id;
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /** Prevent service-provider users from assigning employees to another provider. */
    protected function prepareForValidation()
    {
        if (Auth::check() && Auth::user()->service_provider_id) {
            $this->merge([
                'service_provider_id' => (int) Auth::user()->service_provider_id,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules= ($this->isMethod('POST')? $this->store() : $this->update());
        return $rules;
    }

    public function messages()
    {
        return[
            'name.regex' => __('The Name may only contain letters and spaces.'),
            'name.required' => __('The Employee Name is required.'),
            'gender.required' => __('The Employee Gender is required.'),
            'contact_number.required'=> __('The Employee Contact Number is required.'),
            'contact_number.integer' => __('The Employee Contact Number must be an integer.'),
            'address.string'=>__('The Address must be a string.'),
            'employee_type.required' => __('The Designation is required.'),
            'year_of_experience.integer' => __('The Year of Experience must be an integer.'),
            'wage.integer' => __('The Monthly Remuneration field must be an integer.'),
            'license_number.required_if'=>__('The Driving License Number is required when designation is driver.'),
            'license_number.integer'=>__('The Driving License Number must be an integer.'),
            'license_issue_date.required_if'=>__('The Driving License Issue Date is required when designation is driver.'),
            'employment_start.required'=>__('The Job Start Date is required.'),
            'status.required'=>__('The Status is required.'),
            'employment_end.required_if'=>__('The Job End Date is required when Status is Inactive.'),
            'employment_end.after' => __('The Job End Date must be a date after Job Start Date.'),
            'employment_end.after' => __('The Job End Date must be a date after Job Start Date.'),
            'license_number.unique'=>__('The license plate number has already been taken.'),
            'service_provider_id.required' => __('The Service Provider field is required.'),
            'service_provider_id.exists' => __('The selected service provider is invalid or not operational.'),

        ];
    }

    public function store()
    {
        return [
            'service_provider_id' => [
                'required',
                'integer',
                Rule::exists('pgsql.fsm.service_providers', 'id')->where(function ($query) {
                    return $query->where('status', 1)->whereNull('deleted_at');
                }),
            ],
            'name' => 'required|string',
            'gender' => 'required|string',
            'contact_number' => 'required|integer',
            'dob' =>'nullable|date' ,
            'address' => 'nullable|string' ,
            'employee_type' => 'required|string',
            'year_of_experience' => 'nullable|integer|min:0',
            'wage' => 'nullable|integer|min:0',
            'license_number' => [
                'nullable',
                'required_if:employee_type,Driver',  // Required if employee_type is 'Driver'
                'string',  // Ensure it is a string
                Rule::unique('pgsql.fsm.employees', 'license_number')->where(function ($query) {
                    return $query->whereNull('deleted_at');
                })  // Ensure uniqueness among non-soft-deleted records
            ],            'license_issue_date' => 'required_if:employee_type,Driver, date',
            'training_status' => 'nullable|string',
            'employment_start' => 'required|date',
            'status' => 'required|boolean',
            'employment_end' => [
                'nullable',
                'date',
                'after:employment_start',
                'required_if:status,0'
            ],
        ];
    }

    public function update()
    {
            $id = $this->route()->employee_info;
        return [
            'service_provider_id' => [
                'required',
                'integer',
                Rule::exists('pgsql.fsm.service_providers', 'id')->where(function ($query) {
                    return $query->where('status', 1)->whereNull('deleted_at');
                }),
            ],
            'name' => 'required|string',
            'gender' => 'required|string',
            'contact_number' => 'required|integer',
            'dob' =>'nullable|date' ,
            'address' => 'nullable|string' ,
            'employee_type' => 'required|string',
            'year_of_experience' => 'nullable|integer|min:0',
            'wage' => 'nullable|integer|min:0',
            'license_number' => ['nullable','required_if:employee_type,Driver,string',Rule::unique('pgsql.fsm.employees', 'license_number')->where(function ($query) {
                return $query->whereNull('deleted_at');
            })->ignore($id)],
            'license_issue_date' => 'required_if:employee_type,Driver, date',
            'training_status' => 'nullable|string',
            'employment_start' => 'required|date',
            'status' => 'required|boolean',
            'employment_end' => [
                'nullable',
                'date',
                'after:employment_start',
                'required_if:status,0'
            ],
        ];
    }
}
