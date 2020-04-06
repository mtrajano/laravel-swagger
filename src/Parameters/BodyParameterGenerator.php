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

    public function getParameters(): array
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

    public function getParamLocation(): string
    {
        return 'body';
    }

    protected function addToProperties(array &$properties, array $nameTokens, array $rules): void
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

        if ($name === '*') {
            $name = 0;
        }

        if (!isset($properties[$name])) {
            $propObj = $this->getNewPropObj($type, $rules);

            $properties[$name] = $propObj;
        } else {
            //overwrite previous type in case it wasn't given before
            $properties[$name]['type'] = $type;
        }

        if ($type === 'array') {
            if (!isset($properties[$name]['items'])) {
                $properties[$name]['items'] = [];
            }

            $this->addToProperties($properties[$name]['items'], $nameTokens, $rules);
        } elseif ($type === 'object') {
            if (!isset($properties[$name]['properties'])) {
                $properties[$name]['properties'] = [];
            }

            $this->addToProperties($properties[$name]['properties'], $nameTokens, $rules);
        }
    }

    protected function getNestedParamType($nameTokens): string
    {
        if (current($nameTokens) === '*') {
            return 'array';
        } else {
            return 'object';
        }
    }

    protected function getNewPropObj($type, $rules): array
    {
        $propObj = [
            'type' => $type,
        ];

        if ($enums = $this->getEnumValues($rules)) {
            $propObj['enum'] = $enums;
        }

        if ($type === 'array') {
            $propObj['items'] = [];
        } elseif ($type === 'object') {
            $propObj['properties'] = [];
        }

        return $propObj;
    }
}
