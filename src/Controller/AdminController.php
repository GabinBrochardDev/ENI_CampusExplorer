<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CampusRepository; // Ajoute cette ligne pour importer le repository Campus
use Symfony\Component\HttpFoundation\Request; // Ajoute cette ligne pour importer la classe Request
use Doctrine\ORM\EntityManagerInterface; // Ajoute cette ligne pour importer l'EntityManager
use App\Form\CampusSearchType; // Ajoute cette ligne pour importer le formulaire
use App\Entity\Campus; // Ajoute cette ligne pour importer l'entité Campus
use App\Form\CampusType; // Ajoute cette ligne pour importer le formulaire CampusType


class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        // Vérification de l'accès pour les administrateurs
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/dashboard.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/manage/campus', name: 'admin_manage_campus')]
    public function manageCampus(Request $request, CampusRepository $campusRepository, EntityManagerInterface $entityManager): Response
    {
        // Créer un formulaire de recherche pour filtrer les campus
        $form = $this->createForm(CampusSearchType::class);
        $form->handleRequest($request);
        
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Si le formulaire est soumis, filtrer les résultats
        $campusList = $campusRepository->findAll(); // Par défaut, on affiche tous les campus
        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $campusList = $campusRepository->findByNomLike($criteria['nom']);
        }

        return $this->render('admin/manage_campus.html.twig', [
            'campusList' => $campusList,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/manage/campus/add', name: 'admin_add_campus')]
    public function addCampus(Request $request, EntityManagerInterface $entityManager): Response
    {
        $campus = new Campus();
        $form = $this->createForm(CampusType::class, $campus);

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($campus);
            $entityManager->flush();

            return $this->redirectToRoute('admin_manage_campus');
        }

        return $this->render('admin/add_campus.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/manage/campus/edit/{id}', name: 'admin_edit_campus')]
    public function editCampus(Campus $campus, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CampusType::class, $campus);

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_manage_campus');
        }

        return $this->render('admin/edit_campus.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/manage/campus/delete/{id}', name: 'admin_delete_campus')]
    public function deleteCampus(Campus $campus, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($campus);
        $entityManager->flush();
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        return $this->redirectToRoute('admin_manage_campus');
    }

    #[Route('/admin/manage/villes', name: 'admin_manage_villes')]
    public function manageVilles(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Logique pour gérer les villes
        return $this->render('admin/manage_villes.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/manage/users', name: 'admin_manage_users')]
    public function manageUsers(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Logique pour gérer les utilisateurs
        return $this->render('admin/manage_users.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
