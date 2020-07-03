<?php
namespace DrupalReleaseDate\Repository;

use DateTime;
use DateInterval;
use Doctrine\DBAL\Connection as DbConnection;

use DrupalReleaseDate\DrupalIssueCount;
use DrupalReleaseDate\NumberGenerator\GeometricWeighted;
use DrupalReleaseDate\NumberGenerator\Random;
use DrupalReleaseDate\Sampling\Sample;
use DrupalReleaseDate\Sampling\TimeGroupedSampleSetCollection;
use DrupalReleaseDate\Sampling\TimeGroupedRandomSampleSelector;
use DrupalReleaseDate\MonteCarlo;
use DrupalReleaseDate\MonteCarlo\IncreasingException;
use DrupalReleaseDate\MonteCarlo\TimeoutException;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DomCrawler\Crawler;

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
            'iterations' => MonteCarlo::DEFAULT_ITERATIONS,
            'timeout' => MonteCarlo::DEFAULT_TIME_LIMIT,
        );

        $db = $this->db;

        // Group samples by week.
        $samples = new TimeGroupedSampleSetCollection(604800);

        $samplesResultSet = $db->createQueryBuilder()
            ->select('s.when', 'sv_bugs.value + sv_tasks.value + IFNULL(sv_plans.value, 0) AS value')
            ->from('samples', 's')
            ->innerJoin('s', 'sample_values', 'sv_bugs', 's.version = sv_bugs.version && s.when = sv_bugs.when && sv_bugs.key="critical_bugs"')
            ->innerJoin('s', 'sample_values', 'sv_tasks', 's.version = sv_tasks.version && s.when = sv_tasks.when && sv_tasks.key="critical_tasks"')
            ->leftJoin('s', 'sample_values', 'sv_plans', 's.version = sv_plans.version && s.when = sv_plans.when && sv_plans.key="critical_plans"')
            ->where('s.version = "8.0"')
            ->andWhere('sv_bugs.value IS NOT NULL')
            ->andWhere('sv_tasks.value IS NOT NULL')
            // Exclude samples where fetching plans failed (when, NULL), but not where no plans value was retrieved (NULL, NULL).
            ->andWhere('NOT (sv_plans.when IS NULL XOR sv_plans.value IS NULL)')
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

        // Insert empty before run, update if successful.
        $db->insert(
            $db->quoteIdentifier('estimates'),
            array(
                $db->quoteIdentifier('when') => $lastResult->when,
                $db->quoteIdentifier('version') => '8.0',
                $db->quoteIdentifier('data') => '',
                $db->quoteIdentifier('started') => $db->convertToDatabaseValue(new DateTime(), 'datetime'),
            )
        );
        // Close connection during processing to prevent "Database has gone away" exception.
        $db->close();

        // Give samples twice the weight of those from six months before.
        $geometricRandom = new GeometricWeighted(new Random(), 0, $samples->length() - 1, pow(2, 1/24));
        $sampleSelector = new TimeGroupedRandomSampleSelector($samples, $geometricRandom);

        $monteCarlo = new MonteCarlo($sampleSelector);

        $update = array();

        try {
            set_time_limit($config['timeout'] + 30);
            $estimateDistribution = $monteCarlo->runDistribution($config['iterations'], MonteCarlo::DEFAULT_BUCKET_SIZE, $config['timeout']);

            $update += array(
                $db->quoteIdentifier('note') => 'Run completed in ' . (time() - $_SERVER['REQUEST_TIME']) . ' seconds'
                  . ' after ' . ($estimateDistribution->getSuccessCount() + $estimateDistribution->getFailureCount()) . ' iterations',

            );
        } catch (IncreasingException $e) {
            $estimateDistribution = $e->getDistribution();

            $update += array(
                $db->quoteIdentifier('note') => 'Run terminated due to increasing issue count'
                  . ' after ' . ($estimateDistribution->getSuccessCount() + $estimateDistribution->getFailureCount()) . ' iterations',
            );
        } catch (TimeoutException $e) {
            $estimateDistribution = $e->getDistribution();

            $update += array(
                $db->quoteIdentifier('note') => 'Run terminated due to timeout'
                    . ' after ' . ($estimateDistribution->getSuccessCount() + $estimateDistribution->getFailureCount()) . ' iterations',
            );
        }

        try {
            $estimateInterval = $estimateDistribution->getMedian(true);

            $estimateDate = new DateTime('@' . $_SERVER['REQUEST_TIME']);
            $estimateDate->add(DateInterval::createFromDateString($estimateInterval . ' seconds'));

            $update += array(
                $db->quoteIdentifier('estimate') => $db->convertToDatabaseValue($estimateDate, 'date'),
            );
        }
        catch (\RuntimeException $e) {
            $update += array(
                $db->quoteIdentifier('estimate') => null,
            );
        }

        $update += array(
            $db->quoteIdentifier('data') => serialize($estimateDistribution),
            $db->quoteIdentifier('completed') => $db->convertToDatabaseValue(new DateTime(), 'datetime'),
        );

        $db->connect();
        $db->update(
            $db->quoteIdentifier('estimates'),
            $update,
            array(
                $db->quoteIdentifier('when') => $lastResult->when,
                $db->quoteIdentifier('version') => '8.0',
            )
        );
    }

    /**
     * Retrieve issue count samples according to the provided configuration,
     * and store them to the database.
     *
     * @param  \GuzzleHttp\ClientInterface $httpClient
     * @param  array $config
     */
    public function samples(ClientInterface $httpClient, array $config)
    {
        $db = $this->db;


        $queryDataDefaults = array(
            $db->quoteIdentifier('when') => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
        );

        $counter = new DrupalIssueCount($httpClient);

        $versions = $this->getVersions($httpClient);
        foreach ($versions as $versionId => $versionKey) {
            $commonParameters = array(
                'version' => array($versionKey)
            ) + $config['common'];

            $countResults = $counter->getCounts($commonParameters, $config['sets']);

            $queryData = $queryDataDefaults + array(
                    $db->quoteIdentifier('version') => $versionId,
                );

            if ($db->ping() === false) {
                $db->close();
                $db->connect();
            }

            $db->insert($db->quoteIdentifier('samples'), $queryData);

            foreach ($countResults as $resultKey => $resultValue) {
                $queryData[$db->quoteIdentifier('key')] = $resultKey;
                $queryData[$db->quoteIdentifier('value')] = $resultValue;
                $db->insert($db->quoteIdentifier('sample_values'), $queryData);
            }
        }
    }

    /**
     * Retrieve available Drupal versions.
     *
     * @param ClientInterface $httpClient
     * @return array
     */
    protected function getVersions(ClientInterface $httpClient)
    {
        // Retrieve all version >= 8.0.x from the issue queue select element.
        $response = $httpClient->get('https://www.drupal.org/project/issues/drupal');
        $document = new Crawler($response->getBody()->getContents());
        $versions = $document
            ->filter('#edit-version option')
            ->reduce(function (Crawler $element) {
                return !empty(preg_match('/^(8|9|\\d{2})\\.\\d+\\.x-dev$/', $element->attr('value')));
            })
            ->extract(['value']);

        // Change the array keys to '{major}.{minor}'
        $return = [];
        foreach ($versions as $key => $version) {
            preg_match('/^(\\d+\\.\\d+)/', $version, $versionMatch);
            $return[$versionMatch[1]] = $version;
        }

        return $return;
    }
}
