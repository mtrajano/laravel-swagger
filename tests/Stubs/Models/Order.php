<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mtrajano\LaravelSwagger\Traits\HasAppends;

class Order extends Model
{
    use HasAppends;

    protected $fillable = [
        'value',
    ];

    protected $casts = [
        'value' => 'float',
        'formatted_value' => 'string',
    ];

    protected $appends = [
        'formatted_value',
    ];

    public function getFormattedValueAttribute()
    {
        return '$ '.$this->value;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}