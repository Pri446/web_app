<?php

namespace App\Services\LayerInfo;

use Illuminate\Http\Request;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Auth;
use DataTables;
use DB;
use App\Models\LayerInfo\LowIncomeCommunity;

class LowIncomeCommunityServiceClass
{
    /**
     * Fetch and format data for DataTables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchData($request)
    {
        $lic = LowIncomeCommunity::whereNull('deleted_at');



        return Datatables::of($lic)
            ->filter(function ($query) use ($request) {
                if ($request->community_name) {
                    $query->whereRaw('LOWER(community_name) LIKE ?', ['%' . strtolower($request->community_name) . '%']);
                }
            })

            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['low-income-communities.destroy', $model->id]]);

                if (Auth::user()->can('Edit Low Income Community')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("LayerInfo\LowIncomeCommunityController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }
                if (Auth::user()->can('View Low Income Community')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("LayerInfo\LowIncomeCommunityController@show", [$model->id]) . '"class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }
                if (Auth::user()->can('View Low Income Community History')) {
                    $content .= '<a title="' . e(__("History")) . '" href="' . action("LayerInfo\LowIncomeCommunityController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }
                if (Auth::user()->can('Delete Low Income Community')) {
                    $content .= '<a title="' . e(__("Delete")) . '" class="delete btn btn-danger btn-sm mb-1">&nbsp;<i class="fa fa-trash"></i>&nbsp;</a> ';
                }
                if (Auth::user()->can('View Low Income Community On Map')) {
                    $content .= '<a title="' . e(__("Map")) . '" href="' . action("MapsController@index", ['layer' => 'low_income_communities_layer', 'field' => 'id', 'val' => $model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-map-marker"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->make(true);
    }
    /**
     * Store data for a Low income community record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeData($request)
{
    // If geom is not provided or validation fails, don't update the data
    if (empty($request->geom)) {
        return redirect('layer-info/low-income-communities/create')
            ->with('error', __('Failed to Add Low Income Community'))
            ->withInput();  // Retain the user's input
    }

    $lic = new LowIncomeCommunity();
    $lic->population_total = $request->population_total;
    $lic->number_of_households = $request->number_of_households;
    $lic->population_male = $request->population_male;
    $lic->population_female = $request->population_female;
    $lic->population_others = $request->population_others;
    $lic->no_of_septic_tank = $request->no_of_septic_tank;
    $lic->no_of_holding_tank = $request->no_of_holding_tank;
    $lic->no_of_pit = $request->no_of_pit;
    $lic->no_of_sewer_connection = $request->no_of_sewer_connection;
    $lic->no_of_buildings = $request->no_of_buildings;
    $lic->community_name = $request->community_name;
    $lic->no_of_community_toilets = $request->no_of_community_toilets;

    // Retrieve the municipality boundary geometry
    $citypolygeom = DB::table('layer_info.citypolys')->where('id', 1)->value('geom');

    // Check if the geom is within the municipality boundary
    $contains = DB::select(
        "SELECT ST_Contains(?, ST_GeomFromText(?, 4326)) as contains",
        [$citypolygeom, $request->geom]
    )[0]->contains;

    if ($contains === true) {
        // Find the ward for the geometry if it's inside the boundary
        $ward = DB::select("SELECT w.ward
            FROM layer_info.wards w
            WHERE ST_Intersects(w.geom, ST_GeomFromText('" . $request->geom . "', 4326))
            ORDER BY ST_Area(ST_Intersection(w.geom, ST_GeomFromText('" . $request->geom . "', 4326))) DESC
            LIMIT 1");

        // If a ward is found, save the geometry
        if (!empty($ward)) {
            $lic->geom = DB::raw("ST_Multi(ST_GeomFromText('" . $request->geom . "', 4326))");
            $lic->save();
            return redirect('layer-info/low-income-communities')->with('success', __('Low Income Community added successfully.'));
        } else {
            return redirect('layer-info/low-income-communities/create')
                ->with('error', __('Failed to find the ward for the selected area'))
                ->withInput(); // Retain the user's input
        }
    } else {
        // If the geometry is not within the boundary, show an error
        return redirect('layer-info/low-income-communities/create')
            ->with('error', __('The selected area should be within the Municipality Boundary'))
            ->withInput(); // Retain the user's input
    }
}



    /**
     * Update data for a Low income community record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id The ID of the Low income community record to update
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateData($request, $id)
    {
        $lic = LowIncomeCommunity::find($id);

        if ($lic) {
            // Update all fields as in the original method
            $lic->population_total = $request->population_total;
            $lic->number_of_households = $request->number_of_households;
            $lic->population_male = $request->population_male;
            $lic->population_female = $request->population_female;
            $lic->population_others = $request->population_others;
            $lic->no_of_septic_tank = $request->no_of_septic_tank;
            $lic->no_of_holding_tank = $request->no_of_holding_tank;
            $lic->no_of_pit = $request->no_of_pit;
            $lic->no_of_sewer_connection = $request->no_of_sewer_connection;
            $lic->no_of_buildings = $request->no_of_buildings;
            $lic->community_name = $request->community_name;
            $lic->no_of_community_toilets = $request->no_of_community_toilets;

            // Check if 'geom' is provided in the request
            if (!empty($request->geom)) {
                $citypolygeom = DB::table('layer_info.citypolys')->where('id', 1)->value('geom');
                $contains = DB::select(
                    "SELECT ST_Contains(?, ST_GeomFromText(?, 4326)) as contains",
                    [$citypolygeom, $request->geom]
                )[0]->contains;

                if (!empty($contains) && $contains === true) {
                    $ward = DB::select("SELECT w.ward
                        FROM layer_info.wards w, ST_Intersects(w.geom, ST_GeomFromText('" . $request->geom . "', 4326))
                        ORDER BY
                            ST_Area(ST_Intersection(w.geom, ST_GeomFromText('" . $request->geom . "', 4326))) DESC
                        LIMIT 1");
                    $lic->geom = DB::raw("ST_Multi(ST_GeomFromText('" . $request->geom . "', 4326))");
                    $lic->save();
                } else {
                    // Retain input on error
                    return redirect('layer-info/low-income-communities/' . $id . '/edit')
                        ->with('error', __('The selected area should be within the Municipality Boundary'))
                        ->withInput(); // Retain the user's input
                }
            } else {
                // No geom provided, just update the other fields
                $lic->save();
            }

            return redirect('layer-info/low-income-communities')->with('success', __('Low Income Community updated successfully.'));
        } else {
            return redirect('layer-info/low-income-communities')->with('error', __('Failed to update Low Income Community.'));
        }
    }


    /**
     * Display details of a Low income community record.
     *
     * @param  int  $id The ID of the Low income community record to display
     * @return \Illuminate\Contracts\View\View
     */
    public function showData($id)
    {
        $lic = LowIncomeCommunity::find($id);
        if ($lic) {
            $page_title = __("Low Income Community Details");
            $geomArr = DB::select("SELECT ST_X(ST_AsText(ST_Centroid(ST_Centroid(geom)))) AS long, ST_Y(ST_AsText(ST_Centroid(ST_Centroid(geom)))) AS lat, ST_AsText(geom) AS geom FROM layer_info.low_income_communities WHERE id = $id");
            $geom = ($geomArr[0]->geom);
            $lat = $geomArr[0]->lat;
            $long = $geomArr[0]->long;
            return view('layer-info/low-income-communities.show', compact('page_title', 'lic', 'geom', 'lat', 'long'));
        } else {
            abort(404);
        }
    }

    /**
     * Export Low income community data to a CSV file.
     *
     * @param  array  $data The data containing search criteria
     * @return void
     */
    public function exportData($data)
    {

        $searchData = $data['searchData'] ? $data['searchData'] : null;
        $community_name = $data['community_name'] ?? null;
        $columns = [
            __('ID'),
            __('Community Name'),
            __('No. of Buildings'),
            __('Population'),
            __('No. of Households'),
            __('Male Population'),
            __('Female Population'),
            __('Other Population'),
            __('No. of Septic Tanks'),
            __('No. of Holding Tanks'),
            __('No. of Pits'),
            __('No. of Sewer Connections'),
            __('No. of Community Toilets'),

        ];
        $query = LowIncomeCommunity::select(
            'id',
            'community_name',
            'no_of_buildings',
            'population_total',
            'number_of_households',
            'population_male',
            'population_female',
            'population_others',
            'no_of_septic_tank',
            'no_of_holding_tank',
            'no_of_pit',
            'no_of_sewer_connection',
            'no_of_community_toilets'
        )->whereNull('deleted_at');
        if (!empty($community_name)) {
            $query->whereRaw('LOWER(community_name) LIKE ?', ['%' . strtolower($community_name) . '%']);
        }

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();
        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Low Income Community.csv')
            ->addRowWithStyle($columns, $style);
        $query->chunk(5000, function ($lics) use ($writer) {
            foreach ($lics as $lic) {
                $values = [];
                $values[] = $lic->id;
                $values[] = $lic->community_name;
                $values[] = $lic->no_of_buildings;
                $values[] = $lic->population_total;
                $values[] = $lic->number_of_households;
                $values[] = $lic->population_male;
                $values[] = $lic->population_female;
                $values[] = $lic->population_others;
                $values[] = $lic->no_of_septic_tank;
                $values[] = $lic->no_of_holding_tank;
                $values[] = $lic->no_of_pit;
                $values[] = $lic->no_of_sewer_connection;
                $values[] = $lic->no_of_community_toilets;

                $writer->addRow($values);
            }
        });
        $writer->close();
    }
}
