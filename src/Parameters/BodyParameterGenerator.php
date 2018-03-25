<?php

namespace Mtrajano\LaravelSwagger\Parameters;

class BodyParameterGenerator extends ParameterGenerator
{
    public function getParameters()
    {
        $required = [];
        $properties = [];

        $params = [
            'in' => $this->getParamLocation(),
            'name' => 'body',
            'description' => '',
            'schema' => [
                'type' => 'object',
            ],
        ];

        foreach  ($this->rules as $param => $rule) {
            $paramRules = explode('|', $rule);

            if ($this->isParamRequired($paramRules)) {
                $required[] = $param;
            }

            $properties[$param] = [
                'type' => $this->getParamType($paramRules)
            ];
        }

        if (!empty($required)) {
            $params['schema']['required'] = $required;
        }

        $params['schema']['properties'] = $properties;

        return [$params];
    }

    public function getParamLocation()
    {
        return 'body';
    }
}