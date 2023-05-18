<?php

namespace App\Console\Commands;

use App\Lib\Arbitrage;
use Illuminate\Console\Command;

class SyncExchangeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arb:example';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //$x = 0.0741;
        //$l = -log($x);
        //dump(['x' => $x]);
        //dump(["LOG of $x" => $l]);
        //dump(["EXP of $l" => exp($l)]); // CORRECT
        //die;

        $priceData = [
            'binance' => [
                'USD_EUR' => 0.741, 'USD_GBP' => 0.657, 'USD_CHF' => 1.061, 'USD_CAD' => 1.004,
                'EUR_USD' => 1.340, 'EUR_GBP' => 0.886, 'EUR_CHF' => 1.430, 'EUR_CAD' => 1.351,
                'GBP_USD' => 1.5, 'GBP_EUR' => 1.126, 'GBP_CHF' => 1.51, 'GBP_CAD' => 1.4,
                'CHF_USD' => 0.943, 'CHF_EUR' => 0.698, 'CHF_GBP' => 0.620, 'CHF_CAD' => 0.953,
                'CAD_USD' => 0.996, 'CAD_EUR' => 0.732, 'CAD_GBP' => 0.650, 'CAD_CHF' => 1.049
            ],

            'cex' => [
                'USD_EUR' => 0.74, 'USD_GBP' => 0.657, 'USD_CHF' => 1.061, 'USD_CAD' => 1.004,
                'EUR_USD' => 1.340, 'EUR_GBP' => 0.88, 'EUR_CHF' => 1.430, 'EUR_CAD' => 1.35,
                'GBP_USD' => 1.49, 'GBP_EUR' => 1.126, 'GBP_CHF' => 1.5, 'GBP_CAD' => 1.39,
                'CHF_USD' => 0.943, 'CHF_EUR' => 0.697, 'CHF_GBP' => 0.620, 'CHF_CAD' => 0.95,
                'CAD_USD' => 0.995, 'CAD_EUR' => 0.732, 'CAD_GBP' => 0.650, 'CAD_CHF' => 1.049
            ],

            'poloniex' => [
                'USD_EUR' => 0.7423, 'USD_GBP' => 0.671, 'USD_CHF' => 1.0588, 'USD_CAD' => 1.003,
                'EUR_USD' => 1.3401, 'EUR_GBP' => 0.889, 'EUR_CHF' => 1.431, 'EUR_CAD' => 1.349,
                'GBP_USD' => 1.477, 'GBP_EUR' => 1.125, 'GBP_CHF' => 1.511, 'GBP_CAD' => 1.38,
                'CHF_USD' => 0.9433, 'CHF_EUR' => 0.696, 'CHF_GBP' => 0.622, 'CHF_CAD' => 0.94,
                'CAD_USD' => 0.9945, 'CAD_EUR' => 0.731, 'CAD_GBP' => 0.649, 'CAD_CHF' => 1.047
            ],
        ];


        $arbitrage = new Arbitrage($priceData, storage_path('app/'));

        //$priceGraph = $arbitrage->getPriceGraph();
        //$arbitrage->imageFromGraph($priceGraph, 'price-g-');
        //
        //$priceGraphNeg = $arbitrage->getPriceGraphWithNegativeLogEdges();
        //$arbitrage->imageFromGraph($priceGraphNeg, 'price-g-neg-');
        //
        //$arbGrapNeg = $arbitrage->getArbGraphWithNegativeLogEdges();
        //$arbitrage->imageFromGraph($arbGrapNeg, 'arb-g-neg-');

        $arbGraph = $arbitrage->getArbGraph();
        $priceGraph = $arbitrage->getPriceGraph();

        $arbitrage->imageFromGraph($priceGraph, 'price-g-');
        $arbitrage->imageFromGraph($arbGraph, 'arb-g-');
        dump(Arbitrage::multiplyEdges($arbGraph));
    }
}
