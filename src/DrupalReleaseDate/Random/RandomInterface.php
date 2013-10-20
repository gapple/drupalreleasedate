<?php
namespace DrupalReleaseDate\Random;

interface RandomInterface
{

    /**
     * Set minimum value to be returned by generator.
     * @param int $min
     */
    public function setMin($min);

    /**
     * Set maximum value to be returned by generator.
     * @param int $max
     */
    public function setMax($max);

    /**
     * Return a new random number according to the generator's configuration.
     */
    public function generate();

}
