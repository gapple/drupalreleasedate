<?php
namespace DrupalReleaseDate\Sampling;

use \DrupalReleaseDate\Random\RandomInterface;
use \DrupalReleaseDate\Random\Random;

class SampleSet implements SampleSetInterface
{
    protected $samples = array();
    protected $length = 0;

    public function length()
    {
        return $this->length;
    }

    public function insert($when, $count)
    {
        $last = null;
        if ($this->length) {
            $last = $this->samples[$this->length - 1];
        }
        $this->samples[] = new Sample($when, $count, $last);

        $this->length++;
    }

    public function get($index)
    {
        return $this->samples[$index];
    }

    public function getLast()
    {
        return $this->get($this->length() - 1);
    }
}
