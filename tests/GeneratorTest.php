<?php

namespace Mtrajano\LaravelSwagger\Tests;

use Mtrajano\LaravelSwagger\Generator;
use Mtrajano\LaravelSwagger\LaravelSwaggerException;

class GeneratorTest extends TestCase
{
    protected $config;

    protected $generator;

    protected $endpoints = [
        '/users',
        '/users/{id}',
        '/users/details',
        '/users/ping',
        '/api',
        '/api/store',
        '/oauth/authorize',
        '/oauth/token',
        '/oauth/tokens',
        '/oauth/tokens/{token_id}',
        '/oauth/token/refresh',
        '/oauth/clients',
        '/oauth/clients/{client_id}',
        '/oauth/scopes',
        '/oauth/personal-access-tokens',
        '/oauth/personal-access-tokens/{token_id}',
    ];

    public function setUp(): void
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
        $docs = $this->getDocsWithNewConfig([
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

    public function testSecurityDefinitionsAccessCodeFlow()
    {
        $docs = $this->getDocsWithNewConfig([
            'authFlow' => 'accessCode',
        ]);

        $this->assertArrayHasKey('securityDefinitions', $docs);

        $securityDefinition = $docs['securityDefinitions']['OAuth2'];

        $this->assertEquals('oauth2', $securityDefinition['type']);
        $this->assertEquals('accessCode', $securityDefinition['flow']);
        $this->assertArrayHasKey('user-read', $securityDefinition['scopes']);
        $this->assertArrayHasKey('user-write', $securityDefinition['scopes']);
        $this->assertArrayHasKey('authorizationUrl', $securityDefinition);
        $this->assertArrayHasKey('tokenUrl', $securityDefinition);
    }

    public function testSecurityDefinitionsImplicitFlow()
    {
        $docs = $this->getDocsWithNewConfig([
            'authFlow' => 'implicit',
        ]);

        $securityDefinition = $docs['securityDefinitions']['OAuth2'];

        $this->assertEquals('oauth2', $securityDefinition['type']);
        $this->assertEquals('implicit', $securityDefinition['flow']);
        $this->assertArrayHasKey('authorizationUrl', $securityDefinition);
        $this->assertArrayNotHasKey('tokenUrl', $securityDefinition);
    }

    public function testSecurityDefinitionsPasswordFlow()
    {
        $docs = $this->getDocsWithNewConfig([
            'authFlow' => 'password',
        ]);

        $securityDefinition = $docs['securityDefinitions']['OAuth2'];

        $this->assertEquals('oauth2', $securityDefinition['type']);
        $this->assertEquals('password', $securityDefinition['flow']);
        $this->assertArrayNotHasKey('authorizationUrl', $securityDefinition);
        $this->assertArrayHasKey('tokenUrl', $securityDefinition);
    }

    public function testSecurityDefinitionsApplicationFlow()
    {
        $docs = $this->getDocsWithNewConfig([
            'authFlow' => 'application',
        ]);

        $securityDefinition = $docs['securityDefinitions']['OAuth2'];

        $this->assertEquals('oauth2', $securityDefinition['type']);
        $this->assertEquals('application', $securityDefinition['flow']);
        $this->assertArrayNotHasKey('authorizationUrl', $securityDefinition);
        $this->assertArrayHasKey('tokenUrl', $securityDefinition);
    }

    public function testNoParseSecurity()
    {
        $docs = $this->getDocsWithNewConfig([
            'parseSecurity' => false,
        ]);

        $this->assertArrayNotHasKey('securityDefinitions', $docs);
    }

    public function testInvalidFlowPassed()
    {
        $this->expectException(LaravelSwaggerException::class);

        $this->getDocsWithNewConfig([
            'authFlow' => 'invalidFlow',
        ]);
    }

    /**
     * @depends testRequiredBaseInfo
     */
    public function testHasPaths($docs)
    {
        $this->assertEquals($this->endpoints, array_keys($docs['paths']));

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
        $expectedPostDescription = <<<'EOD'
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

    /**
     * @depends testHasPaths
     */
    public function testRouteScopes($paths)
    {
        $this->assertEquals(['user-read'], $paths['/users']['get']['security'][Generator::SECURITY_DEFINITION_NAME]);
        $this->assertEquals(['user-write', 'user-read'], $paths['/users']['post']['security'][Generator::SECURITY_DEFINITION_NAME]);
    }

    public function testOverwriteIgnoreMethods()
    {
        $docs = $this->getDocsWithNewConfig(['ignoredMethods' => []]);

        $this->assertArrayHasKey('head', $docs['paths']['/users']);
    }

    public function testParseDocBlockFalse()
    {
        $docs = $this->getDocsWithNewConfig(['parseDocBlock' => false]);

        $this->assertEquals('', $docs['paths']['/users']['post']['summary']);
        $this->assertEquals(false, $docs['paths']['/users']['post']['deprecated']);
        $this->assertEquals('', $docs['paths']['/users']['post']['description']);
    }

    public function testOptionalData()
    {
        $docs = $this->getDocsWithNewConfig([
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
        ]);

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
            $this->config,
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
            'No Filter' => [null, $this->endpoints],
            '/api Filter' => ['/api', ['/api', '/api/store']],
            '/=nonexistant Filter' => ['/nonexistant', []],
        ];
    }

    private function getDocsWithNewConfig(array $config)
    {
        $config = array_merge($this->config, $config);

        return (new Generator($config))->generate();
    }
}
