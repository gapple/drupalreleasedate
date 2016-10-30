<?php
namespace DrupalReleaseDate;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class to retrieve issue counts from Drupal.org.
 */
class DrupalIssueCount
{
    /**
     * Guzzle client used for requests.
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * DrupalIssueCount constructor.
     *
     * @param \GuzzleHttp\ClientInterface|null $client
     */
    public function __construct(ClientInterface $client = null)
    {
        if (empty($client)) {
            $client = new Client();
        }
        $this->client = $client;
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
     * @return array
     */
    public function getCounts($commonParameters, $fetchSet)
    {
        $results = array();
        foreach ($fetchSet as $fetchKey => $fetchParameters) {
            $uri = (new Uri('https://drupal.org/project/issues/search/drupal'))
                ->withQuery($this->buildQuery(
                    array_merge($commonParameters, $fetchParameters)
                ));

            try {
                $results[$fetchKey] = $this->getCount($uri);
            } catch (\Exception $e) {
                $results[$fetchKey] = null;
            }
        }

        return $results;
    }

    /**
     * Convert parameters array to format required for query.
     *
     * @param $parameters
     * @return array
     */
    protected function buildQuery($parameters)
    {
        foreach ($parameters as $key => $values) {
            if (is_array($values)) {
                $parameters[$key . '[]'] = $values;
                unset($parameters[$key]);
            }
        }

        return \GuzzleHttp\Psr7\build_query($parameters);
    }

    /**
     * Get the issue count from the provided request.
     *
     *
     * @param UriInterface $uri
     * @return number
     *   The total number of issues for the search paramaters of the request.
     */
    public function getCount(UriInterface $uri)
    {
        $issueRowCount = 0;

        while (true) {
            $response = $this->client->get($uri);
            $document = new Crawler($response->getBody()->getContents());
            $issueView = $document->filter('.view-project-issue-search-project-searchapi');

            $issueRowCount += $issueView
                ->filter('table.views-table tbody tr')
                ->reduce(function (Crawler $element) {
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

            $uri = Uri::withQueryValue($uri, 'page', (int) $urlMatches[1]);
        };

        return $issueRowCount;
    }
}
