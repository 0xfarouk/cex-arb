<?php

namespace App\Lib;

use Carbon\Carbon;
use Fhaculty\Graph\Edge\Directed as Edge;
use Fhaculty\Graph\Graph;
use Graphp\Algorithms\ShortestPath\MooreBellmanFord as BellmanFord;

class Arbitrage
{
    protected array $priceData = [];

    protected Graph $priceGraph;
    protected Graph $priceGraphWithNegativeLogEdges;
    protected Graph $arbGraphWithNegativeLogEdges;
    protected Graph $arbGraph;

    protected $imageDestiantion;

    public function __construct($rawPriceData, $imageDestination = '/var/tmp/')
    {
        $this->setPriceData($rawPriceData);

        $this->imageDestiantion = $imageDestination;
    }

    public function setPriceData($priceData)
    {
        if ($this->priceData === []) {
            $newPriceData = [];
            foreach ($priceData as $exchange => $rates) {
                foreach ($rates as $pair => $price) {
                    $newPriceData[$pair][$exchange] = $price;

                    if (!isset($newPriceData[$pair]['_MAX'])) {
                        $newPriceData[$pair]['_MAX'] = [
                            'exchange' => '',
                            'price' => 0,
                        ];
                    }

                    if ($newPriceData[$pair]['_MAX']['price'] < $price) {
                        $newPriceData[$pair]['_MAX']['price'] = $price;
                        $newPriceData[$pair]['_MAX']['exchange'] = $exchange;
                    }
                }
            }

            $this->priceData = $newPriceData;
        } else {
            throw new \Exception('Price data already set');
        }

        return $this;
    }

    public function getPriceGraph()
    {
        if (isset($this->priceGraph)) {
            return $this->priceGraph;
        }

        $graph = new Graph;

        foreach ($this->priceData as $pair => $priceInfo) {
            [$leftPair, $rightPair] = explode('_', $pair);
            $price = $priceInfo['_MAX']['price'];

            // Skip eg [USD_USD => 1]
            if ($leftPair === $rightPair || $price === 1) {
                continue;
            }

            $exchange = $priceInfo['_MAX']['exchange'];
            $from = $leftPair;
            $to = $rightPair;

            // Start vertex
            if ($graph->hasVertex($from)) {
                $fromVertex = $graph->getVertex($from);
            } else {
                $fromVertex = $graph->createVertex($from);
            }

            // End vertex
            if ($graph->hasVertex($to)) {
                $toVertex = $graph->getVertex($to);
            } else {
                $toVertex = $graph->createVertex($to);
            }

            $edge = $fromVertex->createEdgeTo($toVertex);
            $edge->setWeight($price);
            $edge->setAttribute('exchange', $exchange);
        }

        return $this->priceGraph = $graph;
    }

    public function getPriceGraphWithNegativeLogEdges()
    {
        if (isset($this->priceGraphWithNegativeLogEdges)) {
            return $this->priceGraphWithNegativeLogEdges;
        }

        $graph = $this->getPriceGraph()->createGraphClone();

        /** @var Edge $edge */
        foreach ($graph->getEdges()->getIterator() as $edge) {
            $edge->setWeight(-log($edge->getWeight()));
        }

        return $this->priceGraphWithNegativeLogEdges = $graph;
    }

    public function getArbGraphWithNegativeLogEdges()
    {
        if (isset($this->arbGraphWithNegativeLogEdges)) {
            return $this->arbGraphWithNegativeLogEdges;
        }

        // Start vertex could be any vertex in graph
        $startVertex = $this->getPriceGraphWithNegativeLogEdges()->getVertices()->getVertexFirst();
        $bellmanFord = new BellmanFord($startVertex);

        return $this->arbGraphWithNegativeLogEdges = $bellmanFord->getCycleNegative()->createGraph();
    }

    public function getArbGraph()
    {
        if (isset($this->arbGraph)) {
            return $this->arbGraph;
        }

        $graph = $this->getArbGraphWithNegativeLogEdges()->createGraphClone();

        /** @var Edge $edge */
        foreach ($graph->getEdges()->getIterator() as $edge) {
            $edge->setWeight(exp(-$edge->getWeight()));
        }

        return $this->arbGraph = $graph;
    }

    public function imageFromGraph(Graph $graph, $imagePrefix = '', $additionalText = null)
    {
        $graphViz = new GraphViz;
        $img = $graphViz->createImageFile($graph);

        $destination = $this->imageDestiantion . $imagePrefix . Carbon::now()->format('ymdHis') . '.png';
        copy($img, $destination);

        if ($additionalText !== null) {
            $this->addTextToImage($destination, $additionalText);
        }

        return $destination;
    }

    public function addTextToImage($imgPath, $text = '%placeholder%')
    {
        // Create Image From Existing File
        $image = imagecreatefrompng($imgPath);

        // Allocate A Color For The Text
        $textColor = imagecolorallocate($image, 0, 0, 0);

        // Print Text On Image
        imagettftext($image, 10, 0, 4, 14, $textColor, __DIR__ . '/roboto.ttf', $text);

        // Send Image to Browser
        imagepng($image, $imgPath);

        // Clear Memory
        imagedestroy($image);
    }

    public static function multiplyEdges(Graph $graph)
    {
        $result = 1;
        /** @var Edge $edge */
        foreach ($graph->getEdges()->getIterator() as $edge) {
            $result *= $edge->getWeight();
        }

        return $result;
    }
}
