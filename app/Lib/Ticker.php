<?php

namespace App\Lib;

class Ticker
{
    public $from;
    public $to;
    public $price;
    protected $coins = [
        '1inch',
        'avax',
        'bnb',
        'btc',
        'eos',
        'etc',
        'eth',
        'gmt',
        'iota',
        'knc',
        'link',
        'loka',
        'ltc',
        'mana',
        'nexo',
        'nuls',
        'omg',
        'qtum',
        'rune',
        'rvn',
        'sand',
        'sol',
        'usdc',
        'usdt',
        'waves',
        'xlm',
        'zrx',
    ];

//    public function __construct($possibleCoins)
//    {
//        $this->coins = array_map('strtolower', $possibleCoins);
//    }

    public static function fromBinancePayload($payload)
    {
        $ticker = new static;

        $stream = $payload['stream'];
        $streamParts = explode('@', $stream);

        $pair = $streamParts[0]; // e.g. ethbtc
        $pairs = $ticker->resolveTickerPair($pair);

        if (!isset($pairs[0]) || !isset($pairs[1])) {
            dump($pairs);
            dd($payload);
        }

        $ticker->from = $pairs[0];
        $ticker->to = $pairs[1];
        $ticker->price = (float) $payload['data']['asks'][0][0];

        return $ticker;
    }

    public static function fromKucoinPayload($payload) {

        $ticker = new static;

        $stream = $payload['stream'];
        $streamParts = explode(':', $stream);

        $pair = $streamParts[array_key_last($streamParts)]; // e.g. ethbtc

    }

    public function resolveTickerPair($pair)
    {
        $coins = $this->coins;

        $first = null;
        $second = null;

        foreach ($coins as $coin) {
            if (!$first && strpos($pair, $coin) === 0) {
                $first = $coin;
            }

            if (!$second && strpos($pair, $coin) === 3 || strpos($pair, $coin) === 4  || strpos($pair, $coin) === 5) {
                $second = $coin;
            }

            if ($first && $second) {
                return [$first, $second];
            }
        }
    }
}
