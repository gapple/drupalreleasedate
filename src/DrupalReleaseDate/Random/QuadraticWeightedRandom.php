<?php
namespace DrupalReleaseDate\Random;

class QuadraticWeightedRandom extends WeightedRandom {

    public function calculateWeight($value) {
        return pow($value - $this->min + 1, 2);
    }
}
