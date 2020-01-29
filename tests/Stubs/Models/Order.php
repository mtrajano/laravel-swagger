<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
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
}