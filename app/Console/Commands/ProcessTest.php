<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ProcessTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $result = Process::path(base_path('/'))
            ->timeout(5)
            ->start('php artisan app:dummy-process');

        Log::info('After async init process');

        return 0;

        $this->info($result->output());

        return 0;

        dump($result->successful());
        dump($result->failed());
        dump($result->exitCode());
        dump($result->output());
        dump($result->errorOutput());
    }
}
