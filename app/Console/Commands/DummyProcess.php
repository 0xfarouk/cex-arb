<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DummyProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dummy-process';

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
        $id = rand(1000, 9999);

        $i = 0;

        while(true) {
            if ($i >= 100) {
                break;
            }

            $randString = Str::random(6);
            Log::info("dummy output($id): " . $randString);

            $this->error('DummyProcess::handle | dummy ' . $randString);

            sleep(rand(1, 3));

            $i++;
        }
    }
}
