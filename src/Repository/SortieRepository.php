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

    public function findByFilters($campusId, $search, $startDate, $endDate, $isOrganisateur, $isInscrit, $isNonInscrit, $isTerminees, $sort, Participant $user)
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.etat', 'e')
            ->andWhere('e.libelle != :historisee')
            ->setParameter('historisee', 'Historisée');

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
            $qb->andWhere('e.libelle IN (:terminees)')
            ->setParameter('terminees', ['Terminée', 'Annulée']);
        } else {
            // Par défaut, exclure les "Terminée" et "Annulée" si la case n'est pas cochée
            $qb->andWhere('e.libelle NOT IN (:terminees)')
            ->setParameter('terminees', ['Terminée', 'Annulée']);
        }

        // Appliquer le tri
        switch ($sort) {
            case 'dateAsc':
                $qb->orderBy('s.dateHeureDebut', 'ASC');
                break;
            case 'dateDesc':
                $qb->orderBy('s.dateHeureDebut', 'DESC');
                break;
            case 'clotureAsc':
                $qb->orderBy('s.dateLimiteInscription', 'ASC');
                break;
            case 'clotureDesc':
                $qb->orderBy('s.dateLimiteInscription', 'DESC');
                break;
            default:
                // Tri par défaut, par exemple par date de sortie croissante
                $qb->orderBy('s.dateHeureDebut', 'ASC');
                break;
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
