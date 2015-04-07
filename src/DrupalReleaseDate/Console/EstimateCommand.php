<?php
namespace DrupalReleaseDate\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use DrupalReleaseDate\Repository\Updater;

class EstimateCommand extends Command
{
    protected function configure()
    {
        $this
          ->setName('estimate')
          ->setDescription('Generate estimate with latest data');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication()->getContainer();

        $repositoryUpdater = new Updater($app['db']);

        $config = array();

        if (isset($app['config']['estimate.timeout'])) {
            $config['timeout'] = $app['config']['estimate.timeout'];
        }
        if (!empty($app['config']['estimate.iterations'])) {
            $config['iterations'] = $app['config']['estimate.iterations'];
        }

        $repositoryUpdater->estimate($config);

        // TODO output success message.

    }
}
