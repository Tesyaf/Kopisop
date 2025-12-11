<?php

namespace App\Http\Controllers;

use App\Models\Coffeeshop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CoffeeshopController extends Controller
{
    public function index()
    {
        // Try DB mode first if configured
        if (env('DATA_SOURCE') === 'db') {
            try {
                $rows = Coffeeshop::all();
                $features = $rows->map(function ($r) {
                    $geom = null;
                    if ($r->location_wkt) {
                        $wkt = strtoupper(trim($r->location_wkt));
                        // Parse POINT(lon lat) or POINT (lon lat)
                        if (preg_match('/POINT\s*\(\s*([\d.\-]+)\s+([\d.\-]+)\s*\)/i', $r->location_wkt, $matches)) {
                            $lon = (float)$matches[1];
                            $lat = (float)$matches[2];
                            $geom = ['type' => 'Point', 'coordinates' => [$lon, $lat]];
                        }
                    }
                    return [
                        'type' => 'Feature',
                        'properties' => [
                            'id' => $r->id,
                            'NAMA' => $r->name,
                            'WAKTU_BUKA' => $r->open_time,
                            'WKT_TUTUP' => $r->close_time,
                            'HARGA' => $r->avg_price,
                            'RATING' => $r->rating,
                            'ALAMAT' => $r->address,
                        ],
                        'geometry' => $geom,
                    ];
                })->filter(fn($f) => !is_null($f['geometry']))->values();

                if (count($features) > 0) {
                    return response()->json(['type' => 'FeatureCollection', 'features' => $features]);
                }
            } catch (\Throwable $e) {
                // fallthrough to file mode
            }
        }

        // Fallback: try file from public/data/coffeeshops.geojson
        $path = base_path('public/data/coffeeshops.geojson');
        if (File::exists($path)) {
            try {
                $content = File::get($path);
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['features'])) {
                    return response()->json($decoded);
                }
            } catch (\Throwable $e) {
                // continue to empty fallback
            }
        }

        // Final fallback: empty collection
        return response()->json(['type' => 'FeatureCollection', 'features' => []]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'open_time' => 'nullable|string|max:10',
            'close_time' => 'nullable|string|max:10',
            'avg_price' => 'nullable|numeric',
            'rating' => 'nullable|numeric',
            'address' => 'nullable|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        // If not using DB mode, append to GeoJSON file so preview and map remain consistent
        if (env('DATA_SOURCE') !== 'db') {
            $path = base_path('public/data/coffeeshops.geojson');
            $json = ['type' => 'FeatureCollection', 'features' => []];
            if (File::exists($path)) {
                $decoded = json_decode(File::get($path), true);
                if (is_array($decoded) && isset($decoded['features']) && is_array($decoded['features'])) {
                    $json = $decoded;
                }
            }

            // determine next id
            $maxId = 0;
            foreach ($json['features'] as $ft) {
                $pid = $ft['properties']['id'] ?? 0;
                if (is_numeric($pid) && $pid > $maxId) $maxId = (int)$pid;
            }
            $newId = $maxId + 1;

            $feature = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $newId,
                    'NAMA' => $data['name'],
                    'WAKTU_BUKA' => $data['open_time'] ?? null,
                    'WKT_TUTUP' => $data['close_time'] ?? null,
                    'HARGA' => $data['avg_price'] ?? null,
                    'RATING' => $data['rating'] ?? null,
                    'ALAMAT' => $data['address'] ?? null,
                ],
                'geometry' => ['type' => 'Point', 'coordinates' => [(float)$data['lng'], (float)$data['lat']]],
            ];

            $json['features'][] = $feature;
            File::put($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return response()->json($feature, 201);
        }

        $locationWkt = "POINT({$data['lng']} {$data['lat']})";

        $shop = Coffeeshop::create([
            'name' => $data['name'],
            'open_time' => $data['open_time'] ?? null,
            'close_time' => $data['close_time'] ?? null,
            'avg_price' => $data['avg_price'] ?? null,
            'rating' => $data['rating'] ?? null,
            'address' => $data['address'] ?? null,
            'location_wkt' => $locationWkt,
        ]);

        $feature = [
            'type' => 'Feature',
            'properties' => [
                'id' => $shop->id,
                'NAMA' => $shop->name,
                'WAKTU_BUKA' => $shop->open_time,
                'WKT_TUTUP' => $shop->close_time,
                'HARGA' => $shop->avg_price,
                'RATING' => $shop->rating,
                'ALAMAT' => $shop->address,
            ],
            'geometry' => ['type' => 'Point', 'coordinates' => [(float)$data['lng'], (float)$data['lat']]],
        ];

        return response()->json($feature, 201);
    }

    public function update(Request $request, $id)
    {
        // if file mode, update GeoJSON file
        if (env('DATA_SOURCE') !== 'db') {
            $path = base_path('public/data/coffeeshops.geojson');
            if (!File::exists($path)) return response()->json(['message' => 'Data file not found'], 404);
            $json = json_decode(File::get($path), true);
            if (!is_array($json) || !isset($json['features'])) return response()->json(['message' => 'Invalid geojson'], 500);

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'open_time' => 'nullable|string|max:10',
                'close_time' => 'nullable|string|max:10',
                'avg_price' => 'nullable|numeric',
                'rating' => 'nullable|numeric',
                'address' => 'nullable|string',
                'lat' => 'nullable|numeric',
                'lng' => 'nullable|numeric',
            ]);

            foreach ($json['features'] as &$ft) {
                if ((string)($ft['properties']['id'] ?? '') === (string)$id) {
                    if (isset($data['name'])) $ft['properties']['NAMA'] = $data['name'];
                    if (array_key_exists('open_time', $data)) $ft['properties']['WAKTU_BUKA'] = $data['open_time'];
                    if (array_key_exists('close_time', $data)) $ft['properties']['WKT_TUTUP'] = $data['close_time'];
                    if (array_key_exists('avg_price', $data)) $ft['properties']['HARGA'] = $data['avg_price'];
                    if (array_key_exists('rating', $data)) $ft['properties']['RATING'] = $data['rating'];
                    if (array_key_exists('address', $data)) $ft['properties']['ALAMAT'] = $data['address'];
                    if (isset($data['lat']) && isset($data['lng'])) {
                        $ft['geometry'] = ['type' => 'Point', 'coordinates' => [(float)$data['lng'], (float)$data['lat']]];
                    }
                    File::put($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    return response()->json($ft, 200);
                }
            }
            return response()->json(['message' => 'Feature not found'], 404);
        }

        $shop = Coffeeshop::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'open_time' => 'nullable|string|max:10',
            'close_time' => 'nullable|string|max:10',
            'avg_price' => 'nullable|numeric',
            'rating' => 'nullable|numeric',
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        if (isset($data['lat']) && isset($data['lng'])) {
            $data['location_wkt'] = "POINT({$data['lng']} {$data['lat']})";
        }

        $shop->update($data);

        // Return updated feature
        $feature = [
            'type' => 'Feature',
            'properties' => [
                'id' => $shop->id,
                'NAMA' => $shop->name,
                'WAKTU_BUKA' => $shop->open_time,
                'WKT_TUTUP' => $shop->close_time,
                'HARGA' => $shop->avg_price,
                'RATING' => $shop->rating,
                'ALAMAT' => $shop->address,
            ],
            'geometry' => $shop->location_wkt ? (function () use ($shop) {
                if (preg_match('/POINT\s*\(\s*([\d.\-]+)\s+([\d.\-]+)\s*\)/i', $shop->location_wkt, $m)) {
                    return ['type' => 'Point', 'coordinates' => [(float)$m[1], (float)$m[2]]];
                }
                return null;
            })() : null,
        ];

        return response()->json($feature, 200);
    }

    public function destroy($id)
    {
        if (env('DATA_SOURCE') !== 'db') {
            $path = base_path('public/data/coffeeshops.geojson');
            if (!File::exists($path)) return response()->json(['message' => 'Data file not found'], 404);
            $json = json_decode(File::get($path), true);
            if (!is_array($json) || !isset($json['features'])) return response()->json(['message' => 'Invalid geojson'], 500);
            $before = count($json['features']);
            $json['features'] = array_values(array_filter($json['features'], fn($f) => (string)($f['properties']['id'] ?? '') !== (string)$id));
            if (count($json['features']) === $before) return response()->json(['message' => 'Feature not found'], 404);
            File::put($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return response()->json(null, 204);
        }

        $shop = Coffeeshop::findOrFail($id);
        $shop->delete();
        return response()->json(null, 204);
    }
}
