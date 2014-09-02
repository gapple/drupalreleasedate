<?php
namespace DrupalReleaseDate\NumberGenerator\Random;

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

    public function __construct($min, $max, $coefficients = array())
    {
        $this->coefficients = $coefficients;

        foreach ($coefficients as $coefficient) {
            if (!is_int($coefficient)) {
                $this->integerWeights = false;
                break;
            }
        }

        parent::__construct($min, $max);
    }

    public function calculateWeight($value)
    {
        $weight = 0;
        foreach ($this->coefficients as $exponent => $coefficient) {
            if (empty($coefficient)) {
                continue;
            }
            $weight += $coefficient * pow($value - $this->min, $exponent);
        }

        return $weight;
    }
}
