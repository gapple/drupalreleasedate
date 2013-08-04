<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class History
{

    public function info(Application $app, Request $request)
    {
        $fetcher = new \DrupalReleaseDate\DrupalIssueInfo();

        $issues = $app['db']->query("SELECT * FROM history WHERE severity =''");

        while ($issue = $issues->fetchObject()) {
            sleep(5);
            try {
                $info = $fetcher->getInfo($issue->nid);
            }
            catch(\Guzzle\Http\Exception\BadResponseException $e) {
                continue;
            }

            $app['db']->update('history',
                array(
                    'severity' => $info['Priority'],
                    'type' => $info['Category'],
                ),
                array('nid' => $issue->nid)
            );
        }

        return '';
    }
}