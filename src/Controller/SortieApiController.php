<?php

namespace App\Controller;

use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SortieApiController extends AbstractController
{
    #[Route('/api/sorties', name: 'api_sorties', methods: ['GET'])]
    public function getSorties(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Crée le query builder pour récupérer toutes les sorties sans filtres
        $queryBuilder = $entityManager->getRepository(Sortie::class)->createQueryBuilder('s');

        // Exécuter la requête et récupérer tous les résultats
        $sorties = $queryBuilder->getQuery()->getArrayResult(); // Utiliser getArrayResult() pour éviter la sérialisation

        // Vérifier s'il y a des résultats
        if (empty($sorties)) {
            return $this->json(['message' => 'Aucune sortie trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Renvoyer les sorties directement sous forme de tableau JSON
        return $this->json($sorties, 200);
    }
}
