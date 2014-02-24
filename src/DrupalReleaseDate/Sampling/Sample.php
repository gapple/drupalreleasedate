<?php
namespace DrupalReleaseDate\Sampling;

class Sample
{
    protected $when;
    protected $count;
    protected $resolved;
    protected $duration;

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
        $this->duration = $this->when - $from->getWhen();
        $this->resolved = $from->getCount() - $this->count;
    }

    function getDuration()
    {
        return $this->duration;
    }

    function getResolved()
    {
        return $this->resolved;
    }
}
