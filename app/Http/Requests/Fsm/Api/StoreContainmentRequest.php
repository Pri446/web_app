<?php

namespace App\Http\Requests\Fsm\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreContainmentRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'type_id' => ['required', 'integer'],
            'size' => ['required', 'numeric'],
            'construction_date' => ['required', 'date'],
            'id' => ['required', 'integer'], // application id
        ];
    }
}
