<?php
namespace DrupalReleaseDate\Random;

/**
 * Basic random generator with a flat distribution.
 */
class Random implements RandomInterface
{
    protected $min = 0;
    protected $max = 1;

    public function __construct($min = 0, $max = 1)
    {
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
