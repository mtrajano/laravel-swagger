<?php

namespace Mtrajano\LaravelSwagger\Tests\Parameters;

use Mtrajano\LaravelSwagger\Tests\TestCase;
use Mtrajano\LaravelSwagger\Parameters\QueryParameterGenerator;

class QueryParameterGeneratorTest extends TestCase
{
    public function testRequiredParameter()
    {
        $queryParameters = $this->getQueryParameters([
            'id' => 'integer|required',
        ]);

        $this->assertArraySubset([
            'in' => 'query',
            'type' => 'integer',
            'name' => 'id',
            'required' => true,
        ], $queryParameters[0]);
    }

    public function testRulesAsArray()
    {
        $queryParameters = $this->getQueryParameters([
            'id' => ['integer', 'required'],
        ]);

        $this->assertArraySubset([
            'in' => 'query',
            'type' => 'integer',
            'name' => 'id',
            'required' => true,
        ], $queryParameters[0]);
    }

    public function testOptionalParameter()
    {
        $queryParameters = $this->getQueryParameters([
            'email' => 'email',
        ]);

        $this->assertArraySubset([
            'name' => 'email',
            'type' => 'string',
            'required' => false,
        ], $queryParameters[0]);
    }

    public function testEnumInQuery()
    {
        $queryParameters = $this->getQueryParameters([
            'account_type' => 'integer|in:1,2|in_array:foo',
        ]);

        $this->assertArraySubset([
            'name' => 'account_type',
            'type' => 'integer',
            'enum' => [1,2],
        ], $queryParameters[0]);
    }

    private function getQueryParameters(array $rules)
    {
        return (new QueryParameterGenerator('get', '/', $rules))->getParameters();
    }
}