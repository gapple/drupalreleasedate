<?php
namespace DrupalReleaseDate\Random;

interface RandomInterface {

    /**
     * Initialize a generator with the provided range.
     *
     * @param int $min
     * @param int $max
     */
    public function __construct($min, $max);

    /**
     * Return a new random number according to the generator's configuration.
     */
    public function generate();

}
