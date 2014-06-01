<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

use DrupalReleaseDate\Random\GeometricWeightedRandom;
use DrupalReleaseDate\Sampling\Sample;
use DrupalReleaseDate\Sampling\SampleSet;
use DrupalReleaseDate\Sampling\TimeGroupedSampleSetCollection;
use DrupalReleaseDate\Sampling\SampleSetRandomSampleSelector;
use DrupalReleaseDate\Sampling\TimeGroupedRandomSampleSelector;
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

        // Group samples by week.
        $samples = new TimeGroupedSampleSetCollection(604800);

        $samplesResultSet = $app['db']->createQueryBuilder()
            ->select('s.when', 'sv_bugs.value + sv_tasks.value AS value')
            ->from('samples', 's')
            ->join('s', 'sample_values', 'sv_bugs', 's.version = sv_bugs.version && s.when = sv_bugs.when && sv_bugs.key="critical_bugs"')
            ->join('s', 'sample_values', 'sv_tasks', 's.version = sv_tasks.version && s.when = sv_tasks.when && sv_tasks.key="critical_tasks"')
            ->where('s.version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'ASC')
            ->execute();
        while ($result = $samplesResultSet->fetchObject()) {
            $samples->insert(new Sample(
                $app['db']->convertToPhpValue($result->when, 'datetime')->getTimestamp(),
                $app['db']->convertToPhpValue($result->value, 'smallint')
            ));
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

        // Give samples twice the weight of those from six months before.
        $geometricRandom = new GeometricWeightedRandom(0, $samples->length() - 1, pow(2, 1/26));
        $sampleSelector = new TimeGroupedRandomSampleSelector($samples, $geometricRandom);

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
