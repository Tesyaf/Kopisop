<?php

namespace App\Http\Controllers;

use App\Models\Coffeeshop;
use Illuminate\Http\Request;

class CoffeeshopController extends Controller
{
    public function index()
    {
        $rows = Coffeeshop::all();

        $features = $rows->map(function ($r) {
            $geom = null;
            if ($r->location_wkt && str_starts_with(strtoupper($r->location_wkt), 'POINT')) {
                $coords = trim(str_ireplace(['POINT', '(', ')'], '', $r->location_wkt));
                [$lon, $lat] = array_map('floatval', preg_split('/\s+/', $coords));
                $geom = ['type' => 'Point', 'coordinates' => [$lon, $lat]];
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

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
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

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $shop = Coffeeshop::findOrFail($id);
        $shop->delete();
        return response()->json(null, 204);
    }
}
