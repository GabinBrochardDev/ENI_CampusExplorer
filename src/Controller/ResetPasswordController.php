<?php

namespace App\Controller;

use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password', name: 'app_forgot_password')]
    public function request(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator
    ): Response
    {

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $participant = $entityManager->getRepository(Participant::class)->findOneBy(['email' => $email]);

            if ($participant){
                // Génerer un token de réinitialisation
                $resetToken = $tokenGenerator->generateToken();
                $participant->setResetToken($resetToken);
                $participant->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

                $entityManager->persist($participant);
                $entityManager->flush();

                // Envoyer l'email avec le lien de réinitialisation
                $emailMessage = (new Email())
                    ->from('support@supportix.fr')
                    ->to($participant->getEmail())
                    ->subject('SORTIR.COM - Reinitialisation de votre mot de passe')
                    ->html($this->renderView('emails/reset_password.html.twig', [
                        'resetToken' => $resetToken,
                    ]));

                $mailer->send($emailMessage);

                return $this->redirectToRoute('app_check_email');
            }

            // Retourner une réponse même si l'email n'est pas trouvé, pour des raisons de sécurité
            return $this->redirectToRoute('app_check_email');
        }

        return $this->render('security/request_reset_password.html.twig');
    }


    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request,
        EntityManagerInterface $entityManager,
        string $token,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $participant = $entityManager->getRepository(Participant::class)->findOneBy(['resetToken' => $token]);

        if (!$participant || $participant->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
             // Token invalide ou expiré
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            $hashedPassword = $passwordHasher->hashPassword(
                $participant,
                $newPassword
            );
            $participant->setPassword($hashedPassword);

            // Supprimer le toke après avoir changer le mot de passe
            $participant->setResetToken(null);
            $participant->setResetTokenExpiresAt(null);

            $entityManager->persist($participant);
            $entityManager->flush();

            return $this->redirectToRoute('login');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
        ]);
    }

    #[Route('reset-password/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('security/check_email.html.twig');
    }

}
