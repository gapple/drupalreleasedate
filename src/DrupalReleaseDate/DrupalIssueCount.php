<?php
namespace DrupalReleaseDate;

use Symfony\Component\DomCrawler;

class DrupalIssueCount
{
    /**
     * Guzzle client used for requests.
     * @var \Guzzle\Http\ClientInterface
     */
    protected $client;

    public function __construct(\Guzzle\Http\ClientInterface $client = null)
    {
        if (empty($client)) {
            $client = new \Guzzle\Http\Client();
        }

        $this->client = $client;
        $this->client->setBaseUrl('https://drupal.org/');
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
        $results = array();
        foreach ($fetchSet as $fetchKey => $fetchParameters) {
            $request = $this->client->get('/project/issues/search/drupal');

            $request->getQuery()
                ->overwriteWith($commonParameters)
                ->overwriteWith($fetchParameters);

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
