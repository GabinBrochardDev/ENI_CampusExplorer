<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    public const CAMPUS_REFERENCE = 'campus-';

    public function load(ObjectManager $manager): void
    {
        $nomsCampus = ['ENI Nantes', 'ENI Rennes', 'ENI Quimper', 'ENI Niort'];

        foreach ($nomsCampus as $index => $nom) {
            $campus = new Campus();
            $campus->setNom($nom);

            $manager->persist($campus);

            // Optionnel : Ajouter une référence pour pouvoir la réutiliser dans d'autres fixtures
            $this->addReference(self::CAMPUS_REFERENCE . strtolower($nom), $campus);
        }

        $manager->flush();
    }
}
