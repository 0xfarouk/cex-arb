<?php

namespace App\Console\Commands;

use App\Lib\Arbitrage;
use App\Models\Arb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use Fhaculty\Graph\Edge\Directed;
use Fhaculty\Graph\Vertex;

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

    protected $gain = 1;

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

                /**
                 * @var Directed $edge
                 * @var Vertex $vertex
                 */
//                foreach ($arbGraph->getVertices() as $vertex) {
////                    $edge = $fromVertex->createEdgeTo($toVertex);
////                    $edge->setWeight($price);
////                    $edge->setAttribute('exchange', $exchange);
//
//                    dump([
//                        'getVerticesEdgeTo' => $vertex->getVerticesEdgeTo(),
//                        'getVerticesEdgeFrom' => $vertex->getVerticesEdgeFrom(),
//                        'getEdgesFrom' => $vertex->getEdgesFrom($vertex),
//                        'getEdgesOut' => $vertex->getEdgesOut()
//                    ]);
//                }

                $gain = Arbitrage::multiplyEdges($arbGraph);

                if ($this->gain === $gain) {
                    $this->line('=');
                    continue;
                }

                $this->gain = $gain;

                if ($gain > 1.01) {
                    $arb = new Arb();
                    $arb->gain = $gain;
                    $arb->object = serialize($arbGraph);
                    $arb->save();

                    $arbitrage->imageFromGraph($arbGraph, "arb-g-{$arb->id}_", "id: {$arb->id} gain: $gain");

                    $this->info('gain: ' . $gain);
                } else {
                    $this->line('.');
                }

            } catch (\Exception $e) {
                $this->warn($e->getMessage());
            }

            //sleep(1);
        }
    }
}
