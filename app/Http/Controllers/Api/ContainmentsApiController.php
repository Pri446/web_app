<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fsm\ContainmentType;
use Illuminate\Http\Request;

class ContainmentsApiController extends Controller
{
    public function getAddContainmentFormFields(Request $request)
    {
        // --- Fetch containment type options ---
        $containmentTypes =  ContainmentType::distinct()->pluck('type', 'id')->all();

        $containmentTypeOptions = collect($containmentTypes)->map(function ($label, $value) {
            return ['value' => $value, 'label' => $label];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'form_fields' => [
                      [
                        'fields' => [
                            [
                                'label' => 'Application ID',
                                'name' => 'id',
                                'input_type' => 'hidden',
                                'disabled' => true,
                                'prefilled' => true,
                                'required' => true,
                                'validation' => 'required|string|max:255',
                                'placeholder' => 'Enter Application ID',
                                'value' => null,
                            ],
                        ],
                    ],
                    // =============================================
                    // GROUP 1: Containment Information
                    // =============================================
                    [
                        'group'     => 'Containment Information',
                        'group_key' => 'containment_information',
                        'fields'    => [

                            // 🪣 Containment Type
                            [
                                'label'       => 'Containment Type',
                                'name'        => 'type_id',
                                'input_type'  => 'select',
                                'disabled'    => false,
                                'prefilled'   => false,
                                'required'    => true,
                                'validation'  => 'required|integer|exists:containment_types,id',
                                'placeholder' => '--- Select containment type ---',
                                'options'     => $containmentTypeOptions,
                                'value'       => null,
                            ],

                            // 📐 Containment Size (m³)
                            [
                                'label'       => 'Containment Size (m³)',
                                'name'        => 'size',
                                'input_type'  => 'number',
                                'disabled'    => false,
                                'prefilled'   => false,
                                'required'    => true,
                                'validation'  => 'required|numeric|gt:0',
                                'placeholder' => 'Enter containment size',
                                'value'       => null,
                            ],

                            // 📅 Construction Date
                            [
                                'label'       => 'Construction Date',
                                'name'        => 'construction_date',
                                'input_type'  => 'date',
                                'disabled'    => false,
                                'prefilled'   => false,
                                'required'    => false,
                                'validation'  => 'nullable|date|before_or_equal:today',
                                'placeholder' => 'Select construction date',
                                'value'       => null,
                            ],

                        ],
                    ],

                ],
            ],
            'message' => __('Containment form fields retrieved successfully.'),
        ]);
    }
}
