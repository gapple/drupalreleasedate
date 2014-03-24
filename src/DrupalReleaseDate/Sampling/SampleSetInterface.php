<?php
namespace DrupalReleaseDate\Sampling;

interface SampleSetInterface
{
    public function length();

    public function insert($sample);

    public function get($index);

    public function getLast();
}
