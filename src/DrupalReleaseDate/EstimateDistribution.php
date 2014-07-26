<?php
namespace DrupalReleaseDate;

class EstimateDistribution
{
    protected $data;
    protected $failures;

    public function __construct()
    {
        $this->data = array();
        $this->failures = 0;
    }

    public function success($bucket)
    {
        if (!isset($this->data[$bucket])) {
            $this->data[$bucket] = 0;
        }
        $this->data[$bucket]++;
    }

    public function failure()
    {
        $this->failures++;
    }

    public function getFailures()
    {
        return $this->failures;
    }

    /**
     * Return the average of all values.
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
     * Return the bucket which contains the median value.
     *
     * @return int
     */
    public function getMedian()
    {
        ksort($this->data);

        $medianCount = array_sum($this->data) / 2;

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
