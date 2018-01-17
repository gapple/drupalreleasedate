<?php
namespace DrupalReleaseDate\Sampling\Tests;

use DrupalReleaseDate\Sampling\Sample;
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{

    function setup()
    {
        $this->now = time();
    }

    /**
     *
     * @covers \DrupalReleaseDate\Sampling\Sample
     */
    function testGetters()
    {
        $time = $this->now;
        $value = 50;
        $sample = new Sample($time, $value);

        $this->assertEquals($time, $sample->getWhen());
        $this->assertEquals($value, $sample->getCount());
        $this->assertEquals(0, $sample->getDuration());
        $this->assertEquals(0, $sample->getResolved());
    }

    /**
     *
     * @covers \DrupalReleaseDate\Sampling\Sample
     */
    function testDifference()
    {
        $time1 = $this->now - 600;
        $value1 = 50;
        $sample1 = new Sample($time1, $value1);

        $time2 = $this->now;
        $value2 = 40;
        $sample2 = new Sample($time2, $value2, $sample1);

        $this->assertEquals($time2 - $time1, $sample2->getDuration());
        $this->assertEquals($value1 - $value2, $sample2->getResolved());
    }

    /**
     *
     * @covers \DrupalReleaseDate\Sampling\Sample
     */
    function testNegativeCountDifference()
    {
        $time1 = $this->now - 600;
        $value1 = 40;
        $sample1 = new Sample($time1, $value1);

        $time2 = $this->now;
        $value2 = 50;
        $sample2 = new Sample($time2, $value2, $sample1);

        $this->assertEquals($time2 - $time1, $sample2->getDuration());
        $this->assertEquals($value1 - $value2, $sample2->getResolved());
    }
}
