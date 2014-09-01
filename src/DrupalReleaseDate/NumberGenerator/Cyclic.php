<?php
namespace DrupalReleaseDate\NumberGenerator;

use DrupalReleaseDate\NumberGenerator\AbstractGenerator;

/**
 * Basic number generator that cycles through its range of values.
 */
class Cyclic extends AbstractGenerator
{
    protected $current;

    public function generate()
    {
        if (is_null($this->current) || $this->current >= $this->max) {
            $this->current = $this->min;
        }
        else {
            $this->current++;
        }
        return $this->current;
    }
}
