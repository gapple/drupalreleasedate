<?php
namespace DrupalReleaseDate\Random;

class LinearWeightedRandom extends WeightedRandom
{

    public function calculateWeight($value)
    {
        return $value - $this->min + 1;
    }
}
