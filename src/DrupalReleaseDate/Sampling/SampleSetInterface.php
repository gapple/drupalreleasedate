<?php
namespace DrupalReleaseDate\Sampling;

interface SampleSetInterface
{
    public function length();

    public function insert($when, $count);

    public function get($index);

    public function getLast();
}
