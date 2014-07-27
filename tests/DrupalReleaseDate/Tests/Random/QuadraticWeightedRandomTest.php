<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\Random\QuadraticWeightedRandom;

class QuadraticWeightedRandomTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test that the generator only returns results in the specified range.
     *
     * @covers \DrupalReleaseDate\Random\QuadraticWeightedRandom<extended>
     */
    function testRange()
    {
        $min = 2;
        $max = 15;
        $generator = new QuadraticWeightedRandom($min, $max);

        for ($i = 0; $i < RANDOM_BASE_ITERATIONS; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     *
     * @covers \DrupalReleaseDate\Random\QuadraticWeightedRandom<extended>
     */
    function testSimpleWeights()
    {
        $min = 1;
        $max = 10;
        $generator = new QuadraticWeightedRandom($min, $max);

        $weight = 0;
        $range = $min - $max + 1;
        for ($i = $min; $i <= $max; $i++) {
            $this->assertEquals(pow($weight, 2) + 1, $generator->calculateWeight($i));
            $weight++;
        }
    }

    /**
     *
     * @covers \DrupalReleaseDate\Random\QuadraticWeightedRandom<extended>
     */
    function testShiftedWeights()
    {
        $min = 3;
        $max = 12;
        $generator = new QuadraticWeightedRandom($min, $max);

        $weight = 0;
        $range = $min - $max + 1;
        for ($i = $min; $i <= $max; $i++) {
            $this->assertEquals(pow($weight, 2) + 1, $generator->calculateWeight($i));
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
     *
     * @covers \DrupalReleaseDate\Random\QuadraticWeightedRandom<extended>
     */
    function testDistribution()
    {
        $min = 3;
        $max = 8;
        $generator = new QuadraticWeightedRandom($min, $max);

        $range = $max - $min + 1;
        // TODO use formula to calculate sum of quadratic values instead.
        $probabilitySum = 0;
        for ($i = 0; $i < $range; $i++){
            $probabilitySum += pow($i, 2) + 1;
        }

        $results = array_fill($min, $range, 0);

        for ($i = 0; $i < (RANDOM_BASE_ITERATIONS * $probabilitySum); $i++) {
            $results[$generator->generate()]++;
        }

        // TODO make the required results adaptive based on the range of the generator
        //      and the number of iterations performed.
        $weight = 0.0;
        foreach ($results as $key => $count) {
            $this->assertEquals(pow($weight, 2) + 1, $count / (RANDOM_BASE_ITERATIONS), '', 0.2);
            $weight++;
        }
    }
}
