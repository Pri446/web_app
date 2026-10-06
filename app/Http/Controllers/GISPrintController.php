<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GISPrintController extends Controller
{
    public function print(Request $request)
    {
        $spec = $request->validate(['spec' => ['required', 'array']])['spec'];
        foreach (($spec['layers'] ?? []) as &$entry) {
            foreach (($entry['layers'] ?? []) as $layer) {
                $name = explode(':', $layer, 2);
                $layer = count($name) === 2 ? $name[1] : $name[0];
                $config = config("gis.layers.$layer");
                abort_unless($config, 403, 'Layer is not allowed.');
                if (!empty($config['permission'])) {
                    abort_unless($request->user()->can($config['permission']), 403);
                }
            }
            if (($entry['type'] ?? null) === 'WMS') {
                $entry['baseURL'] = rtrim(config('services.geoserver.url'), '/') . '/wms?authkey=' . urlencode(config('services.geoserver.auth_key'));
            }
        }
        unset($entry);
        $response = Http::timeout(180)->get(config('services.mapfish.print_url'), ['spec' => json_encode($spec)]);
        abort_unless($response->successful(), $response->status());
        return response($response->body(), 200)->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="IMIS-Map.pdf"')->header('Cache-Control', 'private, no-store');
    }
}
