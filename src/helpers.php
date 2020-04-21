<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

if (!function_exists('strip_optional_char')) {
    function strip_optional_char($uri)
    {
        return str_replace('?', '', $uri);
    }
}

if (!function_exists('get_all_model_relations')) {
    /**
     * Identify all relationships for a given model.
     *
     * @param string $heritage A flag that indicates whether parent and/or child
     *                         relationships should be included
     * @throws ReflectionException
     */
    function get_all_model_relations(Model $model, $heritage = 'all'): array
    {
        $types = ['children' => 'Has', 'parents' => 'Belongs', 'all' => ''];
        $filter = $types[$heritage] ?? $types['all'];
        $reflectionClass = new ReflectionClass($model);

        // The method must be public
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        $methods = collect($methods)
            ->map(function (ReflectionMethod $method) use ($filter, $model) {
                if (!$method->getReturnType() || !$method->getReturnType()->getName()) {
                    return;
                }

                $methodName = $method->getName();
                $returnTypeClass = $method->getReturnType()->getName();

                // Must return a Relation child. This is why we only want to do
                // this once
                if (is_subclass_of($returnTypeClass, Relation::class)) {
                    $relation = $method->invoke($model);

                    // If relation is of the desired heritage
                    if (!$filter || strpos($returnTypeClass, $filter) === 0) {
                        return [
                            'method' => $methodName,
                            'related_model' => $relation->getRelated(),
                            'relation' => get_class($relation),
                        ];
                    }
                }
            })
            // Remove elements reflecting methods that do not have the desired
            // return type
            ->filter()
            ->toArray();

        return $methods;
    }
}

if (!function_exists('laravel_swagger_ui_dist_path')) {
    /**
     * Returns swagger-ui composer dist path.
     *
     * @param null $asset
     *
     * @return string
     */
    function laravel_swagger_ui_dist_path($asset = null)
    {
        $allowedFiles = [
            'favicon-16x16.png',
            'favicon-32x32.png',
            'oauth2-redirect.html',
            'swagger-ui-bundle.js',
            'swagger-ui-bundle.js.map',
            'swagger-ui-standalone-preset.js',
            'swagger-ui-standalone-preset.js.map',
            'swagger-ui.css',
            'swagger-ui.css.map',
            'swagger-ui.js',
            'swagger-ui.js.map',
        ];

        $path = base_path('vendor/swagger-api/swagger-ui/dist/');

        if (!$asset) {
            return realpath($path);
        }

        if (!in_array($asset, $allowedFiles)) {
            throw new RuntimeException(sprintf('(%s) - this asset is not allowed', $asset));
        }

        return realpath($path . $asset);
    }
}

if (!function_exists('laravel_swagger_asset')) {
    /**
     * Returns asset from swagger-ui composer package.
     *
     * @param $asset string
     *
     * @return string
     * @throws RuntimeException
     */
    function laravel_swagger_asset($asset)
    {
        $file = laravel_swagger_ui_dist_path($asset);

        if (!file_exists($file)) {
            throw new RuntimeException(sprintf('Requested asset file (%s) does not exists', $asset));
        }

        return route('laravel-swagger.asset', $asset) . '?v=' . md5_file($file);
    }
}

if (!function_exists('is_valid_file_name')) {
    /**
     * Determine if a value is a valid file name.
     *
     * @param string $file
     * @return bool
     */
    function is_valid_file_name(string $file)
    {
        return preg_match('/^([-_.\w]+)$/', $file) > 0;
    }
}
