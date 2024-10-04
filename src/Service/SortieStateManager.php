<?php
// src/Service/SortieStateManager.php

namespace App\Service;

use App\Entity\Sortie;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;

class SortieStateManager
{
    private $etatRepository;

    public function __construct(EtatRepository $etatRepository)
    {
        $this->etatRepository = $etatRepository;
    }

    public function updateState(Sortie $sortie, EntityManagerInterface $entityManager): void
    {
        $now = new \DateTime();
        
        // Debug: Affichage des dates pour vérifier les valeurs
        

        // Ne pas modifier l'état si la sortie est en "En création" ou "Annulée"
        if (in_array($sortie->getEtat()->getLibelle(), ['En création', 'Annulée'])) {
            return;
        }

        // Calcul de l'heure de fin de la sortie
        $heureFin = (clone $sortie->getDateHeureDebut())->modify('+' . $sortie->getDuree() . ' minutes');

        // Vérification des états selon les contraintes

        // 1. Ouverte : Si la date actuelle est avant la date limite d'inscription
        if ($now < $sortie->getDateLimiteInscription()) {
         
            $etatOuverte = $this->etatRepository->findOneBy(['libelle' => 'Ouverte']);
            if ($etatOuverte !== null) {
                $sortie->setEtat($etatOuverte);
            }
        }
        // 2. Clôturée : Si la date actuelle est entre la date limite d'inscription et la date de début de la sortie
        elseif ($now >= $sortie->getDateLimiteInscription() && $now < $sortie->getDateHeureDebut()) {
          
            $etatCloturee = $this->etatRepository->findOneBy(['libelle' => 'Clôturée']);
            if ($etatCloturee !== null) {
                $sortie->setEtat($etatCloturee);
            }
        }
        // 3. En cours : Si la date actuelle est entre la date de début de la sortie et la fin de la sortie
        elseif ($now >= $sortie->getDateHeureDebut() && $now <= $heureFin) {
           
            $etatEnCours = $this->etatRepository->findOneBy(['libelle' => 'En cours']);
            if ($etatEnCours !== null) {
                $sortie->setEtat($etatEnCours);
            }
        }
        // 4. Terminée : Si la date actuelle est après la fin de la sortie et moins d'un mois après
        elseif ($now > $heureFin && $now < (clone $heureFin)->modify('+1 month')) {
           
            $etatTerminee = $this->etatRepository->findOneBy(['libelle' => 'Terminée']);
            if ($etatTerminee !== null) {
                $sortie->setEtat($etatTerminee);
            }
        }
        // 5. Historisée : Si la date actuelle est plus d'un mois après la fin de la sortie
        elseif ($now >= (clone $heureFin)->modify('+1 month')) {
        
            $etatHistorisee = $this->etatRepository->findOneBy(['libelle' => 'Historisée']);
            if ($etatHistorisee !== null) {
                $sortie->setEtat($etatHistorisee);
            }
        }

        // Sauvegarder les changements dans la base de données
        $entityManager->persist($sortie);
        $entityManager->flush();  // Important : Sauvegarder les modifications après persist
    }
}
