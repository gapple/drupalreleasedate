<?php
namespace DrupalReleaseDate\Console;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use DrupalReleaseDate\Repository\Updater;

/**
 * Command to retrieve samples from Drupal.org
 */
class SampleCommand extends Command
{
    protected function configure()
    {
        $this
          ->setName('sample')
          ->setDescription('Fetch issue count samples');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication()->getContainer();

        $repositoryUpdater = new Updater($app['db']);

        $guzzleClientConfig = [];
        if (!empty($app['config']['guzzle']['userAgent'])) {
            $guzzleClientConfig['headers']['User-Agent'] = $app['config']['guzzle']['userAgent'];
        }
        $guzzleClient = new Client($guzzleClientConfig);

        $repositoryUpdater->samples($guzzleClient, $app['config']['drupal_issues']);

        // TODO output success message.
    }
}
