<?php
namespace DrupalReleaseDate\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use DrupalReleaseDate\Repository\Updater;

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

        $guzzleClient = new \Guzzle\Http\Client();
        if (!empty($app['config']['guzzle']['userAgent'])) {
            $guzzleClient->setUserAgent($app['config']['guzzle']['userAgent'], true);
        }

        $repositoryUpdater->samples($guzzleClient, $app['config']['drupal_issues']);

        // TODO output success message.

    }
}
