<?php

namespace Mtrajano\LaravelSwagger\Tests;

use Mtrajano\LaravelSwagger\SwaggerDocsManager;
use PHPUnit\Framework\TestCase as BaseTestCase;

class SwaggerDocsManagerTest extends BaseTestCase
{
    protected $config = [];

    /**
     * @var array
     */
    private $defaultVersion;

    /**
     * @var array
     */
    private $versionTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultVersion = [
            'appVersion' => '1.0.0',
            'host' => env('APP_URL'),
            'basePath' => '/',
            'schemes' => [],
            'consumes' => [],
            'produces' => [],
            'ignoredMethods' => [
                'head',
            ],
            'ignoredRoutes' => [
                'laravel-swagger.docs',
                'laravel-swagger.asset'
            ],
            'parseDocBlock' => true,
            'parseSecurity' => true,
            'authFlow' => 'accessCode',
            'file_path' => env('SWAGGER_FILE_PATH', 'swagger-1-0-0.json'),
            'title' => '',
            'description' => '',
        ];

        $this->versionTwo = [
            'appVersion' => '2.0.0',
            'host' => env('APP_URL'),
            'basePath' => '/',
            'schemes' => [],
            'consumes' => [],
            'produces' => [],
            'ignoredMethods' => [
                'head',
            ],
            'ignoredRoutes' => [
                'laravel-swagger.docs',
                'laravel-swagger.asset'
            ],
            'parseDocBlock' => true,
            'parseSecurity' => true,
            'authFlow' => 'accessCode',
            'file_path' => 'swagger-2-0-0.json',
            'title' => '',
            'description' => '',
        ];

        $this->config = [
            'defaultVersion' => '1.0.0',
            'title' => '',
            'description' => '',
            'route' => [
                'path' => '/docs/{version?}',
                'name' => 'laravel-swagger.docs',
                'middleware' => [],
            ],
            'versions' => [
                $this->defaultVersion,
                $this->versionTwo
            ],
        ];
    }

    public function testGetDefaultVersion()
    {
        $swaggerDocs = new SwaggerDocsManager($this->config);

        $this->assertEquals(
            $this->defaultVersion,
            $swaggerDocs->getDefaultVersionConfig()
        );
    }

    public function testFindVersion()
    {
        $swaggerDocs = new SwaggerDocsManager($this->config);

        $this->assertEquals(
            $this->versionTwo,
            $swaggerDocs->findVersionConfig('2.0.0')
        );
    }
}