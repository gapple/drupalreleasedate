<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

use DrupalReleaseDate\SampleSet;
use DrupalReleaseDate\MonteCarlo;

class Cron
{

    public function emptyResponse(Application $app, Request $request)
    {
        return '';
    }

    /**
     * Calculate a new estimate for the release date based on current samples.
     */
    public function updateEstimate(Application $app, Request $request, $key)
    {
        $config = $app['config'];

        // Check key in request
        if (!isset($config['cron.key']) || $key != $config['cron.key']) {
            return '';
        }

        // Run estimate simulation
        $samples = new SampleSet();
        $sql = "
            SELECT " . $app['db']->quoteIdentifier('when') . ", " . $app['db']->quoteIdentifier('critical_bugs') . "," . $app['db']->quoteIdentifier('critical_tasks') . "
                FROM " . $app['db']->quoteIdentifier('samples') . "
                WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " ASC
        ";
        $results = $app['db']->query($sql);
        while ($result = $results->fetchObject()) {
            $samples->addSample(strtotime($result->when), $result->critical_bugs + $result->critical_tasks);
        }

        // Insert empty before run, update if succsesful.
        $app['db']->insert($app['db']->quoteIdentifier('estimates'), array(
            $app['db']->quoteIdentifier('when') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
            $app['db']->quoteIdentifier('version') => 8,
            $app['db']->quoteIdentifier('estimate') => null,
            $app['db']->quoteIdentifier('note') => 'Timeout during run',
        ));
        // Close connection during processing to prevent "Database has gone away" exception.
        $app['db']->close();

        if (!empty($config['estimate.timeout'])) {
            set_time_limit($config['estimate.timeout']);
        }

        $monteCarlo = new MonteCarlo($samples);
        $iterations = (!empty($config['estimate.iterations'])? $config['estimate.iterations'] : 100000);
        $estimateDistribution = $monteCarlo->runDistribution($iterations);

        $countSum = 0;
        foreach ($estimateDistribution as $estimate => $count) {
            // Count the number of iterations so far, ignoring failed iterations.
            if ($estimate == 0) {
                if ($count >= $iterations / 2) {
                  break;
                }
                continue;
            }

            $countSum += $count;
            if ($countSum >= $iterations / 2) {
              break;
            }
        }

        $update = array();
        if ($estimate) {
            $update += array(
                $app['db']->quoteIdentifier('estimate') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] + $estimate),
                $app['db']->quoteIdentifier('note') => 'Run completed in ' . (time() - $_SERVER['REQUEST_TIME']) . ' seconds',
                $app['db']->quoteIdentifier('data') => serialize($estimateDistribution),
            );
        }
        else if ($estimate === 0) {
            $update += array(
                $app['db']->quoteIdentifier('estimate') => '0000-00-00 00:00:00',
                $app['db']->quoteIdentifier('note') => 'Run failed due to increasing issue count',
            );
        }
        if (!empty($update)) {
            $app['db']->connect();
            $app['db']->update($app['db']->quoteIdentifier('estimates'),
                $update,
                array(
                    $app['db']->quoteIdentifier('when') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
                    $app['db']->quoteIdentifier('version') => 8,
                )
            );
        }

        return '';
    }

    /**
     * Fetch the latest issue counts from Drupal.org and add them to the
     * database as a new sample.
     */
    public function fetchCounts(Application $app, Request $request, $key)
    {
        $config = $app['config'];

        // Check key in request
        if (!isset($config['cron.key']) || $key != $config['cron.key']) {
            return '';
        }

        $counter = new \DrupalReleaseDate\DrupalIssueCount(array(
            'status' => array(
                 1, // Active
                13, // Needs work
                 8, // Needs review
                14, // Reviewed & tested by the community
                15, // Patch (to be ported)
                 4, // Postponed
            //    16, // Postponed (maintainer needs more info)
            ),
            'version' => array('8.x'),
        ));

        $critical_bugs = $counter->getCount(array(
            'priorities' => array(1),
            'categories' => array('bug'),
        ));

        $critical_tasks = $counter->getCount(array(
            'priorities' => array(1),
            'categories' => array('task'),
        ));

        $major_bugs = $counter->getCount(array(
            'priorities' => array(4),
            'categories' => array('bug'),
        ));

        $major_tasks = $counter->getCount(array(
            'priorities' => array(4),
            'categories' => array('task'),
        ));

        $app['db']->insert($app['db']->quoteIdentifier('samples'), array(
            $app['db']->quoteIdentifier('when') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
            $app['db']->quoteIdentifier('version') => 8,
            $app['db']->quoteIdentifier('critical_bugs') => $critical_bugs,
            $app['db']->quoteIdentifier('critical_tasks') => $critical_tasks,
            $app['db']->quoteIdentifier('major_bugs') => $major_bugs,
            $app['db']->quoteIdentifier('major_tasks') => $major_tasks,
        ));

        return '';
    }
}
