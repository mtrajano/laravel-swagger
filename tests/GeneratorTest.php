<?php

namespace Mtrajano\LaravelSwagger\Tests;

use Mtrajano\LaravelSwagger\Generator;

class GeneratorTest extends TestCase
{
    protected $config;

    protected $generator;

    public function setUp() : void
    {
        parent::setUp();

        $this->generator = new Generator(
            $this->config = config('laravel-swagger')
        );
    }

    public function testRequiredBaseInfo()
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

    public function testRequiredBaseInfoData()
    {
        $config = array_merge($this->config, [
            'title' => 'My awesome site!',
            'description' => 'This is my awesome site, please enjoy it',
            'appVersion' => '1.0.0',
            'host' => 'https://example.com',
            'basePath' => '/api',
            'schemes' => [
                'https',
            ],
            'consumes' => [
                'application/json',
            ],
            'produces' => [
                'application/json',
            ],
        ]);

        $docs = (new Generator($config))->generate();

        $this->assertEquals('2.0', $docs['swagger']);
        $this->assertEquals('My awesome site!', $docs['info']['title']);
        $this->assertEquals('This is my awesome site, please enjoy it', $docs['info']['description']);
        $this->assertEquals('1.0.0', $docs['info']['version']);
        $this->assertEquals('https://example.com', $docs['host']);
        $this->assertEquals('/api', $docs['basePath']);
        $this->assertEquals(['https'], $docs['schemes']);
        $this->assertEquals(['application/json'], $docs['consumes']);
        $this->assertEquals(['application/json'], $docs['produces']);
    }

    /**
     * @depends testRequiredBaseInfo
     */
    public function testHasPaths($docs)
    {
        $this->assertEquals([
            '/users',
            '/users/{id}',
            '/users/details',
            '/users/ping',
            '/api',
            '/api/store',
        ], array_keys($docs['paths']));

        return $docs['paths'];
    }

    /**
     * @depends testHasPaths
     */
    public function testPathMethods($paths)
    {
        $this->assertArrayHasKey('get', $paths['/users']);
        $this->assertArrayNotHasKey('head', $paths['/users']);
        $this->assertArrayHasKey('post', $paths['/users']);

        $this->assertArrayHasKey('get', $paths['/users/{id}']);

        $this->assertArrayHasKey('get', $paths['/users/details']);
    }

    /**
     * @depends testHasPaths
     */
    public function testRouteData($paths)
    {

        $expectedPostDescription = <<<EOD
Data is validated [see description here](https://example.com) so no bad data can be passed.
Please read the documentation for more information
EOD;

        $this->assertArrayHasKey('summary', $paths['/users']['get']);
        $this->assertArrayHasKey('description', $paths['/users']['get']);
        $this->assertArrayHasKey('responses', $paths['/users']['get']);
        $this->assertArrayHasKey('deprecated', $paths['/users']['get']);
        $this->assertArrayNotHasKey('parameters', $paths['/users']['get']);

        $this->assertEquals('Get a list of of users in the application', $paths['/users']['get']['summary']);
        $this->assertEquals(false, $paths['/users']['get']['deprecated']);
        $this->assertEquals('', $paths['/users']['get']['description']);

        $this->assertEquals('Store a new user in the application', $paths['/users']['post']['summary']);
        $this->assertEquals(true, $paths['/users']['post']['deprecated']);
        $this->assertEquals($expectedPostDescription, $paths['/users']['post']['description']);

        $this->assertEquals('', $paths['/users/{id}']['get']['summary']);
        $this->assertEquals(false, $paths['/users/{id}']['get']['deprecated']);
        $this->assertEquals('', $paths['/users/{id}']['get']['description']);

        $this->assertEquals('', $paths['/users/details']['get']['summary']);
        $this->assertEquals(true, $paths['/users/details']['get']['deprecated']);
        $this->assertEquals('', $paths['/users/details']['get']['description']);
    }

    public function testOverwriteIgnoreMethods()
    {
        $this->config['ignoredMethods'] = [];

        $docs = (new Generator($this->config))->generate();

        $this->assertArrayHasKey('head', $docs['paths']['/users']);
    }

    public function testParseDocBlockFalse()
    {
        $this->config['parseDocBlock'] = false;

        $docs = (new Generator($this->config))->generate();

        $this->assertEquals('', $docs['paths']['/users']['post']['summary']);
        $this->assertEquals(false, $docs['paths']['/users']['post']['deprecated']);
        $this->assertEquals('', $docs['paths']['/users']['post']['description']);
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

    /**
     * @param string|null $routeFilter
     * @param array $expectedRoutes
     *
     * @dataProvider filtersRoutesProvider
     */
    public function testFiltersRoutes($routeFilter, $expectedRoutes)
    {
        $this->generator = new Generator(
            $this->config = config('laravel-swagger'),
            $routeFilter
        );

        $docs = $this->generator->generate();

        $this->assertEquals($expectedRoutes, array_keys($docs['paths']));
    }

    /**
     * @return array
     */
    public function filtersRoutesProvider()
    {
        return [
            'No Filter' => [null, ['/users', '/users/{id}', '/users/details', '/users/ping', '/api', '/api/store']],
            '/api Filter' => ['/api', ['/api', '/api/store']],
            '/=nonexistant Filter' => ['/nonexistant', []],
        ];
    }
}
