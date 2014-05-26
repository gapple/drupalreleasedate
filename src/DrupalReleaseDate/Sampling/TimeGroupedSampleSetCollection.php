<?php
namespace DrupalReleaseDate\Sampling;

class TimeGroupedSampleSetCollection
{
    protected $bins = array();
    protected $binSize;
    protected $binMinKey;
    protected $binMaxKey;

    /**
     *
     * @param int $binSize
     *   Date range for grouping samples together, in seconds.
     *   Default value is seven days.
     */
    public function __construct($binSize = 604800)
    {
        $this->binSize = $binSize;
    }

    /**
     * Add a Sample to the end of the collection.
     */
    public function insert($sample)
    {
        $binIndex = (int) ($sample->getWhen() / $this->binSize);

        if (empty($this->bins[$binIndex])) {
            if (!empty($this->bins)) {
                // Get the last item from the previous bin, if available
                $previousBinIndex = $binIndex;
                do {
                    $previousBinIndex--;
                    if (isset($this->bins[$previousBinIndex])) {
                        $previousSample = $this->bins[$previousBinIndex]->getLast();
                        $sample->setDiff($previousSample);

                        break;
                    }
                } while ($previousBinIndex > $this->binMinKey);
            }

            $this->bins[$binIndex] = new SampleSet();

            if (!isset($this->binMinKey) || $binIndex < $this->binMinKey) {
                $this->binMinKey = $binIndex;
            }
            $this->binMaxKey = max($binIndex, $this->binMaxKey);
        }

        $this->bins[$binIndex]->insert($sample);
    }

    /**
     * Get the possible number of SampleSet objects in the collection.
     *
     * Since a bin may not have any samples, the actual length could be shorter.
     *
     * @return int
     */
    function length()
    {
        return $this->binMaxKey - $this->binMinKey + 1;
    }

    /**
     * Retrieve the SampleSet at the specified index.
     *
     * Note: this method may return null if no samples are available for the
     *       period corresponding to the specified index.
     */
    function get($index) {
        $key = $this->binMinKey + $index;

        $sampleSet = null;
        if (isset($this->bins[$key])) {
            $sampleSet = $this->bins[$key];
        }

        return $sampleSet;
    }

    /**
     * Get the last SampleSet in the collection.
     *
     * @return SampleSet
     */
    function getLast()
    {
        return $this->bins[$this->binMaxKey];
    }
}
