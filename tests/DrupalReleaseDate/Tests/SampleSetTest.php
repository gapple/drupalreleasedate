<?php
namespace DrupalReleaseDate\Tests;

use DrupalReleaseDate\SampleSet;

class SampleSetTest extends \PHPUnit_Framework_TestCase {

    function testLength() {
        $sampleset = new SampleSet();

        $this->assertEquals(0, $sampleset->length());

        $sampleset->addSample(1, 4);
        $sampleset->addSample(2, 3);
        $sampleset->addSample(3, 2);
        $sampleset->addSample(4, 1);

        $this->assertEquals(4, $sampleset->length());
    }

    function testGetSample() {
        $sampleset = new SampleSet();

        $sampleset->addSample(1, 4);
        $sampleset->addSample(2, 3);
        $sampleset->addSample(3, 2);
        $sampleset->addSample(4, 1);

        $this->assertEquals(1, $sampleset->getSample(0)->getWhen());
        $this->assertEquals(4, $sampleset->getSample(3)->getWhen());
    }
}
