<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GISExportController extends Controller
{
    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['csv', 'kml', 'shp'], true), 404);
        $layer = (string) $request->query('layer');
        $config = config("gis.layers.$layer");
        abort_unless($config, 404, 'Unknown GIS layer.');
        abort_unless(($config['export'] ?? false) === true, 403, 'Export is not allowed.');
        if (!empty($config['permission'])) {
            abort_unless($request->user()->can($config['permission']), 403);
        }
        $formats = [
            'csv' => ['geoserver' => 'csv', 'extension' => 'csv', 'content_type' => 'text/csv'],
            'kml' => ['geoserver' => 'KML', 'extension' => 'kml', 'content_type' => 'application/vnd.google-earth.kml+xml'],
            'shp' => ['geoserver' => 'SHAPE-ZIP', 'extension' => 'zip', 'content_type' => 'application/zip'],
        ];
        $selected = $formats[$format];
        $filename = $config['filename'] ?? $layer;
        $params = [
            'service' => 'WFS', 'version' => '1.0.0', 'request' => 'GetFeature',
            'typeName' => config('services.geoserver.workspace') . ':' . $layer,
            'outputFormat' => $selected['geoserver'],
            'format_options' => 'filename:' . $filename . '.' . $selected['extension'],
            'authkey' => config('services.geoserver.auth_key'),
        ];
        if ($filter = $request->query('filter')) {
            abort_if(strlen($filter) > 4000 || preg_match('/(;|--|\/\*|\*\/)/', $filter), 400, 'Invalid filter.');
            $params['CQL_FILTER'] = $filter;
        }
        $response = Http::timeout(120)->get(rtrim(config('services.geoserver.url'), '/') . '/wfs', $params);
        abort_unless($response->successful(), $response->status());
        return response($response->body(), 200)
            ->header('Content-Type', $response->header('Content-Type', $selected['content_type']))
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.' . $selected['extension'] . '"')
            ->header('Cache-Control', 'private, no-store');
    }
}
