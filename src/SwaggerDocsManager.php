<?php

namespace Mtrajano\LaravelSwagger;

class SwaggerDocsManager
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
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

        $version = $filteredVersions[0];

        $version['title'] = $this->config['title'];
        $version['description'] = $this->config['description'];

        return $version;
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
            $this->config['versions'],
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
        return $this->config['versions'];
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
}