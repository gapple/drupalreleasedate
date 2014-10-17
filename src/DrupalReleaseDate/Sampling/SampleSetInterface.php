<?php
namespace DrupalReleaseDate\Sampling;

interface SampleSetInterface
{
    /**
     * @return int
     */
    public function length();

    /**
     * @param \DrupalReleaseDate\Sampling\Sample $sample
     * @return null
     */
    public function insert(Sample $sample);

    /**
     * @param int $index
     * @return \DrupalReleaseDate\Sampling\Sample
     */
    public function get($index);

    /**
     * @return \DrupalReleaseDate\Sampling\Sample
     */
    public function getLast();
}
