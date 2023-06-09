<?php

namespace App\Console\Commands;

use App\Models\Arb;
use Fhaculty\Graph\Edge\Directed as DirectedEdge;
use Fhaculty\Graph\Graph;
use Illuminate\Console\Command;

class TraderArbTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trader:arb-test';

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
        /** @var Graph $arb */
        $arb = unserialize(Arb::find(21)->object);

        /** @var DirectedEdge $edge */
        foreach ($arb->getEdges() as $edge) {
            dump([
                'from' => $edge->getVertexStart()->getId(),
                'to' => $edge->getVertexEnd()->getId(),
                'exchange' => $edge->getAttribute('exchange'),
                'weight' => $edge->getWeight()
            ]);
        }
    }
}
