<?php

namespace Mtrajano\LaravelSwagger\Console;

use Illuminate\Console\Command;
use Mtrajano\LaravelSwagger\FormatterManager;
use Mtrajano\LaravelSwagger\Generator;
use Mtrajano\LaravelSwagger\SwaggerDocsManager;

class GenerateSwaggerDocCommand extends Command
{
    /**
     * @var SwaggerDocsManager
     */
    private $swaggerDocsManager;

    public function __construct(SwaggerDocsManager $swaggerDocsManager)
    {
        parent::__construct();

        $this->swaggerDocsManager = $swaggerDocsManager;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-swagger:generate
                            {--format=json : The format of the output, current options are json and yaml}
                            {--o|output= : Output file to write the contents to, defaults to stdout}
                            {--all-versions : Generate swagger docs for all versions}
                            {--api-version= : The version of the swagger docs. Uses defaultVersion by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generates a swagger documentation file for this application';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Mtrajano\LaravelSwagger\LaravelSwaggerException
     */
    public function handle()
    {
        // TODO: Only accept file parameter when will generate just one version docs.
        //       If will generate more than one version don't make sense generate by name;
        //       The first versions will be overwrote by last version.
        $file = $this->option('output') ?: null;

        $versions = $this->getVersionsConfigToGenerate();

        foreach ($versions as $versionConfig) {
            $versionConfig['title'] = config('laravel-swagger.title');
            $versionConfig['description'] = config('laravel-swagger.description');
            $versionConfig['host'] = config('laravel-swagger.host');

            $docs = (new Generator($versionConfig, $versionConfig['basePath']))->generate();

            $format = $this->option('format');

            $formattedDocs = (new FormatterManager($docs))
                ->setFormat($format)
                ->format();

            if ($file) {
                file_put_contents($file, $formattedDocs);
                continue;
            }

            $fileName = $this->swaggerDocsManager->generateSwaggerFileName(
                $versionConfig['appVersion'],
                $format
            );

            file_put_contents(public_path($fileName), $formattedDocs);
        }
    }

    /**
     * Get versions config to generate swagger docs.
     *
     * @return array
     */
    private function getVersionsConfigToGenerate(): array
    {
        $apiVersion = $this->option('api-version')
            ?? $this->swaggerDocsManager->getDefaultVersionKey();

        return $this->option('all-versions')
            ? $this->swaggerDocsManager->getAllVersionsConfigs()
            : $this->swaggerDocsManager->filterVersionsConfigs($apiVersion);
    }
}
