<?php
namespace DrupalReleaseDate\NumberGenerator;

use DrupalReleaseDate\NumberGenerator\NumberGeneratorInterface;

/**
 *
 * @package DrupalReleaseDate\NumberGenerator
 */
abstract class AbstractGenerator implements NumberGeneratorInterface
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
        if (!is_int($min) || $min < 0 || $min > $this->max) {
            throw new \InvalidArgumentException("Minimum value must be a positive integer, less than the maximum value");
        }
        $this->min = $min;
    }

    public function setMax($max)
    {
        if (!is_int($max) || $max < $this->min) {
            throw new \InvalidArgumentException("Maximum value must be a positive integer greater than minimum value");
        }
        $this->max = $max;
    }
}
