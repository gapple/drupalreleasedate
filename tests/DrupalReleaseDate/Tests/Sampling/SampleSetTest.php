<?php
namespace DrupalReleaseDate\Sampling\Tests;

use DrupalReleaseDate\Sampling\SampleSet;

class SampleSetTest extends \PHPUnit_Framework_TestCase {

    function testLength() {
        $sampleset = new SampleSet();

        $this->assertEquals(0, $sampleset->length());

        $sampleset->insert(1, 4);
        $sampleset->insert(2, 3);
        $sampleset->insert(3, 2);
        $sampleset->insert(4, 1);

        $this->assertEquals(4, $sampleset->length());
    }

    function testGetSample() {
        $sampleset = new SampleSet();

        $sampleset->insert(1, 4);
        $sampleset->insert(2, 3);
        $sampleset->insert(3, 2);
        $sampleset->insert(4, 1);

        $this->assertEquals(1, $sampleset->get(0)->getWhen());
        $this->assertEquals(4, $sampleset->get(3)->getWhen());
    }
}
