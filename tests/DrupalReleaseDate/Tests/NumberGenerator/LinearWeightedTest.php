<?php
namespace DrupalReleaseDate\Tests;

use DrupalReleaseDate\NumberGenerator\Cyclic as CyclicGenerator;
use DrupalReleaseDate\NumberGenerator\LinearWeighted;
use DrupalReleaseDate\NumberGenerator\NumberGeneratorInterface;

class LinearWeightedTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \DrupalReleaseDate\NumberGenerator\NumberGeneratorInterface
     */
    protected $weightGeneratorStub;

    public function setUp()
    {
        $this->weightGeneratorStub = $this->getMockBuilder('\DrupalReleaseDate\NumberGenerator\Cyclic')
          ->disableOriginalConstructor()
          ->getMock();
    }

    /**
     * Calculate the sum of probabilities for a generator with the specified range.
     *
     * @todo Calculate for differing co-efficients (currently only f(x) = x + 1).
     *
     * @param integer $min
     * @param integer $max
     * @param integer $slope
     * @param integer $base
     * @return integer
     */
    protected function calculateProbabilitySum($min, $max, $slope = 1, $base = 1) {
        $range = $max - $min + 1;
        return $range / 2 * ((($range - 1) * $slope + $base) + $base);
    }

    /**
     * Test that the generator only returns results in the specified range.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     */
    public function testRange()
    {
        $min = 2;
        $max = 15;

        $probabilitySum = $this->calculateProbabilitySum($min, $max);

        $generator = new LinearWeighted($this->weightGeneratorStub, $min, $max);

        $this->weightGeneratorStub
          ->method('generate')
          ->will($this->onConsecutiveCalls(
              -1,
              0,
              1,
              $probabilitySum / 2,
              $probabilitySum - 1,
              $probabilitySum,
              $probabilitySum + 1
            ));

        for ($i = 0; $i < 7; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     * Test that the calculated weights for the generator are correct.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     */
    public function testWeights()
    {
        $generator = new LinearWeighted($this->weightGeneratorStub, 2, 11);

        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals($i + 1, $generator->calculateWeight($i));
        }
    }

    /**
     * Check that the constructor throws an exception if a calculated weight is
     * a negative number.
     *
     * @expectedException RangeException
     * @expectedExceptionMessage The value at index 6 was given a weight of -1
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     */
    public function testConstructNegativeWeight()
    {
        new LinearWeighted($this->weightGeneratorStub, 1, 10, -1, 5);
    }

    /**
     * Check that an exception is thrown if changing the minimum value results
     * in a negative calculated weight.
     *
     * @expectedException RangeException
     * @expectedExceptionMessage The value at index 6 was given a weight of -1
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     */
    public function testSetMinNegativeWeight()
    {
        $generator = new LinearWeighted($this->weightGeneratorStub, 6, 10, -1, 5);
        $generator->setMin(1);
    }

    /**
     * Check that an exception is thrown if changing the maximum value results
     * in a negative calculated weight.
     *
     * @expectedException RangeException
     * @expectedExceptionMessage The value at index 6 was given a weight of -1
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     */
    public function testSetMaxNegativeWeight()
    {
        $generator = new LinearWeighted($this->weightGeneratorStub, 1, 5, -1, 5);
        $generator->setMax(10);
    }

    /**
     * Check that the distribution of results from a generator is accurate
     * within its range.
     *
     * The generator under test must use a Cyclic generator for its weights.
     *
     * @param NumberGeneratorInterface $generator
     * @param int $min
     * @param int $max
     * @param int|float $slope
     * @param int|float $base
     * @param int|float $step
     */
    protected function checkDistribution(NumberGeneratorInterface $generator, $min, $max, $slope = 1, $base = 1, $step = 1)
    {

        $probabilitySum = $this->calculateProbabilitySum($min, $max, $slope, $base);

        $results = array_fill($min, $max - $min + 1, 0);

        for ($i = 0; $i < ($probabilitySum * 10 / $step); $i++) {
            $results[$generator->generate()]++;
        }

        foreach ($results as $value => $count) {
            $this->assertEquals(($slope * ($value - $min) + $base) * 10 / $step, $count);
        }
    }

    /**
     * Test that the generator produces a linearly increasing distribution of results.
     * (e.g. the second item should be twice as likely as the first, the third item
     *  three times as likely, etc)
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testDistribution()
    {
        $generator = new LinearWeighted(new CyclicGenerator(), 3, 8);

        $this->checkDistribution($generator, 3, 8);
    }

    /**
     * Test that a generator produces a correct distribution if the min value is
     * changed.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testDistributionChangeMin()
    {
        $generator = new LinearWeighted(new CyclicGenerator(), 4, 8);

        $generator->setMin(3);
        $this->checkDistribution($generator, 3, 8);

        $generator->setMin(5);
        $this->checkDistribution($generator, 5, 8);
    }

    /**
     * Test that a generator produces a correct distribution if the max value is
     * changed.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testDistributionChangeMax()
    {

        $generator = new LinearWeighted(new CyclicGenerator(), 4, 7);

        $generator->setMax(9);
        $this->checkDistribution($generator, 4, 9);

        $generator->setMax(8);
        $this->checkDistribution($generator, 4, 8);
    }

    /**
     * Test that generator produces a correct distribution when the weight of
     * the initial value is changed from the default.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testDistributionChangeBase()
    {

        $generator = new LinearWeighted(new CyclicGenerator(), 1, 5, 1, 2);
        $this->checkDistribution($generator, 1, 5, 1 ,2);

        $generator = new LinearWeighted(new CyclicGenerator(), 1, 5, 1, 10);
        $this->checkDistribution($generator, 1, 5, 1, 10);
    }

    /**
     * Test that generator produces a correct distribution when the slope of
     * weights is changed from the default.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testDistributionChangeSlope()
    {

        $generator = new LinearWeighted(new CyclicGenerator(0, 1, 0.1), 2, 7, 2.5);
        $this->checkDistribution($generator, 2, 7, 2.5, 1, 0.1);

        $generator = new LinearWeighted(new CyclicGenerator(0, 1, 0.1), 2, 7, 0.5);
        $this->checkDistribution($generator, 2, 7, 0.5, 1, 0.1);
    }

    /**
     * Test that generator produces a correct distribution when both the weight
     * of the initial value, and the slope of weights are change from the
     * default values.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\LinearWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testDistributionChangeBaseAndSlope()
    {

        $generator = new LinearWeighted(new CyclicGenerator(0, 1, 0.1), 2, 7, 2.5, 5);
        $this->checkDistribution($generator, 2, 7, 2.5, 5, 0.1);

        $generator = new LinearWeighted(new CyclicGenerator(0, 1, 0.1), 2, 7, 0.5, 2);
        $this->checkDistribution($generator, 2, 7, 0.5, 2, 0.1);
    }
}
