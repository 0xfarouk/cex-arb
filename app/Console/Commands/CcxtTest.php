<?php

namespace App\Console\Commands;

use App\Models\Orderbook as AppOrderBook;
use ccxt\pro\OrderBook;
use ccxt\pro\OrderBookSide;
use Illuminate\Console\Command;

use ccxt\pro\binance as Binance;

use Illuminate\Support\Facades\DB;
use function React\Async\async;
use function React\Async\coroutine;
use function React\Async\await;
use function React\Promise\all;

class CcxtTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ccxt-test';

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
        //dd(DB::table('order_book')->where('order_book->test', 'a')->first());
//        dd(AppOrderBook::where('order_book->test->0->a', 'b')->first());
//
//        $x = AppOrderBook::whereJson('order_book->test', "a")->first();
//
//        dd($x);

//        $x=AppOrderBook::updateOrCreate(
//            ['exchange' => 'binance', 'symbol' => 'BTC/USDT'],
//            ['order_book' => '{"a": "b"}']
//        );
//
        $exchange = new Binance([
            'enableRateLimit' => true,
        ]);

        //$symbols = ['BTC/USDT', 'ETH/USDT', 'ETH/BTC'];
        $symbols = ['BTC/USDT'];

        $loop = function ($exchange, $symbol) {
            while (true) {
                $orderbook = yield $exchange->watch_order_book($symbol, 10);
                $this->print_orderbook($orderbook, $symbol);
            }
        };

        foreach ($symbols as $symbol) {
            \React\Async\coroutine($loop, $exchange, $symbol);
        }
    }

    public function print_orderbook(OrderBook $orderbook, $symbol)
    {
        $orderbookJson = json_encode($orderbook);

        $this->info($orderbookJson);

        AppOrderBook::updateOrCreate(
            ['exchange' => 'binance', 'symbol' => 'BTC/USDT'],
            ['order_book' => $orderbook]
        );


//        $this->info(
//            $id  . ' ' . $symbol . ' ' .
//            count($orderbook['asks']) . ' asks ' . json_encode($orderbook['asks'][0])
//            . ' ' .
//            count($orderbook['bids']) . ' bids ' . json_encode($orderbook['bids'][0])
//        );
    }
}
