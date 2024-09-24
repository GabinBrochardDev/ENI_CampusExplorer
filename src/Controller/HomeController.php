<?php
// src/Controller/HomeController.php

namespace App\Controller;

use App\Entity\Campus;
use App\Repository\CampusRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(CampusRepository $campusRepository): Response
    {

        // Récupérer tous les campus depuis la base de données
        $campus = $campusRepository->findAll();

        // Passer les campus à la vue
        return $this->render('home/index.html.twig', [
            'campus' => $campus,
        ]);
    }
}
