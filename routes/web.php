<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::view('/', 'welcome')->name('home');
Route::view('/map', 'map')->name('map');
Route::view('/listing', 'listing')->name('listing');
Route::view('/analisis', 'analisis')->name('analisis');
Route::view('/detail', 'detail')->name('detail');
Route::view('/about', 'about')->name('about');

Route::get('/api/geojson/{type}', function (string $type) {
    $files = [
        'shops' => base_path('public/data/coffeeshops.geojson'),
        'boundary' => base_path('public/data/batas-wilayah.geojson'),
    ];

    // Mode database: baca dari tabel spasial jika DATA_SOURCE=db
    if (env('DATA_SOURCE') === 'db') {
        try {
            if ($type === 'shops') {
                $rows = DB::table('coffeeshops')->select('id','name','open_time','close_time','avg_price','rating','address','location_wkt')->get();

                $features = $rows->map(function ($r) {
                    $geom = $r->location_wkt ? self::wktToGeojsonPoint($r->location_wkt) : null;
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
                })->filter(fn($f)=>!is_null($f['geometry']))->values();

                return response()->json([
                    'type' => 'FeatureCollection',
                    'features' => $features,
                ]);
            }

            if ($type === 'boundary') {
                $rows = DB::table('boundaries')->select('id','name','remark','geom_wkt')->get();

                $features = $rows->map(function ($r) {
                    $geom = $r->geom_wkt ? self::wktToGeojsonMultiPolygon($r->geom_wkt) : null;
                    return [
                        'type' => 'Feature',
                        'properties' => [
                            'id' => $r->id,
                            'NAMOBJ' => $r->name,
                            'REMARK' => $r->remark,
                        ],
                        'geometry' => $geom,
                    ];
                })->filter(fn($f)=>!is_null($f['geometry']))->values();

                return response()->json([
                    'type' => 'FeatureCollection',
                    'features' => $features,
                ]);
            }
        } catch (\Throwable $e) {
            // jika DB gagal, fallback ke file
        }
    }

    if (!array_key_exists($type, $files) || !file_exists($files[$type])) {
        abort(404, 'Data not found');
    }

    $content = file_get_contents($files[$type]);
    $decoded = json_decode($content, true);

    if ($decoded === null) {
        return response($content, 200)->header('Content-Type', 'application/json');
    }

    return response()->json($decoded);
})->name('api.geojson');

function wktToGeojsonPoint(string $wkt): ?array {
    if (!str_starts_with(strtoupper($wkt), 'POINT')) return null;
    $coords = trim(str_ireplace(['point','(',')'], '', $wkt));
    [$lon,$lat] = array_map('floatval', preg_split('/\s+/', $coords));
    return ['type'=>'Point','coordinates'=>[$lon,$lat]];
}

function wktToGeojsonMultiPolygon(string $wkt): ?array {
    if (!str_starts_with(strtoupper($wkt), 'MULTIPOLYGON')) return null;
    $clean = trim(str_ireplace(['multipolygon',' '], '', $wkt));
    $clean = trim($clean, '()');
    $polygons = explode(')),((', $clean);
    $coords = [];
    foreach ($polygons as $poly) {
        $rings = explode('),(', $poly);
        $polyCoords = [];
        foreach ($rings as $ring) {
            $points = explode(',', $ring);
            $ringCoords = [];
            foreach ($points as $pt) {
                [$lon,$lat] = array_map('floatval', explode(' ', trim($pt)));
                $ringCoords[] = [$lon,$lat];
            }
            $polyCoords[] = $ringCoords;
        }
        $coords[] = $polyCoords;
    }
    return ['type'=>'MultiPolygon','coordinates'=>$coords];
}
