<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Form\PasswordChangeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    #[Route('/admin/manage/users', name: 'admin_manage_users')]
    public function manageUsers(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Créer le formulaire de recherche
        $form = $this->createFormBuilder()
            ->add('nom', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Rechercher par nom']
            ])
            ->getForm();

        $form->handleRequest($request);

        $users = $entityManager->getRepository(Participant::class)->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $users = $entityManager->getRepository(Participant::class)->findBy(['nom' => $criteria['nom']]);
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
            $plainPassword = $form->get('password')->getData();
            $encodedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($encodedPassword);

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
    public function editUser(
        Participant $user, 
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
        ): Response
    {
        $form = $this->createForm(ParticipantType::class, $user);
        $passwordForm = $this->createForm(PasswordChangeType::class);

        $form->handleRequest($request);
        $passwordForm->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('isAdmin')->getData()) {
                $roles = $user->getRoles();
                $roles[] = 'ROLE_ADMIN';
                $user->setRoles($roles);
            } else {
                $roles = array_diff($user->getRoles(), ['ROLE_ADMIN']);
                $user->setRoles($roles);
            }

            // Gestion de la photo de profil
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'.'.uniqid().'.'.$photoFile->guessExtension();
                
                try{
                    $photoFile->move(
                        $this->getParameter('profile_photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléchargement de la photo.');
                }

                // met à jour la propriété photo de l'utilisateur
                $user->setPhoto($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('admin_manage_users');
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $newPassword = $passwordForm->get('newPassword')->getData();
            $encodedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($encodedPassword);

            $entityManager->flush();

            return $this->redirectToRoute('admin_manage_users');
        }

        return $this->render('admin/user/edit_user.html.twig', [
            'form' => $form->createView(),
            'passwordForm' => $passwordForm->createView(),
            'participant' => $user
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