<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManagerInterface;

#[AsCommand(name: 'debug:selection', description: 'Print selection for a given namespace and manager')]
final class DebugSelectionCommand extends Command
{
    public function __construct(private readonly ContainerInterface $container)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('namespace', InputArgument::REQUIRED, 'Selection namespace')
            ->addOption('manager', null, InputOption::VALUE_REQUIRED, 'Selection manager name', 'default')
            ->addOption('owner', null, InputOption::VALUE_REQUIRED, 'Owner identifier (optional)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $namespace = (string) $input->getArgument('namespace');
        $managerName = (string) $input->getOption('manager');
        $owner = $input->getOption('owner');

        $serviceId = 'persistent_state.selection.manager.'.$managerName;
        if (!$this->container->has($serviceId)) {
            $io->error(sprintf('Selection manager "%s" not found (service id "%s").', $managerName, $serviceId));

            return Command::FAILURE;
        }

        $manager = $this->container->get($serviceId);
        if (!$manager instanceof SelectionManagerInterface) {
            $io->error(sprintf('Service "%s" is not a SelectionManagerInterface.', $serviceId));

            return Command::FAILURE;
        }

        $selection = $manager->getSelection($namespace, $owner);
        $ids = $selection->getSelectedIdentifiers();
        $total = $selection->getTotal();
        $isAll = $selection->isSelectedAll();

        $io->writeln(sprintf('Namespace: <info>%s</info>', $namespace));
        if ($owner) {
            $io->writeln(sprintf('Owner:     <info>%s</info>', $owner));
        }
        $io->writeln(sprintf('Total items in source: <info>%d</info>', $total));
        $io->writeln(sprintf('Select all state:      <info>%s</info>', $isAll ? 'YES' : 'NO'));
        $io->writeln('');

        if ([] === $ids) {
            $io->writeln($isAll ? '(all items selected, no exclusions)' : '(no items selected)');

            return Command::SUCCESS;
        }

        $io->title($isAll ? 'Exclusions' : 'Selected Identifiers');
        $io->listing(array_map(fn($id) => (string) $id, $ids));

        return Command::SUCCESS;
    }
}
