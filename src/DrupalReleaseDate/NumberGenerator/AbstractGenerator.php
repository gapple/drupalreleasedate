<?php
namespace DrupalReleaseDate\NumberGenerator;

/**
 *
 * @package DrupalReleaseDate\NumberGenerator
 */
abstract class AbstractGenerator implements NumberGeneratorInterface
{
    protected $type = NumberGeneratorInterface::TYPE_INT;

    protected $min;
    protected $max;

    public function __construct($min = 0, $max = 1)
    {
        if ($min < 0) {
            throw new \InvalidArgumentException("Minimum value must be a positive integer");
        }
        if ($max < $min) {
            throw new \InvalidArgumentException("Maximum value must be a positive integer greater than minimum value");
        }

        $this->min = $min;
        $this->max = $max;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setMin($min)
    {
        if ($min < 0 || $min > $this->max) {
            throw new \InvalidArgumentException("Minimum value must be a positive integer, less than the maximum value");
        }
        $this->min = $min;
    }

    public function setMax($max)
    {
        if ($max < $this->min) {
            throw new \InvalidArgumentException("Maximum value must be a positive integer greater than minimum value");
        }
        $this->max = $max;
    }
}
