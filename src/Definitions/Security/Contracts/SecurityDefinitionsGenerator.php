<?php

namespace Mtrajano\LaravelSwagger\Definitions\Security\Contracts;

use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\LaravelSwaggerException;

interface SecurityDefinitionsGenerator
{
    /**
     * @return array
     * @throws LaravelSwaggerException
     */
    public function generate(): array;

    /**
     * @param Route|null $route
     * @return array
     */
    public function generateForRoute(Route $route): array;
}