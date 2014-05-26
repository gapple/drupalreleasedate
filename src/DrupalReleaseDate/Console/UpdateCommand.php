<?php
namespace DrupalReleaseDate\Console;

use DrupalReleaseDate\Installation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Run system updates');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $installation = new Installation($this->getApplication()->getContainer());
        $installation->update();
        $output->writeln('Update Complete');
    }
}
