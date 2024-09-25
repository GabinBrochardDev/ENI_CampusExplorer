<?php

namespace App\Repository;

use App\Entity\Campus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campus>
 */
class CampusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campus::class);
    }

     /**
     * Recherche les campus dont le nom contient le terme donné
     *
     * @param string|null $nom
     * @return Campus[]
     */
    public function findByNomLike(?string $nom): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($nom) {
            $qb->andWhere('c.nom LIKE :nom')
               ->setParameter('nom', '%' . $nom . '%');
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Campus[] Returns an array of Campus objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Campus
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
