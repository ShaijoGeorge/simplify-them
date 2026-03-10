<?php

namespace App\Repository;

use App\Entity\PremiumReceipt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PremiumReceipt>
 */
class PremiumReceiptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PremiumReceipt::class);
    }

    /**
     * Get monthly commission statement data for an agency.
     *
     * @return array{totals: array{gross: float, tds: float, net: float}, receipts: PremiumReceipt[]}
     */
    public function getMonthlyCommissionStatement(int $agencyId, int $year, int $month): array
    {
        $from = new \DateTime("$year-$month-01");
        $to   = (clone $from)->modify('last day of this month')->setTime(23, 59, 59);

        $receipts = $this->createQueryBuilder('r')
            ->andWhere('r.agency = :agency')
            ->andWhere('r.collectedDate BETWEEN :from AND :to')
            ->setParameter('agency', $agencyId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.collectedDate', 'ASC')
            ->getQuery()
            ->getResult();

        $totalGross = 0.0;
        $totalTds   = 0.0;
        $totalNet   = 0.0;

        foreach ($receipts as $r) {
            $totalGross += (float) $r->getGrossCommission();
            $totalTds   += (float) $r->getTdsOnCommission();
            $totalNet   += (float) $r->getNetCommission();
        }

        return [
            'totals' => [
                'gross' => round($totalGross, 2),
                'tds'   => round($totalTds, 2),
                'net'   => round($totalNet, 2),
            ],
            'receipts' => $receipts,
        ];
    }

    public function getCollectedThisMonth(int $agencyId): float
    {
        $start = new \DateTime('first day of this month 00:00:00');
        $end   = new \DateTime('last day of this month 23:59:59');

        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.collectedAmount) as total')
            ->andWhere('r.agency = :agency')
            ->andWhere('r.collectedDate BETWEEN :start AND :end')
            ->setParameter('agency', $agencyId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }
}
