<?php
namespace DrupalReleaseDate;

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
            } catch (DrupalOrgParseException $e) {
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

        $document = $this->getXmlDocument($request->send());

        // Check if pager exists on first page; get the next page until we're at
        // the end to find the total number of pages.
        $fullPages = 0;
        $pagerNext = $document->xpath(
            "//_xmlns:div[contains(concat(' ', @class, ' '), ' view-project-issue-search-project-searchapi ')]//_xmlns:li[contains(concat(' ', @class, ' '), ' pager-next ')]//_xmlns:a"
        );

        while ($pagerNext) {
            $pagerNextUrl = (string) $pagerNext[0]['href'];
            preg_match('/page=(\\d+)/', $pagerNextUrl, $urlMatches);

            $fullPages = (int) $urlMatches[1]; // Pager starts at 0,
            $request->getQuery()->set('page', $fullPages);
            $document = $this->getXmlDocument($request->send());

            $pagerNext = $document->xpath(
                "//_xmlns:div[contains(concat(' ', @class, ' '), ' view-project-issue-search-project-searchapi ')]//_xmlns:li[contains(concat(' ', @class, ' '), ' pager-next ')]//_xmlns:a"
            );
        }

        $issueRows = $document->xpath(
            "//_xmlns:div[contains(concat(' ', @class, ' '), ' view-project-issue-search-project-searchapi ')]//_xmlns:table[contains(concat(' ', @class, ' '), ' views-table ')]/_xmlns:tbody/_xmlns:tr"
        );

        // Drupal.org is returning rows where all cells are empty, which bumps
        // up the count incorrectly.
        $issueRowCount = 0;
        foreach ($issueRows as $issueRow) {
            if (!empty($issueRow->td[0]->a)) {
                $issueRowCount++;
            }
        }

        $issues = $issueRowCount + 50 * $fullPages;

        return $issues;
    }

    /**
     * Fetch a page with the specied parameters, and return the resulting
     * document as a SimpleXMLElement.
     *
     * @param array $parameters
     * @return \SimpleXMLElement
     */
    public function getXmlDocument(\Guzzle\Http\Message\Response $response)
    {

        try {
            libxml_use_internal_errors(true);
            $document = $response->xml();
        } catch (\Guzzle\Common\Exception\RuntimeException $e) {
            libxml_clear_errors();

            // Drupal.org may return invalid XML due to unescaped ampersands and
            // non-breaking spaces (which aren't valid in XHTML serialization of
            // HTML5).
            $page = (string) $response->getBody();

            $replace = array(
                ' & ' => ' &amp; ',
                '&nbsp;' => '&#xA0;',
            );
            $page = str_replace(array_keys($replace), array_values($replace), $page);

            try {
                $document = new \SimpleXMLElement($page);
            } catch (\Exception $e) {
                // Throw a better specified Exception.
                throw new DrupalOrgParseException();
            }
        }

        // Need to register name for default namespace to be able to query anything with xpath
        $namespaces = $document->getDocNamespaces();
        $document->registerXPathNamespace('_xmlns', $namespaces['']);

        return $document;
    }
}
