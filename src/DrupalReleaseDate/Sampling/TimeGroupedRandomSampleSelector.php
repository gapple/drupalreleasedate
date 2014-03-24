<?php
namespace DrupalReleaseDate\Sampling;

use DrupalReleaseDate\Random\RandomInterface;

class TimeGroupedRandomSampleSelector implements RandomSampleSelectorInterface
{
    protected $sampleSetCollection;
    protected $collectionRandomGenerator;

    /**
     * A collection of RandomInterface objects for their respective SampleSet.
     *
     * TODO pass this in the constructor instead of generating internally.
     */
    protected $sampleSetRandomGenerators = array();

    public function __construct(
        TimeGroupedSampleSetCollection $sampleSetCollection,
        RandomInterface $collectionRandomGenerator = null
    ) {
        $this->sampleSetCollection = $sampleSetCollection;

        if (!$collectionRandomGenerator) {
            $collectionRandomGenerator = new \DrupalReleaseDate\Random\Random(0, $sampleSetCollection->length() - 1);
        }
        $this->collectionRandomGenerator = $collectionRandomGenerator;
    }

    public function getLastSample()
    {
        return $this->sampleSetCollection->getLast()->getLast();
    }

    public function getRandomSample()
    {
        do {
            $sampleSetIndex = $this->collectionRandomGenerator->generate();
            // Samples may not exist for all periods, so repeat until a
            // SampleSet is returned.
            $sampleSet = $this->sampleSetCollection->get($sampleSetIndex);
        } while (!isset($sampleSet));

        if (!isset($this->sampleSetRandomGenerators[$sampleSetIndex])) {
            $baseWeight = $this->collectionRandomGenerator->calculateWeight($sampleSetIndex);
            $upperWeight = $this->collectionRandomGenerator->calculateWeight($sampleSetIndex + 1);

            $this->sampleSetRandomGenerators[$sampleSetIndex] = new \DrupalReleaseDate\Random\LinearWeightedRandom(
                0,
                $sampleSet->length() - 1,
                ($upperWeight - $baseWeight) / $sampleSet->length(),
                $baseWeight
            );
        }

        return $sampleSet->get($this->sampleSetRandomGenerators[$sampleSetIndex]->generate());
    }
}
