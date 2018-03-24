<?php

namespace Mtrajano\LaravelSwagger;

use Illuminate\Console\Command;

class GenerateSwaggerDoc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-swagger:generate';

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

        $docs = (new Generator($config))->generate();

        echo json_encode($docs, JSON_PRETTY_PRINT) . "\n";
    }
}