<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class GISPrintController extends Controller
{
public function print(Request $request)
{
    $spec = $request->validate([
        'spec' => ['required', 'array'],
        'spec.layers' => ['required', 'array'],
    ])['spec'];

    foreach ($spec['layers'] as &$entry) {
    foreach (($entry['layers'] ?? []) as $index => $layer) {
        $parts = explode(':', $layer, 2);
        $layerName = count($parts) === 2 ? $parts[1] : $parts[0];

        $config = config("gis.layers.$layerName");
        abort_unless($config, 403, 'Layer is not allowed.');

        if (!empty($config['permission'])) {
            abort_unless(
                $request->user()->can($config['permission']),
                403
            );
        }

        $entry['layers'][$index] =
    config('services.geoserver.workspace') . ':' . $layerName;
    }

    if (($entry['type'] ?? null) === 'WMS') {
        $entry['baseURL'] =
            rtrim(config('services.geoserver.url'), '/') .
            '/wms?authkey=' .
            urlencode(config('services.geoserver.auth_key'));
    }
}

unset($entry);

foreach ($spec['pages'] ?? [] as &$page) {
    $page['comment'] = $page['comment'] ?? '';
}

unset($page);

    Log::info('Final GIS print baseURL', [
        'baseURL' => $spec['layers'][0]['baseURL'] ?? null,
    ]);

    $printUrl = config('services.mapfish.print_url');

    try {
        $response = Http::asForm()
            ->timeout(180)
            ->post($printUrl, [
                'spec' => json_encode($spec, JSON_THROW_ON_ERROR),
            ]);
    } catch (ConnectionException $exception) {
        Log::error('GIS print service connection failed', [
            'message' => $exception->getMessage(),
        ]);

        return response()->json([
            'message' => 'The map printing service is unavailable.',
        ], 502);
    }

    if (!$response->successful()) {
        Log::error('GIS print service returned an error', [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 2000),
        ]);

        return response()->json([
            'message' => 'The map printing service returned an error.',
            'status' => $response->status(),
        ], 502);
    }

    return response($response->body(), 200)
        ->header('Content-Type', 'application/pdf')
        ->header(
            'Content-Disposition',
            'attachment; filename="IMIS-Map.pdf"'
        )
        ->header('Cache-Control', 'private, no-store');
}
}
