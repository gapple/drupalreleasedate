<?php
namespace DrupalReleaseDate\Tests\Random;

use DrupalReleaseDate\Random\Random;

class RandomTest extends \PHPUnit_Framework_TestCase {

    /**
     * Test that a negative minimum value is not accepted.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Minimum value must be a positive integer
     */
    function testNegativeMin()
    {
        new Random(-1, 1);
    }

    /**
     * Test that a maximum value smaller than the minimum is not accepted.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Maximum value must be a positive integer greater than minimum value
     */
    function testSmallerMax()
    {
        new Random(5, 2);
    }

    /**
     * Test that non-integer values are not accepted as a minimum value.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Minimum value must be a positive integer
     */
    function testNonIntegerMin()
    {
        new Random(1.5, 2);
    }

    /**
     * Test that non-integer values are not accepted as a maximum value.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Maximum value must be a positive integer greater than minimum value
     */
    function testNonIntegerMax()
    {
        new Random(1, 2.5);
    }

    /**
     * Test that the generator only returns results in the specified range.
     */
    function testResultsWithinRange() {
        $min = 1;
        $max = 10;
        $iterations = 1000;

        $generator = new Random($min, $max);

        for ($i = 0; $i < $iterations; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     * Test that the generator produces a relatively flat distribution of results.
     */
    function testDistribution() {
        $min = 1;
        $max = 10;
        $iterations = 10000;

        $range = $max - $min + 1;
        $expected = $iterations / $range;

        $generator = new Random($min, $max);

        $results = array_fill($min, $range, 0);

        for ($i = 0; $i < $iterations; $i++) {
            $results[$generator->generate()]++;
        }

        foreach ($results as $value => $count) {
            $this->assertEquals(1.0, $count / $expected, '', 0.1);
        }
    }
}
