<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

use DrupalReleaseDate\Sampling\SampleSet;
use DrupalReleaseDate\Sampling\SampleSetRandomSampleSelector;
use DrupalReleaseDate\MonteCarlo;
use DrupalReleaseDate\MonteCarloIncreasingRunException;

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
            $samples->insert(strtotime($result->when), $result->critical_bugs + $result->critical_tasks);
        }

        // Insert empty before run, update if succsesful.
        $app['db']->insert($app['db']->quoteIdentifier('estimates'), array(
            $app['db']->quoteIdentifier('when') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
            $app['db']->quoteIdentifier('version') => 8,
            $app['db']->quoteIdentifier('estimate') => null,
            $app['db']->quoteIdentifier('note') => 'Timeout during run',
            $app['db']->quoteIdentifier('data') => '',
        ));
        // Close connection during processing to prevent "Database has gone away" exception.
        $app['db']->close();

        if (!empty($config['estimate.timeout'])) {
            set_time_limit($config['estimate.timeout']);
        }

        $sampleSelector = new SampleSetRandomSampleSelector($samples);

        $monteCarlo = new MonteCarlo($sampleSelector);
        $iterations = (!empty($config['estimate.iterations'])? $config['estimate.iterations'] : 100000);

        $update = array();

        try {
            $estimateDistribution = $monteCarlo->runDistribution($iterations);

            $medianIterations = array_sum($distribution) / 2;
            $countSum = 0;
            foreach ($estimateDistribution as $estimate => $count) {
                $countSum += $count;
                if ($countSum >= $medianIterations) {
                  break;
                }
            }

            $update += array(
                $app['db']->quoteIdentifier('estimate') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] + $estimate),
                $app['db']->quoteIdentifier('note') => 'Run completed in ' . (time() - $_SERVER['REQUEST_TIME']) . ' seconds',
                $app['db']->quoteIdentifier('data') => serialize($estimateDistribution),
            );
        }
        catch (MonteCarloIncreasingRunException $e) {
            $update += array(
                $app['db']->quoteIdentifier('estimate') => '0000-00-00 00:00:00',
                $app['db']->quoteIdentifier('note') => 'Run failed due to increasing issue count',
            );
        }

        $app['db']->connect();
        $app['db']->update($app['db']->quoteIdentifier('estimates'),
            $update,
            array(
                $app['db']->quoteIdentifier('when') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
                $app['db']->quoteIdentifier('version') => 8,
            )
        );

        return '';
    }

    /**
     * Fetch the latest issue counts from Drupal.org and add them to the
     * database as a new sample.
     */
    public function fetchCounts(Application $app, Request $request, $key)
    {
        $config = $app['config'];

        $queryDataDefaults = array(
            $app['db']->quoteIdentifier('when') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
            $app['db']->quoteIdentifier('notes') => '',
        );

        $userAgent = !empty($config['guzzle']['userAgent'])? $config['guzzle']['userAgent'] : null;
        $counter = new \DrupalReleaseDate\DrupalIssueCount($userAgent);


        $d8results = $counter->getD8Counts();
        $queryData = $queryDataDefaults + array(
            $app['db']->quoteIdentifier('version') => 8,
        );
        foreach ($d8results as $resultKey => $resultValue) {
            $queryData[$app['db']->quoteIdentifier($resultKey)] = $resultValue;
        }
        $app['db']->insert($app['db']->quoteIdentifier('samples'), $queryData);


        $d9results = $counter->getD9Counts();
        $queryData = $queryDataDefaults + array(
            $app['db']->quoteIdentifier('version') => 9,
        );
        foreach ($d9results as $resultKey => $resultValue) {
            $queryData[$app['db']->quoteIdentifier($resultKey)] = $resultValue;
        }
        $app['db']->insert($app['db']->quoteIdentifier('samples'), $queryData);

        return '';
    }
}
