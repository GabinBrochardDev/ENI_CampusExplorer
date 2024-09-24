<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function findByFilters($campusId, $search, $startDate, $endDate, $isOrganisateur, $isInscrit, $isNonInscrit, $isTerminees, Participant $user)
    {
        $qb = $this->createQueryBuilder('s');

        // Filtrer par campus
        if ($campusId) {
            $qb->andWhere('s.campus = :campusId')
               ->setParameter('campusId', $campusId);
        }

        // Filtrer par nom de sortie
        if ($search) {
            $qb->andWhere('s.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtrer par date de début
        if ($startDate) {
            $qb->andWhere('s.dateHeureDebut >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('s.dateHeureDebut <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        // Filtrer par organisateur (si l'utilisateur est l'organisateur)
        if ($isOrganisateur) {
            $qb->andWhere('s.organisateur = :user')
               ->setParameter('user', $user);
        }

        // Filtrer par inscription (inscrit ou non inscrit)
        if ($isInscrit) {
            $qb->andWhere(':user MEMBER OF s.estInscrit')
               ->setParameter('user', $user);
        }

        if ($isNonInscrit) {
            $qb->andWhere(':user NOT MEMBER OF s.estInscrit')
               ->setParameter('user', $user);
        }

        // Filtrer par sorties terminées
        if ($isTerminees) {
            $qb->leftJoin('s.etat', 'e')
               ->andWhere('e.libelle = :termine')
               ->setParameter('termine', 'Terminée');
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Sortie[] Returns an array of Sortie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sortie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
