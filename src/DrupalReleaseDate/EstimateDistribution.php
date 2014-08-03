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
