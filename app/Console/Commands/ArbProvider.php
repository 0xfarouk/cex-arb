<?php

namespace App\Console\Commands;

use App\Lib\Arbitrage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArbProvider extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:arb';

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
        $query = "
            SELECT
                symbol,
                (SELECT CONCAT(exchange, ':', ask) as tmp1 FROM order_book_peaks WHERE symbol = tmp.symbol ORDER BY ask LIMIT 1) lask,
                (SELECT CONCAT(exchange, ':', bid) as tmp1 FROM order_book_peaks WHERE symbol = tmp.symbol ORDER BY bid DESC LIMIT 1) hbid
            FROM (SELECT DISTINCT symbol FROM order_book_peaks) tmp
        ";

        while (true) {
            $res = DB::select($query);

            $priceData = [];
            foreach ($res as $row) {
                [$from, $to] = explode('/', $row->symbol);

                //
                [$exchangeAsk, $lAsk] = explode(':', $row->lask);
                [$exchangeBid, $hBid] = explode(':', $row->hbid);

                if (is_numeric($hBid)) {
                    $priceData[$exchangeBid][$from . '_' . $to] = (float) $hBid;
                }

                if (is_numeric($lAsk)) {
                    $priceData[$exchangeAsk][$to . '_' . $from] = 1 / (float) $lAsk;
                }
            }

            $arbitrage = new Arbitrage($priceData, storage_path('app/'));

            try {
                $arbGraph = $arbitrage->getArbGraph();
                $gain = Arbitrage::multiplyEdges($arbGraph);

                if ($gain > 1.1) {
                    $this->info('gain: ' . $gain);
                } else {
                    $this->line('.');
                }

                if ($gain > 2) {
                    //$arbitrage->imageFromGraph($arbGraph, 'arb-g-');
                }
            } catch (\Exception $e) {
                $this->warn($e->getMessage());
            }

            //sleep(1);
        }
    }
}
