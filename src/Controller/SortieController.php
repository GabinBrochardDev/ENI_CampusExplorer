<?php

// src/Controller/SortieController.php

namespace App\Controller;
 
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Form\SortieModifType;
use App\Repository\EtatRepository;
use App\Repository\VilleRepository;
use App\Service\SortieStateManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class SortieController extends AbstractController
{
    private function checkIfOrganisateur(Sortie $sortie, Security $security)
    {
        if ($sortie->getOrganisateur() !== $security->getUser() && !$security->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à gérer cette sortie.');
        }
    }

    #[Route('/sortie/create', name: 'sortie_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        EtatRepository $etatRepository,
        VilleRepository $villeRepository,
        Security $security
    ): Response {
        $sortie = new Sortie();

        // Récupérer l'utilisateur connecté pour l'organisateur
        $organisateur = $security->getUser();
        $sortie->setOrganisateur($organisateur);

        // Création du formulaire
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        // Récupérer les villes depuis le repository
        $villes = $villeRepository->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation des dates
            if ($sortie->getDateLimiteInscription() >= $sortie->getDateHeureDebut()) {
                $this->addFlash('error', 'La date limite d\'inscription doit être antérieure à la date de début.');
                return $this->render('sortie/create.html.twig', [
                    'form' => $form->createView(),
                    'villes' => $villes,
                    'villes' => $villes,
                ]);
            }
 
            // Définir l'état de la sortie selon l'action
            $action = $request->request->get('action');
            if ($action === 'publish') {
                $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
                $sortie->setEtat($etatOuverte);
            } else {
                $etatCreation = $etatRepository->findOneBy(['libelle' => 'En création']);
                $sortie->setEtat($etatCreation);
            }
 
            // Sauvegarder la sortie
            $entityManager->persist($sortie);
            $entityManager->flush();
 
            // Message de confirmation et redirection
            return $this->redirectToRoute('home');
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
            'villes' => $villes,
            'villes' => $villes,
        ]);
    }

    #[Route('/sortie/modifier/{id}', name: 'sortie_modifier')]
    public function modifier(
        Sortie $sortie,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security,
        SortieStateManager $sortieStateManager
    ): Response {
        $this->checkIfOrganisateur($sortie, $security);

        $form = $this->createForm(SortieModifType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation des dates
            if ($sortie->getDateLimiteInscription() >= $sortie->getDateHeureDebut()) {
                $this->addFlash('error', 'La date limite d\'inscription doit être antérieure à la date de début.');
            } else {
                // Mise à jour de l'état via le service
                $sortieStateManager->updateState($sortie, $entityManager);
                // Mise à jour de l'état via le service
                $sortieStateManager->updateState($sortie, $entityManager);
                $entityManager->flush();

                return $this->redirectToRoute('home');
            }
        }

        return $this->render('sortie/modifier.html.twig', [
            'form' => $form->createView(),
            'sortie' => $sortie
        ]);
    }

    #[Route('/sortie/publier/{id}', name: 'sortie_publier')]
    public function publier(
        Sortie $sortie,
        EntityManagerInterface $entityManager,
        EtatRepository $etatRepository,
        Security $security,
        SortieStateManager $sortieStateManager
    ): Response {
        $this->checkIfOrganisateur($sortie, $security);

        // Validation des dates
        if ($sortie->getDateLimiteInscription() >= $sortie->getDateHeureDebut()) {
            $this->addFlash('error', 'La date limite d\'inscription doit être antérieure à la date de début.');
            return $this->redirectToRoute('sortie_modifier', ['id' => $sortie->getId()]);
        }

        // Passer l'état à "Ouverte"
        $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
        $sortie->setEtat($etatOuverte);

        // Mise à jour de l'état via le service
        $sortieStateManager->updateState($sortie, $entityManager);

        // Enregistrer les changements
        $entityManager->flush();

        return $this->redirectToRoute('home');
    }
 
    #[Route('/sortie/supprimer/{id}', name: 'sortie_supprimer')]
    public function supprimer(Sortie $sortie, EntityManagerInterface $entityManager, Security $security): Response
    {
        $this->checkIfOrganisateur($sortie, $security);
 
        // Supprimer la sortie
        $entityManager->remove($sortie);
        $entityManager->flush();

        return $this->redirectToRoute('home');
    }
 
    #[Route('/sortie/annuler/{id}', name: 'sortie_annuler', methods: ['POST'])]
    public function annuler(
        Sortie $sortie,
        Request $request,
        EntityManagerInterface $entityManager,
        EtatRepository $etatRepository,
        Security $security,
        SortieStateManager $sortieStateManager
    ): Response {
        // Vérifier si l'utilisateur est l'organisateur ou un administrateur
        if ($sortie->getOrganisateur() !== $security->getUser() && !$security->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à gérer cette sortie.');
        }
 
        // Récupérer le motif de l'annulation depuis le formulaire
        $motif = $request->request->get('motif');
        if (empty($motif)) {
            $this->addFlash('error', 'Le motif de l\'annulation est requis.');
            return $this->redirectToRoute('home');
        }
 
        // Passer l'état de la sortie à "Annulée"
        $etatAnnulee = $etatRepository->findOneBy(['libelle' => 'Annulée']);
        $sortie->setEtat($etatAnnulee);
        $sortie->setMotifAnnulation($motif);

        // Mise à jour de l'état via le service
        $sortieStateManager->updateState($sortie, $entityManager);

        // Enregistrer les changements
        $entityManager->flush();
 
        return $this->redirectToRoute('home');
    }
 
    #[Route('/sortie/{id}/inscrire', name: 'sortie_inscrire')]
    public function inscrire(
        Sortie $sortie,
        Security $security,
        EntityManagerInterface $entityManager,
        SortieStateManager $sortieStateManager
    ): Response {
        $user = $security->getUser();

        if ($sortie->getEtat()->getLibelle() === 'Ouverte' && !$sortie->getEstInscrit()->contains($user)) {
            $sortie->addEstInscrit($user);
            $sortieStateManager->updateState($sortie, $entityManager);
            $sortieStateManager->updateState($sortie, $entityManager);
            $entityManager->flush();
        }

        return $this->redirectToRoute('home');
    }
 
    #[Route('/sortie/{id}/desister', name: 'sortie_desister')]
    public function desister(
        Sortie $sortie,
        Security $security,
        EntityManagerInterface $entityManager,
        SortieStateManager $sortieStateManager
    ): Response {

        $user = $security->getUser();

        if ($sortie->getEstInscrit()->contains($user)) {
            $sortie->removeEstInscrit($user);
            $sortieStateManager->updateState($sortie, $entityManager);
            $sortieStateManager->updateState($sortie, $entityManager);
            $entityManager->flush();
        }

        return $this->redirectToRoute('home');
    }
 
    #[Route('/sortie/{id}/afficher', name: 'sortie_afficher')]
    public function afficher(Sortie $sortie, SortieStateManager $sortieStateManager, EntityManagerInterface $entityManager): Response
    {
        $sortieStateManager->updateState($sortie, $entityManager);
        $entityManager->flush();

        // Récupérer les personnes inscrites à la sortie
        $inscrits = $sortie->getEstInscrit();
 
        return $this->render('sortie/afficher.html.twig', [
            'sortie' => $sortie,
            'inscrits' => $inscrits,
        ]);
    }
}
