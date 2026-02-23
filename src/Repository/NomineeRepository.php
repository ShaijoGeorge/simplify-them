<?php

namespace App\Repository;

use App\Entity\Nominee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

// @extends ServiceEntityRepository<Nominee>
class NomineeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Nominee::class);
    }

    // Returns the total share percentage for all nominees on a given policy.
    public function getTotalShareForPolicy(int $policyId, ?int $excludeNomineeId = null): float
    {
        $qb = $this->createQueryBuilder('n')
            ->select('SUM(n.sharePercentage)')
            ->where('n.policy = :policyId')
            ->setParameter('policyId', $policyId);

        if ($excludeNomineeId !== null) {
            $qb->andWhere('n.id != :excludeId')
               ->setParameter('excludeId', $excludeNomineeId);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }
}