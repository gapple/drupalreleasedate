<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\Random\LinearWeightedRandom;

class LinearWeightedRandomTest extends \PHPUnit_Framework_TestCase {

    /**
     * Test that the generator only returns results in the specified range.
     */
    public function testRange() {
        $min = 2;
        $max = 15;
        $generator = new LinearWeightedRandom($min, $max);

        for ($i = 0; $i < RANDOM_BASE_ITERATIONS; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     * Test that the calculated weights for the generator are correct.
     */
    public function testSimpleWeights() {
        $min = 1;
        $max = 10;
        $generator = new LinearWeightedRandom($min, $max);

        $weight = 1;
        for ($i = $min; $i <= $max; $i++) {
            $this->assertEquals($weight, $generator->calculateWeight($i));
            $weight++;
        }
    }

    /**
     * Test that the calculated weights for the generator are correct.
     */
    public function testShiftedWeights() {
        $min = 3;
        $max = 12;
        $generator = new LinearWeightedRandom($min, $max);

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
     */
    public function testConstructNegativeWeight() {
        $generator = new LinearWeightedRandom(1, 10, -1, 5);
    }

    /**
     * Check that an exception is thrown if changing the minimum value results
     * in a negative calculated weight.
     *
     * @expectedException RangeException
     * @expectedExceptionMessage The value 7 was given a weight of -1
     */
    public function testSetMinNegativeWeight() {
        $generator = new LinearWeightedRandom(6, 10, -1, 5);
        $generator->setMin(1);
    }

    /**
     * Check that an exception is thrown if changing the maximum value results
     * in a negative calculated weight.
     *
     * @expectedException RangeException
     * @expectedExceptionMessage The value 7 was given a weight of -1
     */
    public function testSetMaxNegativeWeight() {
        $generator = new LinearWeightedRandom(1, 5, -1, 5);
        $generator->setMax(10);
    }

    /**
     * Check that the distribution of results from a generator is accurate
     * within its range.
     *
     * Since the sum of probabilities is greater than in a flat distribution,
     * addtional iterations need to be performed to get sufficient precision for
     * the least likely items.
     *
     * @param WeightedRandom $generator
     * @param int $min
     * @param int $max
     * @param
     */
    protected function checkDistribution(\DrupalReleaseDate\Random\WeightedRandom $generator, $min, $max, $slope = 1, $base = 1) {

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
     */
    public function testDistribution() {
        $generator = new LinearWeightedRandom(3, 8);

        $this->checkDistribution($generator, 3, 8);
    }

    /**
     * Test that a generator produces a correct distribution if the min value is
     * changed.
     */
    public function testDistributionChangeMin() {
        $generator = new LinearWeightedRandom(4, 8);

        $generator->setMin(3);
        $this->checkDistribution($generator, 3, 8);

        $generator->setMin(5);
        $this->checkDistribution($generator, 5, 8);
    }

    /**
     * Test that a generator produces a correct distribution if the max value is
     * changed.
     */
    public function testDistributionChangeMax() {

        $generator = new LinearWeightedRandom(4, 7);

        $generator->setMax(9);
        $this->checkDistribution($generator, 4, 9);

        $generator->setMax(8);
        $this->checkDistribution($generator, 4, 8);
    }

    /**
     * Test that generator produces a correct distribution when the weight of
     * the initial value is changed.
     */
    public function testDistributionChangeBase() {

        $generator = new LinearWeightedRandom(1, 5, 1, 2);
        $this->checkDistribution($generator, 1, 5, 1 ,2);

        $generator = new LinearWeightedRandom(1, 5, 1, 10);
        $this->checkDistribution($generator, 1, 5, 1, 10);
    }

    /**
     * Test that generator produces a correct distribution when the slope of
     * weights is changed.
     */
    public function testDistributionChangeSlope() {

        $generator = new LinearWeightedRandom(2, 7, 2.5);
        $this->checkDistribution($generator, 2, 7, 2.5);

        $generator = new LinearWeightedRandom(2, 7, 0.5);
        $this->checkDistribution($generator, 2, 7, 0.5);
    }

    /**
     * Test that generator produces a correct distribution when both the weight
     * of the initial value is changed, and the slope of weights is changed
     */
    public function testDistributionChangeBaseAndSlope() {

        $generator = new LinearWeightedRandom(2, 7, 2.5, 5);
        $this->checkDistribution($generator, 2, 7, 2.5, 5);

        $generator = new LinearWeightedRandom(2, 7, 0.5, 2);
        $this->checkDistribution($generator, 2, 7, 0.5, 2);
    }
}
