<?php
namespace DrupalReleaseDate\Sampling;

interface RandomSampleSelectorInterface
{

    public function getLastSample();

    public function getRandomSample();

}
