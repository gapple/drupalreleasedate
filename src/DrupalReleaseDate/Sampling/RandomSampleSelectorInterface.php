<?php
namespace DrupalReleaseDate\Sampling;

interface RandomSampleSelectorInterface
{

    /**
     * @return \DrupalReleaseDate\Sampling\Sample
     */
    public function getLastSample();

    /**
     * @return \DrupalReleaseDate\Sampling\Sample
     */
    public function getRandomSample();

}
