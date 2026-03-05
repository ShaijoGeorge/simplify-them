<?php

namespace App\Service;

use App\Entity\Policy;
use App\Entity\SurvivalBenefit;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Generates SurvivalBenefit rows when a Money Back policy is created.
 *
 * Payout schedules:
 *   20-year plan → years 5, 10, 15 at 20 % of SA each
 *   25-year plan → years 5, 10, 15, 20 at 15 % of SA each
 */
class SurvivalBenefitGeneratorService
{
    /**
     * Schedule map: policyTerm => [[yearOffset, percentageOfSA], ...]
     */
    private const SCHEDULES = [
        20 => [
            [5,  20],
            [10, 20],
            [15, 20],
        ],
        25 => [
            [5,  15],
            [10, 15],
            [15, 15],
            [20, 15],
        ],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Generate survival benefit rows for the given policy.
     *
     * Returns the generated benefits (already persisted but NOT flushed).
     * Returns an empty array when the policy is not a Money Back plan
     * or when its term does not match a known schedule.
     *
     * @return SurvivalBenefit[]
     */
    public function generateForPolicy(Policy $policy): array
    {
        // Guard: only Money Back plans
        if (!$this->isMoneyBackPolicy($policy)) {
            return [];
        }

        $policyTerm = $policy->getPolicyTerm();
        if (!isset(self::SCHEDULES[$policyTerm])) {
            return [];
        }

        $schedule         = self::SCHEDULES[$policyTerm];
        $commencementDate = $policy->getCommencementDate();
        $sumAssured       = (float) $policy->getSumAssured();
        $benefits         = [];

        foreach ($schedule as [$yearOffset, $percentage]) {
            $dueDate = clone $commencementDate;
            $dueDate->modify("+{$yearOffset} years");

            $amount = round($sumAssured * $percentage / 100, 2);

            $benefit = new SurvivalBenefit();
            $benefit->setPolicy($policy);
            $benefit->setDueDate($dueDate);
            $benefit->setAmount((string) $amount);
            $benefit->setPercentageOfSA((string) $percentage);
            $benefit->setStatus(SurvivalBenefit::STATUS_PENDING);

            $this->entityManager->persist($benefit);
            $benefits[] = $benefit;
        }

        return $benefits;
    }

    /**
     * Checks whether the policy's plan type is "Money Back".
     */
    private function isMoneyBackPolicy(Policy $policy): bool
    {
        $planType = $policy->getLicPlan()?->getPlanType();

        if (!$planType) {
            return false;
        }

        return stripos($planType->getName(), 'Money Back') !== false;
    }
}
