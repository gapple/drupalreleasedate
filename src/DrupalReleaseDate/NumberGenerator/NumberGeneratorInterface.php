<?php
namespace DrupalReleaseDate\NumberGenerator;

interface NumberGeneratorInterface
{
    const TYPE_INT = 1;
    const TYPE_FLOAT = 2;

    /**
     * Set the data type to be returned by the generator.
     *
     * Available options are class constants with a TYPE_ prefix.
     *
     * @param int $type
     */
    public function setType($type);

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
