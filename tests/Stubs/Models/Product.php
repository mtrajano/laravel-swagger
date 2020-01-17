<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
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

    public function items()
    {
        return $this->hasMany(ProductItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}