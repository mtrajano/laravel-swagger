<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getAppends(): array
    {
        return $this->appends;
    }
}