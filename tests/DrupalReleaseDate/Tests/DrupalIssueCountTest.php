<?php
namespace DrupalReleaseDate\Tests;

use Guzzle\Plugin\Mock\MockPlugin;
use DrupalReleaseDate\DrupalIssueCount;

class DrupalReleaseDateTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test a count request that encounters an error in the response.
     */
    public function testFailedCount()
    {

        $mockPlugin = new MockPlugin();
        $client = new \Guzzle\Http\Client();
        $client->addSubscriber($mockPlugin);

        $mockPlugin->addResponse(new \Guzzle\Http\Message\Response(503));

        $issueCounter = new DrupalIssueCount($client);

        $issueCount = $issueCounter->getCounts(
            array(),
            array(
                'test' => array(),
            )
        );

        $this->assertNull($issueCount['test']);
    }

    /**
     * Test a count request that can be satisfied by a single page.
     */
    public function testSinglePageCount()
    {

        $responses = array(
            MockPlugin::getMockFile(TEST_RESOURCE_PATH . '/http/drupal_org_critical_bugs')
        );

        $mockPlugin = new MockPlugin($responses);
        $client = new \Guzzle\Http\Client();
        $client->addSubscriber($mockPlugin);

        $issueCounter = new DrupalIssueCount($client);

        $issueCount = $issueCounter->getCounts(
            array(
                'status' => array(
                    1, // Active
                    13, // Needs work
                    8, // Needs review
                    14, // Reviewed & tested by the community
                    15, // Patch (to be ported)
                    4, // Postponed
                ),
            ),
            array(
                'critical_bugs' => array(
                    'priorities' => array(400),
                    'categories' => array(1),
                ),
            )
        );

        $this->assertEquals(49, $issueCount['critical_bugs']);
    }

    /**
     * Test a count request that cannot be satisfied with only the first page
     * of results.
     */
    public function testMultiPageCount()
    {

        $responses = array(
            MockPlugin::getMockFile(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_0'),
            MockPlugin::getMockFile(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_1'),
            MockPlugin::getMockFile(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_2'),
            MockPlugin::getMockFile(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_3'),
            MockPlugin::getMockFile(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_4'),
            MockPlugin::getMockFile(TEST_RESOURCE_PATH . '/http/drupal_org_major_bugs_5'),
        );

        $mockPlugin = new MockPlugin($responses);
        $client = new \Guzzle\Http\Client();
        $client->addSubscriber($mockPlugin);

        $issueCounter = new DrupalIssueCount($client);

        $issueCount = $issueCounter->getCounts(
            array(
                'status' => array(
                    1, // Active
                    13, // Needs work
                    8, // Needs review
                    14, // Reviewed & tested by the community
                    15, // Patch (to be ported)
                    4, // Postponed
                ),
            ),
            array(
                'major_bugs' => array(
                    'priorities' => array(300),
                    'categories' => array(1),
                ),
            )
        );

        $this->assertEquals(271, $issueCount['major_bugs']);
    }
}
