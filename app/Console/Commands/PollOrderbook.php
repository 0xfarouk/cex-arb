<?php

namespace App\Console\Commands;

use App\Models\Orderbook as AppOrderbook;
use Illuminate\Support\Facades\Log;
use function \React\Async\coroutine;
use Illuminate\Console\Command;

class PollOrderbook extends Command
{
    protected $symbols = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:poll-orderbook {exchange} {--delay=5}';


    protected $desiredCoins = [
        'USDC',
        'USDT',
        'DAI',
        'TUSC',
        //
        'BTC',
        'ETH',
        'BNB',
        'SOL',
        'XRP',
        'ADA',
        'DOGE',
        'MATIC',
        'SOL',
        'TRX',
        'LTC',
        'DOT',
        'SHIB',
        'AVAX',
        'LEO',
        'LINK',
        'ATOM',
        'UNI',
        'OKB',
        'XMR',
        'ETC',
        'XLM',
        'TON',
        'BCH',
        'ICP',
        'FIL',
        'LDO',
        'HBAR',
        'APT',
        'CRO',
        'ARB',
        'NEAR',
        'VET',
        'APE',
        'QNT',
        'ALGO',
        'RNDR',
        'GRT',
    ];

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
        $exchangeId = $this->argument('exchange');

        $delay = $this->option('delay');
        foreach (range(0, $delay) as $i) {
            Log::info('PollOrderbook::handle | SLEEPING ' . $exchangeId . ': ' . $delay - $i);
            sleep(1);
        }

        $exchangeClass = '\\ccxt\\pro\\' . $exchangeId;

        Log::info('PollOrderbook::handle | Loading ' . $exchangeId);
        $this->load();
        Log::info('PollOrderbook::handle | Loaded ' . $exchangeId);

        $exchange = new $exchangeClass([
            'enableRateLimit' => true,
        ]);

        $symbols = $this->pickSymbols($this->desiredCoins, $this->symbols);

        $loop = function ($exchange, $symbol) {
            Log::info('PollOrderbook::handle | Watch: ' . $exchange->id . ': ' . $symbol);

            while (true) {
                $orderbook = yield $exchange->watch_order_book($symbol, 5);

                // TODO: orderbook can be object or non object (probably array)
                $orderbook = json_encode($orderbook);
//                if (is_object($orderbook)) {
//                    Log::debug('PollOrderbook::handle | Order book is object: ' . $exchange->id . ': ' . $symbol);
//                    $orderbook = json_encode($orderbook);
//                } else {
//                    Log::debug('PollOrderbook::handle | Order book is NOT object: ' . $exchange->id . ': ' . $symbol);
//                    $orderbook = json_encode($orderbook);
//                }

                $record = AppOrderbook::where(['exchange' => $exchange->id, 'symbol' => $symbol])->first();
                if (!$record) {
                    $record = new AppOrderbook;
                    $record->exchange = $exchange->id;
                    $record->symbol = $symbol;
                }

                $record->order_book = $orderbook;
                $record->save();
            }
        };

        foreach ($symbols as $symbol) {
            coroutine($loop, $exchange, $symbol);
        }

        Log::info('PollOrderbook::handle | EXIT: ' . $exchangeId);
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

            if (in_array($from, $desiredCoins) && in_array($to, $desiredCoins)) {
                $finalSymbols[] = $from . '/' . $to;
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
