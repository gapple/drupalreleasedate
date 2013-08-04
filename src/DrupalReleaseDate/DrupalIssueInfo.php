<?php
namespace DrupalReleaseDate;

class DrupalIssueInfo
{
    protected $client;

    function __construct($defaultParameters = array()) {
        $this->defaultParameters = $defaultParameters;

        $this->client = new \Guzzle\Http\Client('https://drupal.org/node/');

        $this->client->setUserAgent('DrupalReleaseDate.com', true);
    }

    /**
     * Get the attributes for an issue
     *
     * The provided parameters are merged with the default parameters, if any
     * were specified in the contructor.
     *
     * @param array $parameters
     * @return number
     */
    function getInfo($nid) {

        $document = $this->getXmlDocument($nid);

        $fields = $document->xpath("//div[@id='project-issue-summary-table']//tbody/tr");

        $data = array();
        foreach ($fields as $element) {
            $tds = $element->xpath("td");
            $key = (string) $tds[0];
            $key = preg_replace('/:$/', '', $key);
            $value = (string) $tds[1];
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Fetch a page with the specied parameters, and return the resulting
     * document as a SimpleXMLElement.
     *
     * @param array $parameters
     * @return \SimpleXMLElement
     */
    protected function getXmlDocument($nid) {

        $request = $this->client->get($nid);
        $response = $request->send();

//        try {
//            $document = $response->xml();
//        }
//        catch (\RuntimeException $e) {
            // Drupal.org may return invalid XML due to unescaped ampersands
            $page = (string) $response->getBody();

            $page = str_replace(
                array(
                    ' & ',
                    '&raquo;',
                ),
                array(
                    ' &amp; ',
                    '',
                ),
                $page
            );

            $dom = new \DOMDocument();
            $dom->loadHTML($page);
            $document = simplexml_import_dom($dom);
//        }

        return $document;
    }
}
