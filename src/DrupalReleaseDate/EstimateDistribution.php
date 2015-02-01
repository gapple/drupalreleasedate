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
    public static function fromArray(array $array)
    {
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
     * Get the total number of successes.
     *
     * @return int
     */
    public function getSuccessCount()
    {
        return array_sum($this->data);
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
    public function getFailureCount()
    {
        return $this->failures;
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

    /**
     * Calculate the average of all values.
     *
     * @deprecated
     *
     * @return int
     */
    public function getAverage()
    {
        return $this->getArithmeticMean();
    }

    /**
     * @return int|float
     */
    public function getArithmeticMean()
    {
        $totalCount = $this->getSuccessCount();
        $sum = 0;
        foreach ($this->data as $bucket => $count) {
            $sum += $bucket * $count;
        }

        return $sum / $totalCount;
    }

    /**
     * @return int|float
     */
    public function getArithmeticStandardDeviation()
    {
        $totalCount = $this->getSuccessCount();
        $mean = $this->getArithmeticMean();

        $sum = 0;
        foreach ($this->data as $bucket => $count) {
            $sum += pow(abs($bucket - $mean), 2) * $count;
        }

        return sqrt($sum / $totalCount);
    }

    /**
     * @return int|float
     */
    public function getGeometricMean()
    {
        $totalCount = $this->getSuccessCount();

        $sum = 0;
        foreach ($this->data as $bucket => $count) {
            if ($bucket == 0) {
                continue;
            }
            $sum += log($bucket) * $count;
        }

        return exp($sum / $totalCount);
    }

    /**
     * @return int|float|null
     */
    public function getGeometricStandardDeviation()
    {
        $totalCount = $this->getSuccessCount();
        $mean = $this->getGeometricMean();
        if (empty($mean)) {
            return null;
        }

        $sum = 0;
        foreach ($this->data as $bucket => $count) {
            if ($bucket == 0) {
                continue;
            }
            $sum += pow(log($bucket / $mean), 2) * $count;
        }

        return exp(sqrt($sum / $totalCount));
    }
}
