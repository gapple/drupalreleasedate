<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\Random\LinearWeightedRandom;

class LinearWeightedRandomTest extends \PHPUnit_Framework_TestCase {

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
    protected $iterations = 1000;

    function setUp() {
        $this->generator = new LinearWeightedRandom($this->min, $this->max);
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
     * Test that the generator produces a linearly increasing distribution of results.
     * (e.g. the second item should be twice as likely as the first, the third item
     *  three times as likely, etc)
     *
     * Since the sum of probabilities is greater than in a flat distribution,
     * addtional iterations need to be performed to get sufficient precision for
     * the least likely items.
     */
    function testDistribution() {

        $range = $this->max - $this->min + 1;
        $probabilitySum = ($this->max * ($this->max + 1) / 2);

        $results = array();
        for ($i = $this->min; $i <= $this->max; $i++) {
            $results[$i] = 0;
        }

        for ($i = 0; $i < ($this->iterations * $probabilitySum); $i++) {
            $results[$this->generator->generate()]++;
        }

        // TODO make the required results adaptive based on the range of the generator
        //      and the number of iterations performed.
        foreach ($results as $key => $count) {
            $this->assertGreaterThan($key * 0.9, $count / ($this->iterations / $range));
            $this->assertLessThan($key * 1.1, $count / ($this->iterations / $range));
        }
    }
}
