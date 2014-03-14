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

    public function __construct($min = 0, $max = 1)
    {
        parent::__construct($min, $max);

        $cumulativeWeight = 0;
        for ($i = $min; $i <= $max; $i++) {
            $cumulativeWeight += $this->calculateWeight($i);
            $this->weightsArray[$i] = $cumulativeWeight;
        }
    }

    /**
     * Set a new minimum value for the generator.
     *
     * Weighted random values may be calculated as an interval from the minimum
     * value, so all weights will need to be recalculated.
     *
     * @see \DrupalReleaseDate\Random\Random::setMin()
     */
    public function setMin($min)
    {
        parent::setMin($min);

        $this->weightsArray = array();

        $cumulativeWeight = 0;
        for ($i = $min; $i <= $this->max; $i++) {
            $cumulativeWeight += $this->calculateWeight($i);
            $this->weightsArray[$i] = $cumulativeWeight;
        }
    }

    /**
     * Set a new maximum value for the generator.
     *
     * Weighted random will require calculating cumulative values if the desired
     * max is greater than the previous max.
     *
     * @see \DrupalReleaseDate\Random\Random::setMax()
     */
    public function setMax($max)
    {
        end($this->weightsArray);
        $calculatedTo = key($this->weightsArray);

        if ($calculatedTo < $max) {
            $cumulativeWeight = current($this->weightsArray);
            for ($i = $calculatedTo + 1; $i <= $max; $i++) {
                $cumulativeWeight += $this->calculateWeight($i);
                $this->weightsArray[$i] = $cumulativeWeight;
            }
        }
        parent::setMax($max);
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
        $minWeight = $this->weightsArray[$this->min];
        $maxWeight = $this->weightsArray[$this->max];
        $rand = mt_rand($minWeight, $maxWeight);

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
