<?php

namespace App\Repository;

use App\Entity\Hostel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Hostel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hostel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hostel[]    findAll()
 * @method Hostel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HostelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hostel::class);
    }

    public function findHostelsWithFilter(?string $filter)
    {
        $qb = $this->createQueryBuilder('h');

        if ($filter) {
            $qb
                ->andWhere('h.room_types LIKE :filter OR h.partner_id LIKE :filter')
                ->setParameter('filter', '%'.$filter.'%');
        }

        return $qb
            ->orderBy('h.status', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Hostel[] Returns an array of Hostel objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Hostel
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
