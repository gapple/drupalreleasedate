<?php
namespace DrupalReleaseDate\NumberGenerator;

class PolynomialWeighted extends AbstractWeighted
{

    /**
     * An array of coefficients for the polynomial terms keyed by their
     * exponent.
     *
     * e.g. for the quadratic function x^2 + 2x + 4:
     *   array(
     *     2 => 1,
     *     1 => 2,
     *     0 => 4,
     *   )
     * @var array
     */
    protected $coefficients;

    public function __construct(NumberGeneratorInterface $weightGenerator, $min, $max, $coefficients = array())
    {
        $this->coefficients = $coefficients;

        parent::__construct($weightGenerator, $min, $max);
    }

    public function calculateWeight($index)
    {
        $weight = 0;
        foreach ($this->coefficients as $exponent => $coefficient) {
            if (empty($coefficient)) {
                continue;
            }
            $weight += $coefficient * pow($index, $exponent);
        }

        return $weight;
    }
}
