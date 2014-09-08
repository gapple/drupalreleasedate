<?php
namespace DrupalReleaseDate\NumberGenerator;

class QuadraticWeighted extends PolynomialWeighted
{
    public function __construct(NumberGeneratorInterface $weightGenerator, $min, $max, $a = 1, $b = 0, $c = 1)
    {
        $coefficients = array(
            2 => $a,
            1 => $b,
            0 => $c,
        );

        parent::__construct($weightGenerator, $min, $max, $coefficients);
    }
}
