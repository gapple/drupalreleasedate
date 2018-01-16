<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\NumberGenerator\Cyclic as CyclicGenerator;
use DrupalReleaseDate\NumberGenerator\GeometricWeighted;
use DrupalReleaseDate\NumberGenerator\NumberGeneratorInterface;
use PHPUnit\Framework\TestCase;

class GeometricWeightedTest extends TestCase
{

    /**
     * @var NumberGeneratorInterface
     */
    protected $weightGeneratorStub;

    public function setUp()
    {
        $this->weightGeneratorStub = $this->getMockBuilder('\DrupalReleaseDate\NumberGenerator\Cyclic')
          ->disableOriginalConstructor()
          ->getMock();
    }

    /**
     * Calculate the sum of probabilities for a generator with the specified attributes.
     *
     * @param integer $min
     * @param integer $max
     * @param integer $rate
     * @return integer
     */
    protected function calculateProbabilitySum($min, $max, $rate = 1) {
        $range = $max - $min + 1;
        return (1 - pow($rate, $range)) / (1 - $rate);
    }

    /**
     * Test that a rate of zero is not accepted.
     *
     * This would result in every value past the first not receiving any
     * weight.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Rate must be greater than 0
     *
     * @covers \DrupalReleaseDate\NumberGenerator\GeometricWeighted<extended>
     */
    public function testZeroRate()
    {
        new GeometricWeighted($this->weightGeneratorStub, 1, 10, 0);
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
     * @covers \DrupalReleaseDate\NumberGenerator\GeometricWeighted<extended>
     */
    public function testNegativeRate()
    {
        new GeometricWeighted($this->weightGeneratorStub, 1, 10, -2);
    }

    /**
     * Test that the generator only returns results in the specified range.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\GeometricWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testRange()
    {
        $min = 2;
        $max = 15;
        $rate = 2;

        $range = $max - $min + 1;
        $probabilitySum = (1 - pow($rate, $range)) / (1 - $rate);

        $generator = new GeometricWeighted(new CyclicGenerator(0, 1, $probabilitySum / 7), $min, $max, $rate);

        for ($i = 0; $i < 100; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     * Test that the generator produces accurate weights.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\GeometricWeighted<extended>
     */
    public function testWeights()
    {
        $rate = 2;
        $generator = new GeometricWeighted($this->weightGeneratorStub, 2, 11, $rate);

        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals(pow($rate, $i), $generator->calculateWeight($i));
        }
    }

    /**
     * Check that the distribution of results from a generator is accurate
     * within its range.
     *
     * Since the sum of probabilities is greater than in a flat distribution,
     * additional iterations need to be performed to get sufficient precision
     * for the least likely items.
     *
     * @param NumberGeneratorInterface $generator
     * @param int $min
     * @param int $max
     * @param int|float $rate
     * @param int|float $step
     */
    protected function checkDistribution(NumberGeneratorInterface $generator, $min, $max, $rate, $step = 1)
    {
        $probabilitySum = $this->calculateProbabilitySum($min, $max, $rate);

        $results = array_fill($min, $max - $min + 1, 0);

        for ($i = 0; $i < ($probabilitySum * 10 / $step); $i++) {
            $results[$generator->generate()]++;
        }

        foreach ($results as $value => $count) {
            $this->assertEquals(pow($rate, $value - $min) * 10 / $step, $count, '', 10);
        }
    }

    /**
     * Test that the generator produces a Geometrically increasing distribution
     * of results when an integer rate is provided.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\GeometricWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testIntegerRateDistribution()
    {
        $min = 3;
        $max = 8;
        $rate = 2;
        $generator = new GeometricWeighted(new CyclicGenerator(), $min, $max, $rate);
        $this->checkDistribution($generator, $min, $max, $rate);
    }

    /**
     * Test that the generator produces a Geometrically increasing distribution
     * of results when a float value (greater than one) is provided.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\GeometricWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testFloatRateDistribution()
    {
        $min = 3;
        $max = 8;
        $rate = 1.5;
        $generator = new GeometricWeighted(new CyclicGenerator(0, 1, 0.1), $min, $max, $rate);
        $this->checkDistribution($generator, $min, $max, $rate, 0.1);
    }

    /**
     * Test that the generator produces a Geometrically increasing distribution
     * of results when a float value (less than one) is provided.
     *
     * This test can be problematic because the fractional differences in weight
     * of successive values requires very small steps in the Cyclic generator in
     * order to not be skewed by the imprecision of floating point comparisons of
     * particular fractions.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\GeometricWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    public function testSmallFloatRateDistribution()
    {
        $min = 3;
        $max = 8;
        $rate = 0.75;
        $generator = new GeometricWeighted(new CyclicGenerator(0, 1, 0.05), $min, $max, $rate);
        $this->checkDistribution($generator, $min, $max, $rate, 0.05);
    }
}
