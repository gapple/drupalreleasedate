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
     * Test that the generator increments and wraps correctly.
     */
    function testIntegerSteps()
    {
        $generator = new Cyclic(1,5);

        $this->assertEquals(1, $generator->generate());
        $this->assertEquals(2, $generator->generate());
        $this->assertEquals(3, $generator->generate());
        $this->assertEquals(4, $generator->generate());
        $this->assertEquals(5, $generator->generate());
        $this->assertEquals(1, $generator->generate());
    }


    /**
     * Test that the generator increments correctly with steps that are a float value.
     */
    function testFloatSteps()
    {
        $generator = new Cyclic(1,5, 0.1);

        // Must accommodate for float precision, but error should not accumulate with successive values.
        $this->assertEquals(0.1, $generator->generate(), '', 0.00001);
        $this->assertEquals(0.2, $generator->generate(), '', 0.00001);
        $this->assertEquals(0.3, $generator->generate(), '', 0.00001);
        $this->assertEquals(0.4, $generator->generate(), '', 0.00001);
        $this->assertEquals(0.5, $generator->generate(), '', 0.00001);
        $this->assertEquals(0.6, $generator->generate(), '', 0.00001);
        $this->assertEquals(0.7, $generator->generate(), '', 0.00001);
        $this->assertEquals(0.8, $generator->generate(), '', 0.00001);
        $this->assertEquals(0.9, $generator->generate(), '', 0.00001);
        $this->assertEquals(1.0, $generator->generate(), '', 0.00001);
        $this->assertEquals(1.1, $generator->generate(), '', 0.00001);
        for ($i = 0; $i < 35; $i++) {
            $generator->generate();
        }
        $this->assertEquals(4.7, $generator->generate(), '', 0.00001);
    }
}
