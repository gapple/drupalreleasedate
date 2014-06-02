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

    /**
     * The initial proportion of iterations that will be run without checking
     * the failure ratio afterwards.
     *
     * e.g. A minimum of 10% of requested iterations will always be processed
     *      before a large number of failures can cause the run to abort.
     *
     * @var float
     *   A value between 0 and 1
     */
    public $increasingFailureThresholdRatio = 0.1;

    /**
     * The maximum proportion of iterations that can fail before the entire run
     * returns as a failure.
     *
     * e.g. If 10% of iterations have failed, the run will be aborted.
     *
     * @var float
     *   A value between 0 and 1
     */
    public $increasingFailureRatio = 0.1;

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
                throw new MonteCarloIncreasingRunException("Iteration failed due to increasing issue count");
            }
        } while ($issues > 0);

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
        $increasingFailures = 0;

        $estimate = 0;
        for ($run = 0; $run < $iterations; $run++) {
            try {
                $estimate += $this->iteration();
            } catch (MonteCarloIncreasingRunException $e) {
                $increasingFailures++;
                if (
                    $run > ($iterations * $this->increasingFailureThresholdRatio)
                    && ($increasingFailures / $run) > $this->increasingFailureRatio
                ) {
                    throw new MonteCarloIncreasingRunException('Run aborted after iteration ' . $run, 0, $e);
                }
            }
        }

        return $estimate / ($iterations - $increasingFailures);
    }

    /**
     * Return the median value from a distribution array.
     *
     * @param  array $distribution An array as returned from MonteCarlo::runDistribution()
     * @return int
     */
    public static function getMedianFromDistribution($distribution)
    {
        // Calculate the number of iterations required to acheive a median value.
        $medianIterations = array_sum($distribution) / 2;

        $countSum = 0;
        foreach ($distribution as $estimate => $count) {
            $countSum += $count;
            if ($countSum >= $medianIterations) {
                break;
            }
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
        return self::getMedianFromDistribution($this->runDistribution($iterations, $bucketSize));
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

        $increasingFailures = 0;

        for ($run = 0; $run < $iterations; $run++) {
            try {
                $estimate = $this->iteration();
            } catch (MonteCarloIncreasingRunException $e) {
                $increasingFailures++;
                if (
                    $run > ($iterations * $this->increasingFailureThresholdRatio)
                    && ($increasingFailures / $run) > $this->increasingFailureRatio
                ) {
                    throw new MonteCarloIncreasingRunException('Run aborted after iteration ' . $run, 0, $e);
                }

                continue;
            }

            $bucket = $estimate - $estimate % $bucketSize;

            if (isset($estimates[$bucket])) {
                $estimates[$bucket]++;
            } else {
                $estimates[$bucket] = 1;
            }
        }

        ksort($estimates);

        return $estimates;
    }
}
