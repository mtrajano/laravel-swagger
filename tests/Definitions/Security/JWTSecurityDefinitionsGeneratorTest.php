<?php

namespace Mtrajano\LaravelSwagger\Tests\Definitions\Security;

use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Definitions\Security\JWTSecurityDefinitionsGenerator;
use PHPUnit\Framework\TestCase;

class JWTSecurityDefinitionsGeneratorTest extends TestCase
{
    /**
     * @throws \Mtrajano\LaravelSwagger\LaravelSwaggerException
     */
    public function testGenerateSecurityDefinitions()
    {
        $jwt = new JWTSecurityDefinitionsGenerator();
        $generatedDefinitions = $jwt->generate();

        $expectedDefinitions = [
            'Bearer' => [
                'type' => 'apiKey',
                'name' => 'Authorization',
                'in' => 'header',
            ],
        ];

        $this->assertEquals($expectedDefinitions, $generatedDefinitions);
    }

    public function testGenerateSecurityDefinitionsForRouteWithoutAuthMiddleware()
    {
        $route = $this->createMock(Route::class);
        $route->method('hasAuthMiddleware')->willReturn(false);

        $jwt = new JWTSecurityDefinitionsGenerator();
        $routeDefinitions = $jwt->generateForRoute($route);

        $this->assertEmpty($routeDefinitions);
    }

    public function testGenerateSecurityDefinitionsForRouteWithAuthenticatedMiddleware()
    {
        $route = $this->createMock(Route::class);
        $route->method('hasAuthMiddleware')->willReturn(true);

        $jwt = new JWTSecurityDefinitionsGenerator();
        $routeDefinitions = $jwt->generateForRoute($route);

        $expectedRouteDefinitions = [
            [
                'Bearer' => [],
            ],
        ];

        $this->assertEquals($expectedRouteDefinitions, $routeDefinitions);
    }
}
