<?php
namespace DrupalReleaseDate;

class DrupalIssueCount
{
    protected $client;

    protected $drupalOrgVersion = null;

    /**
     * Set of criteria to fetch issues for on D6 version of Drupal.org
     *
     * @var array
     */
    protected static $dOrgD6FetchCategories = array(
        'critical_bugs' => array(
            'priorities' => array(1),
            'categories' => array('bug'),
        ),
        'critical_tasks' => array(
            'priorities' => array(1),
            'categories' => array('task'),
        ),
        'major_bugs' => array(
            'priorities' => array(4),
            'categories' => array('bug'),
        ),
        'major_tasks' => array(
            'priorities' => array(4),
            'categories' => array('task'),
        ),
        'normal_bugs' => array(
            'priorities' => array(2),
            'categories' => array('bug'),
        ),
        'normal_tasks' => array(
            'priorities' => array(2),
            'categories' => array('task'),
        ),
    );

    /**
     * Set of criteria to fetch issues for on D7 version of Drupal.org
     *
     * @var array
     */
    protected static $dOrgD7FetchCategories = array(
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
    //    16, // Postponed (maintainer needs more info)
    );

    public function __construct($userAgent = null) {
        $this->client = new \Guzzle\Http\Client('https://drupal.org/');

        if (!empty($userAgent)) {
            $this->client->setUserAgent($userAgent, true);
        }
    }

    /**
     * Determine the version of Drupal that Drupal.org is running.
     */
    public function determineDrupalOrgVersion() {

        if (empty($this->drupalOrgVersion)) {
            // Default to 6.x
            $this->drupalOrgVersion = '6';

            // We need a simple page that parses cleanly in both versions.
            $request = $this->client->get('/about');

            $document = $this->getXmlDocument($request->send());

            $generator = $document->xpath("//_xmlns:meta[@name='Generator']");

            if (!empty($generator[0]['content']) && stripos($generator[0]['content'], 'Drupal 7') !== false) {
                $this->drupalOrgVersion = '7';
            }
        }

        return $this->drupalOrgVersion;
    }

    /**
     * Get the count of issues against Drupal 8.
     *
     * @return array
     */
    public function getD8Counts() {

        $drupalOrgVersion = $this->determineDrupalOrgVersion();

        if ($drupalOrgVersion == '6') {
            return $this->getCounts(array(
                    'version' => array('8.x'),
                    'status' => static::$fetchStatusIds,
                ),
                static::$dOrgD6FetchCategories,
              'view-project-issue-search-project'
            );
        }
        else if ($drupalOrgVersion == '7') {
            return $this->getCounts(array(
                    'version' => array('8.x'),
                    'status' => static::$fetchStatusIds,
                ),
                static::$dOrgD7FetchCategories,
              'view-project-issue-search-project-searchapi'
            );
        }
    }

    /**
     * Get the count of issues against Drupal 9.
     *
     * @return array
     */
    public function getD9Counts() {

      $drupalOrgVersion = $this->determineDrupalOrgVersion();

      if ($drupalOrgVersion == '6') {
          return $this->getCounts(array(
                  'version' => array('1859548'), // 9.x doesn't have a catch-all version, so the term id for 9.x-dev is used.
                  'status' => static::$fetchStatusIds,
              ),
              static::$dOrgD6FetchCategories,
              'view-project-issue-search-project'
          );
      }
      else if ($drupalOrgVersion == '7') {
        return $this->getCounts(array(
                  'version' => array('9.x'),
                  'status' => static::$fetchStatusIds,
              ),
              static::$dOrgD7FetchCategories,
              'view-project-issue-search-project-searchapi'
          );
      }
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
     * @param $viewClass
     *   The class
     */
    public function getCounts($commonParameters, $fetchSet, $viewClass) {

        $request = $this->client->get('/project/issues/search/drupal');

        $query = $request->getQuery();
        $query->merge($commonParameters);

        $results = array();
        foreach ($fetchSet as $fetchKey => $fetchParameters) {
            // Override each of the unique values for this fetch set.
            foreach ($fetchParameters as $parameterKey => $parameterValue) {
                $query->set($parameterKey, $parameterValue);
            }
            $results[$fetchKey] = $this->getCount($request, $viewClass);
        }

        return $results;
    }

    /**
     * Get the issue count from the provided request.
     *
     *
     * @param array $request
     *   Guzzle request for the first page of results.
     * @param string $viewClass
     *   CSS class for the views table contianing issues.
     * @return number
     *   The total number of issues for the search paramaters of the request.
     */
    public function getCount(\Guzzle\Http\Message\RequestInterface $request, $viewClass) {

        // Make sure page isn't set from a previous call on the same request object.
        $request->getQuery()->remove('page');

        $document = $this->getXmlDocument($request->send());

        // Check if pager exists on first page; get the next page until we're at
        // the end to find the total number of pages.
        $fullPages = 0;
        $pagerNext = $document->xpath("//_xmlns:div[contains(concat(' ', @class, ' '), ' {$viewClass} ')]//_xmlns:li[contains(concat(' ', @class, ' '), ' pager-next ')]//_xmlns:a");

        while ($pagerNext) {
            $pagerNextUrl = (string) $pagerNext[0]['href'];
            preg_match('/page=(\\d+)/', $pagerNextUrl, $urlMatches);

            $fullPages = (int) $urlMatches[1]; // Pager starts at 0,
            $request->getQuery()->set('page', $fullPages);
            $document = $this->getXmlDocument($request->send());

            $pagerNext = $document->xpath("//_xmlns:div[contains(concat(' ', @class, ' '), ' {$viewClass} ')]//_xmlns:li[contains(concat(' ', @class, ' '), ' pager-next ')]//_xmlns:a");
        }

        $issueRows = $document->xpath("//_xmlns:div[contains(concat(' ', @class, ' '), ' {$viewClass} ')]//_xmlns:table[contains(concat(' ', @class, ' '), ' views-table ')]/_xmlns:tbody/_xmlns:tr");

        $issues = count($issueRows) + 50 * $fullPages;

        return $issues;
    }

    /**
     * Fetch a page with the specied parameters, and return the resulting
     * document as a SimpleXMLElement.
     *
     * @param array $parameters
     * @return \SimpleXMLElement
     */
    public function getXmlDocument(\Guzzle\Http\Message\Response $response) {

        try {
            libxml_use_internal_errors(true);
            $document = $response->xml();
        }
        catch (\Guzzle\Common\Exception\RuntimeException $e) {
            libxml_clear_errors();

            // Drupal.org may return invalid XML due to unescaped ampersands and
            // non-breaking spaces (which aren't valid in XHTML serialization of
            // HTML5).
            $page = (string) $response->getBody();

            $replace = array(
                ' & '    => ' &amp; ',
                '&nbsp;' => '&#xA0;',
            );
            $page = str_replace(array_keys($replace), array_values($replace), $page);

            $document = new \SimpleXMLElement($page);
        }

        // Need to register name for default namespace to be able to query anything with xpath
        $namespaces = $document->getDocNamespaces();
        $document->registerXPathNamespace('_xmlns', $namespaces['']);

        return $document;
    }
}
