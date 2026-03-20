<?php

namespace App\Repository;

use App\Entity\PolicyStatusLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PolicyStatusLog>
 */
class PolicyStatusLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PolicyStatusLog::class);
    }

    /**
     * Returns the full status transition history for a given policy,
     * ordered most recent first.
     *
     * @return PolicyStatusLog[]
     */
    public function getHistoryForPolicy(int $policyId, int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.policy = :policyId')
            ->setParameter('policyId', $policyId)
            ->orderBy('l.transitionedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns recent transitions across all policies.
     *
     * @return PolicyStatusLog[]
     */
    public function getRecentTransitions(int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.policy', 'p')
            ->orderBy('l.transitionedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
