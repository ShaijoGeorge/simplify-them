<?php

namespace App\Service;

use App\Entity\Agency;
use App\Entity\CommissionRule;
use App\Entity\Policy;
use App\Repository\PolicyRepository;
use Doctrine\ORM\EntityManagerInterface;

class DueListService
{
    private const SINGLE_PREMIUM_COMMISSION_RATE = 2.0;

    // Late fee: 9.5% p.a. penal interest on overdue basic premium
    private const LATE_FEE_RATE = 9.5;

    // GST on late fee: 18%
    private const LATE_FEE_GST_RATE = 18.0;

    // Grace days by premium mode
    private const GRACE_DAYS = [
        'MLY'    => 15,
        'NACH'   => 15,
        'QLY'    => 30,
        'HLY'    => 30,
        'YLY'    => 30,
        'SINGLE' => 0,
    ];

    // Months per premium installment
    private const MODE_MONTHS = [
        'MLY'    => 1,
        'NACH'   => 1,
        'QLY'    => 3,
        'HLY'    => 6,
        'YLY'    => 12,
        'SINGLE' => 0,
    ];

    public function __construct(
        private readonly PolicyRepository $policyRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Build the full due list for an agency within the given FUP range.
     * Returns an array of enriched entries with all LIC calculations.
     *
     * @param array<string, mixed> $filters  Optional filters: mode, flag, grace_status
     * @return array<int, array<string, mixed>>
     */
    public function buildDueList(Agency $agency, \DateTime $fromDate, \DateTime $toDate, array $filters = []): array
    {
        $policies = $this->policyRepository->findDueListPolicies(
            $agency->getId(),
            $fromDate,
            $toDate,
        );

        $today = new \DateTime('today');
        $entries = [];

        foreach ($policies as $policy) {
            $entry = $this->enrichPolicy($policy, $today, $agency);

            // Apply filters
            if (!empty($filters['fup_month']) && $policy->getFup()) {
                if ($policy->getFup()->format('Y-m') !== $filters['fup_month']) {
                    continue;
                }
            }
            if (!empty($filters['mode']) && $policy->getPremiumMode() !== $filters['mode']) {
                continue;
            }
            if (!empty($filters['flag']) && $entry['flag'] !== $filters['flag']) {
                continue;
            }
            if (!empty($filters['grace_status']) && $entry['grace_status'] !== $filters['grace_status']) {
                continue;
            }

            $entries[] = $entry;
        }

        // Sort by total_payable descending if requested
        if (!empty($filters['sort']) && $filters['sort'] === 'amount_desc') {
            usort($entries, fn($a, $b) => $b['total_payable'] <=> $a['total_payable']);
        }

        return $entries;
    }

    /**
     * Public accessor for the API endpoint - returns the same enriched data.
     *
     * @return array<string, mixed>
     */
    public function enrichPolicyForApi(Policy $policy, \DateTime $today, Agency $agency): array
    {
        return $this->enrichPolicy($policy, $today, $agency);
    }

    /**
     * Enrich a single policy with all due list calculations.
     *
     * @return array<string, mixed>
     */
    private function enrichPolicy(Policy $policy, \DateTime $today, Agency $agency): array
    {
        $fup = $policy->getFup();
        $doc = $policy->getCommencementDate();
        $mode = $policy->getPremiumMode() ?? 'YLY';
        $basicPremium = (float) $policy->getBasicPremium();

        // Policy year (1-based, calculated from FUP context)
        $policyYear = $this->calculatePolicyYear($policy, $today);

        // Flag: FY, ST, LP, MT
        $flag = $this->determineFlag($policy, $policyYear);

        // Pending installments
        $pendingInstallments = $this->calculatePendingInstallments($policy, $today);

        // GST on base premium (per installment) - uses same regime logic as Policy entity
        [$gstRate, $gstReason] = $this->resolveGstRateWithReason($policy, $policyYear);
        $gstPerInstallment = round($basicPremium * $gstRate / 100, 2);
        $premiumWithGst = round($basicPremium + $gstPerInstallment, 2);

        // Late fee (only when FUP is in the past)
        $lateFee = 0.0;
        $lateFeeGst = 0.0;
        $monthsOverdue = 0;

        if ($fup && $fup < $today) {
            $monthsOverdue = $this->calculateMonthsOverdue($fup, $today);
            $lateFee = $this->calculateLateFee($basicPremium, $pendingInstallments, $monthsOverdue);
            $lateFeeGst = round($lateFee * self::LATE_FEE_GST_RATE / 100, 2);
        }

        // Total payable = (basic × pending) + (GST × pending) + late fee + late fee GST
        $totalBase = round($basicPremium * $pendingInstallments, 2);
        $totalGst = round($gstPerInstallment * $pendingInstallments, 2);
        $totalPayable = round($totalBase + $totalGst + $lateFee + $lateFeeGst, 2);

        // Estimated commission (on base + GST, matching PremiumReceipt calculation)
        $estCommission = $this->estimateCommission($policy, $policyYear, $totalBase + $totalGst, $agency);

        // Grace status
        $graceStatus = $this->getGraceStatus($policy, $today);
        $graceDays = self::GRACE_DAYS[$mode] ?? 30;
        $graceDeadline = $fup ? (clone $fup)->modify("+{$graceDays} days") : null;

        // Zone: past / current / future
        $zone = $this->getZone($fup, $today);

        return [
            'policy'               => $policy,
            'policy_year'          => $policyYear,
            'flag'                 => $flag,
            'pending_installments' => $pendingInstallments,
            'basic_premium'        => $basicPremium,
            'gst_rate'             => $gstRate,
            'gst_reason'           => $gstReason,
            'gst_per_installment'  => $gstPerInstallment,
            'premium_with_gst'     => $premiumWithGst,
            'months_overdue'       => $monthsOverdue,
            'late_fee'             => $lateFee,
            'late_fee_gst'         => $lateFeeGst,
            'total_base'           => $totalBase,
            'total_gst'            => $totalGst,
            'total_payable'        => $totalPayable,
            'est_commission'       => $estCommission,
            'grace_status'         => $graceStatus,
            'grace_days'           => $graceDays,
            'grace_deadline'       => $graceDeadline,
            'zone'                 => $zone,
        ];
    }

    /**
     * Calculate the current policy year (1-based).
     */
    private function calculatePolicyYear(Policy $policy, \DateTime $today): int
    {
        $doc = $policy->getCommencementDate();
        if (!$doc) {
            return 1;
        }

        return (int) $doc->diff($today)->y + 1;
    }

    /**
     * Determine the flag for agent intelligence.
     *
     * Priority: MT > LP > FY > ST
     *  - FY: First Year (policyYear == 1)
     *  - ST: 2nd/3rd Year Premium (policyYear 2 or 3)
     *  - LP: Last Premium - current FUP is in the last premium-paying year
     *  - MT: Matures After This Due - policy matures within one premium interval after FUP
     */
    private function determineFlag(Policy $policy, int $policyYear): string
    {
        $doc = $policy->getCommencementDate();
        $fup = $policy->getFup();
        $ppt = $policy->getPremiumPayingTerm();
        $maturity = $policy->getMaturityDate();
        $mode = $policy->getPremiumMode() ?? 'YLY';
        $modeMonths = self::MODE_MONTHS[$mode] ?? 12;

        // MT: Policy maturity date is within one premium interval after this FUP
        if ($maturity && $fup) {
            $nextInterval = (clone $fup)->modify("+{$modeMonths} months");
            if ($maturity <= $nextInterval) {
                return 'MT';
            }
        }

        // LP: This is in the last premium-paying year
        // i.e., policyYear == premiumPayingTerm, or the FUP falls in the last PPT year
        if ($ppt && $doc && $fup) {
            $pptEndDate = (clone $doc)->modify("+{$ppt} years");
            // The last installment window: FUP + one interval would exceed PPT end
            $nextDueAfterFup = (clone $fup)->modify("+{$modeMonths} months");
            if ($nextDueAfterFup > $pptEndDate) {
                return 'LP';
            }
        }

        // FY: First year
        if ($policyYear === 1) {
            return 'FY';
        }

        // ST: 2nd/3rd year only
        if ($policyYear === 2 || $policyYear === 3) {
            return 'ST';
        }

        // Year 4+: no special flag
        return '';
    }

    /**
     * Calculate how many installments are pending (unpaid) between FUP and today.
     * If FUP is in the future, returns 1 (the upcoming installment).
     */
    private function calculatePendingInstallments(Policy $policy, \DateTime $today): int
    {
        $fup = $policy->getFup();
        if (!$fup) {
            return 1;
        }

        // If FUP is in the future or today, there's 1 upcoming installment
        if ($fup >= $today) {
            return 1;
        }

        $mode = $policy->getPremiumMode() ?? 'YLY';
        $modeMonths = self::MODE_MONTHS[$mode] ?? 12;

        if ($modeMonths === 0) {
            return 0; // SINGLE premium
        }

        // Count how many installment periods have elapsed since FUP
        $diffMonths = ((int) $fup->diff($today)->y * 12) + (int) $fup->diff($today)->m;
        $pending = (int) floor($diffMonths / $modeMonths) + 1;

        return max($pending, 1);
    }

    /**
     * Calculate months overdue (from FUP to today).
     */
    private function calculateMonthsOverdue(\DateTime $fup, \DateTime $today): int
    {
        if ($fup >= $today) {
            return 0;
        }

        return ((int) $fup->diff($today)->y * 12) + (int) $fup->diff($today)->m;
    }

    /**
     * Calculate late fee: 9.5% p.a. penal interest on overdue basic premium.
     * Late Fee = (basicPremium × pendingInstallments) × (9.5% × monthsOverdue / 12)
     */
    private function calculateLateFee(float $basicPremium, int $pendingInstallments, int $monthsOverdue): float
    {
        if ($monthsOverdue <= 0 || $pendingInstallments <= 0) {
            return 0.0;
        }

        $overdueAmount = $basicPremium * $pendingInstallments;
        $lateFee = $overdueAmount * (self::LATE_FEE_RATE / 100) * ($monthsOverdue / 12);

        return round($lateFee, 2);
    }

    /**
     * Resolve the GST rate and reason for a policy based on DOC regime and policy year.
     * Mirrors the logic in Policy::calculateTotals().
     *
     * @return array{0: float, 1: string}  [rate, reason]
     */
    private function resolveGstRateWithReason(Policy $policy, int $policyYear): array
    {
        $doc = $policy->getCommencementDate();
        if (!$doc) {
            return [0.0, 'No DOC'];
        }

        $gstReformDate = new \DateTime('2025-09-22');
        $serviceTaxStartDate = new \DateTime('2014-01-01');

        // Pre-2014: 0% - Service Tax absorbed by LIC
        if ($doc < $serviceTaxStartDate) {
            return [0.0, 'Pre-2014 (absorbed by LIC)'];
        }

        // New regime (DOC >= 22 Sep 2025): 0%
        if ($doc >= $gstReformDate) {
            return [0.0, 'New regime (DOC after Sep 2025)'];
        }

        // Old regime (2014 to Sep 2025)
        $isSingle = $policy->isSinglePremiumPolicy();
        if ($isSingle) {
            return [1.25, 'Single premium (1.25%)'];
        }

        $planTypeName = '';
        if ($policy->getLicPlan()?->getPlanType()) {
            $planTypeName = strtoupper($policy->getLicPlan()->getPlanType()->getName());
        }

        if (str_contains($planTypeName, 'TERM')) {
            return [18.0, 'Term plan (18%)'];
        }

        // Traditional plans: 4.5% year 1, 2.25% year 2+
        if ($policyYear === 1) {
            return [4.5, 'Traditional FY (4.5%)'];
        }

        return [2.25, 'Traditional renewal (2.25%)'];
    }

    /**
     * Estimate gross commission on the due amount.
     * Uses same CommissionRule lookup as PremiumReceiptCrudController.
     */
    private function estimateCommission(Policy $policy, int $policyYear, float $premiumAmount, Agency $agency): float
    {
        $plan = $policy->getLicPlan();
        if (!$plan || $premiumAmount <= 0) {
            return 0.0;
        }

        // Single-premium override: 2% flat
        if ($plan->isSinglePremium() || $policy->isSinglePremiumPolicy()) {
            return round($premiumAmount * self::SINGLE_PREMIUM_COMMISSION_RATE / 100, 2);
        }

        $term = $policy->getPolicyTerm();

        $rule = $this->em->getRepository(CommissionRule::class)->createQueryBuilder('c')
            ->where('c.licPlan = :plan')
            ->andWhere('c.policyYearFrom <= :year')
            ->andWhere('c.policyYearTo >= :year')
            ->andWhere('c.minTerm <= :term')
            ->andWhere('c.maxTerm >= :term')
            ->setParameter('plan', $plan)
            ->setParameter('year', $policyYear)
            ->setParameter('term', $term)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($rule) {
            return round($premiumAmount * (float) $rule->getCommissionRate() / 100, 2);
        }

        return 0.0;
    }

    /**
     * Determine grace status: upcoming, due, in_grace, overdue.
     */
    private function getGraceStatus(Policy $policy, \DateTime $today): string
    {
        $fup = $policy->getFup();
        if (!$fup) {
            return 'upcoming';
        }

        $mode = $policy->getPremiumMode() ?? 'YLY';
        $graceDays = self::GRACE_DAYS[$mode] ?? 30;
        $graceDeadline = (clone $fup)->modify("+{$graceDays} days");

        if ($fup > $today) {
            return 'upcoming';
        }

        if ($fup->format('Y-m-d') === $today->format('Y-m-d')) {
            return 'due_today';
        }

        // FUP is in the past
        if ($today <= $graceDeadline) {
            return 'in_grace';
        }

        return 'overdue';
    }

    /**
     * Determine the zone: past (recovery), current, future (pipeline).
     */
    private function getZone(\DateTime $fup, \DateTime $today): string
    {
        $currentMonthStart = new \DateTime($today->format('Y-m-01'));
        $currentMonthEnd = (clone $currentMonthStart)->modify('last day of this month 23:59:59');

        if ($fup < $currentMonthStart) {
            return 'past';
        }

        if ($fup <= $currentMonthEnd) {
            return 'current';
        }

        return 'future';
    }

    /**
     * Compute summary totals from a list of enriched entries.
     *
     * @param array<int, array<string, mixed>> $entries
     * @return array<string, mixed>
     */
    public function computeSummary(array $entries): array
    {
        $totalPayable = 0.0;
        $totalCommission = 0.0;
        $totalLateFee = 0.0;
        $countByZone = ['past' => 0, 'current' => 0, 'future' => 0];
        $countByFlag = ['FY' => 0, 'ST' => 0, 'LP' => 0, 'MT' => 0];
        $countByGrace = ['upcoming' => 0, 'due_today' => 0, 'in_grace' => 0, 'overdue' => 0];

        foreach ($entries as $entry) {
            $totalPayable += $entry['total_payable'];
            $totalCommission += $entry['est_commission'];
            $totalLateFee += $entry['late_fee'] + $entry['late_fee_gst'];

            $countByZone[$entry['zone']] = ($countByZone[$entry['zone']] ?? 0) + 1;
            if ($entry['flag'] !== '') {
                $countByFlag[$entry['flag']] = ($countByFlag[$entry['flag']] ?? 0) + 1;
            }
            $countByGrace[$entry['grace_status']] = ($countByGrace[$entry['grace_status']] ?? 0) + 1;
        }

        return [
            'total_policies'   => count($entries),
            'total_payable'    => round($totalPayable, 2),
            'total_commission' => round($totalCommission, 2),
            'total_late_fee'   => round($totalLateFee, 2),
            'count_by_zone'    => $countByZone,
            'count_by_flag'    => $countByFlag,
            'count_by_grace'   => $countByGrace,
        ];
    }
}
