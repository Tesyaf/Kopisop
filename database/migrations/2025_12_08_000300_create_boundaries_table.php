<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boundaries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('remark')->nullable();
            $table->longText('geom_wkt')->nullable()->comment('WKT MultiPolygon');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boundaries');
    }
};
