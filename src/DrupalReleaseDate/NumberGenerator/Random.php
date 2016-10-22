<?php
namespace DrupalReleaseDate\NumberGenerator;

/**
 * Basic random generator with a flat distribution.
 */
class Random extends AbstractGenerator
{
    public function generate()
    {
        if ($this->type == NumberGeneratorInterface::TYPE_INT) {
            $rand = random_int($this->min, $this->max);
        } else {
            $rand = (random_int(0, PHP_INT_MAX) / PHP_INT_MAX) * ($this->max - $this->min) + $this->min;
        }
        return $rand;
    }
}
