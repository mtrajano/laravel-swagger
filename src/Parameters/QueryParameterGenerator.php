<?php

namespace Mtrajano\LaravelSwagger\Parameters;

class QueryParameterGenerator extends ParameterGenerator
{
    public function getParameters()
    {
        $params = [];

        foreach  ($this->rules as $param => $rule) {
            $paramRules = $this->splitRules($rule);

            $params[] = [
                'in' => $this->getParamLocation(),
                'name' => $param,
                'type' => $this->getParamType($paramRules),
                'required' => $this->isParamRequired($paramRules),
                'description' => '',
            ];
        }

        return $params;
    }

    public function getParamLocation()
    {
        return 'query';
    }
}