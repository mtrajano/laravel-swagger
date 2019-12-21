<?php

namespace Mtrajano\LaravelSwagger;

class FormatterManager
{
    private $docs;

    private $formatter;

    public function __construct($docs)
    {
        $this->docs = $docs;

        $this->formatter = new Formatters\JsonFormatter($docs);
    }

    public function setFormat($format)
    {
        $format = strtolower($format);

        $this->formatter = $this->getFormatter($format);

        return $this;
    }

    protected function getFormatter($format)
    {
        switch ($format) {
            case 'json':
                return new Formatters\JsonFormatter($this->docs);
            case 'yaml':
                return new Formatters\YamlFormatter($this->docs);
            default:
                throw new LaravelSwaggerException('Invalid format passed');
        }
    }

    public function format()
    {
        return $this->formatter->format();
    }
}
