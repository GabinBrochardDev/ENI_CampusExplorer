<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Entity\Etat;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class SortieController extends AbstractController
{
    #[Route('/sortie/create', name: 'sortie_create')]
    public function create(
        Request $request, 
        EntityManagerInterface $entityManager, 
        EtatRepository $etatRepository, 
        CampusRepository $campusRepository, 
        Security $security // Injection du service Security pour récupérer l'utilisateur connecté
    ): Response {
        $sortie = new Sortie();

        // Récupérer l'utilisateur connecté pour le définir comme organisateur
        $organisateur = $security->getUser();

        // Récupérer la liste des campus (si nécessaire)
        $campusList = $campusRepository->findAll();

        // Créer le formulaire pour la sortie
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Déterminer l'état en fonction de l'action
            $action = $request->request->get('action');
            if ($action === 'publish') {
                $etat = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
            } else {
                $etat = $etatRepository->findOneBy(['libelle' => 'En création']);
            }

            // Associer l'état et l'organisateur à la sortie
            $sortie->setEtat($etat);
            $sortie->setOrganisateur($organisateur);  // Organisateur défini comme l'utilisateur connecté

            // Sauvegarder la sortie
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('home');  // Redirection vers la liste des sorties
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
            'campusList' => $campusList,  // Passer la liste des campus à la vue Twig
        ]);
    }

    #[Route('/sortie/{id}', name: 'sortie_show')]
    public function show(Sortie $sortie): Response
    {
        // Afficher les détails de la sortie, y compris les participants inscrits
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }
}
