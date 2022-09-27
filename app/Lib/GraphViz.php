<?php

namespace App\Lib;

use Fhaculty\Graph\Attribute\AttributeBagNamespaced;
use Fhaculty\Graph\Edge\Base as Edge;

class GraphViz extends \Graphp\GraphViz\GraphViz
{
    protected function getLayoutEdge(Edge $edge)
    {
        $bag = new AttributeBagNamespaced($edge, 'graphviz.');
        $layout = $bag->getAttributes();

        // use flow/capacity/weight as edge label
        $label = null;

        $flow = $edge->getFlow();
        $capacity = $edge->getCapacity();
        // flow is set
        if ($flow !== null) {
            // NULL capacity = infinite capacity
            $label = $flow . '/' . ($capacity === null ? '∞' : $capacity);
            // capacity set, but not flow (assume zero flow)
        } elseif ($capacity !== null) {
            $label = '0/' . $capacity;
        }

        $weight = $edge->getWeight();
        // weight is set
        if ($weight !== null) {
            if ($label === null) {
                $label = $weight;
            } else {
                $label .= '/' . $weight;
            }
        }

        if ($label !== null) {
            if (isset($layout['label'])) {
                $layout['label'] .= ' ' . $label;
            } else {
                $layout['label'] = $label;
            }
        }

        $attributes = [];
        foreach ($edge->getAttributeBag()->getAttributes() as $key => $value) {
            $attributes[] = $key . ':' . $value;
            $layout['label'] = $layout['label'] . ' (' . implode(',', $attributes) . ')';
        }

        return $layout;
    }
}
