<?php
namespace DrupalReleaseDate\Random;

class QuadraticWeightedRandom extends PolynomialWeightedRandom
{
    public function __construct($min, $max, $a = 1, $b = 0, $c = 1)
    {
        $coefficients = array(
            2 => $a,
            1 => $b,
            0 => $c,
        );

        parent::__construct($min, $max, $coefficients);
    }
}
