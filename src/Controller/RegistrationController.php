<?php

namespace App\Controller;

use App\Entity\Participant; // Utilisez Participant au lieu de User
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        // Crée un nouveau Participant
        $participant = new Participant();
        
        // Crée le formulaire d'inscription
        $form = $this->createForm(RegistrationFormType::class, $participant);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Encode le mot de passe saisi
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword(
                $participant,
                $plainPassword
            );
            $participant->setPassword($hashedPassword);
            
            // Par défaut, rendre le participant actif
            $participant->setIsActive(true);

            // Sauvegarde le nouveau participant dans la base de données
            $entityManager->persist($participant);
            $entityManager->flush();

            // Redirection ou message de succès
            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
            
            // Redirige l'utilisateur vers la page de login après inscription
            return $this->redirectToRoute('login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
