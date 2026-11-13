<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)
namespace App\Http\Requests\Fsm;

use App\Enums\EmptyingReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmptyingApiRequest extends FormRequest
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
        return [
            'application_id' => 'required|exists:fsm.applications,id',
            'volume_of_sludge' => 'required|numeric|gt:0',
            'desludging_vehicle_id' => 'required',
            'treatment_plant_id' => 'required',
            'driver' => 'required|integer',
            'emptier1' => 'required|integer',
            'emptier2' => 'nullable|integer',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'no_of_trips' => 'required|numeric|gt:0',
            'receipt_number' => 'required',
            'total_cost' => 'required|numeric|gt:0',
            'house_image' => 'mimes:jpeg,jpg',
            'receipt_image' => 'required|mimes:jpeg,jpg',
            'emptying_reason' => ['required', Rule::in(EmptyingReason::values())],
            'service_receiver_contact' => 'required|integer',
            'service_receiver_gender' => 'required',
            'service_receiver_name' => 'required',


        ];
    }

    /**
     * Get the error messages to display if validation fails.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'application_id.required' => __('The application id is required.'),
            'application_id.exists' => __('The application for this ID doesn\'t exist.'),
            'volume_of_sludge.required' => __('Sludge Volume is required.'),
            'volume_of_sludge.numeric' => __('Sludge Volume must be numeric.'),
            'desludging_vehicle_id.required' => __('The desludging vehicle number plate  is required.'),
            'treatment_plant_id.required' => __('The disposal place is required.'),
            'driver.required' => __('The driver name is required.'),
            'emptier1.required' => __('The emptier1 name is required.'),
            'start_time.required' => __('The start time is required.'),
            'end_time.required' => __('The end time is required.'),
            'end_time.after' => __('The end time must be after start time.'),
            'no_of_trips.required' => __('The number of trips is required.'),
            'no_of_trips.numeric' => __('The number of trips must be numeric.'),
            'receipt_number.required' => __('The receipt number is required.'),
            'total_cost.required' => __('The total cost is required.'),
            'total_cost.numeric' => __('The total cost must be numeric.'),
            'house_image.file' => __('The house image must be an image file.'),
            'house_image.mimetypes' => __('The house image type is not supported.'),
            'receipt_image.required' => __('The receipt image is required.'),
            'receipt_image.file' => __('The receipt image must be an image file.'),
            'receipt_image.mimetypes' => __('The receipt image type is not supported.'),
            'emptying_reason.required' => __('The reason for emptying is required.'),
            'emptying_reason.in' => __('The selected reason for emptying is invalid.'),
            'service_receiver_contact.required' => __('The service receiver contact Number is required.'),
            'service_receiver_gender.required' => __('The service receiver gender is required.'),
            'service_receiver_name.required' => __('The service receiver name is required.'),
            'volume_of_sludge.gt' => __('The sludge volume must be greater than 0.'),
            'no_of_trips.gt' => __('The number of trips must be greater than 0.'),
            'total_cost.gt' => __('The total cost must be greater than 0.'),
            'driver.integer' => __('The driver name must be a number.'),
            'emptier1.integer' => __('The emptier1 name must be a number.'),
            'emptier2.integer' => __('The emptier2 name must be a number.'),
            'service_receiver_contact.integer' => __('The service receiver contact number must be a number.'),



        ];
    }
}
