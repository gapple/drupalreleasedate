<?php
namespace DrupalReleaseDate\Tests;

use DrupalReleaseDate\SampleSet;
use DrupalReleaseDate\MonteCarlo;

class MonteCarloTest extends \PHPUnit_Framework_TestCase {

    function testBasicRun() {
        $sampleset = new Sampleset();

        $sampleset->addSample(10, 10);
        $sampleset->addSample(20,  9);
        $sampleset->addSample(30,  8);
        $sampleset->addSample(40,  7);

        $montecarlo = new MonteCarlo($sampleset);

        $result = $montecarlo->run(1);

        $this->assertEquals(70, $result);
    }

    function testIncreasingRun() {
        $sampleset = new Sampleset();

        $sampleset->addSample(10, 10);
        $sampleset->addSample(20, 11);
        $sampleset->addSample(30, 12);
        $sampleset->addSample(40, 13);

        $montecarlo = new MonteCarlo($sampleset);

        $result = $montecarlo->run(1);

        $this->assertEquals(0, $result);
    }
}
