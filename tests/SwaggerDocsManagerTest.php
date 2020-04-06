<?php

namespace Mtrajano\LaravelSwagger\Tests;

use Mtrajano\LaravelSwagger\SwaggerDocsManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SwaggerDocsManagerTest extends TestCase
{
    private $_config;
    private $_globalConfig;
    private $_defaultVersion;
    private $_versionOne;
    private $_docsManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_globalConfig = [
            'title' => '',
            'description' => '',
            'schemes' => [],
            'parseDocBlock' => true,
            'parseSecurity' => true,
            'generateExampleData' => true,
            'parseModelRelationships' => true,
        ];

        $this->_versionOne = [
            'appVersion' => '1.0.0',
            'host' => env('APP_URL'),
            'basePath' => '/',
            'consumes' => [],
            'produces' => [],
            'ignoredMethods' => [
                'head',
            ],
            'ignoredRoutes' => [
                'laravel-swagger.docs',
                'laravel-swagger.asset',
            ],
            'authFlow' => 'accessCode',
            'file_format' => 'json',
        ];

        $this->_defaultVersion = [
            'appVersion' => '2.0.0',
            'host' => env('APP_URL'),
            'basePath' => '/v2',
            'consumes' => [],
            'produces' => [],
            'ignoredMethods' => [
                'head',
            ],
            'ignoredRoutes' => [
                'laravel-swagger.docs',
                'laravel-swagger.asset',
            ],
            'authFlow' => 'accessCode',
            'file_format' => 'json',
        ];

        $this->_config = [
            'schemes' => [],
            'defaultVersion' => '1.0.0',
            'title' => '',
            'description' => '',
            'parseDocBlock' => true,
            'parseSecurity' => true,
            'generateExampleData' => true,
            'parseModelRelationships' => true,
            'route' => [
                'path' => '/docs/{version?}',
                'name' => 'laravel-swagger.docs',
                'middleware' => [],
            ],
            'versions' => [
                $this->_versionOne,
                $this->_defaultVersion,
            ],
        ];

        $this->_docsManager = new SwaggerDocsManager($this->_config);
    }

    public function testGetLastestVersionConfig(): void
    {
        $this->assertEquals(
            $this->_withGlobals($this->_defaultVersion),
            $this->_docsManager->getLastestVersionConfig()
        );
    }

    public function testFindVersionConfig(): void
    {
        $this->assertEquals(
            $this->_withGlobals($this->_versionOne),
            $this->_docsManager->findVersionConfig('1.0.0')
        );
    }

    public function testGetLatestVersion(): void
    {
        $this->assertEquals('2.0.0', $this->_docsManager->getLatestVersion());
    }

    public function testFindVersionConfigWithNotExistentConfig(): void
    {
        $this->assertEmpty($this->_docsManager->findVersionConfig('3.0.0'));
    }

    public function testGenerateSwaggerFileNameWithDefaultGenerator(): void
    {
        $fileName = $this->_docsManager->generateSwaggerFileName('1.0.0', 'json');

        $this->assertEquals('swagger-1.0.0.json', $fileName);
    }

    public function testGetAllVersionConfigs(): void
    {
        $bothVersions = [$this->_withGlobals($this->_versionOne), $this->_withGlobals($this->_defaultVersion)];

        $this->assertEquals($bothVersions, $this->_docsManager->getAllVersionConfigs());
    }

    public function testChangeFileNameGenerator(): void
    {
        SwaggerDocsManager::setFileNameGenerator(function (string $version, string $format) {
            $version = str_replace('.', '_', $version);

            return "my-swagger-file-{$version}.{$format}";
        });

        $fileName = $this->_docsManager->generateSwaggerFileName('1.0.0', 'json');

        $this->assertEquals('my-swagger-file-1_0_0.json', $fileName);
    }

    public function provideInvalidFileNames(): array
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
     */
    public function testChangeFileNameGeneratorReturningInvalidFileName($invalidFileName): void {
        $this->expectException(RuntimeException::class);

        SwaggerDocsManager::setFileNameGenerator(
            function (string $version, string $format) use ($invalidFileName) {
                return $invalidFileName;
            }
        );

        $swaggerDocs = new SwaggerDocsManager($this->_config);
        $swaggerDocs->generateSwaggerFileName('1.0.0', 'json');
    }

    private function _withGlobals(array $versionConfig): array
    {
        return array_merge($this->_globalConfig, $versionConfig);
    }
}
