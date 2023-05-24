<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Process\Pool;

class Manager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:manager';

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
         //= ['binance', 'bitget'];

        $exchangeIds = [
            'binance',
            'bitget',
            'bitmart',
            'bitopro',
            'bitrue',
            'bitstamp',
            'bitvavo',
            'btcex',
            'coinbasepro',
            'coinex',
            'cryptocom',
            'deribit',
            'gemini',
            'hitbtc',
            'hollaex',
            'mexc',
            'ndax',
            'okx',
            'phemex',
            'upbit'
        ];

        $pool = Process::pool(function (Pool $pool) use ($exchangeIds) {
            $delayExecution = 0;

            foreach ($exchangeIds as $exchangeId) {
                $pool
                    ->as($exchangeId)
                    ->forever()
                    //->input('binance')
                    //->command('php artisan app:poll-orderbook ' . $exchangeId)
                    ->command(sprintf('php artisan app:poll-orderbook %s --delay=%s', $exchangeId, $delayExecution+=5));
            }
        })->start(function (string $type, string $output, string $key) {
            Log::debug(sprintf('Manager::handle | POOL OUTPUT -> Type: %s | Output: %s | Key: %s', $type, $output, $key));
        });

        $results = $pool->wait();

        return $results['binance']->output();

//        $process = Process::path(base_path('/'))
//            ->forever()
//            ->start('php artisan app:dummy-process', function (string $type, string $output) {
//                //Log::info($type);
//                //Log::info($output);
//                //$this->info($type);
//                //$this->warn($output);
//            });
//
//        dump($process->wait());

        Log::info('After async init process');
    }
}
