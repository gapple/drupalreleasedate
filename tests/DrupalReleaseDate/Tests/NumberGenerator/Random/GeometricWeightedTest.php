<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\NumberGenerator\Random\GeometricWeighted;

class GeometricWeightedTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test that a rate of zero is not accepted.
     *
     * This would result in every value past the first not receiving any
     * weight.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Rate must be greater than 0
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\GeometricWeighted<extended>
     */
    public function testZeroRate()
    {
        new GeometricWeighted(1, 10, 0);
    }

    /**
     * Test that a negative rate is not accepted.
     *
     * This would result in negative weights for values at odd positions
     * (e.g. the 1st, 3rd, etc values).
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Rate must be greater than 0
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\GeometricWeighted<extended>
     */
    public function testNegativeRate()
    {
        new GeometricWeighted(1, 10, -2);
    }

    /**
     * Test that the generator only returns results in the specified range.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\GeometricWeighted<extended>
     */
    public function testRange()
    {
        $min = 2;
        $max = 15;
        $rate = 2;
        $generator = new GeometricWeighted($min, $max, $rate);

        for ($i = 0; $i < RANDOM_BASE_ITERATIONS; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     * Test that the generator produces accurate weights with values starting
     * at one.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\GeometricWeighted<extended>
     */
    public function testSimpleWeights()
    {
        $min = 1;
        $max = 10;
        $rate = 2;
        $generator = new GeometricWeighted($min, $max, $rate);

        $weight = 1;
        for ($i = $min; $i <= $max; $i++) {
            $this->assertEquals($weight, $generator->calculateWeight($i));
            $weight *= $rate;
        }
    }

    /**
     * Test that the generator produces accurate weights when the minimum value
     * is greater than one.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\GeometricWeighted<extended>
     */
    public function testShiftedWeights()
    {
        $min = 3;
        $max = 12;
        $rate = 2;
        $generator = new GeometricWeighted($min, $max, $rate);

        $weight = 1;
        for ($i = $min; $i <= $max; $i++) {
            $this->assertEquals($weight, $generator->calculateWeight($i));
            $weight *= $rate;
        }
    }

    /**
     * Check that the distribution of results from a generator is accurate
     * within its range.
     *
     * Since the sum of probabilities is greater than in a flat distribution,
     * addtional iterations need to be performed to get sufficient precision for
     * the least likely items.
     *
     * @param Weighted $generator
     * @param int $min
     * @param int $max
     * @param int|float $rate
     */
    protected function checkDistribution(\DrupalReleaseDate\NumberGenerator\Random\Weighted $generator, $min, $max, $rate)
    {
        $range = $max - $min + 1;
        $probabilitySum = (1 - pow($rate, $range)) / (1 - $rate);

        $results = array_fill($min, $range, 0);

        for ($i = 0; $i < (RANDOM_BASE_ITERATIONS * $probabilitySum); $i++) {
            $results[$generator->generate()]++;
        }

        // TODO make the required results adaptive based on the range of the generator
        //      and the number of iterations performed.
        $weight = 1.0;
        foreach ($results as $value => $count) {
            $this->assertEquals($weight, $count / RANDOM_BASE_ITERATIONS, '', 0.2);
            $weight *= $rate;
        }
    }

    /**
     * Test that the generator produces a Geometricaly increasing distribution
     * of results when an integer rate is provided.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\GeometricWeighted<extended>
     */
    public function testIntegerRateDistribution()
    {
        $min = 3;
        $max = 8;
        $rate = 2;
        $generator = new GeometricWeighted($min, $max, $rate);
        $this->checkDistribution($generator, $min, $max, $rate);
    }

    /**
     * Test that the generator produces a Geometricaly increasing distribution
     * of results when a float value (greater than one) is provided.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\GeometricWeighted<extended>
     */
    public function testFloatRateDistribution()
    {
        $min = 3;
        $max = 8;
        $rate = 1.5;
        $generator = new GeometricWeighted($min, $max, $rate);
        $this->checkDistribution($generator, $min, $max, $rate);
    }

    /**
     * Test that the generator produces a Geometricaly increasing distribution
     * of results when a float value (less than one) is provided.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\GeometricWeighted<extended>
     */
    public function testSmallFloatRateDistribution()
    {
        $min = 3;
        $max = 8;
        $rate = 0.75;
        $generator = new GeometricWeighted($min, $max, $rate);
        $this->checkDistribution($generator, $min, $max, $rate);
    }
}
