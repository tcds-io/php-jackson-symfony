<?php

namespace Tcds\Io\Jackson\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'jackson:configure',
    description: 'Configures Jackson config file into the project',
)]
class ConfigureJacksonCommand extends Command
{
    private const string SOURCE_FILE = __DIR__ . '/../../config/jackson.php';

    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $configPathFile = 'config/jackson.php';
        $destination = "$this->projectDir/$configPathFile";

        if (file_exists($destination)) {
            $message = sprintf('File "%s" already exists. Do you want to overwrite?', $configPathFile);

            if (!$io->confirm($message, false)) {
                $io->warning('Installation cancelled.');

                return Command::SUCCESS;
            }
        }

        if (!copy(self::SOURCE_FILE, $destination)) {
            $io->error(sprintf('Failed to write file: %s', $configPathFile));

            return Command::FAILURE;
        }

        $io->success(sprintf('Created: %s', $configPathFile));

        return Command::SUCCESS;
    }
}
