<?php
namespace DrupalReleaseDate\NumberGenerator\Random;

/**
 * Weight values in a geometric series changing by the given rate.
 */
class GeometricWeighted extends AbstractWeighted
{
    /**
     * Rate of change of weights for each value.
     *
     * e.g. 2 means that each value has twice the weight of the previous value.
     *   0.5 means that each value has half the weight of the previous value.
     *
     * @var number
     */
    protected $rate;

    public function __construct($min, $max, $rate = 1)
    {
        if ($rate <= 0) {
            throw new \InvalidArgumentException("Rate must be greater than 0");
        }
        $this->rate = $rate;

        if (!is_int($rate)) {
            $this->integerWeights = false;
        }

        parent::__construct($min, $max);
    }

    public function calculateWeight($value)
    {
        return pow($this->rate, $value - $this->min);
    }
}
