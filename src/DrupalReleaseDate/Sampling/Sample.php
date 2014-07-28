<?php
namespace DrupalReleaseDate\Sampling;

class Sample
{
    protected $when;
    protected $count;
    protected $diffTarget;

    function __construct($when, $count, $last = null)
    {
        $this->when = $when;
        $this->count = $count;
        if ($last) {
            $this->setDiff($last);
        }
    }

    function getWhen()
    {
        return $this->when;
    }

    function getCount()
    {
        return $this->count;
    }

    function setDiff($from)
    {
        $this->diffTarget = $from;
    }

    function getDuration()
    {
        if (!isset($this->diffTarget)) {
            return 0;
        }
        return $this->when - $this->diffTarget->getWhen();
    }

    function getResolved()
    {
        if (!isset($this->diffTarget)) {
            return 0;
        }
        return $this->diffTarget->getCount() - $this->count;
    }
}
