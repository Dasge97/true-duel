<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ClasificacionCompetitivaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:competitive:run', description: 'Ejecuta el job competitivo periodico.')]
final class RunCompetitiveJobCommand extends Command
{
    public function __construct(private readonly ClasificacionCompetitivaService $clasificacionCompetitivaService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->clasificacionCompetitivaService->ejecutarJobCompetitivo();
            $output->writeln('[app:competitive:run] ' . json_encode($result, JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>[app:competitive:run] Failed: ' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
