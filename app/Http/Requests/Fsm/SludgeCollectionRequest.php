<?php

namespace App\Http\Requests\Fsm;

use Illuminate\Foundation\Http\FormRequest;

class SludgeCollectionRequest extends FormRequest
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
        $rules= ($this->isMethod('POST')? $this->store() : $this->update());
        return $rules;
    }

    public function store()
    {
        return [
            'treatment_plant_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
            'entry_time' => 'required|date_format:H:i',
            'exit_time' => 'required|date_format:H:i|after:entry_time',
            'tipping_fee_amount' => 'required|integer|min:0',
            'tipping_fee_receipt_no'=>'required'
        ];
    }
    public function update()
    {
        return [
            'date' => 'required|date|after_or_equal:today',
            'entry_time' => 'required|date_format:H:i',
            'exit_time' => 'required|date_format:H:i|after:entry_time',
            'treatment_plant_id' => 'required|integer',
            'tipping_fee_amount' => 'required|integer|min:0',
            'tipping_fee_receipt_no'=>'required'
        ];
    }

    /**
     * Get the messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'treatment_plant_id.required' => __('The Treatment Plant is required.'),
            'date.required' => __('The Date is required.'),
            'entry_time.required' => __('The Entry Time is required.'),
            'exit_time.required' => __('The Exit Time is required.'),
            'exit_time.after' => __('The Exit Time must be after the Entry Time.'),
            'tipping_fee_amount.required' => __('The Tipping Fee Amount is required.'),
            'tipping_fee_amount.integer' => __('The Tipping Fee Amount must be a whole number.'),
            'tipping_fee_amount.min' => __('The Tipping Fee Amount must be at least 0.'),
            'tipping_fee_receipt_no.required' => __('The Tipping Fee Receipt No is required.'),

        ];
    }
}

