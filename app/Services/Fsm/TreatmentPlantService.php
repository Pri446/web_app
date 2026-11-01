<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Fsm;

use App\Models\Fsm\TreatmentPlant;


use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use DB;
use Carbon\Carbon;
use Auth;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Yajra\DataTables\DataTables;
use App\Enums\TreatmentPlantStatus;
use App\Enums\TreatmentPlantType;


class TreatmentPlantService
{

    protected $session;
    protected $instance;

    /**
     * Constructs a new TreatmentPlant object.
     *
     *
     */
    public function __construct()
    {
        /*Session code
        ....
         here*/
    }

    /**
     * Get all the All Employee Info.
     *
     *
     * @return EmployeeInfo[]|Collection
     */
    public function getAllTreatmentPlants($data)
    {
        $treatmentPlant =  TreatmentPlant::select('*')->whereNull('deleted_at');
        return Datatables::of($treatmentPlant)
            ->filter(function ($query) use ($data) {
                // if ($data['trtpltid']) {
                //     $query->where('id', $data['trtpltid']);
                // }
                if ($data['name']) {

                    $query->where('name', 'ILIKE', '%' .  trim($data['name']) . '%');
                }

                if ($data['caretaker_name']) {
                    $query->where('caretaker_name', 'ILIKE', '%' . $data['caretaker_name'] . '%');
                }

                if ($data['caretaker_number']) {
                    $query->where('caretaker_number', 'ILIKE', '%'.$data['caretaker_number'].'%');
                }

                if ($data['capacity_per_day']) {
                    $query->where('capacity_per_day','ILIKE', trim($data['capacity_per_day']) . '%');
                }

                if ($data['status']) {
                    $query->where('status', $data['status']);
                }
                if ($data['type']) {
                    $query->where('type', $data['type']);
                }
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['treatment-plants.destroy', $model->id]]);

                if (Auth::user()->can('Edit Treatment Plant')) {
                    $content .= '<a title="' . __("Edit") . '" href="' . action("Fsm\TreatmentPlantController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }

                if (Auth::user()->can('View Treatment Plant')) {
                    $content .= '<a title="' . __("Detail") . '" href="' . action("Fsm\TreatmentPlantController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }

                if (Auth::user()->can('View Treatment Plant History')) {
                    $content .= '<a title="' . __("History") . '" href="' . action("Fsm\TreatmentPlantController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }

                if (Auth::user()->can('Delete Treatment Plant')) {

                    $content .= '<a href title="' . __("Delete") . '" class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }

                if (Auth::user()->can('View Treatment Plant on Map')) {
                    $content .= '<a title="' . __("Map") . '" href="' . action("MapsController@index", ['layer' => 'treatmentplants_layer', 'field' => 'id', 'val' => $model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-map-marker"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->editColumn('status', function ($model) {
                return TreatmentPlantStatus::getDescription($model->status);
            })

            ->editColumn('type', function ($model) {
                switch ($model->type) {
                    case TreatmentPlantType::CentralizedWWTP:
                        return 'Centralized WWTP';
                    case TreatmentPlantType::DecentralizedWWTP:
                        return 'Decentralized WWTP';
                    case TreatmentPlantType::FSTP:
                        return 'FSTP';
                        case TreatmentPlantType::CoTreatmentPlant:
                            return 'Co-Treatment Plant';
                }
            })
            ->rawColumns(['disease','action'])
            ->make(true);
    }
    /**
     * Store or update a newly created resource in storage.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function storeOrUpdate($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
        if (is_null($id)) {
            $treatmentPlant = new TreatmentPlant();
            $treatmentPlant->name = $data['name'] ? $data['name'] : null;
            $treatmentPlant->location = $data['location'] ? $data['location'] : null;
            $treatmentPlant->capacity_per_day = $data['capacity_per_day'] ? $data['capacity_per_day'] : null;
            $treatmentPlant->caretaker_number = $data['caretaker_number'] ? $data['caretaker_number'] : null;
            $treatmentPlant->caretaker_name = $data['caretaker_name'] ? $data['caretaker_name'] : null;
            $treatmentPlant->caretaker_gender = $data['caretaker_gender'] ? $data['caretaker_gender'] : null;
            $treatmentPlant->type = $data['type'] ? $data['type'] : null;
            $treatmentPlant->status = $data['status'] ? $data['status'] : 0;
            if ($data['longitude'] && $data['latitude']) {
                $treatmentPlant->geom = DB::raw("ST_GeomFromText('POINT(" . $data['longitude'] . " " . $data['latitude'] .  ")', 4326)");
            }
            $treatmentPlant->save();
        } else {
            $treatmentPlant = TreatmentPlant::whereKey($id)->lockForUpdate()->firstOrFail();
            $treatmentPlant->name = $data['name'] ? $data['name'] : null;
            $treatmentPlant->location = $data['location'] ? $data['location'] : null;
            $treatmentPlant->capacity_per_day = $data['capacity_per_day'] ? $data['capacity_per_day'] : null;
            $treatmentPlant->caretaker_number = $data['caretaker_number'] ? $data['caretaker_number'] : null;
            $treatmentPlant->caretaker_name = $data['caretaker_name'] ? $data['caretaker_name'] : null;
            $treatmentPlant->caretaker_gender = $data['caretaker_gender'] ? $data['caretaker_gender'] : null;
            $treatmentPlant->status = $data['status'] ? $data['status'] : 0;
            $treatmentPlant->type = $data['type'] ? $data['type'] : null;

            $treatmentPlant->save();
        }
        if (!empty($data['cwis_removed_annual'])) {
            $treatmentPlant->annualSustainability()->whereIn('id', $data['cwis_removed_annual'])->delete();
        }
        foreach ($data['cwis_annual'] ?? [] as $row) {
            $hasValues = false;
            foreach (array_keys(\App\Models\Fsm\TreatmentPlantAnnualSustainability::VOLUMES) as $column) {
                if (isset($row[$column]) && $row[$column] !== '') { $hasValues = true; }
            }
            if (!$hasValues && empty($row['id'])) { continue; }
            $annual = !empty($row['id'])
                ? $treatmentPlant->annualSustainability()->whereKey($row['id'])->lockForUpdate()->firstOrFail()
                : $treatmentPlant->annualSustainability()->firstOrNew(['data_year' => $row['data_year']]);
            $annual->data_year = $row['data_year'];
            foreach (array_keys(\App\Models\Fsm\TreatmentPlantAnnualSustainability::VOLUMES) as $column) {
                $annual->$column = isset($row[$column]) && $row[$column] !== '' ? $row[$column] : null;
            }
            if (!$annual->exists) { $annual->created_by = Auth::id(); }
            $annual->updated_by = Auth::id();
            $annual->save();
        }
        return $treatmentPlant;
        });
    }

    /**
     * Download a listing of the specified resource from storage.
     *
     * @param array $data
     * @return null
     */
    public function download($data)
    {
            $searchData = $data['searchData'] ?? null;
            // $trtpltid = $data['trtpltid'] ? $data['trtpltid'] : null;
            $name = $data['name'] ?? null;
            $capacity_per_day = $data['capacity_per_day'] ?? null;
            $caretaker_name = $data['caretaker_name'] ?? null;
            $caretaker_number = $data['caretaker_number'] ?? null;
            $status = $data['status'] ?? null;
            $type = $data['type'] ?? null;
    

            $columns = [
            __('Name'),
            __('Capacity Per Day (m³)'),
            __('Caretaker Name'),
            __('Caretaker Number'),
            __('Status'),
            __('Treatment Plant Type'),
            __('Data Year'),
            ...array_map('__', array_values(\App\Models\Fsm\TreatmentPlantAnnualSustainability::VOLUMES)),
        ];
            $query = TreatmentPlant::with('annualSustainability')->select('id', 'name', 'capacity_per_day', 'caretaker_name', 'caretaker_number', 'status','type')
                ->whereNull('deleted_at');


            if (!empty($name)) {
                $query->where('name', 'ILIKE', '%' . $name . '%');
            }
            if (!empty($capacity_per_day)) {
                $query->where('capacity_per_day', 'ILIKE',  $capacity_per_day . '%');
            }
            if (!empty($caretaker_name)) {
                $query->where('caretaker_name', 'ILIKE', '%' . $caretaker_name . '%');
            }

            if (!empty($caretaker_number)) {
                $query->where('caretaker_number', 'ILIKE', '%' . $caretaker_number . '%');
            }
            if (!empty($status)) {
                $query->where('status', $status);
            }
            if (!empty($type)) {
                $query->where('type', $type);
            }
           
            $style = (new StyleBuilder())
                ->setFontBold()
                ->setFontSize(13)
                ->setBackgroundColor(Color::rgb(228, 228, 228))
                ->build();

            return response()->streamDownload(function () use ($query, $columns, $style) {
            $writer = WriterFactory::create(Type::CSV);

            $writer->openToFile('php://output')
                ->addRowWithStyle($columns, $style); //Top row of excel
             
                $query->chunk(5000, function ($treatmentPlant) use ($writer) {
                    foreach ($treatmentPlant as $data) {
                        $values = [];
                        
                        // Adding values to the array
                        $values[] = $data->name;
                        $values[] = $data->capacity_per_day;
                        $values[] = $data->caretaker_name;
                        $values[] = $data->caretaker_number;
                        $values[] = TreatmentPlantStatus::getDescription($data->status);
                
                        // Conditionally setting the type value
                        switch ($data->type) {
                            case 1:
                                $values[] = 'Centralized WWTP';
                                break;
                            case 2:
                                $values[] = 'Decentralized WWTP';
                                break;
                            case 3:
                                $values[] = 'FSTP';
                                break;
                                case 4:
                                    $values[] = 'Co-Treatment Plant';
                                    break;
                            default:
                                $values[] = 'unknown'; // or any default value you prefer
                                break;
                        }
                
                        $annualRows = $data->annualSustainability;
                        if ($annualRows->isEmpty()) {
                            $writer->addRow(array_merge($values, [null, null, null, null, null]));
                        }
                        foreach ($annualRows as $annual) {
                            $annualValues = [$annual->data_year];
                            foreach (array_keys(\App\Models\Fsm\TreatmentPlantAnnualSustainability::VOLUMES) as $column) {
                                $annualValues[] = $annual->$column;
                            }
                            $writer->addRow(array_merge($values, $annualValues));
                        }
                    }
                });
                $writer->close();
            }, 'Treatment Plants.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);


        }
    }

