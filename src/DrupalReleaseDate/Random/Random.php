<?php
namespace DrupalReleaseDate\Random;

/**
 * Basic random generator with a flat distribution.
 */
class Random implements RandomInterface
{
    protected $min;
    protected $max;

    public function __construct($min = 0, $max = 1)
    {
        if (!is_int($min) || $min < 0) {
            throw new \InvalidArgumentException("Minimum value must be a positive integer");
        }
        if (!is_int($max) || $max < $min) {
            throw new \InvalidArgumentException("Maximum value must be a positive integer greater than minimum value");
        }

        $this->min = $min;
        $this->max = $max;
    }

    public function setMin($min)
    {
        $this->min = $min;
    }

    public function setMax($max)
    {
        $this->max = $max;
    }

    public function generate()
    {
        return mt_rand($this->min, $this->max);
    }
}
