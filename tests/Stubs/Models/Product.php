<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function items(): HasMany
    {
        return $this->hasMany(ProductItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}