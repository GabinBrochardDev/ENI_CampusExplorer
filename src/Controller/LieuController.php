<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LieuController extends AbstractController
{
    #[Route('/fetch-lieu-details', name: 'fetch_lieu_details')]
    public function fetchLieuDetails(Request $request, LieuRepository $lieuRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['lieuId'])) {
            return new Response(json_encode(['error' => 'Lieu ID non fourni']), 400, ['Content-Type' => 'application/json']);
        }

        $lieuId = $data['lieuId'];
        $lieu = $lieuRepository->find($lieuId);

        if (!$lieu) {
            return new Response(json_encode(['error' => 'Lieu non trouvé']), 404, ['Content-Type' => 'application/json']);
        }

        // Répondre avec les détails du lieu, y compris la latitude et la longitude
        return new Response(json_encode([
            'rue' => $lieu->getRue(),
            'latitude' => $lieu->getLatitude(),
            'longitude' => $lieu->getLongitude()
        ]), 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/fetch-lieux-by-ville', name: 'fetch_lieux_by_ville', methods: ['POST'])]
    public function fetchLieuxByVille(Request $request, LieuRepository $lieuRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['villeId'])) {
            return new Response(json_encode(['error' => 'Ville ID non fourni']), 400, ['Content-Type' => 'application/json']);
        }

        $villeId = $data['villeId'];
        $lieux = $lieuRepository->findBy(['ville' => $villeId]);

        $lieuxData = [];
        foreach ($lieux as $lieu) {
            $lieuxData[] = [
                'id' => $lieu->getId(),
                'nom' => $lieu->getNom(),
            ];
        }

        return new Response(json_encode($lieuxData), 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/create-lieu', name: 'create_lieu', methods: ['POST'])]
    public function createLieu(Request $request, VilleRepository $villeRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les données JSON envoyées
        $data = json_decode($request->getContent(), true);
    
        // Vérification des champs reçus
        if (!isset($data['ville']) || !isset($data['nom']) || !isset($data['rue']) || 
            !isset($data['latitude']) || !isset($data['longitude'])) {
            return new Response(json_encode(['error' => 'Tous les champs sont obligatoires.']), 400, ['Content-Type' => 'application/json']);
        }
    
        // Récupérer l'entité Ville à partir de l'ID
        $ville = $villeRepository->find($data['ville']);
        if (!$ville) {
            return new Response(json_encode(['error' => 'Ville non trouvée.']), 404, ['Content-Type' => 'application/json']);
        }
    
        // Création d'une nouvelle entité Lieu
        $lieu = new Lieu();
        $lieu->setNom($data['nom']);
        $lieu->setRue($data['rue']);
        $lieu->setLatitude($data['latitude']);
        $lieu->setLongitude($data['longitude']);
        $lieu->setVille($ville);
    
        try {
            // Sauvegarder l'entité Lieu
            $entityManager->persist($lieu);
            $entityManager->flush();
        } catch (\Exception $e) {
            return new Response(json_encode(['error' => 'Erreur lors de la création du lieu.']), 500, ['Content-Type' => 'application/json']);
        }
    
        // Message de confirmation et redirection
        return new Response(json_encode(['success' => 'Le lieu a bien été créé.']), 200, ['Content-Type' => 'application/json']);
    }
    

}