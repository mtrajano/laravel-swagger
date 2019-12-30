<?php

namespace Mtrajano\LaravelSwagger\DataObjects;

class Middleware
{
    private $name;
    private $parameters;

    public function __construct(string $middleware)
    {
        $tokens = explode(':', $middleware, 2);
        $this->name = $tokens[0];
        $this->parameters = isset($tokens[1]) ? explode(',', $tokens[1]) : [];
    }

    public function name()
    {
        return $this->name;
    }

    public function parameters()
    {
        return $this->parameters;
    }
}
