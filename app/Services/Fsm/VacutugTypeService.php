<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Fsm;

use App\Models\Fsm\VacutugType;
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
use App\Enums\VacutugStatus;
use App\Enums\VacutugComplyMaintainStandard;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Fsm\ServiceProviderSequence;


class VacutugTypeService {

    protected $session;
    protected $instance;

    /**
     * Constructs a new VacutugType object.
     *
     *
     */
    public function __construct()
    {
       

    }

    /**
     * Get all the All Employee Info.
     *
     *
     * @return AllData[]|Collection
     */
    public function getAllVacutugTypes($data)
    {
        if(Auth::user()->hasRole('Service Provider - Admin') || Auth::user()->hasRole('Service Provider - Help Desk'))
        {
            $vacutugtypes =  VacutugType::select('*')->where('service_provider_id',Auth::user()->service_provider_id)->whereNull('deleted_at');
        }
        else
        {
            $vacutugtypes =  VacutugType::select('*')->whereNull('deleted_at');
        }
     
        return Datatables::of($vacutugtypes)
                ->filter(function ($query) use ($data) {
                if ($data['service_provider_id']) {
                    $query->where('fsm.desludging_vehicles.service_provider_id',$data['service_provider_id']);
                }
                if ($data['license_plate_number']) {
                    $query->where('fsm.desludging_vehicles.license_plate_number',$data['license_plate_number']);
                }
                if ($data['capacity']) {
                    $query->where('fsm.desludging_vehicles.capacity', $data['capacity']);
                }
                if ($data['width']) {
                    $query->where('fsm.desludging_vehicles.width', $data['width']);
                }
                 if ($data['status']) {
                    $query->where('status', $data['status']);
                }
                })
                ->addColumn('action', function ($model) {
                    $content = \Form::open(['method' => 'DELETE', 'route' => ['desludging-vehicles.destroy', $model->id]]);

                    $serviceProvider = $model->serviceProvider->status == 0;

                    if (Auth::user()->can('Edit Desludging Vehicle')) {
                       $content .= '<a title="' . e(__("Edit")) . '" href="' . action("Fsm\VacutugTypeController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1 mr-1' . ($serviceProvider ? ' anchor-disabled' : '') . '"><i class="fa fa-edit"></i></a>';

                    } 
                    if (Auth::user()->can('View Desludging Vehicle')) {
                        $content .= '<a title="' . e(__("Detail")) . '" href="' . action("Fsm\VacutugTypeController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                    }

                    if (Auth::user()->can('View Desludging Vehicle History')) {
                        $content .= '<a title="' . e(__("History")) . '"  href="' . action("Fsm\VacutugTypeController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                    }

                    if (Auth::user()->can('Delete Desludging Vehicle')) {
                        $content .= '<a href title="' . e(__("Delete")) . '" class="delete btn btn-danger btn-sm mb-1' . ($serviceProvider ? ' anchor-disabled' : '') . '"><i class="fa fa-trash"></i></a> ';
                    }

                    $content .= \Form::close();
                    return $content;
                })
                ->editColumn('service_provider_id',function ($model){
                     $service_provider = \App\Models\Fsm\ServiceProvider::withTrashed()
                        ->where('id', $model->service_provider_id)
                        ->first();
                        return $service_provider->company_name??'-';
                })
                ->editColumn('status',function ($model){
                 return VacutugStatus::getDescription($model->status);
                })
                ->make(true);
    }
    /**
     * Store or update a newly created resource in storage.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function storeOrUpdate($id,$data)
    {
        if(is_null($id)){
            $vacutugType = new VacutugType();
            $vacutugType->license_plate_number = $data['license_plate_number'] ? $data['license_plate_number'] : null;
            $vacutugType->capacity = $data['capacity'] ? $data['capacity'] : null;
            $vacutugType->width = $data['width'] ? $data['width'] : null;
            $vacutugType->description = $data['description'] ? $data['description'] : null;
            $vacutugType->service_provider_id = $data['service_provider_id'] ? $data['service_provider_id'] : null;
            $vacutugType->comply_with_maintainance_standards = $data['comply_with_maintainance_standards'] ? $data['comply_with_maintainance_standards'] : 0;
            $vacutugType->status = $data['status'] ? $data['status'] : 0;
            $vacutugType->save();
            if($vacutugType->status == 1){ 
            $this->calculateSequence($vacutugType->capacity);
            }
        }
        else{
            $vacutugType = VacutugType::find($id);
            $old = [
                'sp'      => (int)$vacutugType->service_provider_id,
                'cap'     => $vacutugType->capacity,
                'status'  => (int)$vacutugType->status,
            ];

            // update record
            $vacutugType->license_plate_number = $data['license_plate_number'] ?? null;
            $vacutugType->capacity = $data['capacity'] ?? null;
            $vacutugType->width = $data['width'] ?? null;
            $vacutugType->description = $data['description'] ?? null;
            $vacutugType->service_provider_id = $data['service_provider_id'] ?? null;
            $vacutugType->comply_with_maintainance_standards = $data['comply_with_maintainance_standards'] ?? 0;
            $vacutugType->status = $data['status'] ?? 0;
            $vacutugType->save();

            // new values AFTER change
            $new = [
                'sp'      => (int)$vacutugType->service_provider_id,
                'cap'     => $vacutugType->capacity,
                'status'  => (int)$vacutugType->status,
            ];

            $this->handleSequenceOnEdit($old, $new);
                }
    }


public function calculateSequence(): array
{
    return DB::transaction(function () {

        // 1) Count active vehicles by (size, service_provider)
       $vehicleCounts = DB::table('fsm.desludging_vehicles as dv')
        ->join('fsm.service_providers as sp', 'sp.id', '=', 'dv.service_provider_id')
        ->selectRaw('dv.capacity as vehicle_size, dv.service_provider_id, COUNT(*) as total')
        ->where('dv.status', true)->where('dv.deleted_at', null)
        ->where('sp.status', true)
        ->groupBy('dv.capacity', 'dv.service_provider_id')
        ->get();

        // Convert into: $counts[size][sp_id] = total
        $counts = [];
        foreach ($vehicleCounts as $row) {
            $size = $row->vehicle_size;
            $spId = (int)$row->service_provider_id;
            $counts[$size][$spId] = (int)$row->total;
        }

        // If no active vehicles, nothing to do
        if (empty($counts)) {
            return [];
        }

        // 2) Check if sequence table is empty
        $hasAnySequence = ServiceProviderSequence::query()->exists();

        // If empty => build fresh for ALL sizes
        if (!$hasAnySequence) {
            $allInserted = [];

            foreach ($counts as $size => $spCounts) {
                $sequence = $this->buildWeightedRoundRobin(array_keys($spCounts), $spCounts);

                foreach ($sequence as $index => $spId) {
                    ServiceProviderSequence::create([
                        'service_provider_id'       => $spId,
                        'desludging_vehicle_size'   => $size,
                        'sequence_order'            => $index,
                        'current_sequence'          => ($index === 0),
                    ]);
                }

                $allInserted[$size] = $sequence;
            }

            return $allInserted;
        }

        // 3) NOT empty => incrementally add ONLY the recently added capacity slots
        // Existing rows grouped by size and sp
        $existingRows = ServiceProviderSequence::query()
            ->select('id','service_provider_id','desludging_vehicle_size','sequence_order','current_sequence')
            ->orderBy('desludging_vehicle_size')
            ->orderBy('sequence_order')
            ->get();
          

        // Build:
        // $existingCount[size][sp] = how many rows exist already
        // $maxOrder[size] = max sequence_order
        // $sizeHasCurrent[size] = does any current_sequence=true exist
        $existingCount = [];
        $maxOrder = [];
        $sizeHasCurrent = [];

        foreach ($existingRows as $r) {
            $size = $r->desludging_vehicle_size;
            $spId = (int)$r->service_provider_id;

            $existingCount[$size][$spId] = ($existingCount[$size][$spId] ?? 0) + 1;
            $maxOrder[$size] = max($maxOrder[$size] ?? -1, (int)$r->sequence_order);

            if ($r->current_sequence) {
                $sizeHasCurrent[$size] = true;
            }
        }

        $result = [];

        foreach ($counts as $size => $spCounts) {

            // If this size does not exist in sequence table yet, initialize it like fresh
            if (!isset($maxOrder[$size])) {
                $sequence = $this->buildWeightedRoundRobin(array_keys($spCounts), $spCounts);

                foreach ($sequence as $index => $spId) {
                    ServiceProviderSequence::create([
                        'service_provider_id'       => $spId,
                        'desludging_vehicle_size'   => $size,
                        'sequence_order'            => $index,
                        'current_sequence'          => ($index === 0), 
                    ]);
                }

                $result[$size] = [
                    'mode' => 'initialized_size',
                    'added' => $sequence,
                ];
                continue;
            }

            // Compute delta slots for this size
            // delta = current active vehicles - already present in sequence table
            $delta = [];
            foreach ($spCounts as $spId => $totalVehiclesNow) {
                $already = $existingCount[$size][$spId] ?? 0;
                $diff = $totalVehiclesNow - $already;

                if ($diff > 0) {
                    $delta[$spId] = $diff;
                }
            }

            // If no new vehicles for this size, skip
            if (empty($delta)) {
                $result[$size] = [
                    'mode' => 'no_change',
                    'added' => [],
                ];
                continue;
            }

            // Append delta slots using weighted round robin
            $toAppend = $this->buildWeightedRoundRobin(array_keys($delta), $delta);

            $startOrder = $maxOrder[$size] + 1;

            foreach ($toAppend as $i => $spId) {
                ServiceProviderSequence::create([
                    'service_provider_id'       => $spId,
                    'desludging_vehicle_size'   => $size,
                    'sequence_order'            => $startOrder + $i,
                    'current_sequence'          => false, 
                ]);
            }

            // Edge case: if for some reason this size has no current_sequence row, set the first row as current
            if (empty($sizeHasCurrent[$size])) {
                ServiceProviderSequence::query()
                    ->where('desludging_vehicle_size', $size)
                    ->orderBy('sequence_order')
                    ->limit(1)
                    ->update(['current_sequence' => true]);
            }

            $result[$size] = [
                'mode' => 'appended_delta',
                'added' => $toAppend,
            ];
        }
        return $result;
    });
}


/**
 * Weighted Round Robin builder.
 *
 * @param array $providerIds
 * @param array $weights associative: [spId => count]
 * @return array sequence of spIds
 */
public function buildWeightedRoundRobin(array $providerIds, array $weights): array
    {
        $bag = [];
        foreach ($providerIds as $spId) {
            $cnt =($weights[$spId] ?? 0);
            if ($cnt > 0) {
                $bag[] = ['id' => (int)$spId, 'vehicle' => $cnt];
            }
        }

        if (empty($bag)) return [];

        // Randomize then sort desc for fairness among ties
        shuffle($bag);
        usort($bag, fn($a, $b) => $b['vehicle'] <=> $a['vehicle']);

        $maxRounds = max(array_column($bag, 'vehicle'));
        $sequence = [];

        for ($round = 0; $round < $maxRounds; $round++) {
            foreach ($bag as &$sp) {
                if ($sp['vehicle'] > 0) {
                    $sequence[] = $sp['id'];
                    $sp['vehicle'] -= 1;
                }
            }
            unset($sp);
        }

        return $sequence;
    }

   

    public function handleSequenceOnEdit(array $old, array $new): void
    {
        DB::transaction(function () use ($old, $new) {

            // nothing meaningful changed
            if ($old['sp'] === $new['sp'] && $old['cap'] === $new['cap'] && $old['status'] === $new['status']) {
                return;
            }

            // if old was active, we may need to remove one slot from old lineup
            if ($old['status'] === 1) {
                $this->removeOneSlotFromSequence($old['sp'], $old['cap']);
            }

            // if new is active, we may need to add one slot into new lineup
            if ($new['status'] === 1) {
                $this->addOneSlotToSequence($new['sp'], $new['cap']);
            }
        });
    }

    public function removeOneSlotFromSequence(int $serviceProviderId, $capacity): void
    {
        DB::transaction(function () use ($serviceProviderId, $capacity) {

            // pick ONE row to delete.
            // Prefer deleting a non-current row first; if only current exists, delete it.
            $row = ServiceProviderSequence::query()
                ->where('service_provider_id', $serviceProviderId)
                ->where('desludging_vehicle_size', $capacity)
                ->orderByRaw('current_sequence asc') // false first
                ->orderByDesc('sequence_order')      // remove from end (stable)
                ->first();

            if (!$row) return;

            $deletedWasCurrent = (bool)$row->current_sequence;
            $row->delete();

            // if deleted row was current -> set next to current (lowest order)
            if ($deletedWasCurrent) {
                ServiceProviderSequence::query()
                    ->where('desludging_vehicle_size', $capacity)
                    ->update(['current_sequence' => false]);

                $next = ServiceProviderSequence::query()
                    ->where('desludging_vehicle_size', $capacity)
                    ->orderBy('sequence_order')
                    ->first();

                if ($next) {
                    $next->current_sequence = true;
                    $next->save();
                }
            }

            // reorder sequence_order 0..n-1 for that capacity
            $this->reorderCapacity($capacity);
        });
    }

    public function addOneSlotToSequence(int $serviceProviderId, $capacity): void
    {
        DB::transaction(function () use ($serviceProviderId, $capacity) {

            $maxOrder =  (ServiceProviderSequence::query()
                ->where('desludging_vehicle_size', $capacity)
                ->max('sequence_order') ?? -1);

            // ensure capacity has a current row if table has rows but none is current
            $hasCurrent = ServiceProviderSequence::query()
                ->where('desludging_vehicle_size', $capacity)
                ->where('current_sequence', true)
                ->exists();

            // create new slot at end
            ServiceProviderSequence::create([
                'service_provider_id'       => $serviceProviderId,
                'desludging_vehicle_size'   => $capacity,
                'sequence_order'            => $maxOrder + 1,
                'current_sequence'          => false,
            ]);

            // if this capacity had no rows before (so no current), set first as current
            if (!$hasCurrent) {
                ServiceProviderSequence::query()
                    ->where('desludging_vehicle_size', $capacity)
                    ->orderBy('sequence_order')
                    ->limit(1)
                    ->update(['current_sequence' => true]);
            }

            $this->reorderCapacity($capacity);
        });
    }

    public function reorderCapacity( $capacity): void
    {
        DB::statement("
            WITH ordered AS (
                SELECT id,
                    ROW_NUMBER() OVER (ORDER BY sequence_order, id) - 1 AS new_order
                FROM fsm.service_provider_sequence
                WHERE desludging_vehicle_size = ?
            )
            UPDATE fsm.service_provider_sequence s
            SET sequence_order = ordered.new_order
            FROM ordered
            WHERE s.id = ordered.id
        ", [$capacity]);
    }


    /**
     * Download a listing of the specified resource from storage.
     *
     * @param array $data
     * @return null
     */
    public function download($data)
    {
        $searchData = $data['searchData'] ? $data['searchData'] : null;
        $service_provider_id = $data['service_provider_id'] ? $data['service_provider_id'] : null;
        $license_plate_number = $data['license_plate_number'] ? $data['license_plate_number'] : null;
        $capacity = $data['capacity'] ? $data['capacity'] : null;
        $width = $data['width'] ? $data['width'] : null;
        $columns = [
            __('Service Provider'),
            __('Vehicle License Plate Number'),
            __('Capacity (m³)'),
            __('Width (m)'),
            __('Comply with Maintenance Standards'),
            __('Status')
        ];
        
        $status = $data['status'] ? $data['status'] : 0;
        $query =  VacutugType::select('*')->with('serviceProvider')->whereNull('deleted_at');
        
        if(Auth::user()->hasRole('Service Provider - Admin') )
        {
        $query->where('service_provider_id',"=",Auth::user()->service_provider_id);
        }

        if(!empty($service_provider_id)){
            $query->where('fsm.desludging_vehicles.service_provider_id', $service_provider_id);
        }

        if(!empty($license_plate_number)){
            $query->whereRaw('LOWER(fsm.desludging_vehicles.license_plate_number) LIKE ? ', [trim(strtolower($license_plate_number))]);
        }

        if(!empty($capacity)){
            $query->where('fsm.desludging_vehicles.capacity', $capacity);
        }

        if(!empty($width)){
            $query->where('fsm.desludging_vehicles.width', $width);
        }
        if(!empty($status)){
            $query -> where('status', $status);
        }

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Desludging Vehicles.csv')
            ->addRowWithStyle($columns, $style); //Top row of excel

        $query->chunk(5000, function ($vacutugTypeList) use ($writer) {
            foreach($vacutugTypeList as $data) {
                $values = [];
                $values[] = $data->serviceProvider->company_name;
                $values[] = $data->license_plate_number;
                $values[] = $data->capacity;
                $values[] = $data->width;
                $values[] = VacutugComplyMaintainStandard::getDescription($data->comply_with_maintainance_standards);
                $values[] = VacutugStatus::getDescription($data->status);
                $writer->addRow($values);
            }
        });
        $writer->close();

    }
}
