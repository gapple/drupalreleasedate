<?php
namespace DrupalReleaseDate\Tests\NumberGenerator\Random;

use DrupalReleaseDate\NumberGenerator\Random\Basic;

/**
 *
 * @coversDefaultClass \DrupalReleaseDate\NumberGenerator\Random\Basic;
 */
class BasicTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the generator only returns results in the specified range.
     *
     * @group random
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\Basic
     */
    function testResultsWithinRange()
    {
        $min = 1;
        $max = 10;

        $generator = new Basic($min, $max);

        for ($i = 0; $i < RANDOM_BASE_ITERATIONS; $i++) {
            $rand = $generator->generate();
            $this->assertGreaterThanOrEqual($min, $rand);
            $this->assertLessThanOrEqual($max, $rand);
        }
    }

    /**
     * Test that the generator produces a relatively flat distribution of results.
     *
     * @group random
     *
     * @covers \DrupalReleaseDate\NumberGenerator\Random\Basic
     */
    function testDistribution()
    {
        $min = 1;
        $max = 10;

        $range = $max - $min + 1;

        $generator = new Basic($min, $max);

        $results = array_fill($min, $range, 0);

        for ($i = 0; $i < $range * RANDOM_BASE_ITERATIONS; $i++) {
            $results[$generator->generate()]++;
        }

        foreach ($results as $value => $count) {
            $this->assertEquals(1.0, $count / RANDOM_BASE_ITERATIONS, '', 0.1);
        }
    }
}
