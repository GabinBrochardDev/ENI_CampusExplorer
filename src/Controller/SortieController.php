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
            $this->addFlash('success', 'La sortie a été créée avec succès.');
            return $this->redirectToRoute('home');
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/sortie/publier/{id}', name: 'sortie_publier')]
    public function publier(Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository, Security $security): Response
    {
        $this->checkIfOrganisateur($sortie, $security);

        // Passer l'état à "Ouverte"
        $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
        $sortie->setEtat($etatOuverte);

        // Enregistrer les changements
        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'La sortie a été publiée avec succès.');
        return $this->redirectToRoute('home');
    }

    #[Route('/sortie/modifier/{id}', name: 'sortie_modifier')]
    public function modifier(Sortie $sortie, Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $this->checkIfOrganisateur($sortie, $security);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La sortie a été modifiée avec succès.');
            return $this->redirectToRoute('home');
        }

        return $this->render('sortie/modifier.html.twig', [
            'form' => $form->createView(),
            'sortie' => $sortie
        ]);
    }

    #[Route('/sortie/supprimer/{id}', name: 'sortie_supprimer')]
    public function supprimer(Sortie $sortie, EntityManagerInterface $entityManager, Security $security): Response
    {
        $this->checkIfOrganisateur($sortie, $security);

        // Supprimer la sortie
        $entityManager->remove($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'La sortie a été supprimée avec succès.');
        return $this->redirectToRoute('home');
    }

    #[Route('/sortie/annuler/{id}', name: 'sortie_annuler')]
    public function annuler(Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository, Security $security): Response
    {
        $this->checkIfOrganisateur($sortie, $security);

        // Passer l'état de la sortie à "Clôturée"
        $etatCloturee = $etatRepository->findOneBy(['libelle' => 'Clôturée']);
        $sortie->setEtat($etatCloturee);

        // Enregistrer les changements
        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'La sortie a été annulée et est désormais clôturée.');

        return $this->redirectToRoute('home');
    }

    private function checkIfOrganisateur(Sortie $sortie, Security $security): void
    {
        if ($sortie->getOrganisateur() !== $security->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à gérer cette sortie.');
        }
    }
}
