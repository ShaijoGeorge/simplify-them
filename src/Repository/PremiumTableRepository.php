<?php

namespace App\Repository;

use App\Entity\LicPlan;
use App\Entity\PremiumTable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PremiumTable>
 */
class PremiumTableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PremiumTable::class);
    }

    /**
     * Look up the tabular premium rate for a given plan, entry age, and policy term.
     */
    public function findRate(LicPlan $licPlan, int $entryAge, int $policyTerm): ?PremiumTable
    {
        return $this->findOneBy([
            'licPlan'    => $licPlan,
            'entryAge'   => $entryAge,
            'policyTerm' => $policyTerm,
        ]);
    }
}
