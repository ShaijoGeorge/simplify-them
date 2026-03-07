<?php

namespace App\Service;

use App\Entity\Policy;
use App\Repository\BonusRateRepository;

/**
 * Estimates the maturity amount for a policy based on declared bonus rates.
 *
 * Formula:
 *   Maturity Amount = Sum Assured + Accrued SRB + FAB (if applicable)
 *
 * Accrued SRB = sum of (bonus_rate × SA / 1000) for each year in force.
 * If a specific year's rate is unavailable, the latest known rate is used.
 */
class MaturityProjectionService
{
    public function __construct(
        private BonusRateRepository $bonusRateRepository,
    ) {}

    /**
     * Calculate the projected maturity amount for a policy.
     *
     * @return array{
     *     maturityAmount: float,
     *     sumAssured: float,
     *     accruedBonus: float,
     *     fabAmount: float,
     *     yearsWithActualRate: int,
     *     yearsWithEstimatedRate: int,
     *     latestRateUsed: float,
     *     totalPolicyYears: int,
     * }|null  Returns null if projection cannot be calculated.
     */
    public function calculateProjection(Policy $policy): ?array
    {
        $licPlan = $policy->getLicPlan();
        $sa = $policy->getSumAssured();
        $policyTerm = $policy->getPolicyTerm();
        $doc = $policy->getCommencementDate();

        // Cannot calculate without essential data
        if (!$licPlan || !$sa || !$policyTerm || !$doc) {
            return null;
        }

        $sumAssured = (float) $sa;

        // Fetch all bonus rates for this plan
        $bonusRates = $this->bonusRateRepository->findRatesForPlan($licPlan);

        if (empty($bonusRates)) {
            return null;
        }

        // Build a lookup: financialYear => BonusRate
        $ratesByYear = [];
        foreach ($bonusRates as $rate) {
            $ratesByYear[$rate->getFinancialYear()] = $rate;
        }

        // Determine the financial years the policy spans
        // DOC year to DOC + policyTerm years
        $startYear = (int) $doc->format('Y');
        $startMonth = (int) $doc->format('n');

        // LIC financial year runs Apr-Mar.
        // If DOC is Jan-Mar, the FY starts from the previous calendar year.
        // e.g. DOC = Feb 2020 → FY = 2019-20
        $fyStartYear = ($startMonth < 4) ? $startYear - 1 : $startYear;

        // The latest available rate (fallback for missing years)
        $lastRate = end($bonusRates);
        $latestSrbRate = (float) $lastRate->getSimpleReversionaryBonus();

        $accruedBonus = 0.0;
        $yearsWithActualRate = 0;
        $yearsWithEstimatedRate = 0;

        for ($i = 0; $i < $policyTerm; $i++) {
            $fy = ($fyStartYear + $i) . '-' . substr((string) ($fyStartYear + $i + 1), 2);

            if (isset($ratesByYear[$fy])) {
                $srbRate = (float) $ratesByYear[$fy]->getSimpleReversionaryBonus();
                $yearsWithActualRate++;
            } else {
                // Use the latest available rate as estimate
                $srbRate = $latestSrbRate;
                $yearsWithEstimatedRate++;
            }

            $accruedBonus += $srbRate * ($sumAssured / 1000);
        }

        // FAB: applicable if policy term >= 15 years and FAB data exists
        $fabAmount = 0.0;
        $fabRate = (float) ($lastRate->getFinalAdditionalBonus() ?? 0);

        if ($policyTerm >= 15 && $fabRate > 0) {
            $fabAmount = $fabRate * ($sumAssured / 1000);
        }

        $maturityAmount = $sumAssured + $accruedBonus + $fabAmount;

        return [
            'maturityAmount' => round($maturityAmount, 2),
            'sumAssured' => $sumAssured,
            'accruedBonus' => round($accruedBonus, 2),
            'fabAmount' => round($fabAmount, 2),
            'yearsWithActualRate' => $yearsWithActualRate,
            'yearsWithEstimatedRate' => $yearsWithEstimatedRate,
            'latestRateUsed' => $latestSrbRate,
            'totalPolicyYears' => $policyTerm,
        ];
    }
}
