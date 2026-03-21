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

    public function findRevivalOpportunities(int $agencyId): array
    {
        $sixMonthsAgo = new \DateTime('-6 months');

        return $this->createQueryBuilder('p')
        ->andWhere('p.agency = :agencyId')
        ->andWhere('p.status = :status')
        ->andWhere('p.status != :excludedStatus')
        ->andWhere('p.nextDueDate <= :date')
        ->setParameter('agencyId', $agencyId)
        ->setParameter('status', 'LAPSED')
        ->setParameter('excludedStatus', 'PAID_UP')
        ->setParameter('date', $sixMonthsAgo)
        ->orderBy('p.nextDueDate', 'DESC')
        ->getQuery()
        ->getResult();
    }

    public function countRevivalOpportunities(int $agencyId): int
    {
        $sixMonthsAgo = new \DateTime('-6 months');

        return (int) $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->andWhere('p.agency = :agencyId')
            ->andWhere('p.status = :status')
            ->andWhere('p.status != :excludedStatus')
            ->andWhere('p.nextDueDate <= :date')
            ->setParameter('agencyId', $agencyId)
            ->setParameter('status', 'LAPSED')
            ->setParameter('excludedStatus', 'PAID_UP')
            ->setParameter('date', $sixMonthsAgo)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActivePolicies(int $agencyId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->andWhere('p.agency = :agencyId')
            ->andWhere('p.status = :status')
            ->setParameter('agencyId', $agencyId)
            ->setParameter('status', 'IN_FORCE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find policies for the Due List across all three zones:
     *  - Recovery zone (past): FUP in the past (up to 6 months back)
     *  - Current target: FUP in the current month
     *  - Pipeline (future): FUP in the next 1-3 months
     *
     * Includes IN_FORCE and LAPSED (for recovery follow-up).
     * Eager-loads client, licPlan, and licPlan.planType for GST/flag calculations.
     *
     * @return Policy[]
     */
    public function findDueListPolicies(int $agencyId, \DateTime $fromDate, \DateTime $toDate): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.client', 'c')
            ->join('p.licPlan', 'lp')
            ->leftJoin('lp.planType', 'pt')
            ->addSelect('c', 'lp', 'pt')
            ->where('p.agency = :agencyId')
            ->andWhere('p.status IN (:statuses)')
            ->andWhere('p.fup IS NOT NULL')
            ->andWhere('p.fup BETWEEN :fromDate AND :toDate')
            ->setParameter('agencyId', $agencyId)
            ->setParameter('statuses', ['IN_FORCE', 'LAPSED'])
            ->setParameter('fromDate', $fromDate)
            ->setParameter('toDate', $toDate)
            ->orderBy('p.fup', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
