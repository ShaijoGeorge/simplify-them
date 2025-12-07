<?php

namespace App\Repository;

use App\Entity\Policy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Policy>
 */
class PolicyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Policy::class);
    }

    /**
     * @return float Returns the total premium amount due this month for a specific agency
     */
    public function getPremiumDueAmountThisMonth(int $agencyId): float
    {
        $start = new \DateTime('first day of this month 00:00:00');
        $end = new \DateTime('last day of this month 23:59:59');

        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.totalPremium) as total')
            ->where('p.agency = :agencyId')
            ->andWhere('p.nextDueDate BETWEEN :start AND :end')
            ->andWhere('p.status = :status')
            ->setParameter('agencyId', $agencyId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', 'IN_FORCE') // Only count active policies
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    //    /**
    //     * @return Policy[] Returns an array of Policy objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Policy
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
