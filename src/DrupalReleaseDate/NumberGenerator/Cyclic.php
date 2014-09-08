<?php
namespace DrupalReleaseDate\NumberGenerator;

/**
 * Basic number generator that cycles through its range of values.
 */
class Cyclic extends AbstractGenerator
{
    protected $current;
    protected $step;

    public function __construct($min = 0, $max = 1, $step = 1)
    {
        parent::__construct($min, $max);

        if (!is_int($step)) {
            $this->setType(NumberGeneratorInterface::TYPE_FLOAT);
        }

        $this->step = $step;
    }

    public function generate()
    {
        if (is_null($this->current) || ($this->current * $this->step) >= $this->max) {
            $this->current = 1;
        }
        else {
            $this->current++;
        }
        return $this->current * $this->step;
    }
}
