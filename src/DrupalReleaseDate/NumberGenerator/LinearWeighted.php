<?php
namespace DrupalReleaseDate\NumberGenerator;

class LinearWeighted extends PolynomialWeighted
{
    public function __construct(NumberGeneratorInterface $weightGenerator, $min, $max, $slope = 1, $base = 1)
    {
        $coefficients = array(
            1 => $slope,
            0 => $base,
        );

        parent::__construct($weightGenerator, $min, $max, $coefficients);
    }
}
