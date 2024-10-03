<?php
namespace App\EventListener;

use App\Entity\Sortie;
use App\Repository\EtatRepository;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;

class SortieListener
{
    private $etatRepository;

    public function __construct(EtatRepository $etatRepository)
    {
        $this->etatRepository = $etatRepository;
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad(PostLoadEventArgs $args)
    {
        $entity = $args->getObject();

        // Vérifier si l'entité est une instance de Sortie
        if (!$entity instanceof Sortie) {
            return;
        }

        $entityManager = $args->getObjectManager();
        $this->updateSortieState($entity, $entityManager);
    }

    public function updateSortieState(Sortie $sortie, EntityManagerInterface $entityManager): void
    {
        $now = new \DateTime();

        // Ne pas modifier l'état si la sortie est en "En création" ou "Annulée"
        if (in_array($sortie->getEtat()->getLibelle(), ['En création', 'Annulée'])) {
            return;
        }

        // Vérifier si la date limite d'inscription est dépassée
        if ($sortie->getDateLimiteInscription() < $now) {
            $etatCloturee = $this->etatRepository->findOneBy(['libelle' => 'Clôturée']);
            $sortie->setEtat($etatCloturee);
        }

        // Calcul de l'heure de fin de la sortie
        $heureFin = (clone $sortie->getDateHeureDebut())->modify('+' . $sortie->getDuree() . ' minutes');

        // Vérifier si l'heure actuelle est entre le début et la fin de la sortie (sortie en cours)
        if ($now >= $sortie->getDateHeureDebut() && $now <= $heureFin) {
            $etatEnCours = $this->etatRepository->findOneBy(['libelle' => 'En cours']);
            $sortie->setEtat($etatEnCours);
        }

        // Vérifier si la date de la sortie est dépassée d'un mois
        $oneMonthAfter = (clone $sortie->getDateHeureDebut())->modify('+1 month');
        if ($now > $oneMonthAfter) {
            $etatHistorisee = $this->etatRepository->findOneBy(['libelle' => 'Historisée']);
            $sortie->setEtat($etatHistorisee);
        }

        // Si la sortie est encore ouverte et les dates sont valides, la remettre à "Ouverte"
        if ($sortie->getDateLimiteInscription() >= $now && $now <= $oneMonthAfter) {
            $etatOuverte = $this->etatRepository->findOneBy(['libelle' => 'Ouverte']);
            $sortie->setEtat($etatOuverte);
        }

        // NE PAS FLUSH ICI, L'ÉVÉNEMENT POSTLOAD N'EST PAS CONÇU POUR CELA
        // Le flush doit être appelé dans une autre méthode après avoir modifié l'état
        // $entityManager->flush();
    }
}
