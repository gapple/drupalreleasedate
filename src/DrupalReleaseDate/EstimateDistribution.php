<?php
namespace DrupalReleaseDate;

class EstimateDistribution implements \IteratorAggregate
{
    protected $data;
    protected $failures;

    public function __construct()
    {
        $this->data = array();
        $this->failures = 0;
    }

    /**
     * Build an object from an array in the internal format.
     *
     * @param array $array
     * @return \DrupalReleaseDate\EstimateDistribution
     */
    public static function fromArray(array $array) {
        $new = new static();
        $new->data = $array;
        return $new;
    }

    public function getIterator()
    {
        ksort($this->data);
        return new \ArrayIterator($this->data);
    }

    /**
     * Record a success for the provided estimate value.
     *
     * @param integer $bucket
     */
    public function success($bucket)
    {
        if (!isset($this->data[$bucket])) {
            $this->data[$bucket] = 0;
        }
        $this->data[$bucket]++;
    }

    /**
     * Record a failure.
     */
    public function failure()
    {
        $this->failures++;
    }

    /**
     * Get the current number of recorded failures.
     * @return int
     */
    public function getFailures()
    {
        return $this->failures;
    }

    /**
     * Calculate the average of all values.
     *
     * @return int
     */
    public function getAverage()
    {
        $totalCount = array_sum($this->data);
        $sum = 0;

        foreach ($this->data as $bucket => $count) {
            $sum += $bucket * $count;
        }

        return $sum / $totalCount;
    }

    /**
     * Calculate the median value.
     *
     * If a median cannot be calculated due to there being insufficient
     * successful results, an exception is thrown.
     *
     * @throws \RuntimeException
     *
     * @param bool $includeFailures
     * @return int
     */
    public function getMedian($includeFailures = false)
    {
        ksort($this->data);

        $successCount = array_sum($this->data);
        $medianCount = $successCount / 2;

        if ($includeFailures) {
            $medianCount += $this->failures / 2;

            if ($medianCount > $successCount) {
                throw new \RuntimeException();
            }
        }

        $countSum = 0;
        foreach ($this->data as $bucket => $count) {
            $countSum += $count;
            if ($countSum >= $medianCount) {
                break;
            }
        }

        return $bucket;
    }
}
