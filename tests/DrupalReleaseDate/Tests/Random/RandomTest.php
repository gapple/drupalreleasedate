<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\Random\Random;

class RandomTest extends \PHPUnit_Framework_TestCase {

    /**
     * Minimum value for the generator to produce.
     */
    protected $min = 1;

    /**
     * Maximim value for the generator to produce.
     */
    protected $max = 10;

    /**
     * Number of iterations to retrieve from the generator when multiple results
     * are required.
     */
    protected $iterations = 10000;

    function setUp() {
        $this->generator = new Random($this->min, $this->max);
    }

    /**
     * Test that the generator only returns results in the specified range.
     */
    function testRange() {
        for ($i = 0; $i < $this->iterations; $i++) {
            $rand = $this->generator->generate();
            $this->assertGreaterThanOrEqual($this->min, $rand);
            $this->assertLessThanOrEqual($this->max, $rand);
        }
    }

    /**
     * Test that the generator produces a relatively flat distribution of results.
     */
    function testDistribution() {

        $range = $this->max - $this->min + 1;
        $expected = $this->iterations / $range;

        $results = array();
        for ($i = $this->min; $i <= $this->max; $i++) {
            $results[$i] = 0;
        }

        for ($i = 0; $i < $this->iterations; $i++) {
            $results[$this->generator->generate()]++;
        }


        foreach ($results as $key => $count) {
            $this->assertGreaterThan(0.9, $count / $expected);
            $this->assertLessThan(1.1, $count / $expected);
        }
    }
}
