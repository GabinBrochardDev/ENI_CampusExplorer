<?php

namespace App\Controller;

use App\Repository\LieuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LieuController extends AbstractController
{
    #[Route('/fetch-lieu-details', name: 'fetch_lieu_details')]
    public function fetchLieuDetails(Request $request, LieuRepository $lieuRepository): Response
    {
        $lieuId = $request->request->get('lieuId'); // Récupération de l'ID du lieu

        if (!$lieuId) {
            return new Response('Lieu ID non fourni', 400);
        }

        $lieu = $lieuRepository->find($lieuId);

        if (!$lieu) {
            return new Response('Lieu non trouvé', 404);
        }

        return new Response('<p>Rue : ' . $lieu->getRue() . '</p>');
    }
}
