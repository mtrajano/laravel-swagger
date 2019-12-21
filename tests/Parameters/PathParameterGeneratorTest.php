<?php

namespace Mtrajano\LaravelSwagger\Tests\Parameters;

use Mtrajano\LaravelSwagger\Parameters\PathParameterGenerator;
use Mtrajano\LaravelSwagger\Tests\TestCase;

class PathParameterGeneratorTest extends TestCase
{
    public function testRequiredParameter()
    {
        $pathParameters = $this->getPathParameters('/users/{id}');

        $this->assertSame('path', $pathParameters[0]['in']);
        $this->assertSame('id', $pathParameters[0]['name']);
        $this->assertSame(true, $pathParameters[0]['required']);
    }

    public function testOptionalParameter()
    {
        $pathParameters = $this->getPathParameters('/users/{id?}');

        $this->assertSame(false, $pathParameters[0]['required']);
    }

    public function testMultipleParameters()
    {
        $pathParameters = $this->getPathParameters('/users/{username}/{id?}');

        $this->assertSame('username', $pathParameters[0]['name']);
        $this->assertSame(true, $pathParameters[0]['required']);

        $this->assertSame('id', $pathParameters[1]['name']);
        $this->assertSame(false, $pathParameters[1]['required']);
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
