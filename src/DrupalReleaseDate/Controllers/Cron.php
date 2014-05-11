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
                WHERE " . $app['db']->quoteIdentifier('version') . " = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " ASC
        ";
        $results = $app['db']->query($sql);
        while ($result = $results->fetchObject()) {
            $samples->insert(strtotime($result->when), $result->critical_bugs + $result->critical_tasks);
        }

        // Insert empty before run, update if succsesful.
        $app['db']->insert(
            $app['db']->quoteIdentifier('estimates'),
            array(
                $app['db']->quoteIdentifier('when') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
                $app['db']->quoteIdentifier('version') => 8,
                $app['db']->quoteIdentifier('estimate') => null,
                $app['db']->quoteIdentifier('note') => 'Timeout during run',
                $app['db']->quoteIdentifier('data') => '',
            )
        );
        // Close connection during processing to prevent "Database has gone away" exception.
        $app['db']->close();

        if (!empty($config['estimate.timeout'])) {
            set_time_limit($config['estimate.timeout']);
        }

        $sampleSelector = new SampleSetRandomSampleSelector($samples);

        $monteCarlo = new MonteCarlo($sampleSelector);
        $iterations = (!empty($config['estimate.iterations']) ? $config['estimate.iterations'] : 100000);

        $update = array();

        try {
            $estimateDistribution = $monteCarlo->runDistribution($iterations);

            $medianIterations = array_sum($estimateDistribution) / 2;
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
        } catch (MonteCarloIncreasingRunException $e) {
            $update += array(
                $app['db']->quoteIdentifier('estimate') => '0000-00-00 00:00:00',
                $app['db']->quoteIdentifier('note') => 'Run failed due to increasing issue count',
            );
        }

        $app['db']->connect();
        $app['db']->update(
            $app['db']->quoteIdentifier('estimates'),
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
        );

        $guzzleClient = new \Guzzle\Http\Client();
        if (!empty($config['guzzle']['userAgent'])) {
            $guzzleClient->setUserAgent($config['guzzle']['userAgent'], true);
        }
        $counter = new \DrupalReleaseDate\DrupalIssueCount($guzzleClient);


        $d8CommonParameters = array(
            'version' => array('8.x')
        ) + $config['drupal_issues']['common'];
        $d8results = $counter->getCounts($d8CommonParameters, $config['drupal_issues']['sets']);
        $queryData = $queryDataDefaults + array(
                $app['db']->quoteIdentifier('version') => 8,
            );
        $app['db']->insert($app['db']->quoteIdentifier('samples'), $queryData);
        foreach ($d8results as $resultKey => $resultValue) {
            $queryData[$app['db']->quoteIdentifier('key')] = $resultKey;
            $queryData[$app['db']->quoteIdentifier('value')] = $resultValue;
            $app['db']->insert($app['db']->quoteIdentifier('sample_values'), $queryData);
        }


        $d9CommonParameters = array(
            'version' => array('9.x')
        ) + $config['drupal_issues']['common'];
        $d9results = $counter->getCounts($d9CommonParameters, $config['drupal_issues']['sets']);
        $queryData = $queryDataDefaults + array(
                $app['db']->quoteIdentifier('version') => 9,
            );
        $app['db']->insert($app['db']->quoteIdentifier('samples'), $queryData);
        foreach ($d9results as $resultKey => $resultValue) {
            $queryData[$app['db']->quoteIdentifier('key')] = $resultKey;
            $queryData[$app['db']->quoteIdentifier('value')] = $resultValue;
            $app['db']->insert($app['db']->quoteIdentifier('sample_values'), $queryData);
        }

        return '';
    }
}
