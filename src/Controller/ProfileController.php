<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ProfileType;
use App\Repository\LieuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function editProfile(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserInterface $user, 
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger // Ajoutez le Slugger pour gérer le nom de fichier
        ): Response
    {
        // Vérifiez si l'utilisateur est une instance de Participant
        if (!$user instanceof Participant) {
            throw new \LogicException('The user is not a valid Participant.');
        }

        $participant = $user;

        $form = $this->createForm(ProfileType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Gestion de la photo de profil
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try{
                    $photoFile->move(
                        $this->getParameter('profile_photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléchargement de la photo.');
                }

                // met à jour la propriété photo de l'utilisateur
                $participant->setPhoto($newFilename);
            }

            // Vérifiez si le mot de passe est fourni
            $plainPassword = $form->get('password')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashedPassword);
            }


            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    #[Route('/fetch_lieux_by_ville', name: 'fetch_lieux_by_ville', methods: ['POST'])]
    public function fetchLieuxByVille(Request $request, LieuRepository $lieuRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $villeId = $data['villeId'] ?? null;

        if ($villeId) {
            $lieux = $lieuRepository->findBy(['ville' => $villeId]);
            $result = [];

            foreach ($lieux as $lieu) {
                $result[] = [
                    'id' => $lieu->getId(),
                    'nom' => $lieu->getNom(),
                ];
            }

            return new JsonResponse($result);
        }

        return new JsonResponse([]);
    }
}