<?php
namespace DrupalReleaseDate\Tests;

use DrupalReleaseDate\EstimateDistribution;

class EstimateDistributionTest extends \PHPUnit_Framework_TestCase
{


    /**
     * Test Failure Counting.
     *
     * @covers \DrupalReleaseDate\EstimateDistribution::__construct
     * @covers \DrupalReleaseDate\EstimateDistribution::failure
     * @covers \DrupalReleaseDate\EstimateDistribution::getFailures
     */
    public function testFailureCount()
    {
        $estimates = new EstimateDistribution();
        $this->assertEquals(0, $estimates->getFailures());

        $estimates->failure();
        $this->assertEquals(1, $estimates->getFailures());

        $estimates->failure();
        $estimates->failure();
        $this->assertEquals(3, $estimates->getFailures());
    }

    /**
     * Test an average where all buckets have a single item.
     *
     * @covers \DrupalReleaseDate\EstimateDistribution::__construct
     * @covers \DrupalReleaseDate\EstimateDistribution::success
     * @covers \DrupalReleaseDate\EstimateDistribution::getAverage
     */
    public function testSimpleAverage()
    {
        $estimates = new EstimateDistribution();

        $estimates->success(1);
        $estimates->success(2);
        $estimates->success(3);
        $estimates->success(4);
        $estimates->success(5);

        $this->assertEquals(3, $estimates->getAverage());
    }

    /**
     * Test an average where some buckets have multiple values.
     *
     * @covers \DrupalReleaseDate\EstimateDistribution::__construct
     * @covers \DrupalReleaseDate\EstimateDistribution::success
     * @covers \DrupalReleaseDate\EstimateDistribution::getAverage
     */
    public function testWeightedAverage()
    {
        $estimates = new EstimateDistribution();

        $estimates->success(1);
        $estimates->success(1);
        $estimates->success(3);
        $estimates->success(5);
        $estimates->success(5);

        $this->assertEquals(3, $estimates->getAverage());
    }

    /**
     * Test a median where all buckets have a single item.
     *
     * @covers \DrupalReleaseDate\EstimateDistribution::__construct
     * @covers \DrupalReleaseDate\EstimateDistribution::success
     * @covers \DrupalReleaseDate\EstimateDistribution::getMedian
     */
    public function testSimpleMedian()
    {
        $estimates = new EstimateDistribution();

        $estimates->success(1);
        $estimates->success(2);
        $estimates->success(3);
        $estimates->success(4);
        $estimates->success(5);

        $this->assertEquals(3, $estimates->getMedian());
    }

    /**
     * Test a median where some buckets have multiple values.
     *
     * @covers \DrupalReleaseDate\EstimateDistribution::__construct
     * @covers \DrupalReleaseDate\EstimateDistribution::success
     * @covers \DrupalReleaseDate\EstimateDistribution::getMedian
     */
    public function testWeightedMedian()
    {
        $estimates = new EstimateDistribution();

        $estimates->success(1);
        $estimates->success(1);
        $estimates->success(3);
        $estimates->success(5);
        $estimates->success(5);

        $this->assertEquals(3, $estimates->getMedian());
    }

    /**
     * Test a median when values are not entered in increasing order.
     *
     * @covers \DrupalReleaseDate\EstimateDistribution::__construct
     * @covers \DrupalReleaseDate\EstimateDistribution::success
     * @covers \DrupalReleaseDate\EstimateDistribution::getMedian
     */
    public function testUnorderedMedian()
    {
        $estimates = new EstimateDistribution();

        $estimates->success(5);
        $estimates->success(1);
        $estimates->success(5);
        $estimates->success(3);
        $estimates->success(1);

        $this->assertEquals(3, $estimates->getMedian());
    }

    /**
     * Test a median when failures are included in the calculation
     *
     * @covers \DrupalReleaseDate\EstimateDistribution::getMedian
     * @uses \DrupalReleaseDate\EstimateDistribution::__construct
     * @uses \DrupalReleaseDate\EstimateDistribution::success
     */
    public function testMedianWithFailures()
    {
        $estimates = new EstimateDistribution();

        $estimates->success(1);
        $estimates->success(2);
        $estimates->success(3);
        $estimates->success(4);
        $estimates->success(5);

        $estimates->failure();
        $estimates->failure();

        $this->assertEquals(3, $estimates->getMedian());
        $this->assertEquals(4, $estimates->getMedian(true));
    }

    /**
     * Test a median when failures are included in the calculation
     *
     * @expectedException \RuntimeException
     *
     * @covers \DrupalReleaseDate\EstimateDistribution::getMedian
     * @uses \DrupalReleaseDate\EstimateDistribution::__construct
     * @uses \DrupalReleaseDate\EstimateDistribution::success
     */
    public function testMedianWithTooManyFailures()
    {
        $estimates = new EstimateDistribution();

        $estimates->success(1);
        $estimates->success(2);
        $estimates->success(3);

        $estimates->failure();
        $estimates->failure();
        $estimates->failure();
        $estimates->failure();

        $estimates->getMedian(true);
    }
}
