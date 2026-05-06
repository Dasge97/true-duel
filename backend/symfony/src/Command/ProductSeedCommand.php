<?php

declare(strict_types=1);

namespace App\Command;

use App\Seed\ProductSeedRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:product:seed', description: 'Siembra o resetea el baseline de producto.')]
final class ProductSeedCommand extends Command
{
    public function __construct(
        private readonly ProductSeedRunner $productSeedRunner,
        private readonly \Doctrine\Migrations\DependencyFactory $dependencyFactory,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('reset', null, InputOption::VALUE_NONE, 'Limpia baseline y lo vuelve a sembrar.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reset = (bool) $input->getOption('reset');

        try {
            $this->dependencyFactory->getMetadataStorage()->ensureInitialized();
            $result = $this->productSeedRunner->run($reset);

            if ($result['reset']) {
                $output->writeln('<info>[app:product:seed]</info> Product baseline reset and seeded.');

                return Command::SUCCESS;
            }

            $output->writeln($result['freshInstall']
                ? '<info>[app:product:seed]</info> Fresh install baseline seeded.'
                : '<info>[app:product:seed]</info> Catalogs refreshed, existing player data preserved.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>[app:product:seed] Failed: ' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
