<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coffeeshops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('open_time', 10)->nullable();
            $table->string('close_time', 10)->nullable();
            $table->decimal('avg_price', 12, 2)->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('address')->nullable();
            // simpan WKT POINT untuk kompatibilitas tanpa ekstensi geom
            $table->string('location_wkt')->nullable()->comment('POINT(lon lat)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coffeeshops');
    }
};
