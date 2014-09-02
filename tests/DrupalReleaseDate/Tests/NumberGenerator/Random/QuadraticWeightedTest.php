<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\NumberGenerator\Random\QuadraticWeighted;

class QuadraticWeightedTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test that the generator only returns results in the specified range.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\QuadraticWeighted<extended>
     */
    function testRange()
    {
        $min = 2;
        $max = 15;
        $generator = new QuadraticWeighted($min, $max);

        for ($i = 0; $i < RANDOM_BASE_ITERATIONS; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\QuadraticWeighted<extended>
     */
    function testWeights()
    {
        $generator = new QuadraticWeighted(2, 11);

        $weight = 0;
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals(pow($weight, 2) + 1, $generator->calculateWeight($i));
            $weight++;
        }
    }

    /**
     * Test that the generator produces a quadratically increasing distribution
     * of results. (e.g. the four item should be twice as likely as the first,
     * the third item nine times as likely, etc)
     *
     * Since the sum of probabilities is greater than in a flat distribution,
     * additional iterations need to be performed to get sufficient precision
     * for the least likely items.
     *
     * @group random
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\QuadraticWeighted<extended>
     */
    function testDistribution()
    {
        $min = 3;
        $max = 8;
        $generator = new QuadraticWeighted($min, $max);

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
