<?php
namespace DrupalReleaseDate\Tests\NumberGenerator;

use DrupalReleaseDate\NumberGenerator\Cyclic;

/**
 * @coversDefaultClass \DrupalReleaseDate\NumberGenerator\Cyclic<extended>
 */
class CyclicTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test that a negative minimum value is not accepted.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Minimum value must be a positive integer
     */
    function testNegativeMin()
    {
        new Cyclic(-1, 1);
    }

    /**
     * Test that a maximum value smaller than the minimum is not accepted.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Maximum value must be a positive integer greater than minimum value
     */
    function testSmallerMax()
    {
        new Cyclic(5, 2);
    }

    /**
     * Test that non-integer values are not accepted as a minimum value.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Minimum value must be a positive integer
     */
    function testNonIntegerMin()
    {
        new Cyclic(1.5, 2);
    }

    /**
     * Test that non-integer values are not accepted as a maximum value.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Maximum value must be a positive integer greater than minimum value
     */
    function testNonIntegerMax()
    {
        new Cyclic(1, 2.5);
    }

    /**
     * Test that changing the minimum value to a negative value is not accepted.
     *
     * @expectedException InvalidArgumentException
     */
    function testChangeToNegativeMin()
    {
        $generator = new Cyclic(1, 10);
        $generator->setMin(-1);
    }

    /**
     * Test that changing the minimum value above the maximum is not accepted.
     *
     * @expectedException InvalidArgumentException
     */
    function testChangeToHighMin()
    {
        $generator = new Cyclic(1, 10);
        $generator->setMin(12);
    }

    /**
     * Test that changing the maximum below the minimum is not accepted.
     *
     * @expectedException InvalidArgumentException
     */
    function testChangeToLowMax()
    {
        $generator = new Cyclic(10, 20);
        $generator->setMax(5);
    }

    /**
     * Test that the generator only returns results in the specified range.
     */
    function testResultsWithinRange()
    {
        $min = 1;
        $max = 10;

        $generator = new Cyclic($min, $max);

        for ($i = 0; $i < 11; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }
}
