<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model
{
    protected $fillable = [
        'name',
        'price',
        'active',
    ];

    protected $hidden = [
        'active',
    ];

    protected $casts = [
        'name' => 'string',
        'price' => 'float',
        'active' => 'boolean',
    ];

    protected $dates = [
        'finished_at',
    ];
}