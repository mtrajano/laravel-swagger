<?php

namespace Mtrajano\LaravelSwagger\Parameters;

use Illuminate\Support\Str;

class PathParameterGenerator implements ParameterGenerator
{
    protected $uri;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    public function getParameters(): array
    {
        $params = [];
        $pathVariables = $this->getAllVariablesFromUri();

        foreach ($pathVariables as $variable) {
            $params[] = [
                'in' => $this->getParamLocation(),
                'name' => strip_optional_char($variable),
                'type' => 'string', //best guess for a variable in the path
                'required' => $this->isPathVariableRequired($variable),
                'description' => '',
            ];
        }

        return $params;
    }

    private function getAllVariablesFromUri()
    {
        preg_match_all('/{(\w+\??)}/', $this->uri, $pathVariables);

        return $pathVariables[1];
    }

    public function getParamLocation(): string
    {
        return 'path';
    }

    private function isPathVariableRequired($pathVariable): bool
    {
        return !Str::contains($pathVariable, '?');
    }
}
