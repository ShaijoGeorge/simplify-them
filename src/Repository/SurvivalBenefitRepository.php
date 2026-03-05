<?php

namespace App\Repository;

use App\Entity\SurvivalBenefit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SurvivalBenefit>
 */
class SurvivalBenefitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurvivalBenefit::class);
    }

    /**
     * Returns all PENDING survival benefits due within the next N days,
     * joined with Policy → Client so the command can display client info.
     *
     * @return SurvivalBenefit[]
     */
    public function findUpcomingBenefits(int $daysAhead = 30): array
    {
        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        $until = new \DateTime("+{$daysAhead} days");
        $until->setTime(23, 59, 59);

        return $this->createQueryBuilder('sb')
            ->join('sb.policy', 'p')
            ->join('p.client', 'c')
            ->addSelect('p', 'c')
            ->where('sb.status = :status')
            ->andWhere('sb.dueDate BETWEEN :today AND :until')
            ->andWhere('p.status = :policyStatus')
            ->setParameter('status', SurvivalBenefit::STATUS_PENDING)
            ->setParameter('today', $today)
            ->setParameter('until', $until)
            ->setParameter('policyStatus', 'IN_FORCE')
            ->orderBy('sb.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all survival benefits for a given policy, ordered by due date.
     *
     * @return SurvivalBenefit[]
     */
    public function findByPolicy(int $policyId): array
    {
        return $this->createQueryBuilder('sb')
            ->where('sb.policy = :policyId')
            ->setParameter('policyId', $policyId)
            ->orderBy('sb.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns PENDING survival benefits due this month OR overdue from
     * previous months, scoped to a specific agency.
     *
     * @return SurvivalBenefit[]
     */
    public function findPendingDueThisMonth(int $agencyId): array
    {
        $endOfMonth = new \DateTime('last day of this month');
        $endOfMonth->setTime(23, 59, 59);

        return $this->createQueryBuilder('sb')
            ->join('sb.policy', 'p')
            ->join('p.client', 'c')
            ->addSelect('p', 'c')
            ->where('sb.status = :status')
            ->andWhere('sb.dueDate <= :endOfMonth')
            ->andWhere('p.agency = :agency')
            ->setParameter('status', SurvivalBenefit::STATUS_PENDING)
            ->setParameter('endOfMonth', $endOfMonth)
            ->setParameter('agency', $agencyId)
            ->orderBy('sb.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
