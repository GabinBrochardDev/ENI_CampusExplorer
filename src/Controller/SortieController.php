<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\EventListener\SortieListener; 

class SortieController extends AbstractController
{
    #[Route('/sortie/create', name: 'sortie_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, EtatRepository $etatRepository, Security $security): Response
    {
        $sortie = new Sortie();
    
        // Récupérer l'utilisateur connecté pour l'organisateur
        $organisateur = $security->getUser();
        $sortie->setOrganisateur($organisateur);
      
        // Création du formulaire
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Validation des dates
            if ($sortie->getDateLimiteInscription() >= $sortie->getDateHeureDebut()) {
                $this->addFlash('error', 'La date limite d\'inscription doit être antérieure à la date de début.');
            } else {
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
        }
    
        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    
    #[Route('/sortie/modifier/{id}', name: 'sortie_modifier')]
    public function modifier(Sortie $sortie, Request $request, EntityManagerInterface $entityManager, Security $security, SortieListener $sortieListener): Response
    {
        $this->checkIfOrganisateur($sortie, $security);
    
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Validation des dates
            if ($sortie->getDateLimiteInscription() >= $sortie->getDateHeureDebut()) {
                $this->addFlash('error', 'La date limite d\'inscription doit être antérieure à la date de début.');
            } else {
                $entityManager->flush();
                $sortieListener->updateSortieState($sortie, $entityManager);

                return $this->redirectToRoute('home');
            }
        }
    
        return $this->render('sortie/modifier.html.twig', [
            'form' => $form->createView(),
            'sortie' => $sortie
        ]);
    }
    
    #[Route('/sortie/publier/{id}', name: 'sortie_publier')]
    public function publier(Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository, Security $security): Response
    {
        $this->checkIfOrganisateur($sortie, $security);
    
        // Validation des dates
        if ($sortie->getDateLimiteInscription() >= $sortie->getDateHeureDebut()) {
            $this->addFlash('error', 'La date limite d\'inscription doit être antérieure à la date de début.');
            return $this->redirectToRoute('sortie_modifier', ['id' => $sortie->getId()]);
        }
    
        // Passer l'état à "Ouverte"
        $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
        $sortie->setEtat($etatOuverte);
    
        // Enregistrer les changements
        $entityManager->persist($sortie);
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

    #[Route('/sortie/annuler/{id}', name: 'sortie_annuler')]
    public function annuler(Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository, Security $security): Response
    {
        // Vérifier si l'utilisateur est l'organisateur ou un administrateur
        if ($sortie->getOrganisateur() !== $security->getUser() && !$security->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à gérer cette sortie.');
        }
    
        // Passer l'état de la sortie à "Annulée"
        $etatAnnulee = $etatRepository->findOneBy(['libelle' => 'Annulée']);
        $sortie->setEtat($etatAnnulee);
    
        // Enregistrer les changements
        $entityManager->persist($sortie);
        $entityManager->flush();
    
        return $this->redirectToRoute('home');
    }

    #[Route('/sortie/{id}/inscrire', name: 'sortie_inscrire')]
    public function inscrire(Sortie $sortie, Security $security, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        $user = $security->getUser();
        $now = new \DateTime();
        
        // Vérifier si la date limite d'inscription est dépassée
        if ($sortie->getDateLimiteInscription() < $now) {
            $etatCloturee = $etatRepository->findOneBy(['libelle' => 'Clôturée']);
            $sortie->setEtat($etatCloturee);
            $entityManager->persist($sortie);
            $entityManager->flush();
        }

        // Vérifier si la date de la sortie est dépassée d'un mois
        $oneMonthAfter = (clone $sortie->getDateHeureDebut())->modify('+1 month');
        if ($now > $oneMonthAfter) {
            $etatHistorisee = $etatRepository->findOneBy(['libelle' => 'Historisée']);
            $sortie->setEtat($etatHistorisee);
            $entityManager->persist($sortie);
            $entityManager->flush();
        }

        if ($sortie->getEtat()->getLibelle() === 'Ouverte' && $sortie->getOrganisateur() !== $user && !$sortie->getEstInscrit()->contains($user)) {
            $sortie->addEstInscrit($user);
            $entityManager->persist($sortie);
            $entityManager->flush();

            // Vérifier si le nombre de participants atteint le maximum
            if ($sortie->getEstInscrit()->count() >= $sortie->getNbInscriptionMax()) {
                $etatCloturee = $etatRepository->findOneBy(['libelle' => 'Clôturée']);
                $sortie->setEtat($etatCloturee);
                $entityManager->persist($sortie);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('home', ['id' => $sortie->getId()]);
    }

    #[Route('/sortie/{id}/desister', name: 'sortie_desister')]
    public function desister(Sortie $sortie, Security $security, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        $user = $security->getUser();
        if ($sortie->getEstInscrit()->contains($user)) {
            $sortie->removeEstInscrit($user);
            $entityManager->persist($sortie);
            $entityManager->flush();

            // Vérifier si le nombre de participants est inférieur au maximum
            if ($sortie->getEstInscrit()->count() < $sortie->getNbInscriptionMax()) {
                $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
                $sortie->setEtat($etatOuverte);
                $entityManager->persist($sortie);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('home', ['id' => $sortie->getId()]);
    }

    #[Route('/sortie/{id}/afficher', name: 'sortie_afficher')]
    public function afficher(Sortie $sortie): Response
    {
        // Récupérer les personnes inscrites à la sortie
        $inscrits = $sortie->getEstInscrit();

        return $this->render('sortie/afficher.html.twig', [
            'sortie' => $sortie,
            'inscrits' => $inscrits,
        ]);
    }
}