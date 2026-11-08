<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Places;
use Yajra\DataTables\Facades\DataTables;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Illuminate\Validation\Rule;
use DB;


class PlacesController extends Controller
{

public function __construct()
    {
        // User must be logged in for all Places functions.
        $this->middleware('auth');

        // Users with List Places permission can access/manage Places.
        $this->middleware('permission:List Places')->only([
            'index',
            'create',
            'store',
            'edit',
            'update',
            'show',
            'getData',
            'export',
        ]);

        // Only users with Delete Places permission can delete.
        $this->middleware('permission:Delete Places')->only([
            'destroy',
        ]);
    }
    
    public function index()
    {
        $page_title = 'Places';
        $types = Places::whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->toArray();
        return view('places.index', compact('page_title', 'types'));
    }

    public function create()
    {
        $page_title = 'Add Places';
        $types = Places::whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->toArray();
        return view('places.create', compact('page_title', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'ward' => 'required|integer',
            'geom' => 'required',
            'type' => 'nullable|string|max:255',
            'unique_reference_id' => [
                                        'nullable',
                                        'string',
                                        'max:255',
                                        Rule::unique(Places::class, 'unique_reference_id'),
                                    ],
        ], [
            'name.required' => 'The Place Name is required.',
            'ward.required' => 'The ward is required.',
            'geom.required' => 'The Location is required.',
            'unique_reference_id.unique' => 'This reference ID already exists.',
        ]);

        $places = new Places();
        $maxId = Places::max('id');
        $places->id = $maxId + 1;
        $places->name = $validated['name'];
        $places->ward = $validated['ward'];
        $places->type = !empty($validated['type']) ? trim($validated['type']) : null;
        $places->unique_reference_id = !empty($validated['unique_reference_id']) ? trim($validated['unique_reference_id']) : null;
        $places->geom = DB::raw("ST_GeomFromText('{$validated['geom']}')");
        $places->save();

        return redirect()->route('places.index')
            ->with('success', 'Place created successfully.');
    }

    public function edit($id)
    {
        $page_title = 'Edit Places';
        $place = Places::findOrFail($id);
        $geometry = DB::selectOne(
            'SELECT ST_AsText(geom) AS geom FROM layer_info.places WHERE id = ?',
            [$id]
        );
        $geom = $geometry->geom ?? null;
        $types = Places::whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->toArray();
        return view('places.edit', compact('page_title', 'types', 'place', 'geom'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ward' => 'required',
            'geom' => 'required',
            'type' => 'nullable|string|max:255',
            'unique_reference_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(Places::class, 'unique_reference_id')->ignore($id),
            ],
        ], [
            'name.required' => 'The Place Name is required.',
            'ward.required' => 'The ward is required.',
            'geom.required' => 'The Location is required.',
            'unique_reference_id.unique' => 'This reference ID already exists.',
        ]);

        $places = Places::findOrFail($id);
        $places->name = $validated['name'];
        $places->ward = $validated['ward'];
        $places->type = !empty($validated['type']) ? trim($validated['type']) : null;
        $places->unique_reference_id = !empty($validated['unique_reference_id']) ? trim($validated['unique_reference_id']) : null;
        $places->geom = DB::raw("ST_GeomFromText('{$validated['geom']}')");
        $places->save();

        return redirect()->route('places.index')
            ->with('success', 'Place updated successfully.');
    }

    public function show($id)
    {
        $page_title = 'Places Detail';
        $places = Places::findOrFail($id);
        return view('places.show', compact('page_title', 'places'));
    }

    public function destroy($id)
    {
        $places = Places::findOrFail($id);
        $places->delete();
        
        return redirect()->route('places.index')
            ->with('success', 'Places deleted successfully.');
    }

    public function getData(Request $request)
    {
        $places = Places::whereNull('deleted_at');
        
        return DataTables::of($places)
            ->filter(function ($query) use ($request) {
                if ($request->filled('house_number')) {
                    $query->where('house_number', $request->house_number);
                }
                if ($request->filled('name')) {
                    $query->whereRaw('LOWER(name) = LOWER(?)', [$request->name]);
                }
                if ($request->filled('type')) {
                    $query->where('type', $request->type);
                }
                if ($request->filled('ward')) {
                    $query->where('ward', $request->ward);
                }
                if ($request->filled('unique_reference_id')) {
                    $query->where('unique_reference_id', $request->unique_reference_id);
                }
            })
            ->addColumn('action', function ($places) {
                $content = \Form::open([
                    'method' => 'DELETE', 
                    'url' => route('places.destroy', $places->id),
                    'style' => 'display:inline'
                ]);

                $content .= '<a title="' . e(__("Edit")) . '" href="' . action("PlacesController@edit", [$places->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';

                $content .= '<a title="' . e(__("Detail")) . '" href="' . action("PlacesController@show", [$places->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';

                $content .= '<button type="button" title="' . e(__("Delete")) . '" class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></button> ';

                $content .= \Form::close();
                return $content;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function export()
   {
      $unique_reference_id = $_GET['unique_reference_id'] ?? null;
      $type = $_GET['type'] ?? null;
      $name = $_GET['name'] ?? null;
      $ward = $_GET['ward'] ?? null;
      $searchData = $_GET['searchData'] ?? null;

      $columns = [
         __('ID'),
         __('Unique Reference ID'),
         __('Type'),
         __('Places Name'),
         __('Ward'),
      ];

      $query = DB::table('layer_info.places AS fc')
         ->select(
               'fc.id',
               'fc.unique_reference_id',
               'fc.name',
               'fc.type',
               'fc.ward',
         )
         ->orderBy('fc.id')
         ->whereNull('fc.deleted_at');

      // Apply filters
      if (!empty($unique_reference_id)) {
         $query->where('fc.unique_reference_id', 'LIKE', "%$unique_reference_id%");
      }

      if (!empty($name)) {
         $query->whereRaw('LOWER(fc.name) LIKE LOWER(?)', ["%$name%"]);
      }

      if (!empty($type)) {
         $query->where('fc.type', $type);
      }
      if (!empty($unique_reference_id)) {
         $query->where('fc.unique_reference_id', $unique_reference_id);
      }

      if (!empty($ward)) {
         $query->where('fc.ward', $ward);
      }

      if (!empty($searchData)) {
         $query->where(function($q) use ($searchData) {
               $q->where('fc.unique_reference_id', 'LIKE', "%$searchData%")
               ->orWhere('fc.name', 'LIKE', "%$searchData%")
               ->orWhere('fc.type', 'LIKE', "%$searchData%")
               ->orWhereRaw('fc.ward::text LIKE ?', ["%$searchData%"]);
         });
      }

      $style = (new StyleBuilder())
         ->setFontBold()
         ->setFontSize(13)
         ->setBackgroundColor(Color::rgb(228, 228, 228))
         ->build();

      $writer = WriterFactory::create(Type::CSV);
      $writer->openToBrowser('Places.csv')
         ->addRowWithStyle($columns, $style);

      $query->chunk(5000, function ($complains) use ($writer) {
         foreach ($complains as $complain) {
               $values = [
                  $complain->id ?? '-',
                  $complain->unique_reference_id ?? '-',
                $complain->type ?? '-',
                  $complain->name ?? '-',
                  $complain->ward ?? '-'
               ];
               $writer->addRow($values);
         }
      });

      $writer->close();
   }

}
