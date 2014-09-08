<?php
namespace DrupalReleaseDate\Tests\NumberGenerator;

use DrupalReleaseDate\NumberGenerator\NumberGeneratorInterface;
use DrupalReleaseDate\NumberGenerator\Random;

class RandomTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the generator only returns results in the specified range.
     *
     * @group random
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random<extended>
     */
    function testIntegerResultsWithinRange()
    {
        $min = 1;
        $max = 10;

        $generator = new Random($min, $max);

        for ($i = 0; $i < 1000; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     * Test that the generator only returns results in the specified range.
     *
     * @group random
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random<extended>
     */
    function testFloatResultsWithinRange()
    {
        $min = 0;
        $max = 2;

        $generator = new Random($min, $max);
        $generator->setType(NumberGeneratorInterface::TYPE_FLOAT);

        for ($i = 0; $i < 1000; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     * Test that the generator produces a relatively flat distribution of results.
     *
     * Any individual value can not vary more than 10% from the expected value,
     * and the standard deviation must be within 5%.
     *
     * @group random
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random<extended>
     */
    function testIntegerDistribution()
    {
        $min = 1;
        $max = 10;

        $range = $max - $min + 1;

        $generator = new Random($min, $max);

        $results = array_fill($min, $range, 0);

        for ($i = 0; $i < $range * 1000; $i++) {
            $results[$generator->generate()]++;
        }
        $variance = 0;
        foreach ($results as $value => $count) {
            $this->assertEquals(1000, $count, '', 100);
            $variance += pow($count - 1000, 2) / $range;
        }

        // Check the standard deviation.
        $this->assertLessThan(50, sqrt($variance));
    }
}
