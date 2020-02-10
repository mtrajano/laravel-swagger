<?php

namespace Mtrajano\LaravelSwagger\Tests\Parameters;

use Mtrajano\LaravelSwagger\Tests\TestCase;
use Mtrajano\LaravelSwagger\Traits\HasAppends;
use Illuminate\Database\Eloquent\Model;

class HasAppendsTest extends TestCase
{
    public function testModelReturnsAppends() : void
    {
        $this->assertEquals([
            'property-1',
            'property-2',
        ], (new ModelWithAppends())->getAppends());
    }

    public function testModelWithoutAppendsReturnsEmpty() : void
    {
        $this->assertEquals([], (new ModelWithoutAppends())->getAppends());
    }
}

class ModelWithAppends extends Model
{
    use HasAppends;

    protected $appends = [
        'property-1',
        'property-2',
    ];
}

class ModelWithoutAppends extends Model
{
    use HasAppends;
}
