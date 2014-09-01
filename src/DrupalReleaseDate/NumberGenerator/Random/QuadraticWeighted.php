<?php
namespace DrupalReleaseDate\NumberGenerator\Random;

class QuadraticWeighted extends PolynomialWeighted
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
