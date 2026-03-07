<?php

namespace App\Service;

use App\Entity\LicPlan;
use App\Repository\PremiumTableRepository;

class PremiumValidationService
{
    public function __construct(
        private PremiumTableRepository $premiumTableRepository
    ) {}

    /**
     * Cross-check the agent-entered annual premium against the LIC premium table.
     *
     * Returns null if no matching rate is found in the table.
     *
     * @return array{found: bool, expectedPremium: float, deviationPercent: float, isWarning: bool}|null
     */
    public function validatePremium(
        LicPlan $licPlan,
        int $entryAge,
        int $policyTerm,
        float $sumAssured,
        float $enteredAnnualPremium
    ): ?array {
        $rate = $this->premiumTableRepository->findRate($licPlan, $entryAge, $policyTerm);

        if (!$rate) {
            return null;
        }

        $ratePerThousand = (float) $rate->getAnnualPremiumPerThousand();
        $expectedPremium = round($ratePerThousand * ($sumAssured / 1000), 2);

        if ($expectedPremium <= 0) {
            return null;
        }

        $deviationPercent = round(abs($enteredAnnualPremium - $expectedPremium) / $expectedPremium * 100, 2);

        return [
            'found'             => true,
            'expectedPremium'   => $expectedPremium,
            'deviationPercent'  => $deviationPercent,
            'isWarning'         => $deviationPercent > 5.0,
        ];
    }
}
