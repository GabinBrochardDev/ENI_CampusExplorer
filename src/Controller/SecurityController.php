<?php

namespace App\Controller;

use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Security;

class SecurityController extends AbstractController
{
    private $participantRepository;

    public function __construct(ParticipantRepository $participantRepository)
    {
        $this->participantRepository = $participantRepository;
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, Security $security): Response
    {
        // Si l'utilisateur est déjà connecté, redirigez-le
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        // Récupérer les erreurs de connexion
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier identifiant saisi (email ou pseudo)
        $identifier = $authenticationUtils->getLastUsername();

        // Récupérer l'utilisateur soit par pseudo, soit par email
        $participant = $this->participantRepository->findOneBy(['email' => $identifier]) 
            ?? $this->participantRepository->findOneBy(['pseudo' => $identifier]);

        if ($participant) {
            // Utilisation de l'alias de l'authentificateur pour éviter les erreurs
            $security->login($participant, 'security.authenticator.form_login.main');
        }

        return $this->render('security/login.html.twig', [
            'login' => $identifier, // Notez que j'ai changé 'nom' en 'login'
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
