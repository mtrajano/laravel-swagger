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
        $arrayTypes = [];

        foreach  ($this->rules as $param => $rule) {
            $paramRules = $this->splitRules($rule);
            $enums = $this->getEnumValues($paramRules);
            $type = $this->getParamType($paramRules);

            if ($this->isArrayParameter($param)) {
                $arrayKey = $this->getArrayKey($param);
                $arrayTypes[$arrayKey] = $type;
                continue;
            }

            $paramObj = [
                'in' => $this->getParamLocation(),
                'name' => $param,
                'type' => $type,
                'required' => $this->isParamRequired($paramRules),
                'description' => '',
            ];

            if (!empty($enums)) {
                $paramObj['enum'] = $enums;
            }

            if ($type === 'array') {
                $paramObj['items'] = ['type' => 'string'];
            }

            $params[$param] = $paramObj;
        }

        $params = $this->addArrayTypes($params, $arrayTypes);

        return array_values($params);
    }

    protected function addArrayTypes($params, $arrayTypes)
    {
        foreach ($arrayTypes as $arrayKey => $type) {
            if (!isset($params[$arrayKey])) {
                $params[$arrayKey] = [
                    'in' => $this->getParamLocation(),
                    'name' => $arrayKey,
                    'type' => 'array',
                    'required' => false,
                    'description' => '',
                    'items' => [
                        'type' => $type,
                    ],
                ];
            } else {
                $params[$arrayKey]['type'] = 'array';
                $params[$arrayKey]['items']['type'] = $type;
            }
        }

        return $params;
    }

    public function getParamLocation()
    {
        return 'query';
    }
}