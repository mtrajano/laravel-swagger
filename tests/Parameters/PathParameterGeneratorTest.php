<?php

namespace Mtrajano\LaravelSwagger\Tests\Parameters;

use Mtrajano\LaravelSwagger\Tests\TestCase;
use Mtrajano\LaravelSwagger\Parameters\PathParameterGenerator;

class PathParameterGeneratorTest extends TestCase
{
    public function testRequiredParameter()
    {
        $pathParameters = $this->getPathParameters('/users/{id}');

        $this->assertArraySubset([
            'in' => 'path',
            'name' => 'id',
            'required' => true,
        ], $pathParameters[0]);
    }

    public function testOptionalParameter()
    {
        $pathParameters = $this->getPathParameters('/users/{id?}');

        $this->assertArraySubset([
            'required' => false,
        ], $pathParameters[0]);
    }

    public function testMultipleParameters()
    {
        $pathParameters = $this->getPathParameters('/users/{username}/{id?}');

        $this->assertArraySubset([
            'name' => 'username',
            'required' => true,
        ], $pathParameters[0]);

        $this->assertArraySubset([
            'name' => 'id',
            'required' => false,
        ], $pathParameters[1]);
    }

    public function testEmptyParameters()
    {
        $pathParameters = $this->getPathParameters('/users');

        $this->assertEmpty($pathParameters);
    }

    private function getPathParameters($uri)
    {
        return (new PathParameterGenerator($uri))->getParameters();
    }
}
