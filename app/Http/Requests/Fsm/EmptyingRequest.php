<?php
// Last Modified Date: 10-11-2025
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)

namespace App\Http\Requests\Fsm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class EmptyingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

   
    public function rules(): array
    {
        $applicationId = (int) $this->input('application_id');

        // Check if ANF
        $isAnf = (bool) DB::table('fsm.applications')
            ->where('id', $applicationId)
            ->value('is_anf');

        // Resolve containment size (m³) from the application's selected containment
        $selectedContainmentId = DB::table('fsm.applications')
            ->where('id', $applicationId)
            ->value('containment_id');

        $containmentSize = $selectedContainmentId
            ? (float) (DB::table('fsm.containments')->where('id', $selectedContainmentId)->value('size') ?? 0)
            : 0.0;

        $id = DB::table('fsm.emptyings')
            ->where('application_id', $applicationId)
            ->first();
        if (!$id) {
            $totalSludgeVolume = 0;
        } else {
            $totalSludgeVolume = (float) $id->volume_of_sludge;
        }

        $tripNo    = (int) $this->input('trip_no', 0);
        $tripCount = (int) $this->input('trip_count', 0);
        $isCreate  = $this->isMethod('post');

        $rules = [
            'application_id'           => ['required', 'integer', 'exists:fsm.applications,id'],
            'emptying_reason'          => ['required'],
            'service_receiver_name'    => ['required'],
            'service_receiver_gender'  => ['required'],
            'service_receiver_contact' => ['required'],
            'desludging_vehicle_id'    => ['required', 'integer'],
            'treatment_plant_id'       => ['required', 'integer'],
            'driver'                   => ['required', 'integer'],
            'emptier1'                 => ['required', 'integer'],
            'emptier2'                 => ['nullable', 'integer'],
            'trip_no'                  => ['required', 'integer', 'min:1'],
            'trip_count'               => ['required', 'integer', 'min:1'],
            'volume_of_sludge' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($containmentSize, $totalSludgeVolume, $isAnf) {
                    // Skip volume check if ANF not yet resolved (no containment linked)
                    if ($isAnf) {
                        $fail(__('Error! Please locate and link the building (ANF) before saving emptying service.'));
                        return;
                    }

                    $value = (float) $value;

                    if ($this->isMethod('post')) {
                        $newTotalSludgeVolume = $totalSludgeVolume + $value;
                        if ($newTotalSludgeVolume > $containmentSize) {
                            $fail(__(
                                "The previous Sludge Volume is {$totalSludgeVolume} m³. " .
                                    "With the addition of {$value} m³, " .
                                    "the total volume would be {$newTotalSludgeVolume} m³, " .
                                    "exceeding the selected containment size of {$containmentSize} m³."
                            ));
                        }
                    } else {
                        $newCumulative = $value;
                        if ($newCumulative > $containmentSize) {
                            $fail(__(
                                "The sludge volume ({$newCumulative} m³) exceeds the containment size ({$containmentSize} m³)."
                            ));
                        }
                    }
                },
            ],
            'house_image_existing'   => ['nullable', 'string'],
            'receipt_image_existing' => ['nullable', 'string'],
        ];

        if ($isCreate) {
            $rules = array_merge($rules, [
                'start_time' => ['required', 'date_format:H:i'],
                'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'start_time' => ['nullable', 'date_format:H:i'],
                'end_time'   => ['nullable', 'date_format:H:i', 'after:start_time'],
                'total_time' => ['required', 'integer'],
            ]);
        }

        if ($tripNo === $tripCount) {
            $rules = array_merge($rules, [
                'receipt_number' => ['required'],
                'total_cost'     => ['required', 'numeric', 'min:0'],
                'house_image' => [
                    'nullable',
                    'file',
                    'mimes:jpeg,jpg',
                    // 'max:5120',
                    'required_without:house_image_existing',
                ],
                'house_image_existing'   => ['nullable', 'string'],
                'receipt_image' => [
                    'nullable',
                    'file',
                    'mimes:jpeg,jpg',
                    // 'max:5120',
                    'required_without:receipt_image_existing',
                ],
                'receipt_image_existing' => ['nullable', 'string'],
            ]);
        } elseif (!($tripNo < $tripCount)) {
            $rules['trip_no'][] = function ($attribute, $value, $fail) {
                $fail(__('The No. of Trips cannot be less than the Current Trip No.'));
            };
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'application_id.required'          => __('The Application is required.'),
            'application_id.integer'           => __('The Application is invalid.'),
            'application_id.exists'            => __('The selected Application does not exist.'),
            'service_receiver_name.required'   => __('The Service Receiver Name is required.'),
            'service_receiver_contact.required' => __('The Service Receiver Contact Number is required.'),
            'service_receiver_gender.required' => __('The Service Receiver Gender is required.'),
            'emptying_reason.required'         => __('The Reason for Emptying is required.'),
            'trip_no.required'                 => __('The Current Trip No. is required.'),
            'trip_no.integer'                  => __('The Current Trip No. must be an integer.'),
            'trip_no.min'                      => __('The Current Trip No. must be at least 1.'),
            'trip_count.required'              => __('The No. of Trips is required.'),
            'trip_count.integer'               => __('The No. of Trips must be an integer.'),
            'trip_count.min'                   => __('The No. of Trips must be at least 1.'),
            'volume_of_sludge.required'        => __('The Sludge Volume (m³) is required.'),
            'volume_of_sludge.numeric'         => __('The Sludge Volume (m³) must be numeric.'),
            'volume_of_sludge.min'             => __('The Sludge Volume (m³) must be at least 0.'),
            'desludging_vehicle_id.required'   => __('The Desludging Vehicle Number Plate is required.'),
            'desludging_vehicle_id.integer'    => __('The Desludging Vehicle Number Plate must be an integer.'),
            'driver.required'                  => __('The Driver Name is required.'),
            'emptier1.required'                => __('The Emptier 1 Name is required.'),
            'emptier1.integer'                 => __('The Emptier 1 Name is invalid.'),
            'emptier2.integer'                 => __('The Emptier 2 Name is invalid.'),
            'start_time.required'              => __('The Start Time is required.'),
            'start_time.date_format'           => __('The Start Time format must be HH:MM.'),
            'end_time.required'                => __('The End Time is required.'),
            'end_time.date_format'             => __('The End Time format must be HH:MM.'),
            'end_time.after'                   => __('The End Time must be after Start Time.'),
            'total_time.required'              => __('The Total Time (mins) is required.'),
            'treatment_plant_id.required'      => __('The Disposal Place is required.'),
            'treatment_plant_id.integer'       => __('The Disposal Place must be an integer.'),
            'receipt_number.required'          => __('The Receipt Number is required.'),
            'total_cost.required'              => __('The Total Cost is required.'),
            'total_cost.numeric'               => __('The Total Cost must be numeric.'),
            'house_image.required'             => __('The House Image is required.'),
            'house_image.file'                 => __('The House Image must be a file.'),
            'house_image.mimes'                => __('The House Image must be a file of type: jpeg, jpg.'),
            'house_image.max'                  => __('The House Image should not be greater than 5 MB.'),
            'receipt_image.required'           => __('The Receipt Image is required.'),
            'receipt_image.file'               => __('The Receipt Image must be a file.'),
            'receipt_image.mimes'              => __('The Receipt Image must be a file of type: jpeg, jpg.'),
            'receipt_image.max'                => __('The Receipt Image should not be greater than 5 MB.'),
        ];
    }
}
