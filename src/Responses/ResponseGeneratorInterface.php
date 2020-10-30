<?php

namespace Mtrajano\LaravelSwagger\Responses;

interface ResponseGeneratorInterface
{
    /**
     * @param string $uri
     * @param string $method
     * @param ?\ReflectionMethod $actionInstance
     * @return array
     */
    public function getResponses($uri, $method, $actionInstance);
}
