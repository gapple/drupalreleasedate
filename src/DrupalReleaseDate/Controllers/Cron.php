<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

use DrupalReleaseDate\SampleSet;
use DrupalReleaseDate\MonteCarlo;

class Cron
{

    public function emptyResponse(Application $app, Request $request) {
        return '';
    }

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
            $app['db']->quoteIdentifier('when') => date('Y-m-d h:i:s', $_SERVER['REQUEST_TIME']),
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
                $app['db']->quoteIdentifier('estimate') => date('Y-m-d h:i:s', $_SERVER['REQUEST_TIME'] + $estimate),
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
                    $app['db']->quoteIdentifier('when') => date('Y-m-d h:i:s', $_SERVER['REQUEST_TIME']),
                    $app['db']->quoteIdentifier('version') => 8,
                )
            );
        }

        return '';
    }
}
