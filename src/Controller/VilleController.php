<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\VilleRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\VilleSearchType;
use App\Entity\Ville;
use App\Form\VilleType;

class VilleController extends AbstractController
{
    #[Route('/admin/manage/villes', name: 'admin_manage_villes')]
    public function manageVilles(Request $request, VilleRepository $villeRepository): Response
    {
        $form = $this->createForm(VilleSearchType::class);
        $form->handleRequest($request);

        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $villeList = $villeRepository->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $villeList = $villeRepository->findByNomLike($criteria['nom']);
        }

        return $this->render('admin/ville/manage_villes.html.twig', [
            'villeList' => $villeList,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/manage/ville/add', name: 'admin_add_ville')]
    public function addVille(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ville = new Ville();
        $form = $this->createForm(VilleType::class, $ville);
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ville);
            $entityManager->flush();

            return $this->redirectToRoute('admin_manage_villes');
        }

        return $this->render('admin/ville/add_ville.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/manage/ville/edit/{id}', name: 'admin_edit_ville')]
    public function editVille(Ville $ville, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VilleType::class, $ville);
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_manage_villes');
        }

        return $this->render('admin/ville/edit_ville.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/manage/ville/delete/{id}', name: 'admin_delete_ville')]
    public function deleteVille(Ville $ville, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager->remove($ville);
        $entityManager->flush();

        return $this->redirectToRoute('admin_manage_villes');
    }
}
