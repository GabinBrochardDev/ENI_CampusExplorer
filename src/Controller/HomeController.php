<?php

namespace App\Controller;

use App\Repository\SortieRepository;
use App\Entity\Campus;
use App\Repository\CampusRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(SortieRepository $sortieRepository, CampusRepository $campusRepository ): Response
    {
        // Récupérer toutes les sorties
        $sorties = $sortieRepository->findAll();

        // Passer les sorties à la vue Twig

        // Récupérer tous les campus depuis la base de données
        $campus = $campusRepository->findAll();

        // Passer les campus à la vue
        return $this->render('home/index.html.twig', [
            'sorties' => $sorties,
            'campus' => $campus,
        ]);
    }
}
