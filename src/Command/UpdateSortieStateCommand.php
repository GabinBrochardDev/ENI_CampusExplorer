<?php

namespace App\Command;

use App\Repository\SortieRepository;
use App\Service\SortieStateManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateSortieStateCommand extends Command
{
    protected static $defaultName = 'update:sortie-state';
    private $sortieRepository;
    private $sortieStateManager;
    private $entityManager;

    public function __construct(SortieRepository $sortieRepository, SortieStateManager $sortieStateManager, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->sortieRepository = $sortieRepository;
        $this->sortieStateManager = $sortieStateManager;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Met à jour les états des sorties selon les dates.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sorties = $this->sortieRepository->findAll();

        foreach ($sorties as $sortie) {
            // Appel du service qui met à jour l'état
            $this->sortieStateManager->updateState($sortie, $this->entityManager);
        }

        $io->success('Les états des sorties ont été mis à jour avec succès.');

        return Command::SUCCESS;
    }
}
