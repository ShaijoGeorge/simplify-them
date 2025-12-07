<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * @return Client[] Returns an array of Client objects
     */
    public function findBirthdaysThisMonth(int $agencyId): array
    {
        // We use Doctrine's DAY() and MONTH() functions if available, 
        // but standard SQL is safer for compatibility.
        // Here is a raw SQL approach for reliability across MySQL versions:
        
        $conn = $this->getEntityManager()->getConnection();
        $currentMonth = (int) date('m');

        $sql = '
            SELECT * FROM client c 
            WHERE c.agency_id = :agencyId 
            AND MONTH(c.dob) = :currentMonth
            ORDER BY DAY(c.dob) ASC
        ';

        $resultSet = $conn->executeQuery($sql, [
            'agencyId' => $agencyId,
            'currentMonth' => $currentMonth
        ]);

        // Convert raw arrays back to Objects (optional, or just pass array to view)
        // For dashboard simplicity, let's return the raw array data
        return $resultSet->fetchAllAssociative();
    }

    //    /**
    //     * @return Client[] Returns an array of Client objects
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

    //    public function findOneBySomeField($value): ?Client
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
