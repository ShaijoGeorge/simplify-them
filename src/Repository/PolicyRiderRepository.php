<?php

namespace App\Repository;

use App\Entity\PolicyRider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PolicyRider>
 */
class PolicyRiderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PolicyRider::class);
    }

    /**
     * Returns all active riders for a given policy.
     *
     * @return PolicyRider[]
     */
    public function findActiveByPolicy(int $policyId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.policy = :policyId')
            ->andWhere('r.isActive = true')
            ->setParameter('policyId', $policyId)
            ->orderBy('r.riderStartDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns true when the policy has at least one active DAB rider
     * that has not yet expired.
     */
    public function hasActiveDabRider(int $policyId): bool
    {
        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.policy = :policyId')
            ->andWhere('r.riderType = :type')
            ->andWhere('r.isActive = true')
            ->andWhere('r.riderEndDate IS NULL OR r.riderEndDate >= :today')
            ->setParameter('policyId', $policyId)
            ->setParameter('type', PolicyRider::TYPE_DAB)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Returns the total annual rider premium for a given policy.
     * Used in the receipt PDF premium breakdown.
     */
    public function getTotalRiderPremiumForPolicy(int $policyId): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.riderPremium)')
            ->where('r.policy = :policyId')
            ->andWhere('r.isActive = true')
            ->setParameter('policyId', $policyId)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }
}