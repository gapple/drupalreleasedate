<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\NumberGenerator\Random\LinearWeighted;

class LinearWeightedTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test that the generator only returns results in the specified range.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testRange()
    {
        $min = 2;
        $max = 15;
        $generator = new LinearWeighted($min, $max);

        for ($i = 0; $i < RANDOM_BASE_ITERATIONS; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     * Test that the calculated weights for the generator are correct.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testSimpleWeights()
    {
        $min = 1;
        $max = 10;
        $generator = new LinearWeighted($min, $max);

        $weight = 1;
        for ($i = $min; $i <= $max; $i++) {
            $this->assertEquals($weight, $generator->calculateWeight($i));
            $weight++;
        }
    }

    /**
     * Test that the calculated weights for the generator are correct.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testShiftedWeights()
    {
        $min = 3;
        $max = 12;
        $generator = new LinearWeighted($min, $max);

        $weight = 1;
        for ($i = $min; $i <= $max; $i++) {
            $this->assertEquals($weight, $generator->calculateWeight($i));
            $weight++;
        }
    }

    /**
     * Check that the constructor throws an exception if a calculated weight is
     * a negative number.
     *
     * @expectedException RangeException
     * @expectedExceptionMessage The value 7 was given a weight of -1
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testConstructNegativeWeight()
    {
        $generator = new LinearWeighted(1, 10, -1, 5);
    }

    /**
     * Check that an exception is thrown if changing the minimum value results
     * in a negative calculated weight.
     *
     * @expectedException RangeException
     * @expectedExceptionMessage The value 7 was given a weight of -1
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testSetMinNegativeWeight()
    {
        $generator = new LinearWeighted(6, 10, -1, 5);
        $generator->setMin(1);
    }

    /**
     * Check that an exception is thrown if changing the maximum value results
     * in a negative calculated weight.
     *
     * @expectedException RangeException
     * @expectedExceptionMessage The value 7 was given a weight of -1
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testSetMaxNegativeWeight()
    {
        $generator = new LinearWeighted(1, 5, -1, 5);
        $generator->setMax(10);
    }

    /**
     * Check that the distribution of results from a generator is accurate
     * within its range.
     *
     * Since the sum of probabilities is greater than in a flat distribution,
     * additional iterations need to be performed to get sufficient precision for
     * the least likely items.
     *
     * @param \DrupalReleaseDate\NumberGenerator\NumberGeneratorInterface $generator
     * @param int $min
     * @param int $max
     * @param int|float $slope
     * @param int|float $base
     */
    protected function checkDistribution(\DrupalReleaseDate\NumberGenerator\NumberGeneratorInterface $generator, $min, $max, $slope = 1, $base = 1)
    {

        $range = $max - $min + 1;
        $probabilitySum = $range / 2 * ((($range - 1) * $slope + $base) + $base);

        $results = array_fill($min, $range, 0);

        for ($i = 0; $i < (RANDOM_BASE_ITERATIONS * $probabilitySum); $i++) {
            $results[$generator->generate()]++;
        }

        // TODO make the required results adaptive based on the range of the generator
        //      and the number of iterations performed.
        $weight = (float) $base;
        foreach ($results as $value => $count) {
            $this->assertEquals($weight, $count / (RANDOM_BASE_ITERATIONS), '', 0.2);
            $weight += $slope;
        }
    }

    /**
     * Test that the generator produces a linearly increasing distribution of results.
     * (e.g. the second item should be twice as likely as the first, the third item
     *  three times as likely, etc)
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testDistribution()
    {
        $generator = new LinearWeighted(3, 8);

        $this->checkDistribution($generator, 3, 8);
    }

    /**
     * Test that a generator produces a correct distribution if the min value is
     * changed.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testDistributionChangeMin()
    {
        $generator = new LinearWeighted(4, 8);

        $generator->setMin(3);
        $this->checkDistribution($generator, 3, 8);

        $generator->setMin(5);
        $this->checkDistribution($generator, 5, 8);
    }

    /**
     * Test that a generator produces a correct distribution if the max value is
     * changed.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testDistributionChangeMax()
    {

        $generator = new LinearWeighted(4, 7);

        $generator->setMax(9);
        $this->checkDistribution($generator, 4, 9);

        $generator->setMax(8);
        $this->checkDistribution($generator, 4, 8);
    }

    /**
     * Test that generator produces a correct distribution when the weight of
     * the initial value is changed.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testDistributionChangeBase()
    {

        $generator = new LinearWeighted(1, 5, 1, 2);
        $this->checkDistribution($generator, 1, 5, 1 ,2);

        $generator = new LinearWeighted(1, 5, 1, 10);
        $this->checkDistribution($generator, 1, 5, 1, 10);
    }

    /**
     * Test that generator produces a correct distribution when the slope of
     * weights is changed.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testDistributionChangeSlope()
    {

        $generator = new LinearWeighted(2, 7, 2.5);
        $this->checkDistribution($generator, 2, 7, 2.5);

        $generator = new LinearWeighted(2, 7, 0.5);
        $this->checkDistribution($generator, 2, 7, 0.5);
    }

    /**
     * Test that generator produces a correct distribution when both the weight
     * of the initial value is changed, and the slope of weights is changed
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\LinearWeighted<extended>
     */
    public function testDistributionChangeBaseAndSlope()
    {

        $generator = new LinearWeighted(2, 7, 2.5, 5);
        $this->checkDistribution($generator, 2, 7, 2.5, 5);

        $generator = new LinearWeighted(2, 7, 0.5, 2);
        $this->checkDistribution($generator, 2, 7, 0.5, 2);
    }
}
