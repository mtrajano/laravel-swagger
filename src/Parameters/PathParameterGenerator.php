<?php

namespace Mtrajano\LaravelSwagger\Parameters;

class PathParameterGenerator extends ParameterGenerator
{
    public function getParameters()
    {
        $params = [];
        $pathVariables = $this->getAllVariablesFromUri();

        foreach  ($pathVariables as $variable) {
            $params[] = [
                'in' => $this->getParamLocation(),
                'name' => strip_optional_char($variable),
                'type' => 'integer', //best guess for a variable in the path
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

    public function getParamLocation()
    {
        return 'path';
    }

    private function isPathVariableRequired($pathVariable)
    {
        return !str_contains($pathVariable, '?');
    }
}