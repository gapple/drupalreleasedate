<?php
namespace DrupalReleaseDate\Random;

class LinearWeightedRandom extends Random {

    private $weightsArray = array();

    public function __construct($min, $max) {
        $binCount = $max - $min + 1;
        $binMax = ($binCount * ($binCount + 1) ) / 2;

        parent::__construct($min, $binMax);

        // calculate the cumulative weights for each bin.
        for ($b = 1; $b <= $binCount; $b++) {
            $this->weightsArray[$b] = ($b * ($b+1) ) / 2;
        }
    }

    public function generate() {
      $rand = parent::generate();
      // find the first bin that the number fits in to.
      foreach ($this->weightsArray as $key => $bin) {
          if ($rand <= $bin) {
              return $key;
          }
      }
    }
}
