<?php
namespace DrupalReleaseDate;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer;
use Doctrine\DBAL\Types\Type;
use \Silex\Application;

/**
 * Install and update database schema.
 */
class Installation
{

    /** @var Connection */
    protected $db;

    /** @var string */
    protected $configDir;

    /**
     * Extract version number integer from update method name.
     *
     * @param $method
     * @return int
     */
    public static function getVersionFromUpdateMethod($method)
    {
        return (int) substr($method, 7);
    }

    /**
     * Installation constructor.
     *
     * @param \Silex\Application $app
     */
    public function __construct(Application $app)
    {
        $this->db = $app['db'];
        $this->configDir = $app['config.dir'];
    }

    /**
     * Get the current installed version.
     *
     * @return int|null
     */
    public function getVersion()
    {
        if ($this->db->getSchemaManager()->tablesExist(array('state'))) {
            /** @var ResultStatement $stateValue */
            $stateValue = $this->db->createQueryBuilder()
              ->select('st.value')
              ->from('state', 'st')
              ->where('st.key = "installationVersion"')
              ->execute();
            $versionValue = $stateValue->fetchColumn(0);
            return (int) $versionValue;
        } elseif (file_exists($this->configDir . '/InstallationVersion')) {
            return (int) file_get_contents($this->configDir . '/InstallationVersion');
        }

        return null;
    }

    /**
     * Update the current installed version.
     *
     * @param int $value
     */
    public function setVersion($value)
    {
        $this->db->createQueryBuilder()
          ->update('state', 'st')
          ->set('st.value', ':version')
          ->where('st.key = "installationVersion"')
          ->setParameter('version', $value)
          ->execute();
    }

    /**
     * Retrieve all available updates.
     *
     * @return array
     */
    public function getUpdates()
    {
        $updates = array_filter(
            get_class_methods($this),
            function ($method) {
                return preg_match('/^update_\d+$/', $method) === 1;
            }
        );
        natsort($updates);

        return $updates;
    }

    /**
     * Retrieve all un-applied updates.
     *
     * @return array
     */
    public function getPendingUpdates()
    {
        $self = $this;
        $currentVersion = $this->getVersion();
        $updates = array_filter(
            $this->getUpdates(),
            function ($method) use ($self, $currentVersion) {
                return $self->getVersionFromUpdateMethod($method) > $currentVersion;
            }
        );

        return $updates;
    }

    /**
     * Initialize database to latest schema version.
     */
    public function install()
    {
        if ($this->getVersion() !== null) {
            return;
        }

        $this->installSchema();

        $version = 0;
        $updateMethods = $this->getUpdates();
        if (!empty($updateMethods)) {
            $lastUpdate = end($updateMethods);
            $version = self::getVersionFromUpdateMethod($lastUpdate);
        }
        $this->setVersion($version);
    }

    /**
     * Apply all pending updates.
     */
    public function update()
    {
        foreach ($this->getPendingUpdates() as $method) {
            $this->{$method}();
            $this->setVersion(self::getVersionFromUpdateMethod($method));
        }
    }

    /**
     * Helper method to run the actions needed for installation.
     */
    protected function installSchema()
    {
        $schema = new Schema();

        $samples = $schema->createTable('samples');
        $samples->addColumn('version', 'string', array('length' => 32));
        $samples->addColumn('when', 'datetime');
        $samples->addColumn('notes', 'string', array('default' => ''));
        $samples->setPrimaryKey(array('version', 'when'));

        $sample_values = $schema->createTable('sample_values');
        $sample_values->addColumn('version', 'string', array('length' => 32));
        $sample_values->addColumn('when', 'datetime');
        $sample_values->addColumn('key', 'string', array('length' => 64));
        $sample_values->addColumn('value', 'smallint', array('notnull' => false));
        $sample_values->setPrimaryKey(array('version', 'when', 'key'));

        $estimates = $schema->createTable('estimates');
        $estimates->addColumn('version', 'string', array('length' => 32));
        $estimates->addColumn('when', 'datetime');
        $estimates->addColumn('started', 'datetime');
        $estimates->addColumn('completed', 'datetime', array('notnull' => false));
        $estimates->addColumn('estimate', 'datetime', array('notnull' => false));
        $estimates->addColumn('note', 'string', array('default' => ''));
        $estimates->addColumn('data', 'blob');
        $estimates->setPrimaryKey(array('version', 'when'));

        $state = $schema->createTable('state');
        $state->addColumn('key', 'string', array('length' => 255));
        $state->addColumn('value', 'blob');
        $state->setPrimaryKey(array('key'));

        $synchronizer = new SingleDatabaseSynchronizer($this->db);
        $synchronizer->createSchema($schema);

        // Create the version entry in the database.  It will be updated with
        // the correct value at the end of `install()`.
        $this->db->insert(
            'state',
            array(
                $this->db->quoteIdentifier('key') => 'InstallationVersion',
                $this->db->quoteIdentifier('value') => 0
            )
        );
    }

    /**
     * Normalize sample storage in database.
     */
    protected function update_1()
    {
        /** @var Schema $schema */
        $schema = $this->db->getSchemaManager()->createSchema();

        // Create sample_values table.
        $schema = clone $schema;
        $sample_values = $schema->createTable('sample_values');
        $sample_values->addColumn('version', 'smallint');
        $sample_values->addColumn('when', 'datetime');
        $sample_values->addColumn('key', 'string', array('length' => 64));
        $sample_values->addColumn('value', 'smallint', array('notnull' => false));
        $sample_values->setPrimaryKey(array('version', 'when', 'key'));

        $synchronizer = new SingleDatabaseSynchronizer($this->db);
        $synchronizer->updateSchema($schema);

        // Select all samples.
        /** @var ResultStatement $samples */
        $samples = $this->db->createQueryBuilder()
            ->select('s.*')
            ->from('samples', 's')
            ->execute();
        $keys = array(
            'critical_tasks',
            'critical_bugs',
            'major_tasks',
            'major_bugs',
            'normal_tasks',
            'normal_bugs',
        );

        // Foreach sample, insert values into `sample_values`.
        while (($sample = $samples->fetch(\PDO::FETCH_ASSOC))) {
            foreach ($keys as $key) {
                $this->db->insert(
                    $this->db->quoteIdentifier('sample_values'),
                    array(
                        $this->db->quoteIdentifier('version') => $sample['version'],
                        $this->db->quoteIdentifier('when') => $sample['when'],
                        $this->db->quoteIdentifier('key') => $key,
                        $this->db->quoteIdentifier('value') => $sample[$key],
                    )
                );
            }
        }

        // Update samples table structure to remove columns
        $schema = clone $schema;
        $samplesSchema = $schema->getTable('samples');
        $samplesSchema->dropColumn('critical_tasks');
        $samplesSchema->dropColumn('critical_bugs');
        $samplesSchema->dropColumn('major_tasks');
        $samplesSchema->dropColumn('major_bugs');
        $samplesSchema->dropColumn('normal_tasks');
        $samplesSchema->dropColumn('normal_bugs');

        $synchronizer->updateSchema($schema);
    }

    /**
     * Convert estimate to only store date and not time.
     */
    protected function update_2()
    {

        /** @var Schema $schema */
        $schema = $this->db->getSchemaManager()->createSchema();

        $schema = clone $schema;
        $schema
            ->getTable('estimates')
            ->getColumn('estimate')
            ->setType(Type::getType('date'));

        $synchronizer = new SingleDatabaseSynchronizer($this->db);
        $synchronizer->updateSchema($schema);
    }

    /**
     * Add column to estimates to record time of completion.
     */
    protected function update_3()
    {
        /** @var Schema $schema */
        $schema = $this->db->getSchemaManager()->createSchema();

        $schema = clone $schema;
        $estimates = $schema->getTable('estimates');
        $estimates->addColumn('started', 'datetime');
        $estimates->addColumn('completed', 'datetime', array('notnull' => false));

        $synchronizer = new SingleDatabaseSynchronizer($this->db);
        $synchronizer->updateSchema($schema);

        $this->db->createQueryBuilder()
            ->update('estimates', 'e')
            ->set('e.started', 'e.when')
            ->set('e.completed', 'e.when')
            ->execute();
    }

    /**
     * Update format of data for existing estimates.
     */
    protected function update_4()
    {
        /** @var ResultStatement $estimates */
        $estimates = $this->db->createQueryBuilder()
            ->select('e.*')
            ->from('estimates', 'e')
            ->where('e.data != ""')
            ->execute();


        while (($estimate = $estimates->fetch(\PDO::FETCH_OBJ))) {
            $dataArray = unserialize($estimate->data);

            $dataObject = EstimateDistribution::fromArray($dataArray);

            $this->db->createQueryBuilder()
                ->update('estimates', 'e')
                ->set('e.data', ':data')
                ->where('e.version = :version')
                ->andWhere('e.when = :when')
                ->setParameter('data', serialize($dataObject))
                ->setParameter('version', $estimate->version)
                ->setParameter('when', $estimate->when)
                ->execute();
        }
    }

    /**
     * Update version fields to store semantic version string.
     */
    protected function update_5()
    {
        // Update version field type.
        /** @var Schema $schema */
        $schema = $this->db->getSchemaManager()->createSchema();
        $schema = clone $schema;

        $estimates = $schema->getTable('estimates');
        $estimates->changeColumn('version', array(
            'type' => Type::getType('string'),
            'length' => 32,
        ));

        $samples = $schema->getTable('samples');
        $samples->changeColumn('version', array(
            'type' => Type::getType('string'),
            'length' => 32,
        ));

        $sampleValues = $schema->getTable('sample_values');
        $sampleValues->changeColumn('version', array(
            'type' => Type::getType('string'),
            'length' => 32,
        ));

        $synchronizer = new SingleDatabaseSynchronizer($this->db);
        $synchronizer->updateSchema($schema);

        // Convert existing data.
        $this->db->createQueryBuilder()
            ->update('estimates', 'e')
            ->set('e.version', 'CONCAT(e.version, ".0")')
            ->execute();

        $this->db->createQueryBuilder()
            ->update('samples', 's')
            ->set('s.version', 'CONCAT(s.version, ".0")')
            ->execute();

        $this->db->createQueryBuilder()
            ->update('sample_values', 'sv')
            ->set('sv.version', 'CONCAT(sv.version, ".0")')
            ->execute();
    }

    /**
     * Get median value from past incomplete runs.
     */
    protected function update_6()
    {
        /** @var ResultStatement $estimates */
        $estimates = $this->db->createQueryBuilder()
          ->select('e.*')
          ->from('estimates', 'e')
          ->where('e.data != ""')
          ->execute();


        while (($estimate = $estimates->fetch(\PDO::FETCH_OBJ))) {

            /** @var EstimateDistribution $distribution */
            $distribution = unserialize($estimate->data);

            try {
                $estimateInterval = $distribution->getMedian(true);

                $estimateDate = new \DateTime('@' . $_SERVER['REQUEST_TIME']);
                $estimateDate->add(\DateInterval::createFromDateString($estimateInterval . ' seconds'));
            }
            catch (\RuntimeException $e) {
                $estimateDate = null;
            }

            $this->db->createQueryBuilder()
              ->update('estimates', 'e')
              ->set('e.estimate', ':estimate')
              ->where('e.version = :version')
              ->andWhere('e.when = :when')
              ->setParameter('estimate', $this->db->convertToDatabaseValue($estimateDate, 'date'))
              ->setParameter('version', $estimate->version)
              ->setParameter('when', $estimate->when)
              ->execute();
        }
    }

    /**
     * Create a state table, and move the db version value from a file to it.
     */
    protected function update_7()
    {
        /** @var Schema $schema */
        $schema = $this->db->getSchemaManager()->createSchema();
        $schema = clone $schema;

        $state = $schema->createTable('state');
        $state->addColumn('key', 'string', array('length' => 255));
        $state->addColumn('value', 'blob');
        $state->setPrimaryKey(array('key'));

        $synchronizer = new SingleDatabaseSynchronizer($this->db);
        $synchronizer->updateSchema($schema);


        $this->db->insert(
            'state',
            array(
                $this->db->quoteIdentifier('key') => 'InstallationVersion',
                $this->db->quoteIdentifier('value') => 7
            )
        );

        unlink($this->configDir . '/InstallationVersion');
    }
}
