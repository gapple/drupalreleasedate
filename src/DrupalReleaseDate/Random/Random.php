<?php
namespace DrupalReleaseDate\Random;

/**
 * Basic random generator with a flat distribution.
 */
class Random implements RandomInterface {
    protected $min = 0;
    protected $max = 1;

    public function __construct($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }

    public function generate() {
        return mt_rand($this->min, $this->max);
    }
}
