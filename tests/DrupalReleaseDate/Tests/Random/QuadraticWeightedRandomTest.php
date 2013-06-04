<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\Random\QuadraticWeightedRandom;

class QuadraticWeightedRandomTest extends \PHPUnit_Framework_TestCase {

    /**
     * Number of iterations to retrieve from the generator when multiple results
     * are required.
     */
    protected $iterations = 1000;

    /**
     * Test that the generator only returns results in the specified range.
     */
    function testRange() {
        $min = 2;
        $max = 15;
        $generator = new QuadraticWeightedRandom($min, $max);

        for ($i = 0; $i < $this->iterations; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    function testSimpleWeights() {
        $min = 1;
        $max = 10;
        $generator = new QuadraticWeightedRandom($min, $max);

        $weight = 1;
        $range = $min - $max + 1;
        for ($i = $min; $i <= $max; $i++) {
            $this->assertEquals(pow($weight, 2), $generator->calculateWeight($i));
            $weight++;
        }
    }

    function testShiftedWeights() {
        $min = 3;
        $max = 12;
        $generator = new QuadraticWeightedRandom($min, $max);

        $weight = 1;
        $range = $min - $max + 1;
        for ($i = $min; $i <= $max; $i++) {
            $this->assertEquals(pow($weight, 2), $generator->calculateWeight($i));
            $weight++;
        }
    }

    /**
     * Test that the generator produces a quadraticly increasing distribution of results.
     * (e.g. the four item should be twice as likely as the first, the third item
     *  nine times as likely, etc)
     *
     * Since the sum of probabilities is greater than in a flat distribution,
     * addtional iterations need to be performed to get sufficient precision for
     * the least likely items.
     */
    function testDistribution() {
        $min = 3;
        $max = 8;
        $generator = new QuadraticWeightedRandom($min, $max);

        $range = $max - $min + 1;
        // TODO use formula to calculate sum of quadratic values instead.
        $probabilitySum = 0;
        for ($i = 1; $i <= $range; $i++){
            $probabilitySum += pow($i, 2);
        }

        $results = array();
        for ($i = $min; $i <= $max; $i++) {
            $results[$i] = 0;
        }

        for ($i = 0; $i < ($this->iterations * $probabilitySum); $i++) {
            $results[$generator->generate()]++;
        }

        // TODO make the required results adaptive based on the range of the generator
        //      and the number of iterations performed.
        $weight = 1;
        foreach ($results as $key => $count) {
            $this->assertGreaterThan(pow($weight, 2) * 0.9, $count / ($this->iterations));
            $this->assertLessThan(pow($weight, 2) * 1.1, $count / ($this->iterations));
            $weight++;
        }
    }
}
