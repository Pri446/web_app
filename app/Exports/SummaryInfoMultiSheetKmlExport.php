<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SummaryInfoMultiSheetKmlExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * @var array|string  WKT geometry or array of WKT geometries
     */
    private $geom;

    /**
     * @var int|float|null  Buffer distance in meters
     */
    private $distance;

    /**
     * @var array|string  Polygon names or single name
     */
    private $name;

    /**
     * @param  mixed $bufferPolygonGeom     WKT string or array of WKT strings
     * @param  mixed $bufferPolygonDistance Buffer distance (meters)
     * @param  mixed $name                  String or array of names
     */
    public function __construct($bufferPolygonGeom, $bufferPolygonDistance, $name)
    {
        $this->geom     = $bufferPolygonGeom;
        $this->distance = $bufferPolygonDistance;
        $this->name     = $name;

    }

    /**
     * Returns an array of sheets for the export.
     *
     * @return array
     */
    public function sheets(): array
    {
        // STEP 1: Normalize arrays
        $geometries = is_array($this->geom) ? $this->geom : [$this->geom];
        $names      = is_array($this->name) ? $this->name : [$this->name];

        $buffer = $this->distance ?? 0;

         // ✅ NEW: store polygon name + area in km²
        $polygonAreaKm2List = [];
        $binPolygonMap = [];

        // STEP 2: Loop all polygons and get bins
        foreach ($geometries as $index => $polygon) {
            if (empty($polygon)) {
                continue;
            }

    

            $polygonName = $names[$index] ?? ('Polygon ' . ($index + 1));
            $areaRow = DB::selectOne(
                "SELECT (ST_Area(ST_GeomFromText(?, 4326)::geography) / 1000000.0) AS area_km2",
                [$polygon]
            );

            $areaKm2 = (float) ($areaRow->area_km2 ?? 0);

            $polygonAreaKm2List[] = [
                'polygon_name' => $polygonName,
                'area_km2'     => round($areaKm2, 6), // adjust decimals as needed
            ];

            $sql = "
                   WITH poly AS (
                        SELECT ST_Buffer(
                            ST_GeomFromText(?, 4326)::geography,
                            ?
                        )::geometry AS g
                    )
                    SELECT DISTINCT b.bin
                    FROM building_info.buildings b
                    LEFT JOIN building_info.sanitation_systems ss
                        ON ss.id = b.sanitation_system_id
                    CROSS JOIN poly
                    WHERE b.deleted_at IS NULL
                    AND ss.map_display IS TRUE
                    AND b.geom && poly.g
                    AND ST_Intersects(b.geom, poly.g)
                ";

            $rows = DB::select($sql, [$polygon, $buffer]);

            foreach ($rows as $r) {
                if (!empty($r->bin)) {
                    $binPolygonMap[] = [
                        'bin'          => $r->bin,
                        'polygon_name' => $polygonName,
                    ];
                }
            }
        }

        return [
            new BuildingsKmlExport($binPolygonMap),
            new BuildingsListKmlExport($binPolygonMap),
            new ContainmentsListKmlExport($binPolygonMap),
            new BuildContainKmlExport($binPolygonMap),
            new PolygonKmlExport($polygonAreaKm2List),

        ];
    }
}
