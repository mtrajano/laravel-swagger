<?php

namespace Mtrajano\LaravelSwagger\Traits;

use Exception;

trait HasAppends
{
    /**
     * Get appends.
     *
     * @return array
     * @throws Exception
     */
    public function getAppends(): array
    {
        if (!property_exists($this, 'appends')) {
            throw new Exception('The class that use HasAppend must have the property $appends');
        }

        return $this->appends;
    }
}