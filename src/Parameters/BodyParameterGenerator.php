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
            $paramRules = $this->splitRules($rule);
            $enums = $this->getEnumValues($paramRules);

            if ($this->isParamRequired($paramRules)) {
                $required[] = $param;
            }

            $propObj = [
                'type' => $this->getParamType($paramRules)
            ];

            if (!empty($enums)) {
                $propObj['enum'] = $enums;
            }

            $properties[$param] = $propObj;
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