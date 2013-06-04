<?php
namespace DrupalReleaseDate\Random;

abstract class WeightedRandom extends Random {

    protected $cumulativeWeight = 0;

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

    public function __construct($min, $max) {
        parent::__construct($min, $max);

        for ($i = $min; $i <= $max ; $i++) {
            $this->cumulativeWeight += $this->calculateWeight($i);
            $this->weightsArray[$i] = $this->cumulativeWeight;
        }
    }

    /**
     * Calculate the weight of a value provided by the generator.
     *
     * @param int $value
     * @return int
     */
    abstract public function calculateWeight($value);

    public function generate() {
      $rand = mt_rand(1, $this->cumulativeWeight);

      // find the first bin that the number fits in to.
      foreach ($this->weightsArray as $key => $bin) {
          if ($rand <= $bin) {
              return $key;
          }
      }
    }
}
