<?php
namespace App\EventListener;

use App\Entity\Sortie;
use App\Repository\EtatRepository;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping as ORM;

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
        $now = new \DateTime();

        // Vérifier si la date limite d'inscription est dépassée
        if ($entity->getDateLimiteInscription() < $now) {
            $etatCloturee = $this->etatRepository->findOneBy(['libelle' => 'Clôturée']);
            $entity->setEtat($etatCloturee);
            $entityManager->persist($entity);
            $entityManager->flush();
        }

        // Vérifier si la date de la sortie est dépassée d'un mois
        $oneMonthAfter = (clone $entity->getDateHeureDebut())->modify('+1 month');
        if ($now > $oneMonthAfter) {
            $etatHistorisee = $this->etatRepository->findOneBy(['libelle' => 'Historisée']);
            $entity->setEtat($etatHistorisee);
            $entityManager->persist($entity);
            $entityManager->flush();
        }
    }
}