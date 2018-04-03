<?php

namespace Mtrajano\LaravelSwagger\Parameters;

class QueryParameterGenerator implements ParameterGenerator
{
    use Concerns\GeneratesFromRules;

    protected $rules;

    public function __construct($rules)
    {
        $this->rules = $rules;
    }

    public function getParameters()
    {
        $params = [];

        foreach  ($this->rules as $param => $rule) {
            $paramRules = $this->splitRules($rule);
            $enums = $this->getEnumValues($paramRules);

            $paramObj = [
                'in' => $this->getParamLocation(),
                'name' => $param,
                'type' => $this->getParamType($paramRules),
                'required' => $this->isParamRequired($paramRules),
                'description' => '',
            ];

            if (!empty($enums)) {
                $paramObj['enum'] = $enums;
            }

            $params[] = $paramObj;
        }

        return $params;
    }

    public function getParamLocation()
    {
        return 'query';
    }
}