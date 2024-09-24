<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Entity\Etat;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\SortieType;  // Import correct

class SortieController extends AbstractController
{
    #[Route('/sortie/create', name: 'sortie_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie); // Assurez-vous d'avoir un SortieType form
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Déterminer l'état en fonction de l'action
            $action = $request->request->get('action');
            if ($action === 'publish') {
                $etat = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
            } else {
                $etat = $etatRepository->findOneBy(['libelle' => 'En création']);
            }

            $sortie->setEtat($etat);

            // Sauvegarder la sortie
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('home'); // Remplacez 'sortie_list' par la route de votre liste de sorties
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/sortie/publier/{id}", name="sortie_publier")
     */
    public function publier(Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        // Changer l'état de la sortie à "Ouverte"
        $etat = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
        $sortie->setEtat($etat);
        
        // Sauvegarder les changements
        $entityManager->persist($sortie);
        $entityManager->flush();

        return $this->redirectToRoute('home'); // Redirection vers la liste des sorties
    }
}
