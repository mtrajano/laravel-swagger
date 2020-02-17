<?php

namespace Mtrajano\LaravelSwagger\Definitions\Security;

use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Definitions\Security\Contracts\SecurityDefinitionsGenerator;

class JWTSecurityDefinitionsGenerator implements SecurityDefinitionsGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generate(): array
    {
        return [
            'Bearer' => [
                'type' => 'apiKey',
                'name' => 'Authorization',
                'in' => 'header',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function generateForRoute(Route $route): array
    {
        if (!$route->hasAuthMiddleware()) {
            return [];
        }

        return [
            [
                'Bearer' => [],
            ],
        ];
    }
}
