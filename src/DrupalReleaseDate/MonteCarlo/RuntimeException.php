<?php
namespace DrupalReleaseDate\MonteCarlo;

class RuntimeException extends \RuntimeException
{
    /**
     * @var \DrupalReleaseDate\EstimateDistribution
     */
    protected $distribution = null;

    /**
     * @param \DrupalReleaseDate\EstimateDistribution $distribution
     */
    public function setDistribution($distribution)
    {
        $this->distribution = $distribution;
    }

    /**
     * @return \DrupalReleaseDate\EstimateDistribution
     */
    public function getDistribution()
    {
        return $this->distribution;
    }
}
