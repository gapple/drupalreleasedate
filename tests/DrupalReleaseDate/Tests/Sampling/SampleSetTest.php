<?php
namespace DrupalReleaseDate\Sampling\Tests;

use DrupalReleaseDate\Sampling\Sample;
use DrupalReleaseDate\Sampling\SampleSet;

class SampleSetTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @covers \DrupalReleaseDate\Sampling\SampleSet
     * @uses \DrupalReleaseDate\Sampling\Sample
     */
    function testLength()
    {
        $sampleset = new SampleSet();

        $this->assertEquals(0, $sampleset->length());

        $sampleset->insert(new Sample(1, 4));
        $sampleset->insert(new Sample(2, 3));
        $sampleset->insert(new Sample(3, 2));
        $sampleset->insert(new Sample(4, 1));

        $this->assertEquals(4, $sampleset->length());
    }

    /**
     *
     * @covers \DrupalReleaseDate\Sampling\SampleSet
     * @uses \DrupalReleaseDate\Sampling\Sample
     */
    function testGetSampleAtIndex()
    {
        $sampleset = new SampleSet();

        $sampleset->insert(new Sample(1, 4));
        $sampleset->insert(new Sample(2, 3));
        $sampleset->insert(new Sample(3, 2));
        $sampleset->insert(new Sample(4, 1));

        $this->assertEquals(1, $sampleset->get(0)->getWhen());
        $this->assertEquals(4, $sampleset->get(3)->getWhen());
    }

    /**
     *
     * @covers \DrupalReleaseDate\Sampling\SampleSet
     * @uses \DrupalReleaseDate\Sampling\Sample
     */
    function testGetLastSample()
    {
        $sampleset = new SampleSet();

        $sampleset->insert(new Sample(1, 4));
        $sampleset->insert(new Sample(2, 3));
        $this->assertEquals(2, $sampleset->getLast()->getWhen());

        $sampleset->insert(new Sample(3, 2));
        $sampleset->insert(new Sample(4, 1));
        $this->assertEquals(4, $sampleset->getLast()->getWhen());
    }
}
