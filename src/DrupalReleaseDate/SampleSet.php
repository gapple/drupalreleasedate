<?php
namespace DrupalReleaseDate;

class SampleSet {
    protected $samples = array();
    protected $length = 0;

    function addSample($when, $count) {
        $last = null;
        if ($this->length) {
            $last = $this->samples[$this->length - 1];
        }
        $this->samples[] = new Sample($when, $count, $last);
        $this->length++;
    }

    function getSample($index) {
        return $this->samples[$index];
    }

    function length() {
        return $this->length;
    }
}
