<?php
namespace DrupalReleaseDate\Random;

class LinearWeightedRandom extends WeightedRandom
{
    /**
     * Weight of the initial value.
     * @var number
     */
    protected $base;

    /**
     * Rate of change of weights for each value.
     * @var number
     */
    protected $slope;

    public function __construct($min, $max, $base = 1, $slope = 1)
    {
        if ($base < 0) {
            throw new \InvalidArgumentException("Base must be a positive number");
        }
        $this->base = $base;
        $this->slope = $slope;

        if (!is_int($base) || !is_int($slope)) {
            $this->integerWeights = false;
        }

        parent::__construct($min, $max);
    }

    public function calculateWeight($value)
    {
        return $this->base + (($value - $this->min) * $this->slope);
    }
}
