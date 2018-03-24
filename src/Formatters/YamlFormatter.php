<?php

namespace Mtrajano\LaravelSwagger\Formatters;

class YamlFormatter extends Formatter
{
    public function format()
    {
        if (!extension_loaded('yaml')) {
            throw new \Exception('YAML extension must be loaded to use the yaml output format');
        }

        return yaml_emit($this->docs);
    }
}