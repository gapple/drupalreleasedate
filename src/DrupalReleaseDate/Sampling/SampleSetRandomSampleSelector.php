<?php
namespace DrupalReleaseDate\Sampling;

use DrupalReleaseDate\Random\RandomInterface;

class SampleSetRandomSampleSelector implements RandomSampleSelectorInterface
{
    public $sampleSet;
    protected $randomGenerator;

    public function __construct(SampleSet $sampleSet, RandomInterface $randomGenerator = null)
    {
        $this->sampleSet = $sampleSet;

        if (!$randomGenerator) {
            $randomGenerator = new \DrupalReleaseDate\Random\Random(1, $sampleSet->length() - 1);
        }
        $this->randomGenerator = $randomGenerator;
    }

    public function getLastSample()
    {
        return $this->sampleSet->getLast();
    }

    public function getRandomSample()
    {
        return $this->sampleSet->get($this->randomGenerator->generate());
    }

}