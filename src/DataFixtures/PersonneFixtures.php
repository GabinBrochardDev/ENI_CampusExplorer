<?php

namespace App\DataFixtures;

use App\Entity\Participant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer le participant administrateur
        $admin = new Participant();
        $admin->setEmail('admin@campusexplorer');
        $admin->setNom('admin');
        $admin->setPrenom('campusexplorer');
        $admin->setLastName('Admin'); // Vous pouvez ajuster cette valeur selon vos besoins
        $admin->setTelephone('0000000000');
        $admin->setIsActive(true);

        // Hacher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'campusexplorer'
        );
        $admin->setPassword($hashedPassword);

        // Assigner le campus "Nantes"
        // Assurez-vous que la fixture CampusFixtures a déjà été chargée et que la référence est disponible
        $campus = $this->getReference(CampusFixtures::CAMPUS_REFERENCE . 'nantes');
        $admin->setCampus($campus);

        // Persister l'entité
        $manager->persist($admin);
        $manager->flush();

        // Optionnel : Ajouter une référence pour pouvoir l'utiliser dans d'autres fixtures
        $this->addReference('admin-participant', $admin);
    }

    public function getDependencies()
    {
        return [
            CampusFixtures::class,
        ];
    }
}
