<?php

namespace Mtrajano\LaravelSwagger\Definitions\Security\Contracts;

use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\LaravelSwaggerException;

interface SecurityDefinitionsGenerator
{
    /**
     * @throws LaravelSwaggerException
     */
    public function generate(): array;

    public function generateForRoute(Route $route): array;
}
