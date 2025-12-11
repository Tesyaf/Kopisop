<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coffeeshop extends Model
{
    protected $table = 'coffeeshops';

    protected $fillable = [
        'name',
        'open_time',
        'close_time',
        'avg_price',
        'rating',
        'address',
        'location_wkt',
    ];
}
