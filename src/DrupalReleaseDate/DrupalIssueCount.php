<?php
namespace DrupalReleaseDate;

use Symfony\Component\DomCrawler;

class DrupalIssueCount
{
    protected $client;

    /**
     * Set of criteria to fetch issues for.
     *
     * @var array
     */
    protected static $fetchCategories = array(
        'critical_bugs' => array(
            'priorities' => array(400),
            'categories' => array(1),
        ),
        'critical_tasks' => array(
            'priorities' => array(400),
            'categories' => array(2),
        ),
        'major_bugs' => array(
            'priorities' => array(300),
            'categories' => array(1),
        ),
        'major_tasks' => array(
            'priorities' => array(300),
            'categories' => array(2),
        ),
        'normal_bugs' => array(
            'priorities' => array(200),
            'categories' => array(1),
        ),
        'normal_tasks' => array(
            'priorities' => array(200),
            'categories' => array(2),
        ),
    );

    /**
     * Set of status IDs to include in issue counts.
     *
     * @var array
     */
    protected static $fetchStatusIds = array(
        1, // Active
        13, // Needs work
        8, // Needs review
        14, // Reviewed & tested by the community
        15, // Patch (to be ported)
        4, // Postponed
        // 16, // Postponed (maintainer needs more info)
    );

    public function __construct($userAgent = null)
    {
        $this->client = new \Guzzle\Http\Client('https://drupal.org/');

        if (!empty($userAgent)) {
            $this->client->setUserAgent($userAgent, true);
        }
    }

    /**
     * Get the count of issues against Drupal 8.
     *
     * @return array
     */
    public function getD8Counts()
    {
        return $this->getCounts(
            array(
                'version' => array('8.x'),
                'status' => static::$fetchStatusIds,
            ),
            static::$fetchCategories
        );
    }

    /**
     * Get the count of issues against Drupal 9.
     *
     * @return array
     */
    public function getD9Counts()
    {
        return $this->getCounts(
            array(
                'version' => array('9.x'),
                'status' => static::$fetchStatusIds,
            ),
            static::$fetchCategories
        );
    }

    /**
     * Get the issues counts from Drupal.org for the specified parameters.
     *
     * @param array $commonParameters
     *   An array of query parameters to use in all requests.
     * @param array $fetchSet
     *   An array to specify separate requests to make and their unique
     *   parameters, in the format
     *     'setKey' => array(
     *       'parameterKey' => 'parameterValue',
     *     )
     */
    public function getCounts($commonParameters, $fetchSet)
    {

        $request = $this->client->get('/project/issues/search/drupal');

        $query = $request->getQuery();
        $query->merge($commonParameters);

        $results = array();
        foreach ($fetchSet as $fetchKey => $fetchParameters) {
            // Override each of the unique values for this fetch set.
            foreach ($fetchParameters as $parameterKey => $parameterValue) {
                $query->set($parameterKey, $parameterValue);
            }
            try {
                $results[$fetchKey] = $this->getCount($request);
            } catch (Exception $e) {
                $results[$fetchKey] = null;
            }
        }

        return $results;
    }

    /**
     * Get the issue count from the provided request.
     *
     *
     * @param array $request
     *   Guzzle request for the first page of results.
     * @return number
     *   The total number of issues for the search paramaters of the request.
     */
    public function getCount(\Guzzle\Http\Message\RequestInterface $request)
    {
        // Make sure page isn't set from a previous call on the same request object.
        $request->getQuery()->remove('page');

        $issueRowCount = 0;

        while(true) {
            $document = new DomCrawler\Crawler((string) $request->send()->getBody());
            $issueView = $document->filter('.view-project-issue-search-project-searchapi');

            $issueRowCount += $issueView
                ->filter('table.views-table tbody tr')
                ->reduce(function (DomCrawler\Crawler $element) {
                    // Drupal.org is returning rows where all cells are empty,
                    // which bumps up the count incorrectly.
                    return $element->filter('td')->first()->filter('a')->count() > 0;
                })
                ->count();

            $pagerNext = $issueView->filter('.pager-next a');

            if (!$pagerNext->count()) {
                break;
            }

            preg_match('/page=(\\d+)/', $pagerNext->attr('href'), $urlMatches);

            $request->getQuery()->set('page', (int) $urlMatches[1]);
        };

        return $issueRowCount;
    }
}
