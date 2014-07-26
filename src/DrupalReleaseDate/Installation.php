<?php
namespace DrupalReleaseDate;

use \Silex\Application;

class Installation
{

    protected $app;

    public static function getVersionFromUpdateMethod($method)
    {
        return (int) substr($method, 7);
    }

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getVersion()
    {
        if (file_exists($this->app['config.dir'] . '/InstallationVersion')) {
            return (int) file_get_contents($this->app['config.dir'] . '/InstallationVersion');
        }

        return null;
    }

    /**
     * @param int $value
     */
    public function setVersion($value)
    {
        file_put_contents($this->app['config.dir'] . '/InstallationVersion', $value);
    }

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

    public function install()
    {
        if ($this->getVersion() !== null) {
            return;
        }

        $this->doInstall();

        $version = 0;
        $updateMethods = $this->getUpdates();
        if (!empty($updateMethods)) {
            $lastUpdate = end($updateMethods);
            $version = self::getVersionFromUpdateMethod($lastUpdate);
        }
        $this->setVersion($version);
    }

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
    public function doInstall()
    {
        $schema = new \Doctrine\DBAL\Schema\Schema();

        $samples = $schema->createTable('samples');
        $samples->addColumn('version', 'smallint');
        $samples->addColumn('when', 'datetime');
        $samples->addColumn('notes', 'string', array('default' => ''));
        $samples->setPrimaryKey(array('version', 'when'));

        $sample_values = $schema->createTable('sample_values');
        $sample_values->addColumn('version', 'smallint');
        $sample_values->addColumn('when', 'datetime');
        $sample_values->addColumn('key', 'string', array('length' => 64));
        $sample_values->addColumn('value', 'smallint', array('notnull' => false));
        $sample_values->setPrimaryKey(array('version', 'when', 'key'));

        $estimates = $schema->createTable('estimates');
        $estimates->addColumn('version', 'smallint');
        $estimates->addColumn('when', 'datetime');
        $estimates->addColumn('started', 'datetime');
        $estimates->addColumn('completed', 'datetime', array('notnull' => false));
        $estimates->addColumn('estimate', 'datetime', array('notnull' => false));
        $estimates->addColumn('note', 'string', array('default' => ''));
        $estimates->addColumn('data', 'blob');

        $synchronizer = new \Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer($this->app['db']);
        $synchronizer->createSchema($schema);
    }

    /**
     * Normalize sample storage in database.
     */
    public function update_1 ()
    {
        $schema = $this->app['db']->getSchemaManager()->createSchema();
        $synchronizer = new \Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer($this->app['db']);

        // Create sample_values table.
        $schema = clone $schema;
        $sample_values = $schema->createTable('sample_values');
        $sample_values->addColumn('version', 'smallint');
        $sample_values->addColumn('when', 'datetime');
        $sample_values->addColumn('key', 'string', array('length' => 64));
        $sample_values->addColumn('value', 'smallint', array('notnull' => false));
        $sample_values->setPrimaryKey(array('version', 'when', 'key'));

        $synchronizer->updateSchema($schema);

        // Select all samples.
        $samples = $this->app['db']->createQueryBuilder()
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
        while ($sample = $samples->fetch(\PDO::FETCH_ASSOC)) {
            foreach ($keys as $key) {
                $this->app['db']->insert(
                    $this->app['db']->quoteIdentifier('sample_values'),
                    array(
                        $this->app['db']->quoteIdentifier('version') => $sample['version'],
                        $this->app['db']->quoteIdentifier('when') => $sample['when'],
                        $this->app['db']->quoteIdentifier('key') => $key,
                        $this->app['db']->quoteIdentifier('value') => $sample[$key],
                    )
                );
            }
        }

        // Update samples table structure to remove columns
        $schema = clone $schema;
        $samples = $schema->getTable('samples');
        $samples->dropColumn('critical_tasks');
        $samples->dropColumn('critical_bugs');
        $samples->dropColumn('major_tasks');
        $samples->dropColumn('major_bugs');
        $samples->dropColumn('normal_tasks');
        $samples->dropColumn('normal_bugs');

        $synchronizer->updateSchema($schema);
    }

    /**
     * Convert estimate to only store date and not time.
     */
    public function update_2()
    {

        $schema = $this->app['db']->getSchemaManager()->createSchema();
        $synchronizer = new \Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer($this->app['db']);

        $schema = clone $schema;
        $schema
            ->getTable('estimates')
            ->getColumn('estimate')
            ->setType(\Doctrine\DBAL\Types\Type::getType('date'));

        $synchronizer->updateSchema($schema);
    }

    /**
     * Add column to estimates to record time of completion.
     */
    public function update_3()
    {

        $schema = $this->app['db']->getSchemaManager()->createSchema();
        $synchronizer = new \Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer($this->app['db']);

        $schema = clone $schema;
        $estimates = $schema->getTable('estimates');
        $estimates->addColumn('started', 'datetime');
        $estimates->addColumn('completed', 'datetime', array('notnull' => false));

        $synchronizer->updateSchema($schema);

        $this->app['db']->createQueryBuilder()
            ->update('estimates', 'e')
            ->set('e.started', 'e.when')
            ->set('e.completed', 'e.when')
            ->execute();
    }

    /**
     * Update format of data for existing estimates.
     */
    public function update_4()
    {
        $estimates = $this->app['db']->createQueryBuilder()
            ->select('e.*')
            ->from('estimates', 'e')
            ->where('e.data != ""')
            ->execute();


        while ($estimate = $estimates->fetch(\PDO::FETCH_OBJ)) {
            $dataArray = unserialize($estimate->data);

            $dataObject = \DrupalReleaseDate\EstimateDistribution::fromArray($dataArray);

            $this->app['db']->createQueryBuilder()
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
}
