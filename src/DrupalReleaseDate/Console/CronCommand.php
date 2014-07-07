<?php
namespace DrupalReleaseDate\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use DrupalReleaseDate\Repository\Updater;

class CronCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cron')
            ->setDescription('Run cron task')
            ->addArgument(
                'task',
                InputArgument::REQUIRED,
                '[samples|estimate]'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $task = $input->getArgument('task');

        $app = $this->getApplication()->getContainer();

        $repositoryUpdater = new Updater($app['db']);


        if ($task === 'samples') {
            $guzzleClient = new \Guzzle\Http\Client();
            if (!empty($app['config']['guzzle']['userAgent'])) {
                $guzzleClient->setUserAgent($app['config']['guzzle']['userAgent'], true);
            }

            $repositoryUpdater->samples($guzzleClient, $app['config']['drupal_issues']);
        }
        else if ($task === 'estimate') {
            $config = array();

            if (isset($app['config']['estimate.timeout'])) {
                $config['timeout'] = $app['config']['estimate.timeout'];
            }
            if (!empty($app['config']['estimate.iterations'])) {
                $config['iterations'] = $app['config']['estimate.iterations'];
            }

            $repositoryUpdater->estimate($config);
        }
    }
}
