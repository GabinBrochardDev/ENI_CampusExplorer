<?php

namespace App\Controller;

use App\Repository\SortieRepository;
use App\Repository\CampusRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, SortieRepository $sortieRepository, CampusRepository $campusRepository ): Response
    {
        // Récupérer les filtres depuis la requête
        $campusId = $request->query->get('campus');
        $search = $request->query->get('search');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $isOrganisateur = $request->query->get('organisateur');
        $isInscrit = $request->query->get('inscrit');
        $isNonInscrit = $request->query->get('non-inscrit');
        $isTerminees = $request->query->get('terminees');

        // Filtrer les sorties avec le repository
        $sorties = $sortieRepository->findByFilters(
            $campusId,
            $search,
            $startDate,
            $endDate,
            $isOrganisateur,
            $isInscrit,
            $isNonInscrit,
            $isTerminees,
            $this->getUser() // Le participant connecté
        );


        // Récupérer tous les campus depuis la base de données
        $campus = $campusRepository->findAll();

        // Passer les campus à la vue
        return $this->render('home/index.html.twig', [
            'sorties' => $sorties,
            'campuses' => $campus,
        ]);
    }
}
