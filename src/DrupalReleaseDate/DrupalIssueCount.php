<?php
namespace DrupalReleaseDate;

class DrupalIssueCount
{
    protected $defaultParameters = array();
    protected $client;

    function __construct($defaultParameters = array()) {
        $this->defaultParameters = $defaultParameters;

        $this->client = new \Guzzle\Http\Client('https://drupal.org/project/issues/search/drupal');

        $this->client->setUserAgent('DrupalReleaseDate.com', true);
    }

    /**
     * Get the issue count for the specified parameters.
     *
     * The provided parameters are merged with the default parameters, if any
     * were specified in the contructor.
     *
     * @param array $parameters
     * @return number
     */
    function getCount($parameters) {

        $parameters += $this->defaultParameters;

        $document = $this->getXmlDocument($parameters);

        // Check if pager exists on first page; get page count
        $fullPages = 0;
        $pagerLinks = $document->xpath("//_xmlns:div[contains(concat(' ', @class, ' '), ' view-project-issue-search-project ')]//_xmlns:li[contains(concat(' ', @class, ' '), ' pager-item ')]");

        if ($pagerLinks) {
            // The current pager link doesn't have the page-item class applied to it.
            $fullPages = count($pagerLinks);
            $parameters += array(
                'page' => $fullPages,
            );
            $document = $this->getXmlDocument($parameters);
        }

        $issueRows = $document->xpath("//_xmlns:div[contains(concat(' ', @class, ' '), ' view-project-issue-search-project ')]//_xmlns:table[contains(concat(' ', @class, ' '), ' views-table ')]/_xmlns:tbody/_xmlns:tr");

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
    protected function getXmlDocument($parameters) {

        $request = $this->client->get('', array(), array(
            'query' => $parameters,
        ));
        $response = $request->send();

        try {
            $document = $response->xml();
        }
        catch (\RuntimeException $e) {
            // Drupal.org may return invalid XML due to unescaped ampersands
            $page = (string) $response->getBody();

            $page = str_replace(' & ', ' &amp; ', $page);

            $document = new \SimpleXMLElement($page);
        }

        // Need to register name for default namespace to be able to query anything with xpath
        $namespaces = $document->getDocNamespaces();
        $document->registerXPathNamespace('_xmlns', $namespaces['']);

        return $document;
    }
}
