<?php

namespace App\Repository;

use App\Entity\SaRebate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SaRebate>
 */
class SaRebateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaRebate::class);
    }

    /**
     * Find the SA rebate band matching a given Sum Assured value.
     *
     * Matching rules:
     *  - minSumAssured <= sumAssured
     *  - maxSumAssured >= sumAssured  OR  maxSumAssured IS NULL (open-ended top band)
     */
    public function findRebateForSumAssured(float $sumAssured): ?SaRebate
    {
        return $this->createQueryBuilder('r')
            ->where('r.minSumAssured <= :sa')
            ->andWhere('r.maxSumAssured >= :sa OR r.maxSumAssured IS NULL')
            ->setParameter('sa', $sumAssured)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
