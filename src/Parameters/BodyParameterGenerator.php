<?php

namespace Mtrajano\LaravelSwagger\Parameters;

class BodyParameterGenerator implements ParameterGenerator
{
    use Concerns\GeneratesFromRules;

    protected $rules;

    public function __construct($rules)
    {
        $this->rules = $rules;
    }

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

        foreach ($this->rules as $param => $rule) {
            $paramRules = $this->splitRules($rule);
            $nameTokens = explode('.', $param);

            $this->addToProperties($properties, $nameTokens, $paramRules);

            if ($this->isParamRequired($paramRules)) {
                $required[] = $param;
            }
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

    protected function addToProperties(&$properties, $nameTokens, $rules)
    {
        if (empty($nameTokens)) {
            return;
        }

        $name = array_shift($nameTokens);

        if (!empty($nameTokens)) {
            $type = $this->getNestedParamType($nameTokens);
        } else {
            $type = $this->getParamType($rules);
        }

        $propObj = [
            'type' => $type
        ];

        if ($enums = $this->getEnumValues($rules)) {
            $propObj['enum'] = $enums;
        }

        if ($name === '*') {
            $name = 0;
        }

        $properties[$name] = $propObj;

        if ($type === 'array') {
            $properties[$name]['items'] = [];
            $this->addToProperties($properties[$name]['items'], $nameTokens, $rules);
        }
    }

    protected function getNestedParamType($nameTokens)
    {
        if (current($nameTokens) === '*') {
            return 'array';
        } else {
            return 'object';
        }
    }
}