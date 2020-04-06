<?php

namespace Mtrajano\LaravelSwagger;

use Closure;
use RuntimeException;

class SwaggerDocsManager
{
    /**
     * @var array
     */
    private $_config;

    /**
     * A callable to handle with swagger file name generation.
     *
     * @var Closure|null
     */
    private static $_fileNameGenerator;

    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * Set a personalized file name generator. The $generator must be a Closure
     * that can receive two parameters: a string with the $version and another
     * string with the $format. The $generator must return a string with the
     * generated file name. If no one generator was defined, the default
     * generator from method "_getDefaultFileNameGenerator" will be used.
     */
    public static function setFileNameGenerator(Closure $generator)
    {
        self::$_fileNameGenerator = $generator;
    }

    /**
     * Get the default version config (latest version).
     */
    public function getLastestVersionConfig(): array
    {
        return $this->findVersionConfig($this->getLatestVersion());
    }

    /**
     * Get the last version key.
     */
    public function getLatestVersion(): string
    {
        return collect($this->_config['versions'])
            ->pluck('appVersion')
            ->sort()
            ->last();
    }

    /**
     * Find config version by version key.
     */
    public function findVersionConfig(string $version): array
    {
        $filteredVersions = $this->_filterVersionsConfigs($version);

        return $filteredVersions[0] ?? [];
    }

    /**
     * Get all versions configured.
     */
    public function getAllVersionConfigs(): array
    {
        return array_map(function (array $version) {
            return $this->_getFilledWithGlobalConfigs($version);
        }, $this->_config['versions']);
    }

    public function getVersionRoutePaths(): array
    {
        $versions = array_column($this->getAllVersionConfigs(), 'appVersion');

        $routesWithVersions = [];
        foreach ($versions as $version) {
            $route = route(
                config('laravel-swagger.route.name'),
                $version,
                false
            );

            $routesWithVersions[$version] = $route;
        }

        return $routesWithVersions;
    }

    /**
     * Returns the swagger docs file path from version.
     */
    public function getSwaggerFileUrl(string $version): string
    {
        $versionConfig = $this->findVersionConfig($version);
        $format = $versionConfig['file_format'] ?? 'json';

        $fileName = $this->generateSwaggerFileName($version, $format);

        return sprintf("%s/%s", config('app.url'), $fileName);
    }

    /**
     * Generate swagger file name using the generator defined on class.
     *
     * @param string $version
     * @param string $format
     * @return string
     */
    public function generateSwaggerFileName(string $version, string $format): string
    {
        $generator = self::$_fileNameGenerator ?? $this->_getDefaultFileNameGenerator();

        $fileName = $generator($version, $format);

        if (!is_string($fileName) || !is_valid_file_name($fileName)) {
            throw new RuntimeException('The filename is invalid.');
        }

        return $fileName;
    }

    /**
     * Filter versions by version key.
     */
    private function _filterVersionsConfigs(string $version): array
    {
        return array_values(array_filter(
            $this->getAllVersionConfigs(),
            function ($config) use ($version) {
                return $config['appVersion'] == $version;
            }
        ));
    }

    /**
     * The default swagger file name generator.
     */
    private function _getDefaultFileNameGenerator(): Closure
    {
        return function (string $version, string $format = 'json') {
            return "swagger-{$version}.{$format}";
        };
    }

    /**
     * Fill an array version with global configs.
     */
    private function _getFilledWithGlobalConfigs(array $version): array
    {
        $version['title'] = $this->_config['title'];
        $version['description'] = $this->_config['description'];
        $version['schemes'] = $this->_config['schemes'];
        $version['parseDocBlock'] = $this->_config['parseDocBlock'];
        $version['parseSecurity'] = $this->_config['parseSecurity'];
        $version['generateExampleData'] = $this->_config['generateExampleData'];
        $version['parseModelRelationships'] = $this->_config['parseModelRelationships'];

        return $version;
    }
}
