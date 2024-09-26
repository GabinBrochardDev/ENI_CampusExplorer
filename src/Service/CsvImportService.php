<?php

namespace App\Service;

use App\Entity\Campus;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CsvImportService
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function importParticipants(UploadedFile $file): array
    {
        $participants = [];
        $errors = [];

        $campusRepository = $this->entityManager->getRepository(Campus::class);

        // Ouvrir le fichier CSV
        if (($handle = fopen($file->getPathname(), "r")) !== false) {
            // Lire le fichier CSV ligne par ligne
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                // Vérifier si le nombre de colonnes est correct
                if (count($data) < 7) {
                    $errors[] = 'Ligne mal formatée : ' . implode(';', $data);
                    continue;
                }

                // Les colonnes sont : email, nom, prenom, telephone, pseudo, campus_nom, password
                [
                    $email,
                    $nom,
                    $prenom,
                    $telephone,
                    $pseudo,
                    $nomCampus,
                    $password
                ] = $data;

                // Vérifier si l'email existe déjà
                $existingParticipant = $this->entityManager->getRepository(Participant::class)->findOneBy(['email' => $email]);

                if (!$existingParticipant) {

                    // Rechercher le campus correspondant au nom
                    $campus = $campusRepository->findOneBy(['nom' => $nomCampus]);

                    if (!$campus) {
                        $campus = new Campus();
                        $campus->setNom($nomCampus);
                        $this->entityManager->persist($campus);
                    }

                    // Créer le participant
                    $participant = new Participant();
                    $participant->setEmail($email);
                    $participant->setNom($nom);
                    $participant->setPrenom($prenom);
                    $participant->setTelephone($telephone);
                    $participant->setPseudo($pseudo);
                    $participant->setCampus($campus);
                    $participant->setPassword($this->passwordHasher->hashPassword($participant, $password));
                    $participant->setIsActive(true);
                    $participant->setRoles(['ROLE_USER']);
                    $this->entityManager->persist($participant);
                    $participants[] = $participant;
                }
            }

            // Sauvegarder les changements en base de données
            $this->entityManager->flush();

            // Fermer le fichier CSV
            fclose($handle);
        }

        // Retourner les participants et les erreurs
        return ['participants' => $participants, 'errors' => $errors];
    }
}
