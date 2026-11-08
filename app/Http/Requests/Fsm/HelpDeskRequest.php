<?php
// Last Modified Date: 10-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)    
namespace App\Http\Requests\Fsm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;



class HelpDeskRequest extends FormRequest
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


    public function messages()
{
    return [
        'name.required' => __('The Help Desk Name is required.'),
        'email.required' => __('The Email Address is required.'),
        'email.regex' => __('The Email Address format is invalid.'),
        'email.required' => __('The Email Address is required.'),
        'email.unique' => __('The Email Address has already been taken.'),
        'description.required' => __('The Description is required.'),
        'contact_number.required'=>__('The Contact Number is required.'),
        'contact_number.integer'=>__('The Contact Number must be an integer.'),
        'password.required_if' => __('The Password is required when create user is on.'),
        'password.confirmed' => __('The Confirm Password does not match the Password.')

    ];
}

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route()->help_desk;
        switch ($this->method()) {
        case 'GET':
        case 'DELETE':
            {
                return [];
            }
        case 'POST':
            {
                return [
                    'name' => 'required',
                    'description' => 'required',
                    'contact_number' => 'required|integer',
                    'email' => ['required', 'max:255', 'unique:pgsql.auth.users' ,'regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix'],
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
                    'name' => 'required',
                    'description' => 'required',
                    'contact_number' => 'required|integer',
                    'email' => [
                        'required',
                        'email',
                        'max:255',
                        Rule::unique('pgsql.auth.users', 'email')->ignore($id, 'help_desk_id'),
                        'regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
                    ],
                ];
            }
        default:break;
    }
}


}
