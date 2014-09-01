<?php
namespace DrupalReleaseDate\NumberGenerator\Random;

class LinearWeighted extends PolynomialWeighted
{
    public function __construct($min, $max, $slope = 1, $base = 1)
    {
        $coefficients = array(
            1 => $slope,
            0 => $base,
        );

        parent::__construct($min, $max, $coefficients);
    }
}
