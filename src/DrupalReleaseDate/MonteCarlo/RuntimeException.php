<?php
namespace DrupalReleaseDate\MonteCarlo;

class RuntimeException extends \RuntimeException
{
    protected $distribution = null;

    public function setDistribution($distribution)
    {
        $this->distribution = $distribution;
    }

    public function getDistribution()
    {
        return $this->distribution;
    }
}
