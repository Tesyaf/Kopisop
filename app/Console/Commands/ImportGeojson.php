<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportGeojson extends Command
{
    protected $signature = 'geojson:import 
        {--shops=public/data/coffeeshops.geojson : Path ke GeoJSON titik coffee shop}
        {--boundary=public/data/batas-wilayah.geojson : Path ke GeoJSON batas wilayah}
        {--truncate : Bersihkan tabel sebelum import}';

    protected $description = 'Import GeoJSON ke tabel spasial coffeeshops dan boundaries';

    public function handle(): int
    {
        $shopPath = base_path($this->option('shops'));
        $boundaryPath = base_path($this->option('boundary'));

        if (!File::exists($shopPath)) {
            $this->error("File shops tidak ditemukan: {$shopPath}");
            return self::FAILURE;
        }
        if (!File::exists($boundaryPath)) {
            $this->error("File boundary tidak ditemukan: {$boundaryPath}");
            return self::FAILURE;
        }

        if ($this->option('truncate')) {
            DB::table('coffeeshops')->truncate();
            DB::table('boundaries')->truncate();
            $this->info('Tabel coffeeshops dan boundaries dibersihkan.');
        }

        $shopJson = json_decode(File::get($shopPath), true);
        $boundaryJson = json_decode(File::get($boundaryPath), true);

        $shopFeatures = $shopJson['features'] ?? [];
        $boundaryFeatures = $boundaryJson['features'] ?? [];

        $shopRows = [];
        foreach ($shopFeatures as $f) {
            $props = $f['properties'] ?? [];
            $geom = $f['geometry'] ?? null;
            if (!$geom || !isset($geom['coordinates'][0], $geom['coordinates'][1])) {
                continue;
            }
            $lon = $geom['coordinates'][0];
            $lat = $geom['coordinates'][1];
            $shopRows[] = [
                'name'       => $props['NAMA'] ?? 'Tanpa nama',
                'open_time'  => $props['WAKTU_BUKA'] ?? null,
                'close_time' => $props['WKT_TUTUP'] ?? null,
                'avg_price'  => $props['HARGA'] ?? null,
                'rating'     => $props['RATING'] ?? null,
                'address'    => $props['ALAMAT'] ?? 'Pahoman, Bandar Lampung',
                'location_wkt'   => "POINT($lon $lat)",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $boundaryRows = [];
        foreach ($boundaryFeatures as $f) {
            $props = $f['properties'] ?? [];
            $geom = $f['geometry'] ?? null;
            if (!$geom) {
                continue;
            }
            $boundaryRows[] = [
                'name'       => $props['NAMOBJ'] ?? $props['WADMKK'] ?? 'Boundary',
                'remark'     => $props['REMARK'] ?? null,
                'geom_wkt'       => $this->geojsonToWkt($geom),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($shopRows, $boundaryRows) {
            if ($shopRows) {
                DB::table('coffeeshops')->insert($shopRows);
            }
            if ($boundaryRows) {
                DB::table('boundaries')->insert($boundaryRows);
            }
        });

        $this->info("Import selesai. Shops: ".count($shopRows)." | Boundaries: ".count($boundaryRows));
        $this->info("Pastikan DB MySQL mendukung fungsi ST_GeomFromGeoJSON (MySQL 5.7+/8).");

        return self::SUCCESS;
    }

    private function geojsonToWkt(array $geom): string
    {
        $type = strtoupper($geom['type'] ?? '');
        $coords = $geom['coordinates'] ?? [];

        if ($type === 'POINT') {
            return 'POINT('.implode(' ', $coords).')';
        }

        if ($type === 'MULTIPOLYGON') {
            $polys = [];
            foreach ($coords as $poly) {
                $rings = [];
                foreach ($poly as $ring) {
                    $rings[] = '(' . implode(', ', array_map(fn($c)=> $c[0].' '.$c[1], $ring)) . ')';
                }
                $polys[] = '(' . implode(', ', $rings) . ')';
            }
            return 'MULTIPOLYGON(' . implode(', ', $polys) . ')';
        }

        return '';
    }
}
