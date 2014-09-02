<?php
namespace DrupalReleaseDate\NumberGenerator\Random;

use DrupalReleaseDate\NumberGenerator\AbstractWeighted as ParentAbstractWeighted;

abstract class AbstractWeighted extends ParentAbstractWeighted
{
    protected function generateWeight()
    {
        $maxWeight = $this->weightsArray[$this->max];

        if ($this->integerWeights) {
            $rand = mt_rand(1, $maxWeight);
        } else {
            $rand = (mt_rand() / mt_getrandmax()) * $maxWeight;
        }
        return $rand;
    }
}
