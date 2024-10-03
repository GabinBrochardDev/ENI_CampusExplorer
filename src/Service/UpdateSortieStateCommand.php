<?php

namespace App\Service;

use App\Repository\SortieRepository;
use App\Service\SortieStateManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateSortieStateCommand extends Command
{
    protected static $defaultName = 'app:update-sortie-state';

    private $sortieRepository;
    private $sortieStateManager;
    private $entityManager;

    public function __construct(
        SortieRepository $sortieRepository,
        SortieStateManager $sortieStateManager,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();

        $this->sortieRepository = $sortieRepository;
        $this->sortieStateManager = $sortieStateManager;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Vérifie et met à jour les états des sorties')
            ->setHelp('Cette commande permet de vérifier et de mettre à jour les états des sorties régulièrement.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer toutes les sorties
        $sorties = $this->sortieRepository->findAll();

        $io->progressStart(count($sorties));

        foreach ($sorties as $sortie) {
            // Appeler la méthode qui met à jour l'état
            $this->sortieStateManager->updateState($sortie, $this->entityManager);
            $io->progressAdvance();
        }

        // Enregistrer les changements en base de données
        $this->entityManager->flush();

        $io->progressFinish();
        $io->success('Les états des sorties ont été mis à jour.');

        return Command::SUCCESS;
    }
}
