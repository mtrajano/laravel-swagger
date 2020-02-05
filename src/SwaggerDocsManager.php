<?php

namespace Mtrajano\LaravelSwagger;

use Closure;
use RuntimeException;

class SwaggerDocsManager
{
    /**
     * @var array
     */
    private $config;

    /**
     * A callable to handle with swagger file name generation.
     *
     * @var Closure|null
     */
    private static $fileNameGenerator;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Set a personalized file name generator. The $generator must be a Closure
     * that can receive two parameters: a string with the $version and another
     * string with the $format. The $generator must return a string with the
     * generated file name. If no one generator was defined, the default
     * generator from method "getDefaultFileNameGenerator" will be used.
     *
     * @param Closure $generator
     */
    public static function setFileNameGenerator(Closure $generator)
    {
        self::$fileNameGenerator = $generator;
    }

    /**
     * Get the default version config.
     *
     * @return array
     */
    public function getDefaultVersionConfig(): array
    {
        return $this->findVersionConfig($this->getDefaultVersionKey());
    }

    /**
     * Find config version by version key.
     *
     * @param string $version
     * @return array
     */
    public function findVersionConfig(string $version): array
    {
        $filteredVersions = $this->filterVersionsConfigs($version);
        if (empty($filteredVersions)) {
            return [];
        }

        return $filteredVersions[0];
    }

    /**
     * Filter versions by version key.
     *
     * @param string $version
     * @return array
     */
    public function filterVersionsConfigs(string $version): array
    {
        return array_values(array_filter(
            $this->getAllVersionsConfigs(),
            function ($config) use ($version) {
                return $config['appVersion'] == $version;
            }
        ));
    }

    /**
     * Get all versions configured.
     *
     * @return array
     */
    public function getAllVersionsConfigs(): array
    {
        return array_map(function (array $version) {
            return $this->getFilledWithGlobalConfigs($version);
        }, $this->config['versions']);
    }

    /**
     * Get the default version key.
     *
     * @return mixed
     */
    public function getDefaultVersionKey()
    {
        return $this->config['defaultVersion'];
    }

    /**
     * @return array
     */
    public function getRoutesWithVersions(): array
    {
        $versions = array_column($this->getAllVersionsConfigs(), 'appVersion');

        $routesWithVersions = [];
        foreach ($versions as $key => $version) {
            $route = route(
                config('laravel-swagger.route.name'),
                $version,
                false
            );
            $routesWithVersions[$route] = $version;
        }

        return $routesWithVersions;
    }

    /**
     * Returns the swagger docs file path from version.
     *
     * @param string $version
     * @param bool $absolute  Define if should return the absolute url.
     * @return string
     */
    public function getFilePathByVersion(
        string $version,
        bool $absolute = true
    ): string {
        $versionConfig = $this->findVersionConfig($version);
        $format = $versionConfig['file_format'] ?? 'json';

        $fileName = $this->generateSwaggerFileName($version, $format);

        $url = "/{$fileName}";
        if ($absolute) {
            $url = config('app.url').$url;
        }

        return $url;
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
        $generator = self::$fileNameGenerator ?? $this->getDefaultFileNameGenerator();

        $fileName = $generator($version, $format);

        if (!is_string($fileName) || !is_valid_file_name($fileName)) {
            throw new RuntimeException("The filename is invalid.");
        }

        return $fileName;
    }

    /**
     * The default swagger file name generator.
     *
     * @return Closure
     */
    private function getDefaultFileNameGenerator(): Closure
    {
        return function (string $version, string $format = 'json') {
            return "swagger-{$version}.{$format}";
        };
    }

    /**
     * Fill an array version with global configs.
     *
     * @param $version
     * @return array
     */
    private function getFilledWithGlobalConfigs(array $version): array
    {
        $version['title'] = $this->config['title'];
        $version['description'] = $this->config['description'];
        $version['host'] = $this->config['host'];
        $version['parseDocBlock'] = $this->config['parseDocBlock'];
        $version['parseSecurity'] = $this->config['parseSecurity'];

        return $version;
    }
}