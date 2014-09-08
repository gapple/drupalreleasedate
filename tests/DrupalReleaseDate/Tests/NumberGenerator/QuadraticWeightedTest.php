<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\NumberGenerator\Cyclic as CyclicGenerator;
use DrupalReleaseDate\NumberGenerator\QuadraticWeighted;

class QuadraticWeightedTest extends \PHPUnit_Framework_TestCase
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
     * @todo Calculate for differing co-efficients (currently only f(x) = x^2 + 1).
     *
     * @param integer $min
     * @param integer $max
     * @return integer
     */
    protected function calculateProbabilitySum($min, $max) {
        $range = $max - $min + 1;
        return ($range - 1) * ($range) * (2 * ($range - 1) + 1) / 6 + $range;
    }

    /**
     * Test that the generator only returns results in the specified range.
     *
     * @covers \DrupalReleaseDate\NumberGenerator\QuadraticWeighted<extended>
     */
    function testRange()
    {
        $min = 2;
        $max = 15;

        $probabilitySum = $this->calculateProbabilitySum($min, $max);

        $generator = new QuadraticWeighted($this->weightGeneratorStub, $min, $max);

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
     *
     * @covers \DrupalReleaseDate\NumberGenerator\QuadraticWeighted<extended>
     */
    function testWeights()
    {
        $generator = new QuadraticWeighted($this->weightGeneratorStub, 2, 11);

        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals(pow($i, 2) + 1, $generator->calculateWeight($i));
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
     * @covers \DrupalReleaseDate\NumberGenerator\QuadraticWeighted<extended>
     * @uses \DrupalReleaseDate\NumberGenerator\Cyclic
     */
    function testDistribution()
    {
        $min = 3;
        $max = 15;
        $generator = new QuadraticWeighted(new CyclicGenerator(), $min, $max);

        $probabilitySum = $this->calculateProbabilitySum($min, $max);

        $results = array_fill($min, $max - $min + 1, 0);

        for ($i = 0; $i < $probabilitySum * 2; $i++) {
            $results[$generator->generate()]++;
        }

        foreach ($results as $key => $count) {
            $this->assertEquals((pow($key - $min, 2) + 1) * 2, $count);
        }
    }
}
