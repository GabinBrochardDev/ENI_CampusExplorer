<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Charger les autres fixtures
        $this->addFixture(new VilleFixtures(), $manager);
        // Ajoutez d'autres fixtures si nécessaire
    }

    private function addFixture(Fixture $fixture, ObjectManager $manager): void
    {
        $fixture->load($manager);
    }
}