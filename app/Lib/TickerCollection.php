<?php

namespace App\Lib;

class TickerCollection
{
    protected $tickers = [];

    public function addOrUpdateTicker(Ticker $ticker)
    {
        $pair = $ticker->from . '_' . $ticker->to;

        $existingTicker = $this->tickers[$pair] ?? null;

        // This ticker dont exist, add it
        if (!$existingTicker) {
            $this->tickers[$pair] = $ticker;

            return;
        }
        //
        // Ticker exists, check if we need to update
        elseif ($existingTicker->price !== $ticker->price) {
            $existingTicker->price = (float) $ticker->price;
            // Emit updated
        }

        dump(count($this->tickers));

        if (count($this->tickers) > 89) {
            $this->arb();
        }
    }

    public function arb()
    {
        echo 'ARB StaRT';

        $data = ['binance' => []];
        foreach ($this->tickers as $pair => $ticker) {
            $data['binance'][$pair] = $ticker->price;
        }

        try {
            $arb = new Arbitrage($data);

            $arbGraph = $arb->getArbGraph();
            $arb->imageFromGraph($arbGraph, 'arb-g-x-');

            dd(Arbitrage::multiplyEdges($arbGraph));

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
