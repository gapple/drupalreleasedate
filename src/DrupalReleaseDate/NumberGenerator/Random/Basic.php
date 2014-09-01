<?php
namespace DrupalReleaseDate\NumberGenerator\Random;

use DrupalReleaseDate\NumberGenerator\AbstractGenerator;

/**
 * Basic random generator with a flat distribution.
 */
class Basic extends AbstractGenerator
{
    public function generate()
    {
        return mt_rand($this->min, $this->max);
    }
}
