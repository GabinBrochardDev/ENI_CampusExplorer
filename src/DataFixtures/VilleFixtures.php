<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use App\Entity\Lieu;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class VilleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Liste des villes avec leur code postal et des lieux associés
        $villesData = [
            [
                'nom' => 'Niort', 
                'codePostal' => '79000', 
                'lieux' => [
                    ['nom' => 'Bar Le Chamois', 'rue' => '5 Rue Basse', 'latitude' => 46.32422, 'longitude' => -0.46297],
                    ['nom' => 'Cinéma CGR Niort', 'rue' => '11 Rue du Maréchal Leclerc', 'latitude' => 46.32114, 'longitude' => -0.46581]
                ]
            ],
            [
                'nom' => 'Rennes', 
                'codePostal' => '35000', 
                'lieux' => [
                    ['nom' => 'Parc du Thabor', 'rue' => 'Place Saint-Melaine', 'latitude' => 48.113475, 'longitude' => -1.669681],
                    ['nom' => 'Musée de Bretagne', 'rue' => '10 Cours des Alliés', 'latitude' => 48.105983, 'longitude' => -1.677269]
                ]
            ],
            [
                'nom' => 'Nantes', 
                'codePostal' => '44000', 
                'lieux' => [
                    ['nom' => 'Château des Ducs de Bretagne', 'rue' => '4 Place Marc Elder', 'latitude' => 47.216019, 'longitude' => -1.549444],
                    ['nom' => 'Les Machines de l\'île', 'rue' => 'Boulevard Léon Bureau', 'latitude' => 47.206449, 'longitude' => -1.566729]
                ]
            ]
        ];

        // Boucle sur chaque ville et ajout des lieux associés
        foreach ($villesData as $villeData) {
            // Créer une ville
            $ville = new Ville();
            $ville->setNom($villeData['nom']);
            $ville->setCodePostal($villeData['codePostal']);
            $manager->persist($ville);

            // Ajouter les lieux pour la ville
            foreach ($villeData['lieux'] as $lieuData) {
                $lieu = new Lieu();
                $lieu->setNom($lieuData['nom'])
                     ->setRue($lieuData['rue'])
                     ->setLatitude($lieuData['latitude'])
                     ->setLongitude($lieuData['longitude'])
                     ->setVille($ville);

                $manager->persist($lieu);
            }
        }

        // Sauvegarder les données en base
        $manager->flush();
    }
}
