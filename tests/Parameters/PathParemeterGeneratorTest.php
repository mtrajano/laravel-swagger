<?php

namespace Mtrajano\LaravelSwagger\Tests\Parameters;

use Mtrajano\LaravelSwagger\Tests\TestCase;
use Mtrajano\LaravelSwagger\Parameters\PathParameterGenerator;

class PathParemeterGeneratorTest extends TestCase
{
    public function testRequiredParameter()
    {
        $pathParameters = $this->getPathParameter('/users/{id}')->getParameters();

        $this->assertArraySubset([
            'in' => 'path',
            'variable' => 'id',
            'required' => true
        ], $pathParameters);
    }

    private function getPathParameter($uri)
    {
        return new PathParameterGenerator('get', $uri, []);
    }
}
