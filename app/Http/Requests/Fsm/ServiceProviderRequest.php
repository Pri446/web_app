<?php
// Last Modified Date: 09-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Http\Requests\Fsm;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
class ServiceProviderRequest extends FormRequest
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



    public function rules()
    {
      $id = $this->route()->service_provider;

        switch ($this->method()) {
            case 'GET':
            case 'DELETE':
                {
                    return [];
                }
            case 'POST':
                {
                    return [
                        'company_name' => [
                            'required',
                            'string',
                            Rule::unique('pgsql.fsm.service_providers', 'company_name')
                                ->where(function ($query) use ($id) {
                                    $query->whereNull('deleted_at')
                                          ->where('id', '<>', $id);
                                }),],

                        'email' => ['required', 'email', 'max:255', 'unique:pgsql.auth.users'],

                        'ward' => 'required|integer',
                        'company_location' => ['required', 'string', 'max:255'],
                        'contact_person' => ['required', 'string', 'max:255'],                                                                                                                                                              
                        'contact_number' => 'required|regex:/^[0-9]+$/',
                        'contact_gender' => 'required|string',
                        'status' => 'required|boolean',
                        'contract_document_pdf' => [
                                                        'required',
                                                        'file',
                                                        'mimes:pdf',
                                                        'max:5120',
                                                    ],

                                                    'service_area' => [
                                                        'required',
                                                        'array',
                                                        'min:1',
                                                    ],

                                                    'service_area.*' => [
                                                        'required',
                                                        'integer',
                                                    ],
                        'password' => ['exclude_unless:create_user,on', 'required_if:create_user,on', 'nullable',Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),'confirmed'],
                    ];
                }
            case 'PUT':
                case 'PATCH':
                    {
                        return [
                            'company_name' => [
                                'required',
                                'string',
                                Rule::unique('pgsql.fsm.service_providers', 'company_name')->where(function ($query) {
                                    return $query->whereNull('deleted_at');
                                })->ignore($id),
                            ],
                            'email' => [
                                'required',
                                'email',
                                'max:255',
                                Rule::unique('pgsql.auth.users', 'email')->ignore($id, 'service_provider_id'),
                                'regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
                            ],
                            'ward' => 'required|integer',
                            'company_location' => ['required', 'string', 'max:255'],
                            'contact_person' => ['required', 'string', 'max:255'],
                            'contact_number' => 'required|regex:/^[0-9]+$/',
                            'contact_gender' => 'required|string',
                            'contract_document_pdf' => [
                                                            'nullable',
                                                            'file',
                                                            'mimes:pdf',
                                                            'max:5120',
                                                        ],

                                                        'service_area' => [
                                                            'required',
                                                            'array',
                                                            'min:1',
                                                        ],

                                                        'service_area.*' => [
                                                            'required',
                                                            'integer',
                                                        ],
                            'status' => 'required|boolean',
                        ];
                    }


            default:break;
        }
    }

    public function messages()
    {
        return [
            'company_name.regex' => __('The service provider name field may only contain letters and spaces.'),
            'company_name.required' => __('The Company Name is required.'),
            'email.required' => __('The Email is required.'),
            'email.email' => __('Please enter a valid email address.'),
            'email.unique' => __('The email has already been taken.'),
            'ward.required' => __('The Ward Number is required.'),
            'company_location.required' =>__('The Address is required.'),
            'company_location.string' =>__('The Address must be string.'),
            'contact_person.required' =>__('The Contact Person Name is required.'),
            'contact_person.string' =>__('The Contact Person Name must be a string.'),
            'contact_number.required'=>__('The Contact Person Number is required.'),
            'contact_number.integer'=>__('The Contact Person Number must be an integer.'),
            'contact_gender.required' => __('The Contact Person Gender is required.'),
            'contract_document_pdf.required' =>__('The Contract Document is required.'),
            'contract_document_pdf.file' =>__('The Contract Document must be a file.'),
            'contract_document_pdf.mimes' =>__('The Contract Document must be a PDF.'),
            'contract_document_pdf.max' =>__('The Contract Document must not exceed 5 MB.'),
            'service_area.required' =>__('The Service Area (Wards) is required.'),
            'service_area.array' =>__('Please select valid Service Area wards.'),
            'service_area.min' =>__('Please select at least one Service Area ward.'),
            'service_area.*.integer' =>__('Each selected Service Area ward must be valid.'),
            'status.required' => __('The Status is required.'),
            'password.required_if' => __('The Password is required when create user is on.'),
            'password.confirmed' => __('The Confirm Password does not match the Password.'),
            'password.uncompromised' => __('The given password has appeared in a data leak. Please choose a different password.'),

        ];
    }
}
