<?php

namespace Mtrajano\LaravelSwagger\Definitions\ErrorHandlers;

use Mtrajano\LaravelSwagger\DataObjects\Route;

abstract class DefaultDefinitionHandler
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var string
     */
    private $ref;

    public function __construct(Route $route, string $ref)
    {
        $this->route = $route;
        $this->ref = $ref;
    }

    /**
     * @return array
     */
    final public function handle()
    {
        return [
            $this->ref => $this->getDefinitionContent(),
        ];
    }

    /**
     * @return array
     */
    abstract protected function getDefinitionContent(): array;

    /**
     * @return Route
     */
    protected function getRoute(): Route
    {
        return $this->route;
    }
}
