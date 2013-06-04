<?php
namespace DrupalReleaseDate;

class MonteCarlo {

    private $sampleSet;

    function __construct(SampleSet $sampleSet, Random\RandomInterface $randomGenerator = null) {
        $this->sampleSet = $sampleSet;

        if (!$randomGenerator) {
            $randomGenerator = new Random\Random(1, $sampleSet->length() -1);
        }
        $this->randomGenerator = $randomGenerator;
    }

    function run($iterations = 1000) {

        // Get the current number of issues from the last sample in the set.
        $currentIssues = $this->sampleSet->getSample($this->sampleSet->length() - 1)->getCount();

        $estimate = 0;
        for ($run = 0; $run < $iterations; $run++) {
            $runDuration = 0;
            $runIssues = $currentIssues;
            do {
                $i = $this->randomGenerator->generate();

                $sample = $this->sampleSet->getSample($i);
                $runDuration += $sample->getDuration();
                $runIssues -= $sample->getResolved();

                // Failsafe for if simulation goes in the wrong direction.
                if ($runIssues > $currentIssues * 2) {
                  return 0;
                }
            }
            while ($runIssues > 0);

            $estimate += $runDuration / $iterations;
        }

        return $estimate;
    }
}
