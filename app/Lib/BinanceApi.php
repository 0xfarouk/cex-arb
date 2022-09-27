<?php

namespace App\Lib;

use GuzzleHttp\Client;

class BinanceApi
{
    const SIDE_BUY = 'BUY';
    const SIDE_SELL = 'SELL';
    const PSIDE_LONG = 'LONG';
    const PSIDE_SHORT = 'SHORT';

    protected $apiKey;
    protected $apiSecret;

    protected $base = 'https://fapi.binance.com/fapi/';

    protected static $base2 = 'https://api.binance.com/api/v1/';

    protected $symbolInfo = [];

    public function __construct($apiKey, $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public static function exchangeInfo()
    {
        $resp = self::apiRequestSpot('GET', 'exchangeInfo');

        return array_column($resp['symbols'], 'symbol');

    }

    public function symbolInfo($symbol)
    {
        $resp = $this->apiRequest('GET', 'v1/exchangeInfo');

        if (empty($symbolInfo)) {
            foreach ($resp['symbols'] as $responseSymbol) {
                $this->symbolInfo[$responseSymbol['symbol']] = $responseSymbol;
            }
        }

        return $this->symbolInfo[$symbol];
    }

    /**
     * @param string $symbol
     * @param $quantity
     * @param null $price
     *
     * @return array
     *
     * [
     *     "orderId" => 543907109
     *     "symbol" => "ALPHAUSDT"
     *     "status" => "NEW"
     *     "clientOrderId" => "Qmv78ZY8s0nslrgfrYGc0e"
     *     "price" => "1.83600"
     *     "avgPrice" => "0.00000"
     *     "origQty" => "5"
     *     "executedQty" => "0"
     *     "cumQty" => "0"
     *     "cumQuote" => "0"
     *     "timeInForce" => "GTC"
     *     "type" => "LIMIT"
     *     "reduceOnly" => false
     *     "closePosition" => false
     *     "side" => "BUY"
     *     "positionSide" => "BOTH"
     *     "stopPrice" => "0"
     *     "workingType" => "CONTRACT_PRICE"
     *     "priceProtect" => false
     *     "origType" => "LIMIT"
     *     "updateTime" => 1616951398263
     * ]
     *
     */
    public function limitBuy(string $symbol, $quantity, $price = null)
    {
        $symbolInfo = $this->symbolInfo($symbol);
        $pricePrecision = $symbolInfo['pricePrecision'];

        // Add 2% to the price to buy instantly
        $price = (float) $price + (2 * $price / 100);
        $price = round($price, $pricePrecision);

        $params = [
            'symbol' => $symbol,
            'side' => 'BUY',
            'type' => 'LIMIT',
            'quantity' => $quantity,
            'price' => $price,
        ];

        return $this->apiRequest('POST', 'v1/order', $params, true);
    }

    public function limitSell(string $symbol, $quantity, $price)
    {
        $symbolInfo = $this->symbolInfo($symbol);
        $pricePrecision = $symbolInfo['pricePrecision'];

        // Reduce price by 2% to sell instantly
        $price = (float) $price - (2 * $price / 100);
        $price = round($price, $pricePrecision);

        $params = [
            'symbol' => $symbol,
            'side' => 'SELL',
            'type' => 'LIMIT',
            'quantity' => $quantity,
            'price' => $price
        ];

        return $this->apiRequest('POST', 'v1/order', $params, true);
    }

    public function closePosition($symbol)
    {
        $position = $this->getPosition($symbol);

        $quantity = $position['positionAmt'];
        $currentPrice = $position['markPrice'];

        $resp = $quantity > 0
            ? $resp = $this->limitSell($symbol, $quantity, $currentPrice)
            : $resp = $this->limitBuy($symbol, $quantity, $currentPrice);

        return [
            'orderResponse' => $resp,
            'closedPosition' => $position
        ];
    }

    /**
     * @return array
     *
     * [
     *     "dualSidePosition" => false,
     * ]
     */
    public function getPositionMode()
    {
        return $this->apiRequest('GET', 'v1/positionSide/dual');
    }

    /**
     *
     *
     * @param null $symbol
     *
     * @return mixed
     */
    public function openOrders($symbol = null)
    {
        $params = [];

        if ($symbol !== null) {
            $params['symbol'] = $symbol;
        }

        return $this->apiRequest('GET', 'v1/openOrders', $params);
    }

    public function allOrders($symbol, $limit = 10)
    {
        $params = [
            'symbol' => $symbol,
            'limit' => $limit
        ];

        return $this->apiRequest('GET', 'v1/allOrders', $params);
    }

    /**
     * @param $symbol
     *
     * @return array
     *
     * [
     *     "symbol" => "ALPHAUSDT"
     *     "positionAmt" => "5"
     *     "entryPrice" => "1.81401"
     *     "markPrice" => "1.81386000"
     *     "unRealizedProfit" => "-0.00075000"
     *     "liquidationPrice" => "0"
     *     "leverage" => "20"
     *     "maxNotionalValue" => "25000"
     *     "marginType" => "cross"
     *     "isolatedMargin" => "0.00000000"
     *     "isAutoAddMargin" => "false"
     *     "positionSide" => "BOTH"
     *     "notional" => "9.06930000"
     *     "isolatedWallet" => "0"
     * ]
     */
    public function getPosition($symbol)
    {
        $params = [
            'symbol' => $symbol
        ];

        // If position side BOTH there will only be 1 array el
        $resp = $this->apiRequest('GET', 'v2/positionRisk', $params);

        return $resp[0] ?? [];
    }

    public function timestamp()
    {
        return number_format(microtime(true) * 1000, 0, '.', ''); // TRY  (int) (time() * 1000)
    }

    public function apiRequest($method, $uri, $params = [])
    {
        $defaultParams = [
            'timeInForce' => 'GTC',
            'recWindows' => 5000,
            'timestamp' => $this->timestamp()
        ];

        $params = array_merge($defaultParams, $params);

        // Append signature to the request params
        $params['signature'] = $this->sign($params);

        $responseContent = (new Client())->request($method, $this->base . $uri, [
            'headers' => ['X-MBX-APIKEY' => $this->apiKey],
            'query' => $params,
        ])->getBody()->getContents();

        return json_decode($responseContent, true);
    }

    public static function apiRequestSpot($method, $uri)
    {
        $responseContent = (new Client())->request($method, static::$base2 . $uri)->getBody()->getContents();

        return json_decode($responseContent, true);
    }


    protected function sign($params)
    {
        return hash_hmac('sha256', http_build_query($params), $this->apiSecret);
    }
}
