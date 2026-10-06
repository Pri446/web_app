<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GISProxyController extends Controller
{
    public function wms(Request $request)
    {
        $params = $request->query();
        $service = strtoupper((string) ($params['SERVICE'] ?? $params['service'] ?? ''));
        $operation = strtoupper((string) ($params['REQUEST'] ?? $params['request'] ?? ''));
        abort_unless($service === 'WMS', 400, 'Invalid service.');
        abort_unless(in_array($operation, ['GETMAP', 'GETLEGENDGRAPHIC', 'GETFEATUREINFO'], true), 403, 'WMS operation is not allowed.');

        $raw = $operation === 'GETLEGENDGRAPHIC'
            ? ($params['LAYER'] ?? $params['layer'] ?? '')
            : ($params['QUERY_LAYERS'] ?? $params['query_layers'] ?? $params['LAYERS'] ?? $params['layers'] ?? '');
        foreach (array_filter(array_map('trim', explode(',', (string) $raw))) as $layer) {
            $this->authorizeLayer($request, $layer);
        }

        return $this->forward($params, '/wms', 60);
    }

    public function wfs(Request $request)
    {
        $params = $request->query();
        $service = strtoupper((string) ($params['SERVICE'] ?? $params['service'] ?? ''));
        $operation = strtoupper((string) ($params['REQUEST'] ?? $params['request'] ?? ''));
        abort_unless($service === 'WFS' && $operation === 'GETFEATURE', 403, 'WFS operation is not allowed.');
        $typeName = $params['typeName'] ?? $params['TYPENAME'] ?? $params['typeNames'] ?? $params['TYPENAMES'] ?? null;
        abort_unless($typeName, 400, 'Layer is required.');
        foreach (array_filter(array_map('trim', explode(',', (string) $typeName))) as $layer) {
            $this->authorizeLayer($request, $layer);
        }
        return $this->forward($params, '/wfs', 60);
    }

    private function forward(array $params, string $endpoint, int $timeout)
    {
        $params['authkey'] = config('services.geoserver.auth_key');
        $response = Http::timeout($timeout)->get(rtrim(config('services.geoserver.url'), '/') . $endpoint, $params);
        $result = response($response->body(), $response->status());
        return $result->header('Content-Type', $response->header('Content-Type', 'application/octet-stream'))
            ->header('Cache-Control', 'private, no-store');
    }

    private function authorizeLayer(Request $request, string $qualifiedLayer): void
    {
        [$workspace, $layer] = array_pad(explode(':', $qualifiedLayer, 2), 2, null);
        if ($layer === null) {
            $layer = $workspace;
        } elseif ($workspace !== config('services.geoserver.workspace')) {
            abort(403, 'Workspace is not allowed.');
        }
        $config = config("gis.layers.$layer");
        abort_unless($config, 403, 'Layer is not allowed.');
        if (!empty($config['permission'])) {
            abort_unless($request->user()->can($config['permission']), 403, 'You are not authorized for this layer.');
        }
    }
}
