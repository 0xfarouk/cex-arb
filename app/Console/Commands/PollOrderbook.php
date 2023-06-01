<?php

namespace App\Console\Commands;

use App\Models\Orderbook as AppOrderbook;
use Illuminate\Support\Facades\Log;
use function \React\Async\coroutine;
use Illuminate\Console\Command;

class PollOrderbook extends Command
{
    protected $symbols = [];

    protected $exceptions = [
        'bitmart' => [
            'LEO' // not standard leo coin
        ]
    ];

    protected $exchangeId;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:poll-orderbook {exchange} {--bootdelay=5} {--coin=*}';

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
        $desiredCoins = $this->option('coin') ?? ['BTC', 'ETH'];
        $exchangeId = $this->argument('exchange');
        $this->exchangeId = $exchangeId;

        $bootDelay = $this->option('bootdelay');
        foreach (range(0, $bootDelay) as $i) {
            Log::info('PollOrderbook::handle | SLEEPING ' . $exchangeId . ': ' . $bootDelay - $i);
            sleep(1);
        }

        $exchangeClass = '\\ccxt\\pro\\' . $exchangeId;

        Log::info('PollOrderbook::handle | Loading ' . $exchangeId);
        $this->load();
        Log::info('PollOrderbook::handle | Loaded ' . $exchangeId);

        $exchange = new $exchangeClass(['enableRateLimit' => true]);
        $symbols = $this->pickSymbols($desiredCoins, $this->symbols);

        foreach ($symbols as $symbol) {
            coroutine($this->loop(), $exchange, $symbol);
        }

        Log::info('PollOrderbook::handle | EXIT: ' . $exchangeId);
    }

    public function loop() {
        return function ($exchange, $symbol) {
            Log::info('PollOrderbook::handle | Watch: ' . $exchange->id . ': ' . $symbol);

            while (true) {
                $orderbook = yield $exchange->watch_order_book($symbol, 5);

                $record = AppOrderbook::where(['exchange' => $exchange->id, 'symbol' => $symbol])->first();
                if (!$record) {
                    $record = new AppOrderbook;
                    $record->exchange = $exchange->id;
                    $record->symbol = $symbol;
                }

                $record->order_book = json_encode($orderbook);
                $record->save();
            }
        };
    }

    /**
     * @param array $desiredCoins (Example: ['ETH', 'BTC'])
     * @param array $availableSymbols (Example: ['ETH/BTC', 'ETH/BTC:213', 'SOL/USDT'])
     *
     * @return void
     */
    public function pickSymbols(array $desiredCoins, array $availableSymbols): array
    {
        $finalSymbols = [];

        foreach ($availableSymbols as $availableSymbol) {
            // Skip the strange ones
            if (str_contains($availableSymbol, ':')) {
                continue;
            }

            [$from, $to] = explode('/', $availableSymbol);

            // If exchange->coin is in exception, skip it
            if (
                isset($this->exceptions[$this->exchangeId])
                && (
                    in_array($from, $this->exceptions[$this->exchangeId])
                    || in_array($to, $this->exceptions[$this->exchangeId])
                )) {

                Log::debug('PollOrderbook::pickSymbols | SKIPPING: ' . $this->exchangeId . ': ' . $from . '/' . $to);
                continue;
            }

            if (in_array($from, $desiredCoins) && in_array($to, $desiredCoins)) {
                $finalSymbols[] = $from . '/' . $to;
                $finalSymbols[] = $to . '/' . $from;
            }
        }

        return $finalSymbols;
    }

    public function load()
    {
        $exchangeId = $this->argument('exchange');
        $exchangeClass = '\\ccxt\\' . $exchangeId;
        $exchange = new $exchangeClass([
            'enableRateLimit' => true,
        ]);

        $exchange->load_markets();
        $symbols = $exchange->symbols;
        $this->symbols = $symbols;

//        $i = 0;
//        foreach ($symbols as $symbol) {
//            if ($i > 10) {
//                break;
//            }
//
//            $this->info($exchange->id . ': ' . $symbol);
//            $i++;
//        }
    }
}
