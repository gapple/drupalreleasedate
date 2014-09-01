<?php
namespace DrupalReleaseDate\Sampling\Tests;

use DrupalReleaseDate\Sampling\Sample;
use DrupalReleaseDate\Sampling\SampleSet;
use DrupalReleaseDate\Sampling\SampleSetRandomSampleSelector;

class SampleSetRandomSampleSelectorTest extends \PHPUnit_Framework_TestCase
{

    protected $sampleSetStub;
    protected $randomStub;
    protected $sampleSelector;

    protected function setUp() {
        $this->sampleSetStub = $this->getMock('\DrupalReleaseDate\Sampling\SampleSet');

        $this->randomStub = $this->getMockBuilder('\DrupalReleaseDate\NumberGenerator\Random\Basic')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sampleSelector = new SampleSetRandomSampleSelector($this->sampleSetStub, $this->randomStub);
    }
    /**
     * Test that the last item is requested from the sample set and returned to
     * the caller.
     *
     * @covers \DrupalReleaseDate\Sampling\SampleSetRandomSampleSelector
     */
    public function testGetLast()
    {
        $expectedObject = new \stdClass;

        $this->sampleSetStub->method('getLast')
             ->willReturn($expectedObject);

        $this->sampleSetStub->expects($this->once())
            ->method('getLast');

        $this->assertSame($expectedObject, $this->sampleSelector->getLastSample());
    }

    /**
     * Test that the selector gets and returns the appropriate sample based on
     * the provided random generator.
     *
     * @covers \DrupalReleaseDate\Sampling\SampleSetRandomSampleSelector
     */
    public function testGetRandom()
    {

        $this->randomStub->method('generate')
            ->will($this->onConsecutiveCalls(1, 2, 4));

        $expected1 = new \stdClass;
        $expected2 = new \stdClass;
        $expected3 = new \stdClass;
        $this->sampleSetStub->method('get')
            ->will($this->onConsecutiveCalls(
                $expected1,
                $expected2,
                $expected3
            ));

        $this->sampleSetStub->expects($this->exactly(3))
                 ->method('get')
                 ->withConsecutive(
                    array($this->equalTo(1)),
                    array($this->equalTo(2)),
                    array($this->equalTo(4))
                );

        $this->assertSame($expected1, $this->sampleSelector->getRandomSample());
        $this->assertSame($expected2, $this->sampleSelector->getRandomSample());
        $this->assertSame($expected3, $this->sampleSelector->getRandomSample());
    }
}
