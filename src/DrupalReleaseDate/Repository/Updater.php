<?php
namespace DrupalReleaseDate\Repository;

use DateTime;
use DateInterval;
use Doctrine\DBAL\Connection as DbConnection;

use DrupalReleaseDate\Random\GeometricWeightedRandom;
use DrupalReleaseDate\Sampling\Sample;
use DrupalReleaseDate\Sampling\SampleSet;
use DrupalReleaseDate\Sampling\TimeGroupedSampleSetCollection;
use DrupalReleaseDate\Sampling\SampleSetRandomSampleSelector;
use DrupalReleaseDate\Sampling\TimeGroupedRandomSampleSelector;
use DrupalReleaseDate\MonteCarlo;
use DrupalReleaseDate\MonteCarloIncreasingRunException;

/**
 * Class to encapsulate methods that make updates to the database.
 */
class Updater
{
    protected $db;

    public function __construct(DbConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Calculate a new estimate based on the available data, and store it to the
     * database.
     *
     * @param  array $config
     */
    public function estimate(array $config = array())
    {
        $config += array(
            'iterations' => 100000,
        );

        $db = $this->db;

        // Group samples by week.
        $samples = new TimeGroupedSampleSetCollection(604800);

        $samplesResultSet = $db->createQueryBuilder()
            ->select('s.when', 'sv_bugs.value + sv_tasks.value AS value')
            ->from('samples', 's')
            ->join('s', 'sample_values', 'sv_bugs', 's.version = sv_bugs.version && s.when = sv_bugs.when && sv_bugs.key="critical_bugs"')
            ->join('s', 'sample_values', 'sv_tasks', 's.version = sv_tasks.version && s.when = sv_tasks.when && sv_tasks.key="critical_tasks"')
            ->where('s.version = 8')
            ->having('value IS NOT NULL')
            ->orderBy($db->quoteIdentifier('when'), 'ASC')
            ->execute();
        $lastResult = null;
        while ($result = $samplesResultSet->fetchObject()) {
            $lastResult = $result;
            $samples->insert(new Sample(
                $db->convertToPhpValue($result->when, 'datetime')->getTimestamp(),
                $db->convertToPhpValue($result->value, 'smallint')
            ));
        }

        // Insert empty before run, update if succsesful.
        $db->insert(
            $db->quoteIdentifier('estimates'),
            array(
                $db->quoteIdentifier('when') => $lastResult->when,
                $db->quoteIdentifier('version') => 8,
                $db->quoteIdentifier('estimate') => null,
                $db->quoteIdentifier('note') => 'Timeout during run',
                $db->quoteIdentifier('data') => '',
            )
        );
        // Close connection during processing to prevent "Database has gone away" exception.
        $db->close();

        if (isset($config['timeout'])) {
            set_time_limit($config['timeout']);
        }

        // Give samples twice the weight of those from six months before.
        $geometricRandom = new GeometricWeightedRandom(0, $samples->length() - 1, pow(2, 1/26));
        $sampleSelector = new TimeGroupedRandomSampleSelector($samples, $geometricRandom);

        $monteCarlo = new MonteCarlo($sampleSelector);

        $update = array();

        try {
            $estimateDistribution = $monteCarlo->runDistribution($config['iterations']);
            $estimateInterval = MonteCarlo::getMedianFromDistribution($estimateDistribution);

            $estimateDate = new DateTime('@' . $_SERVER['REQUEST_TIME']);
            $estimateDate->add(DateInterval::createFromDateString($estimateInterval . ' seconds'));

            $update += array(
                $db->quoteIdentifier('estimate') => $db->convertToDatabaseValue($estimateDate, 'date'),
                $db->quoteIdentifier('note') => 'Run completed in ' . (time() - $_SERVER['REQUEST_TIME']) . ' seconds',
                $db->quoteIdentifier('data') => serialize($estimateDistribution),
            );
        } catch (MonteCarloIncreasingRunException $e) {
            $update += array(
                $db->quoteIdentifier('estimate') => '0000-00-00',
                $db->quoteIdentifier('note') => 'Run failed due to increasing issue count',
            );
        }

        $db->connect();
        $db->update(
            $db->quoteIdentifier('estimates'),
            $update,
            array(
                $db->quoteIdentifier('when') => $lastResult->when,
                $db->quoteIdentifier('version') => 8,
            )
        );
    }

    /**
     * Retrieve issue count samples according to the provided configuration,
     * and store them to the database.
     *
     * @param  \Guzzle\Http\ClientInterface $httpClient
     * @param  array $config
     */
    public function samples(\Guzzle\Http\ClientInterface $httpClient, array $config)
    {
        $db = $this->db;

        $queryDataDefaults = array(
            $db->quoteIdentifier('when') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
        );

        $counter = new \DrupalReleaseDate\DrupalIssueCount($httpClient);

        $d8CommonParameters = array(
            'version' => array('8.x')
        ) + $config['common'];
        $d8results = $counter->getCounts($d8CommonParameters, $config['sets']);
        $queryData = $queryDataDefaults + array(
                $db->quoteIdentifier('version') => 8,
            );
        $db->insert($db->quoteIdentifier('samples'), $queryData);
        foreach ($d8results as $resultKey => $resultValue) {
            $queryData[$db->quoteIdentifier('key')] = $resultKey;
            $queryData[$db->quoteIdentifier('value')] = $resultValue;
            $db->insert($db->quoteIdentifier('sample_values'), $queryData);
        }


        $d9CommonParameters = array(
            'version' => array('9.x')
        ) + $config['common'];
        $d9results = $counter->getCounts($d9CommonParameters, $config['sets']);
        $queryData = $queryDataDefaults + array(
                $db->quoteIdentifier('version') => 9,
            );
        $db->insert($db->quoteIdentifier('samples'), $queryData);
        foreach ($d9results as $resultKey => $resultValue) {
            $queryData[$db->quoteIdentifier('key')] = $resultKey;
            $queryData[$db->quoteIdentifier('value')] = $resultValue;
            $db->insert($db->quoteIdentifier('sample_values'), $queryData);
        }
    }
}
