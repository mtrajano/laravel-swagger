<?php

namespace Mtrajano\LaravelSwagger;

use Illuminate\Console\Command;

class GenerateSwaggerDoc extends Command
{
    protected $responseGenerator;

    public function __construct(Responses\ResponseGeneratorInterface $responseGenerator = null)
    {
        parent::__construct();
        $this->responseGenerator = $responseGenerator;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-swagger:generate
                            {--format=json : The format of the output, current options are json and yaml}
                            {--f|filter= : Filter to a specific route prefix, such as /api or /v2/api}
                            {--o|output= : Output file to write the contents to, defaults to stdout}';

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
     */
    public function handle()
    {
        $config = config('laravel-swagger');
        $filter = $this->option('filter') ?: null;
        $file = $this->option('output') ?: null;

        $docs = (new Generator($config, $filter, $this->responseGenerator))->generate();

        $formattedDocs = (new FormatterManager($docs))
            ->setFormat($this->option('format'))
            ->format();

        if ($file) {
            file_put_contents($file, $formattedDocs);
        } else {
            $this->line($formattedDocs);
        }
    }
}
