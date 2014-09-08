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
            $rand = mt_rand($this->min, $this->max);
        } else {
            $rand = (mt_rand() / mt_getrandmax()) * ($this->max - $this->min) + $this->min;
        }
        return $rand;
    }
}
