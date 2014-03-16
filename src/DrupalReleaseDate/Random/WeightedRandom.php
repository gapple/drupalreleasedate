<?php
namespace DrupalReleaseDate\Random;

abstract class WeightedRandom extends Random
{

    /**
     * An array of cumulative weights for each possible outcome of the
     * generator.
     *
     * e.g.
     *   if two items have 50% chance:
     *     array(1 => 1, 2 => 2)
     *   if two items have a 25% and 75% chance:
     *     array(1 => 1, 2 => 4)
     *     or
     *     array(1 => 3, 2 => 4)
     *
     * @var array
     */
    protected $weightsArray = array();

    /**
     * Specify if the generator will only use integer values for weights.
     *
     * If set to false, the generator will use a float value internally to
     * determine the value to be returned so that results are not biased by
     * integer rounding.
     *
     * @var boolean
     */
    protected $integerWeights = true;

    public function __construct($min, $max)
    {
        parent::__construct($min, $max);

        $this->evaluateWeights();
    }

    /**
     * Evaluate and store weights for the range currently set.
     *
     * @todo It is only necessary to calculate weights for higher values if
     * the minimum value has not changed.
     */
    protected function evaluateWeights() {

        $this->weightsArray = array();

        $cumulativeWeight = 0;
        for ($i = $this->min; $i <= $this->max; $i++) {
            $weight = $this->calculateWeight($i);
            if ($weight < 0) {
                throw new \RangeException('The value ' . $i . ' was given a weight of ' . $weight);
            }
            $cumulativeWeight += $weight;
            $this->weightsArray[$i] = $cumulativeWeight;
        }

        // Check that the cumulative weight has not grown over PHP_INT_MAX,
        // and been converted to a float.
        if (!is_int($cumulativeWeight)) {
            $this->integerWeights = false;
        }
    }

    /**
     * Set a new minimum value for the generator.
     *
     * @see \DrupalReleaseDate\Random\Random::setMin()
     */
    public function setMin($min)
    {
        parent::setMin($min);

        $this->evaluateWeights();
    }

    /**
     * Set a new maximum value for the generator.
     *
     * @see \DrupalReleaseDate\Random\Random::setMax()
     */
    public function setMax($max)
    {
        parent::setMax($max);

        $this->evaluateWeights();
    }

    /**
     * Calculate the weight of a value provided by the generator.
     *
     * @param int $value
     * @return int
     */
    abstract public function calculateWeight($value);

    /**
     * Generate a random value, according to the evaluated weights.
     *
     * @return int
     */
    public function generate()
    {
        $maxWeight = $this->weightsArray[$this->max];

        if ($this->integerWeights) {
            $rand = mt_rand(1, $maxWeight);
        } else {
            $rand = (mt_rand() / mt_getrandmax()) * $maxWeight;
        }

        // Find the first weight that the random number fits in to.
        $value = $this->min;
        foreach ($this->weightsArray as $value => $weight) {
            if ($rand <= $weight) {
                break;
            }
        }
        return $value;
    }
}
