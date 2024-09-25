<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Form\ParticipantSearchType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route('/admin/manage/users', name: 'admin_manage_users')]
    public function manageUsers(Request $request, ParticipantRepository $participantRepository): Response
    {
        // Vérification de l'accès pour les administrateurs
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Formulaire de recherche pour les utilisateurs
        $form = $this->createForm(ParticipantSearchType::class);
        $form->handleRequest($request);

        $users = $participantRepository->findAll(); // Afficher tous les utilisateurs par défaut

        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $users = $participantRepository->findByNomLike($criteria['nom']);
        }

        return $this->render('admin/user/manage_users.html.twig', [
            'users' => $users,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/manage/user/add', name: 'admin_add_user')]
    public function addUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new Participant();
        $form = $this->createForm(ParticipantType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe
            $plainPassword = $form->get('password')->getData();
            $encodedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($encodedPassword);

            // Gérer le rôle admin
            if ($form->get('isAdmin')->getData()) {
                $roles = $user->getRoles();
                $roles[] = 'ROLE_ADMIN';
                $user->setRoles($roles);
            } else {
                $roles = array_diff($user->getRoles(), ['ROLE_ADMIN']);
                $user->setRoles($roles);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('admin_manage_users');
        }

        return $this->render('admin/user/add_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/manage/user/edit/{id}', name: 'admin_edit_user')]
    public function editUser(Participant $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(ParticipantType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe est fourni, on l'encode
            $plainPassword = $form->get('password')->getData();
            if (!empty($plainPassword)) {
                $encodedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($encodedPassword);
            }

            // Gérer le rôle admin
            if ($form->get('isAdmin')->getData()) {
                $roles = $user->getRoles();
                $roles[] = 'ROLE_ADMIN';
                $user->setRoles($roles);
            } else {
                $roles = array_diff($user->getRoles(), ['ROLE_ADMIN']);
                $user->setRoles($roles);
            }

            $entityManager->flush();

            return $this->redirectToRoute('admin_manage_users');
        }

        return $this->render('admin/user/edit_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/manage/user/delete/{id}', name: 'admin_delete_user')]
    public function deleteUser(Participant $user, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('admin_manage_users');
    }
}
