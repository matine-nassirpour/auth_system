<?php

namespace App\Command;

use Exception;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DropAndRecreateDatabaseCommand extends Command
{
    protected static $defaultName = 'clean:database';

    protected function configure(): void
    {
        $this->setDescription('Drop and recreate the database with a "fake" set of data.');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Drop and recreate the database with a "fake" set of data.');

        $this->runTheCommand($input, $output, 'doctrine:database:drop', true);
        $this->runTheCommand($input, $output, 'doctrine:database:create');
        $this->runTheCommand($input, $output, 'doctrine:migrations:migrate');
        $this->runTheCommand($input, $output, 'doctrine:fixtures:load');

        $io->success('Well done !');

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function runTheCommand(
        InputInterface $input,
        OutputInterface $output,
        string $command,
        bool $forceFlag = false
    ): void
    {
        $application = $this->getApplication();

        if (!$application) {
            throw new LogicException('App not found :(');
        }

        $command = $application->find($command);

        if ($forceFlag) {
            $input = new ArrayInput([
                '--force' => true
            ]);
        }

        $input->setInteractive(false);

        $command->run($input, $output);
    }
}
