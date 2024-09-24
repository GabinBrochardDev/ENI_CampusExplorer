<?php

namespace App\Controller;

use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(SortieRepository $sortieRepository): Response
    {
        // Récupérer toutes les sorties
        $sorties = $sortieRepository->findAll();

        // Passer les sorties à la vue Twig
        return $this->render('home/index.html.twig', [
            'sorties' => $sorties
        ]);
    }
}
