<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Doctrine\DBAL\Connection as DbConnection;

class Cron
{
    protected $repositoryUpdater;

    public function __construct(DbConnection $db)
    {
        $this->repositoryUpdater = new \DrupalReleaseDate\Repository\Updater($db);
    }

    public function emptyResponse()
    {
        return '';
    }

    /**
     * Calculate a new estimate for the release date based on current samples.
     */
    public function updateEstimate(Application $app)
    {
        $config = array();

        if (!empty($app['config']['estimate.timeout'])) {
            $config['timeout'] = $app['config']['estimate.timeout'];
        }
        if (!empty($app['config']['estimate.iterations'])) {
            $config['iterations'] = $app['config']['estimate.iterations'];
        }

        $this->repositoryUpdater->estimate($config);

        return '';
    }

    /**
     * Fetch the latest issue counts from Drupal.org and add them to the
     * database as a new sample.
     */
    public function fetchCounts(Application $app)
    {
        $guzzleClient = new \Guzzle\Http\Client();
        if (!empty($app['config']['guzzle']['userAgent'])) {
            $guzzleClient->setUserAgent($app['config']['guzzle']['userAgent'], true);
        }

        $this->repositoryUpdater->samples($guzzleClient, $app['config']['drupal_issues']);

        return '';
    }
}
