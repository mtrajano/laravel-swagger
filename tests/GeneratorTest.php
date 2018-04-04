<?php

namespace Mtrajano\LaravelSwagger\Tests;

use Mtrajano\LaravelSwagger\Generator;

class GeneratorTest extends TestCase
{
    protected $config;

    protected $generator;

    public function setUp()
    {
        parent::setUp();

        $this->generator = new Generator(
            $this->config = config('laravel-swagger')
        );
    }

    public function testBaseInfo()
    {
        $docs = $this->generator->generate();

        $this->assertArrayHasKey('swagger', $docs);
        $this->assertArrayHasKey('info', $docs);
        $this->assertArrayHasKey('title', $docs['info']);
        $this->assertArrayHasKey('description', $docs['info']);
        $this->assertArrayHasKey('version', $docs['info']);
        $this->assertArrayHasKey('host', $docs);
        $this->assertArrayHasKey('basePath', $docs);
        $this->assertArrayHasKey('paths', $docs);

        return $docs;
    }

    /**
     * @depends testBaseInfo
     */
    public function testHasPaths($docs)
    {
        $this->assertEquals([
            '/users',
            '/users/{id}',
        ], array_keys($docs['paths']));

        return $docs['paths'];
    }

    /**
     * @depends testHasPaths
     */
    public function testPathData($paths)
    {
        $this->assertArrayHasKey('get', $paths['/users']);
        $this->assertArrayHasKey('post', $paths['/users']);

        $this->assertArrayHasKey('description', $paths['/users']['get']);
        $this->assertArrayHasKey('responses', $paths['/users']['get']);
        $this->assertArrayNotHasKey('parameters', $paths['/users']['get']);

        $this->assertArrayHasKey('description', $paths['/users']['post']);
        $this->assertArrayHasKey('responses', $paths['/users']['post']);
        $this->assertArrayHasKey('parameters', $paths['/users']['post']);
    }

    public function testOptionalData()
    {
        $optionalData = [
            'schemes' => [
                'http',
                'https',
            ],

            'consumes' => [
                'application/json',
            ],

            'produces' => [
                'application/json',
            ],
        ];

        $config = array_merge($this->config, $optionalData);

        $docs = (new Generator($config))->generate();

        $this->assertArrayHasKey('schemes', $docs);
        $this->assertArrayHasKey('consumes', $docs);
        $this->assertArrayHasKey('produces', $docs);

        $this->assertContains('http', $docs['schemes']);
        $this->assertContains('https', $docs['schemes']);
        $this->assertContains('application/json', $docs['consumes']);
        $this->assertContains('application/json', $docs['produces']);
    }
}