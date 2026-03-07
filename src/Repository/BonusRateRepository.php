<?php

namespace App\Repository;

use App\Entity\BonusRate;
use App\Entity\LicPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonusRate>
 */
class BonusRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonusRate::class);
    }

    /**
     * Returns all bonus rates for a plan, ordered by financial year ascending.
     *
     * @return BonusRate[]
     */
    public function findRatesForPlan(LicPlan $plan): array
    {
        return $this->createQueryBuilder('br')
            ->andWhere('br.licPlan = :plan')
            ->setParameter('plan', $plan)
            ->orderBy('br.financialYear', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
