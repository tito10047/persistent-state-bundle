<?php

namespace Tito10047\PersistentStateBundle\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceManagerInterface;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceSessionStorage;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentStateBundle\Storage\DoctrinePreferenceStorage;

#[AsCommand(name: 'debug:preference', description: 'Print preferences for a given context and manager')]
final class DebugPreferenceCommand extends Command
{
    public function __construct(private readonly ContainerInterface $container)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('context', InputArgument::REQUIRED, 'Context key (e.g. "user_15")')
            ->addOption('manager', null, InputOption::VALUE_REQUIRED, 'Preference manager name', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $context = (string) $input->getArgument('context');
        $managerName = (string) $input->getOption('manager');

        $serviceId = 'persistent.preference.manager.' . $managerName;
        if (!$this->container->has($serviceId)) {
            $io->error(sprintf('Preference manager "%s" not found (service id "%s").', $managerName, $serviceId));
            return Command::FAILURE;
        }

        $manager = $this->container->get($serviceId);
        if (!$manager instanceof PreferenceManagerInterface) {
            $io->error(sprintf('Service "%s" is not a PreferenceManagerInterface.', $serviceId));
            return Command::FAILURE;
        }

        $storage = $manager->getPreferenceStorage();
        $storageName = $this->detectStorageName($storage);

        $preference = $manager->getPreference($context);
        $all = $preference->all();

        $io->writeln(sprintf('Context: %s', $context));
        $io->writeln(sprintf('Storage: %s', $storageName));
        $io->writeln('');

        if ($all === []) {
            $io->writeln('(no preferences)');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($all as $k => $v) {
            $rows[] = [
                (string) $k,
                $this->stringifyValue($v),
            ];
        }

        $io->table(['Key', 'Value'], $rows);

        return Command::SUCCESS;
    }

    private function detectStorageName(PreferenceStorageInterface $storage): string
    {
        return match (true) {
            $storage instanceof DoctrinePreferenceStorage => 'doctrine',
            $storage instanceof PreferenceSessionStorage => 'session',
            default => (new \ReflectionClass($storage))->getShortName(),
        };
    }

    private function stringifyValue(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return match (true) {
                $value === null => 'null',
                is_bool($value) => $value ? 'true' : 'false',
                default => (string) $value,
            };
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
