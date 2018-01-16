<?php
namespace DrupalReleaseDate\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use DrupalReleaseDate\DrupalIssueCount;

/**
 * Test retrieving correct issue counts from Drupal.org.
 */
class DrupalIssueCountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockHandler
     */
    protected $mockHandler;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var DrupalIssueCount
     */
    protected $issueCounter;


    public function setUp()
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
        $this->client = new Client([
            'handler' => HandlerStack::create($this->mockHandler)
        ]);

        $this->issueCounter = new DrupalIssueCount($this->client);
    }

    /**
     * Test a count request that encounters an error in the response.
     */
    public function testFailedCount()
    {
        $this->mockHandler->append(new Response(503));

        $issueCount = $this->issueCounter->getCounts(
            [],
            [
                'test' => [],
            ]
        );

        $this->assertNull($issueCount['test']);
    }

    /**
     * Test a count request that can be satisfied by a single page.
     */
    public function testSinglePageCount()
    {
        $this->mockHandler->append(
            new Response(200, [], fopen(TEST_RESOURCE_PATH . '/http/drupal_org_critical_bugs', 'r'))
        );

        $issueCount = $this->issueCounter->getCounts(
            [
                'status' => [
                    1, // Active
                    13, // Needs work
                    8, // Needs review
                    14, // Reviewed & tested by the community
                    15, // Patch (to be ported)
                    4, // Postponed
                ],
            ],
            [
                'critical_bugs' => [
                    'priorities' => [400],
                    'categories' => [1],
                ],
            ]
        );

        $this->assertEquals(49, $issueCount['critical_bugs']);
    }

    /**
     * Test a count request that cannot be satisfied with only the first page
     * of results.
     */
    public function testMultiPageCount()
    {
        $this->mockHandler->append(
            new Response(200, [], fopen(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_0', 'r')),
            new Response(200, [], fopen(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_1', 'r')),
            new Response(200, [], fopen(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_2', 'r')),
            new Response(200, [], fopen(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_3', 'r')),
            new Response(200, [], fopen(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_4', 'r')),
            new Response(200, [], fopen(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_5', 'r'))
        );

        $issueCount = $this->issueCounter->getCounts(
            [
                'status' => [
                    1, // Active
                    13, // Needs work
                    8, // Needs review
                    14, // Reviewed & tested by the community
                    15, // Patch (to be ported)
                    4, // Postponed
                ],
            ],
            [
                'major_bugs' => [
                    'priorities' => [300],
                    'categories' => [1],
                ],
            ]
        );

        $this->assertEquals(271, $issueCount['major_bugs']);
    }
}
