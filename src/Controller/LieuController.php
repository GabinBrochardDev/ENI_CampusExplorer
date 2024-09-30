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
        // Récupération du contenu JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Vérification que lieuId est bien présent
        if (!isset($data['lieuId'])) {
            return new Response(json_encode(['error' => 'Lieu ID non fourni']), 400, ['Content-Type' => 'application/json']);
        }

        $lieuId = $data['lieuId'];
        $lieu = $lieuRepository->find($lieuId);

        if (!$lieu) {
            return new Response(json_encode(['error' => 'Lieu non trouvé']), 404, ['Content-Type' => 'application/json']);
        }

        // Répondre avec les détails du lieu au format HTML
        return new Response('<p>Rue : ' . $lieu->getRue() . '</p>');
    }
}
