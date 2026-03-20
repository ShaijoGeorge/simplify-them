<?php
// src/Command/DetectLapsesCommand.php

namespace App\Command;

use App\Entity\PolicyStatusLog;
use App\Repository\PolicyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:detect-lapses',
    description: 'Detects lapsed policies and auto-transitions eligible ones to Paid-Up status.',
)]
class DetectLapsesCommand extends Command
{
    public function __construct(
        private PolicyRepository $policyRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Detecting Lapsed & Paid-Up Policies');

        // Fetch all IN_FORCE policies with an FUP date set (candidates for lapse)
        $policies = $this->policyRepository->createQueryBuilder('p')
            ->where('p.status = :inForce')
            ->andWhere('p.fup IS NOT NULL')
            ->setParameter('inForce', 'IN_FORCE')
            ->getQuery()
            ->getResult();

        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        $lapsedCount = 0;
        $paidUpCount = 0;

        foreach ($policies as $policy) {
            $fup = $policy->getFup();
            $mode = $policy->getPremiumMode();

            if (!$fup) {
                continue;
            }

            // Determine grace period based on premium payment mode
            $gracePeriodDays = match (strtoupper($mode)) {
                'YLY', 'YEARLY', 'HLY', 'HALF-YEARLY', 'QLY', 'QUARTERLY' => 30,
                'NACH', 'MLY', 'MONTHLY' => 15,
                default => 0,
            };

            if ($gracePeriodDays <= 0) {
                continue;
            }

            $lapseDate = clone $fup;
            $lapseDate->modify("+$gracePeriodDays days");

            // Skip if still within grace period
            if ($today <= $lapseDate) {
                continue;
            }

            $oldStatus = $policy->getStatus();
            $doc = $policy->getCommencementDate();
            $ppt = (int) $policy->getPremiumPayingTerm();

            // Check Paid-Up eligibility:
            // - Must have commencement date and PPT
            // - Must have paid premiums for at least 3 full years (LIC vesting period)
            // - Policy must not yet have matured
            $yearsElapsed = ($doc) ? (int) $doc->diff($today)->y : 0;
            $maturityDate = $policy->getMaturityDate();
            $notMatured = !$maturityDate || $maturityDate > $today;

            if ($doc && $ppt > 0 && $yearsElapsed >= 3 && $notMatured) {
                // Eligible for Paid-Up - reduced benefit instead of total loss
                $policy->setStatus('PAID_UP');
                $policy->calculatePaidUpSumAssured();
                $paidUpCount++;

                $this->logTransition(
                    $policy,
                    $oldStatus,
                    'PAID_UP',
                    sprintf(
                        'Policy lapsed after FUP %s + %d-day grace. Eligible for Paid-Up: %d years premiums paid (≥3). Paid-Up SA: ₹%s',
                        $fup->format('d-M-Y'),
                        $gracePeriodDays,
                        $yearsElapsed,
                        number_format((float) $policy->getPaidUpSumAssured(), 2)
                    )
                );

                $io->text(sprintf(
                    '  → PAID_UP: %s | %d yrs paid | Paid-Up SA: ₹%s',
                    $policy->getPolicyNumber(),
                    $yearsElapsed,
                    number_format((float) $policy->getPaidUpSumAssured(), 2)
                ));
            } else {
                // Not eligible - full lapse
                $policy->setStatus('LAPSED');
                $lapsedCount++;

                $reason = sprintf(
                    'FUP %s + %d-day grace period expired.',
                    $fup->format('d-M-Y'),
                    $gracePeriodDays
                );
                if ($yearsElapsed < 3) {
                    $reason .= sprintf(' Only %d year(s) premiums paid (minimum 3 required for Paid-Up).', $yearsElapsed);
                }

                $this->logTransition($policy, $oldStatus, 'LAPSED', $reason);

                $io->text(sprintf(
                    '  → LAPSED:  %s | %d yrs paid | Not eligible for Paid-Up',
                    $policy->getPolicyNumber(),
                    $yearsElapsed
                ));
            }
        }

        $this->entityManager->flush();

        $io->newLine();
        $io->success(sprintf(
            'Finished. %d policy/policies marked LAPSED, %d marked PAID_UP.',
            $lapsedCount,
            $paidUpCount
        ));

        return Command::SUCCESS;
    }

    private function logTransition(object $policy, string $oldStatus, string $newStatus, string $reason): void
    {
        $log = new PolicyStatusLog();
        $log->setPolicy($policy);
        $log->setOldStatus($oldStatus);
        $log->setNewStatus($newStatus);
        $log->setTriggeredBy('app:detect-lapses');
        $log->setReason($reason);

        if ($newStatus === 'PAID_UP') {
            $log->setPaidUpSumAssured($policy->getPaidUpSumAssured());
        }

        $this->entityManager->persist($log);
    }
}
