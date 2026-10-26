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
            ->andWhere('r.paymentDate BETWEEN :from AND :to')
            ->setParameter('agency', $agencyId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.paymentDate', 'ASC')
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
            ->select('SUM(r.amount) as total')
            ->andWhere('r.agency = :agency')
            ->andWhere('r.paymentDate BETWEEN :start AND :end')
            ->setParameter('agency', $agencyId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Returns monthly aggregated receipt & commission metrics for dashboard charts.
     *
     * @return array{
     *     labels: string[],
     *     paid: float[],
     *     collected: float[],
     *     gross_commission: float[],
     *     tds: float[],
     *     net_commission: float[],
     *     receipt_counts: int[],
     *     totals: array{
     *         paid: float,
     *         collected: float,
     *         gross_commission: float,
     *         tds: float,
     *         net_commission: float,
     *         receipt_count: int
     *     }
     * }
     */
    public function getDashboardChartData(int $agencyId, int $months = 6): array
    {
        $buckets = [];
        $firstMonth = (new \DateTime('first day of this month 00:00:00'))->modify('-' . ($months - 1) . ' months');
        $endMonth   = new \DateTime('last day of this month 23:59:59');

        for ($i = $months - 1; $i >= 0; $i--) {
            $m = (new \DateTime('first day of this month'))->modify("-{$i} months");
            $key = $m->format('Y-m');
            $buckets[$key] = [
                'label'            => $m->format('M Y'),
                'paid'             => 0.0,
                'collected'        => 0.0,
                'gross_commission' => 0.0,
                'tds'              => 0.0,
                'net_commission'   => 0.0,
                'receipt_count'    => 0,
            ];
        }

        if ($agencyId > 0) {
            $receipts = $this->createQueryBuilder('r')
                ->andWhere('r.agency = :agency')
                ->andWhere('(r.paymentDate BETWEEN :start AND :end) OR (r.collectionDate BETWEEN :start AND :end)')
                ->setParameter('agency', $agencyId)
                ->setParameter('start', $firstMonth)
                ->setParameter('end', $endMonth)
                ->orderBy('r.paymentDate', 'ASC')
                ->getQuery()
                ->getResult();

            foreach ($receipts as $r) {
                $payDate = $r->getPaymentDate();
                if ($payDate) {
                    $payKey = $payDate->format('Y-m');
                    if (isset($buckets[$payKey])) {
                        $buckets[$payKey]['paid']             += (float) $r->getAmount();
                        $buckets[$payKey]['gross_commission'] += (float) $r->getGrossCommission();
                        $buckets[$payKey]['tds']              += (float) $r->getTdsOnCommission();
                        $buckets[$payKey]['net_commission']   += (float) $r->getNetCommission();
                        $buckets[$payKey]['receipt_count']++;
                    }
                }

                $colDate = $r->getCollectionDate() ?? $r->getPaymentDate();
                $colAmt  = (float) $r->getCollectedFromClient();
                if ($colDate && $colAmt > 0) {
                    $colKey = $colDate->format('Y-m');
                    if (isset($buckets[$colKey])) {
                        $buckets[$colKey]['collected'] += $colAmt;
                    }
                }
            }
        }

        $labels          = [];
        $paid            = [];
        $collected       = [];
        $grossCommission = [];
        $tds             = [];
        $netCommission   = [];
        $receiptCounts   = [];

        foreach ($buckets as $b) {
            $labels[]          = $b['label'];
            $paid[]            = round($b['paid'], 2);
            $collected[]       = round($b['collected'], 2);
            $grossCommission[] = round($b['gross_commission'], 2);
            $tds[]             = round($b['tds'], 2);
            $netCommission[]   = round($b['net_commission'], 2);
            $receiptCounts[]   = $b['receipt_count'];
        }

        return [
            'labels'           => $labels,
            'paid'             => $paid,
            'collected'        => $collected,
            'gross_commission' => $grossCommission,
            'tds'              => $tds,
            'net_commission'   => $netCommission,
            'receipt_counts'   => $receiptCounts,
            'totals' => [
                'paid'             => round(array_sum($paid), 2),
                'collected'        => round(array_sum($collected), 2),
                'gross_commission' => round(array_sum($grossCommission), 2),
                'tds'              => round(array_sum($tds), 2),
                'net_commission'   => round(array_sum($netCommission), 2),
                'receipt_count'    => array_sum($receiptCounts),
            ],
        ];
    }
}
