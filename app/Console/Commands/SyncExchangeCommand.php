<?php

namespace App\Console\Commands;

use Amp\Loop;
use Amp\Websocket\Client\Connection;
use Amp\Websocket\Message;
use App\Lib\BinanceApi;
use App\Lib\TickerCollection;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use function Amp\Websocket\Client\connect;

class SyncExchangeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:exchange {exchange}';

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
        $exchange = $this->argument('exchange');
        $method = 'sync' . ucfirst($exchange);
        $this->{$method}();

        return 0;
    }

    public function syncKucoin()
    {
        $httpClient = new Client;
        $resp = $httpClient->post('https://api.kucoin.com/api/v1/bullet-public');
        $respDataRaw = $resp->getBody()->getContents();
        $respData = json_decode($respDataRaw, true);
        $apiToken = $respData['data']['token'];

        $rand = md5(microtime());
        $newStream = "wss://ws-api.kucoin.com/endpoint?token={$apiToken}&[connectId={$rand}]";
        Loop::run(function () use ($newStream) {
            /** @var Connection $connection */
            $connection = yield connect($newStream);

            yield $connection->send(json_encode([
                'type' => 'subscribe',
                'topic' => '/spotMarket/level2Depth5:BTC-USDT,ETH-USDT',
                'response' => true
            ]));

            while ($message = yield $connection->receive()) {
                /** @var Message $message */
                $payload = yield $message->buffer();
                $payload = (json_decode($payload, true));

                dump($payload);

                //$tickers->addOrUpdateTicker($ticker);

                sleep(1);
            }
        });
    }

    public function syncBinance()
    {
        $coins = $this->getCoins();
        $newStream = $this->makeStreamUrl($coins);

        $tickers = new TickerCollection;

        Loop::run(function () use ($newStream, $tickers) {
            /** @var Connection $connection */
            $connection = yield connect($newStream);

            while ($message = yield $connection->receive()) {
                /** @var Message $message */
                $payload = yield $message->buffer();
                $payload = (json_decode($payload, true));

                if ($payload['type'] === 'message') {
                }
                dump($payload);

                sleep(1);
                continue;

                $ticker = Ticker::fromBinancePayload($payload);
                $tickers->addOrUpdateTicker($ticker);
            }
        });
    }

    private function getConsoleArg($index)
    {
        if (isset($argc) && $argc[$index]) {
            return $argc[$index];
        } else {
            throw new \Exception("argc and argv disabled or no arg at index: [$index]");
        }
    }

    private function makeStreamUrl($tickers)
    {
        $stream = 'wss://stream.binance.com:9443/stream?streams=';
        $ss = [];
        $i = 0;
        foreach ($tickers as $ticker) {
            // E.g. btcusdt@depth5@1000ms
            $ts = explode('_', $ticker);
            $t = strtolower($ts[0] . $ts[1]);
            $ss[] = $t . '@depth5@1000ms';
            $i++;
        }

        return $stream . implode('/', $ss);
    }

    private function getCoins()
    {
        $symbols = BinanceApi::exchangeInfo();
        $someSimbols = [];

        $filters = [
            '1INCH',
            'AVAX',
            'BNB',
            'BTC',
            'EOS',
            'ETC',
            'ETH',
            'GMT',
            'IOTA',
            'KNC',
            'LINK',
            'LOKA',
            'LTC',
            'MANA',
            'NEXO',
            'NULS',
            'OMG',
            'QTUM',
            'RUNE',
            'RVN',
            'SAND',
            'SOL',
            'USDC',
            'USDT',
            'WAVES',
            'XLM',
            'ZRX',
        ];

        foreach ($symbols as $symbol) {
            $filters2 = $filters;

            foreach ($filters as $filter) {
                foreach ($filters2 as $filter2) {
                    if ($symbol === $filter . $filter2) {
                        $someSimbols[] = $filter . '_' . $filter2;
                    } elseif ($symbol === $filter2 . $filter) {
                        $someSimbols[] = $filter2 . '_' . $filter;
                    }
                }
            }
        }

        $x = array_unique($someSimbols);

        sort($x);

        return $x;
    }
}
