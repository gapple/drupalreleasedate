<?php
namespace DrupalReleaseDate;

use \DrupalReleaseDate\Sampling\RandomSampleSelectorInterface;

class MonteCarlo
{

    /**
     * The default number of iterations to perform when running an estimate.
     * @var number
     */
    const DEFAULT_ITERATIONS = 10000;

    /**
     * The default size for grouping distribution samples into buckets (One day)
     * in seconds.
     * @var number
     */
    const DEFAULT_BUCKET_SIZE = 86400;

    protected $sampleSelector;

    public function __construct(RandomSampleSelectorInterface $sampleSelector)
    {
        $this->sampleSelector = $sampleSelector;
    }

    /**
     * Get an estimated value from a single iteration.
     *
     * @return number
     */
    public function iteration()
    {

      // Get the current number of issues from the last sample in the set.
      $issues = $currentIssues = $this->sampleSelector->getLastSample()->getCount();

      $duration = 0;

      do {
          $sample = $this->sampleSelector->getRandomSample();
          $duration += $sample->getDuration();
          $issues -= $sample->getResolved();

          // Failsafe for if simulation goes in the wrong direction too far.
          if ($issues > $currentIssues * 10) {
              return 0;
          }
      }
      while ($issues > 0);

      return $duration;
    }

    /**
     * Get the average value of the specified number of iterations.
     *
     * @param number $iterations
     * @return number
     */
    public function runAverage($iterations = self::DEFAULT_ITERATIONS)
    {

        $estimate = 0;
        for ($run = 0; $run < $iterations; $run++) {
            $estimate += $this->iteration() / $iterations;
        }

        return $estimate;
    }

    /**
     * Get the median estimate value from the specified number of iterations.
     *
     * @param number $iterations
     * @param number $bucketSize
     * @return number
     */
    public function runMedian($iterations = self::DEFAULT_ITERATIONS, $bucketSize = self::DEFAULT_BUCKET_SIZE)
    {

        $distribution = $this->runDistribution($iterations, $bucketSize);

        $countSum = 0;
        foreach ($distribution as $estimate => $count) {
            // Count the number of iterations so far, ignoring failed iterations.
            if ($estimate == 0) {
                if ($count >= $iterations / 2) {
                    break;
                }
                continue;
            }

            $countSum += $count;
            if ($countSum >= $iterations / 2) {
                return $estimate;
            }
        }

        return 0;
    }

    /**
     * Get the distribution of estimates from the specified number of
     * iterations, grouped into buckets of the specified size.
     *
     * @param unknown $iterations
     * @param unknown $bucketSize
     *   The period in seconds to group estimates by.
     * @return number
     */
    public function runDistribution($iterations = self::DEFAULT_ITERATIONS, $bucketSize = self::DEFAULT_BUCKET_SIZE)
    {
        $estimates = array();

        for ($run = 0; $run < $iterations; $run++) {
            $estimate = $this->iteration();

            $bucket = $estimate - $estimate % $bucketSize;

            if (isset($estimates[$bucket])) {
                $estimates[$bucket]++;
            }
            else {
                $estimates[$bucket] = 1;
            }
        }

        ksort($estimates);

        return $estimates;
    }
}
