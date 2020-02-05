<?php

namespace Mtrajano\LaravelSwagger\Tests;

use Mtrajano\LaravelSwagger\SwaggerDocsManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SwaggerDocsManagerTest extends TestCase
{
    protected $config = [];

    /**
     * @var array
     */
    private $defaultVersion;

    /**
     * @var array
     */
    private $versionOne;

    protected function setUp(): void
    {
        parent::setUp();

        $this->versionOne = [
            'appVersion' => '1.0.0',
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
            'authFlow' => 'accessCode',
            'file_path' => env('SWAGGER_FILE_PATH', 'swagger-1.0.0.json'),
            'title' => '',
            'description' => '',
            'host' => env('APP_URL'),
            'parseDocBlock' => true,
            'parseSecurity' => true,
        ];

        $this->defaultVersion = [
            'appVersion' => '2.0.0',
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
            'file_path' => 'swagger-2.0.0.json',
            'title' => '',
            'description' => '',
            'host' => env('APP_URL'),
        ];

        $this->config = [
            'host' => env('APP_URL'),
            'defaultVersion' => '1.0.0',
            'title' => '',
            'description' => '',
            'parseDocBlock' => true,
            'parseSecurity' => true,
            'route' => [
                'path' => '/docs/{version?}',
                'name' => 'laravel-swagger.docs',
                'middleware' => [],
            ],
            'versions' => [
                $this->defaultVersion,
                $this->versionOne
            ],
        ];
    }

    public function testGetDefaultVersion()
    {
        $swaggerDocs = new SwaggerDocsManager($this->config);

        $this->assertEquals(
            $this->defaultVersion,
            $swaggerDocs->getLastVersionConfig()
        );
    }

    public function testFindVersion()
    {
        $swaggerDocs = new SwaggerDocsManager($this->config);

        $this->assertEquals(
            $this->versionOne,
            $swaggerDocs->findVersionConfig('1.0.0')
        );
    }

    public function testFindVersionConfigWithNotExistentConfig()
    {
        $swaggerDocs = new SwaggerDocsManager($this->config);

        $this->assertEmpty($swaggerDocs->findVersionConfig('3.0.0'));
    }

    public function testGenerateSwaggerFileNameWithDefaultGenerator()
    {
        $swaggerDocs = new SwaggerDocsManager($this->config);

        $fileName = $swaggerDocs->generateSwaggerFileName('1.0.0', 'json');

        $this->assertEquals('swagger-1.0.0.json', $fileName);
    }

    public function testChangeFileNameGenerator()
    {
        SwaggerDocsManager::setFileNameGenerator(function (string $version, string $format) {
            $version = str_replace('.', '_', $version);

            return "my-swagger-file-{$version}.{$format}";
        });

        $swaggerDocs = new SwaggerDocsManager($this->config);

        $fileName = $swaggerDocs->generateSwaggerFileName('1.0.0', 'json');

        $this->assertEquals('my-swagger-file-1_0_0.json', $fileName);
    }

    public function provideInvalidFileNames()
    {
        return [
            [''],
            [[]],
            [0],
            ['swagger file name'],
        ];
    }

    /**
     * @dataProvider provideInvalidFileNames
     * @param $invalidFileName
     */
    public function testChangeFileNameGeneratorReturningInvalidFileName(
        $invalidFileName
    ) {
        $this->expectException(RuntimeException::class);

        SwaggerDocsManager::setFileNameGenerator(
            function (string $version, string $format) use ($invalidFileName) {
                return $invalidFileName;
            }
        );

        $swaggerDocs = new SwaggerDocsManager($this->config);
        $swaggerDocs->generateSwaggerFileName('1.0.0', 'json');
    }
}